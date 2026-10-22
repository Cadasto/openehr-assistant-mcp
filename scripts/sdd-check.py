#!/usr/bin/env python3
"""sdd-check — the shared drift gate for a spec-driven repository.

One file, standard library only, Python 3.9 or newer. The contract this file
implements is `references/sdd-check.md` (families, report format, exit codes) and
`references/traceability-schema.md` (the descriptor keys, the record fields, the
generated-block markers).

Exit codes: 0 ran with no errors, 1 ran with at least one error, 2 could not configure
itself or verified nothing: the descriptor is missing, unparseable, wrong-shape, or names a
path outside the repository; the command line is invalid; ``context`` was given an id with
no record; or a ``check`` run in which no family ran.
"""

import collections
import dataclasses
import difflib
import fnmatch
import io
import os
import re
import shutil
import subprocess
import sys
import tarfile
import tempfile
import urllib.parse
from pathlib import Path
from typing import Callable, Dict, List, Optional, Tuple

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

__version__ = "0.15.0"

#: Every family, in report order.
FAMILIES = (
    "descriptor",
    "map-schema",
    "map-to-tree",
    "index-sync",
    "tree-to-map",
    "doc-kinds",
    "rfc2119",
    "one-home",
    "links",
    "changelog",
    "generated",
    "draft-reason",
)

#: Families a release retired. A `check.families` line naming one is a NOTE, not an error,
#: so a descriptor written for the previous release still loads; delete the line to clear it.
RETIRED_FAMILIES = ("plans",)

#: The severity a family has when the descriptor names none.
DEFAULT_SEVERITY = {
    "descriptor": "error",
    "map-schema": "error",
    "map-to-tree": "error",
    "index-sync": "error",
    "tree-to-map": "warn",
    "doc-kinds": "warn",
    "rfc2119": "warn",
    "one-home": "error",
    "links": "error",
    "changelog": "warn",
    "generated": "error",
    "draft-reason": "off",
}

SEVERITIES = ("error", "warn", "off")

#: The families that cannot run without records.
RECORD_FAMILIES = ("map-to-tree", "index-sync", "tree-to-map", "generated", "draft-reason")

#: The profiles a descriptor may name (methodology §1a), and the older names each one reads as.
PROFILES = ("formal", "informative")
PROFILE_ALIASES = {"full": "formal", "lightweight": "formal"}
#: On the informative profile only the constitution binds, so these families never run.
INFORMATIVE_SKIPPED = (
    "map-schema",
    "map-to-tree",
    "index-sync",
    "tree-to-map",
    "rfc2119",
    "one-home",
    "draft-reason",
)
DEFAULT_CONSTITUTION = "docs/architecture.md"

STATUS = ("draft", "stable", "deprecated")
IMPLEMENTATION = ("proposed", "planned", "in_progress", "partial", "landed", "shipped", "deferred", "retired")
ENFORCED = ("in_progress", "partial", "landed", "shipped")
MODES = ("spec-first", "implementation-aligned")
ADR_STATUS = ("proposed", "accepted", "superseded", "deprecated")
UPSTREAM_STATE = ("proposed", "submitted", "landed-upstream", "landed", "rejected")
#: Which headings open a normative section for the rfc2119 family (check.rfc2119.sections).
RFC2119_SECTIONS = ("section-sign", "requirement-id", "either")

DEFAULT_KINDS = (
    "requirement",
    "specification",
    "adr",
    "guide",
    "analysis",
    "operations",
    "reference",
    "upstream",
    "constitution",
)
NORMATIVE_KINDS = ("requirement", "specification", "adr")

#: The frontmatter key and the vocabulary each kind's status axis is checked against.
KIND_VOCABULARY = {
    "requirement": ("status", STATUS),
    "specification": ("status", STATUS),
    "adr": ("status", ADR_STATUS),
    "upstream": ("state", UPSTREAM_STATE),
}

#: A keyword in one of these kinds is an error whatever the family's severity: each one
#: cites the specification, so a binding word there is a second source of truth.
RFC2119_CITING_KINDS = ("requirement", "adr", "reference")

DEFAULT_TEST_GLOBS = (
    "*_test.go",
    "test_*.py",
    "*_test.py",
    "*Test.php",
    "*.test.ts",
    "*.spec.ts",
    "*_test.rs",
)

#: `docs` is pruned at the repository root only; a nested directory named `docs` is code.
ROOT_PRUNED = ("docs",)
#: These are pruned wherever they sit.
NESTED_PRUNED = (".git", "vendor", "node_modules")

GENERATED_BLOCKS = ("requirements-index", "specifications-index", "adr-index")
#: The blocks rendered from map records; a map error leaves them as they are.
RECORD_BLOCKS = ("requirements-index",)
GENERATED_OPEN = "<!-- sdd:generated %s -->"
GENERATED_CLOSE = "<!-- /sdd:generated -->"

DESCRIPTOR_REL = "docs/.sdd.yaml"

LEVELS = ("ERROR", "WARN", "NOTE")


# ---------------------------------------------------------------------------
# YAML parser (a strict subset: no anchors, no tags, no flow mappings)
# ---------------------------------------------------------------------------


class YamlError(Exception):
    """A shape the subset parser refuses. ``.line`` is the 1-based line number."""

    def __init__(self, message: str, line: int = 0, source: str = "<yaml>"):
        super().__init__(message)
        self.message = message
        self.line = line
        self.source = source

    def where(self) -> str:
        return "%s:%d" % (self.source, self.line) if self.line else self.source


_BLOCK_SCALAR = ("|", "|-", "|+", ">", ">-", ">+")
_INT_RE = re.compile(r"^-?\d+$")


def _coerce_plain_scalar(token: str):
    """The typed value a plain (unquoted) scalar token carries: ``None``, a bool, an
    int, or the token itself.

    The one coercion table the block scalar form and each inline-list item share, so
    the two forms cannot drift apart on which literals they recognise — a hand-written
    parser that silently produced the wrong value for one of them would be worse than
    one that failed loudly.
    """
    if token in ("null", "~"):
        return None
    if token in ("true", "True", "TRUE"):
        return True
    if token in ("false", "False", "FALSE"):
        return False
    if _INT_RE.match(token):
        return int(token)
    return token


class _YamlParser:
    """Recursive descent over pre-tokenised lines ``(indent, content, lineno)``."""

    def __init__(self, text: str, source: str):
        self.source = source
        self.raw = text.split("\n")
        self.tokens: List[Tuple[int, str, int]] = []
        self.i = 0
        self._tokenise()

    # -- tokenising -------------------------------------------------------
    def fail(self, message: str, lineno: int):
        raise YamlError(message, lineno, self.source)

    def _tokenise(self):
        started = False
        for index, raw in enumerate(self.raw):
            lineno = index + 1
            stripped = raw.strip()
            if not stripped:
                continue
            lead = raw[: len(raw) - len(raw.lstrip())]
            if "\t" in lead:
                self.fail("a tab in the indentation is not supported", lineno)
            if stripped.startswith("#"):
                continue
            if stripped == "---" or stripped.startswith("--- "):
                if started or self.tokens:
                    self.fail("a second document is not supported", lineno)
                started = True
                continue
            if stripped == "...":
                continue
            self.tokens.append((len(lead), raw[len(lead) :].rstrip(), lineno))

    # -- entry point ------------------------------------------------------
    def parse(self):
        if not self.tokens:
            return None
        indent = self.tokens[0][0]
        value = self._node(indent)
        if self.i < len(self.tokens):
            _, _, lineno = self.tokens[self.i]
            self.fail("this indentation closes no open block", lineno)
        return value

    def _node(self, indent: int):
        _, content, _ = self.tokens[self.i]
        if content == "-" or content.startswith("- "):
            return self._sequence(indent)
        return self._mapping(indent)

    # -- blocks -----------------------------------------------------------
    def _mapping(self, indent: int) -> dict:
        result: Dict[str, object] = {}
        while self.i < len(self.tokens):
            ind, content, lineno = self.tokens[self.i]
            if ind < indent:
                break
            if ind > indent:
                self.fail("this indentation closes no open block", lineno)
            if content == "-" or content.startswith("- "):
                self.fail("a sequence item where a 'key: value' was expected", lineno)
            key, value_text = self._split_key(content, lineno)
            if key in result:
                # Last-wins is exactly the silent-wrong-value failure this parser exists to
                # avoid: two `status:` lines, or two `links:` blocks, and the second quietly
                # decides the outcome. YAML 1.2 requires unique keys; fail, naming the line.
                self.fail("duplicate key '%s'" % key, lineno)
            self.i += 1
            result[key] = self._value(value_text, indent, lineno)
        return result

    def _sequence(self, indent: int) -> list:
        items: List[object] = []
        while self.i < len(self.tokens):
            ind, content, lineno = self.tokens[self.i]
            if ind < indent:
                break
            if ind > indent:
                self.fail("this indentation closes no open block", lineno)
            if not (content == "-" or content.startswith("- ")):
                break
            offset = 1
            while offset < len(content) and content[offset] == " ":
                offset += 1
            rest = content[offset:]
            item_indent = ind + offset
            if not rest:
                self.i += 1
                nxt = self.tokens[self.i] if self.i < len(self.tokens) else None
                if nxt is not None and nxt[0] > ind:
                    items.append(self._node(nxt[0]))
                else:
                    items.append(None)
                continue
            if rest == "-" or rest.startswith("- "):
                self.tokens[self.i] = (item_indent, rest, lineno)
                items.append(self._sequence(item_indent))
            elif self._looks_like_key(rest):
                self.tokens[self.i] = (item_indent, rest, lineno)
                items.append(self._mapping(item_indent))
            else:
                self.i += 1
                items.append(self._value(rest, item_indent, lineno))
        return items

    # -- scalars ----------------------------------------------------------
    @staticmethod
    def _looks_like_key(content: str) -> bool:
        if content[0] in "\"'":
            quote = content[0]
            end = content.find(quote, 1)
            return end != -1 and content[end + 1 :].startswith(":")
        return ": " in content or content.endswith(":")

    def _split_key(self, content: str, lineno: int) -> Tuple[str, str]:
        if content[0] in "\"'":
            quote = content[0]
            end = content.find(quote, 1)
            if end == -1:
                self.fail("an unterminated quoted key", lineno)
            rest = content[end + 1 :]
            if not rest.startswith(":"):
                self.fail("expected ':' after a quoted key", lineno)
            return content[1:end], rest[1:].strip()
        cut = content.find(": ")
        if cut == -1:
            if not content.endswith(":"):
                self.fail("expected a 'key: value' pair", lineno)
            return content[:-1].strip(), ""
        return content[:cut].strip(), content[cut + 2 :].strip()

    def _value(self, text: str, indent: int, lineno: int):
        if text in _BLOCK_SCALAR:
            return self._block_scalar(text, indent, lineno)
        if text.startswith("#"):
            # A comment after a key that opens a block ("families:  # error | warn | off").
            # The value is the block below, not the comment.
            text = ""
        if text == "":
            nxt = self.tokens[self.i] if self.i < len(self.tokens) else None
            if nxt is None:
                return None
            if nxt[0] > indent:
                return self._node(nxt[0])
            if nxt[0] == indent and (nxt[1] == "-" or nxt[1].startswith("- ")):
                return self._sequence(indent)
            return None
        return self._scalar(text, lineno)

    def _block_scalar(self, marker: str, indent: int, lineno: int) -> str:
        style, chomp = marker[0], marker[1:]
        collected: List[str] = []
        last = lineno
        for index in range(lineno, len(self.raw)):
            raw = self.raw[index]
            if not raw.strip():
                collected.append("")
                last = index + 1
                continue
            lead = raw[: len(raw) - len(raw.lstrip())]
            if "\t" in lead:
                self.fail("a tab in the indentation is not supported", index + 1)
            if len(lead) <= indent:
                break
            collected.append(raw)
            last = index + 1
        while self.i < len(self.tokens) and self.tokens[self.i][2] <= last:
            self.i += 1
        body = collected
        while body and not body[-1].strip():
            body.pop()
        if not body:
            return ""
        width = min(len(l) - len(l.lstrip()) for l in body if l.strip())
        rows = [l[width:] if l.strip() else "" for l in body]
        if style == "|":
            out = "\n".join(rows)
        else:
            folded: List[str] = []
            for row in rows:
                if not row:
                    folded.append("\n")
                elif folded and folded[-1] not in ("\n",):
                    folded[-1] = folded[-1] + " " + row
                else:
                    folded.append(row)
            out = "".join(folded)
        if chomp == "-":
            return out.rstrip("\n")
        if chomp == "+":
            return out + "\n"
        return out.rstrip("\n") + "\n"

    def _read_quoted(self, text: str, lineno: int) -> Tuple[str, str]:
        """Parse the quoted scalar at ``text[0]`` and return ``(value, remainder)``. A single
        quote inside a single-quoted string is written ``''``; ``\\"`` and ``\\\\`` are the only
        escapes honoured inside a double-quoted string, every other backslash kept literally,
        so a Windows path in single quotes survives."""
        quote = text[0]
        out: List[str] = []
        i = 1
        n = len(text)
        while i < n:
            ch = text[i]
            if quote == "'":
                if ch == "'":
                    if i + 1 < n and text[i + 1] == "'":
                        out.append("'")
                        i += 2
                        continue
                    return "".join(out), text[i + 1 :]
                out.append(ch)
                i += 1
                continue
            if ch == "\\" and i + 1 < n and text[i + 1] in '"\\':
                out.append(text[i + 1])
                i += 2
                continue
            if ch == quote:
                return "".join(out), text[i + 1 :]
            out.append(ch)
            i += 1
        self.fail("an unterminated quoted scalar", lineno)

    def _scalar(self, text: str, lineno: int):
        if text[0] in "\"'":
            value, rest = self._read_quoted(text, lineno)
            rest = rest.strip()
            if rest and not rest.startswith("#"):
                # Silently dropping `bar` from `"foo" bar` is the wrong-value trap again.
                self.fail("text after a closing quote", lineno)
            return value
        if text[0] == "[":
            # An inline list is parsed whole, before any comment-stripping: a `#`
            # inside one of its own quoted items is not a trailing comment, and
            # cutting the line there would truncate the list before it ever reaches
            # `_inline_list` (which already ignores a genuine trailing comment itself).
            return self._inline_list(text, lineno)
        cut = text.find(" #")
        if cut != -1:
            text = text[:cut].rstrip()
        if not text:
            return None
        first = text[0]
        if first == "&":
            self.fail("an anchor is not supported", lineno)
        if first == "*":
            self.fail("an alias is not supported", lineno)
        if first == "!":
            self.fail("a tag is not supported", lineno)
        if first == "{":
            self.fail("a flow mapping is not supported", lineno)
        return _coerce_plain_scalar(text)

    def _inline_list(self, text: str, lineno: int) -> list:
        """An inline ``[a, "b", 'c']`` list. A quoted item decodes through
        :meth:`_read_quoted` — the one path block scalars take, so ``''`` and ``\\"`` mean
        the same thing in both forms — and text after its closing quote, before the next
        ``,`` or ``]``, is refused exactly as it is after a quoted block scalar."""
        out: List[object] = []
        rest = text[1:]
        while True:
            rest = rest.lstrip(" ")
            if not rest:
                self.fail("an unterminated inline list", lineno)
            if rest[0] == "]":
                rest = rest[1:]
                break
            if rest[0] in "\"'":
                value, rest = self._read_quoted(rest, lineno)
                rest = rest.lstrip(" ")
                if not rest:
                    self.fail("an unterminated inline list", lineno)
                if rest[0] not in ",]":
                    self.fail("text after a closing quote", lineno)
                out.append(value)
                if rest[0] == ",":
                    rest = rest[1:]
                continue
            cut = len(rest)
            for position, char in enumerate(rest):
                if char in ",]":
                    cut = position
                    break
                if char == "[":
                    self.fail("a nested inline list is not supported", lineno)
                if char == "{":
                    self.fail("a flow mapping is not supported", lineno)
            if cut == len(rest):
                self.fail("an unterminated inline list", lineno)
            token = rest[:cut].strip()
            rest = rest[cut + 1 :] if rest[cut] == "," else rest[cut:]
            if not token:
                continue
            if token[0] == "&":
                self.fail("an anchor is not supported", lineno)
            if token[0] == "*":
                self.fail("an alias is not supported", lineno)
            out.append(_coerce_plain_scalar(token))
        rest = rest.strip()
        if rest and not rest.startswith("#"):
            self.fail("trailing content after an inline list", lineno)
        return out


def load_yaml(text: str, *, source: str = "<yaml>"):
    """Parse the YAML subset this tool supports. Raises :class:`YamlError`."""
    return _YamlParser(text, source).parse()


# ---------------------------------------------------------------------------
# Markdown helpers
# ---------------------------------------------------------------------------

_HEADING_RE = re.compile(r"^(#{1,6})\s+(.*)$")
_ANCHOR_RE = re.compile(r"<a\s+(?:id|name)\s*=\s*[\"']([^\"']+)[\"']", re.IGNORECASE)
_ANCHOR_TAG_RE = re.compile(
    r"<a\s+(?:id|name)\s*=\s*[\"'][^\"']+[\"']\s*/?>\s*(?:</a>)?", re.IGNORECASE
)
_FENCE_RE = re.compile(r"^(```+|~~~+)")
_CODE_SPAN_RE = re.compile(r"(`+)(.+?)\1")


def slugify(heading: str) -> str:
    """The GitHub heading-anchor rule: lower-case, drop everything outside letters,
    digits, space, hyphen and underscore, then spaces to hyphens with no collapsing."""
    kept = []
    for char in heading.strip().lower():
        if char.isalnum() or char in " -_":
            kept.append(char)
    return "".join(kept).replace(" ", "-")


def _frontmatter_span(text: str) -> Optional[Tuple[int, int]]:
    """``(line of the opening '---', line after the closing '---')``, or ``None``."""
    lines = text.split("\n")
    index = 0
    in_comment = False
    while index < len(lines):
        stripped = lines[index].strip()
        if in_comment:
            if "-->" in stripped:
                in_comment = False
            index += 1
            continue
        if not stripped:
            index += 1
            continue
        if stripped.startswith("<!--"):
            if "-->" not in stripped:
                in_comment = True
            index += 1
            continue
        break
    if index >= len(lines) or lines[index].strip() != "---":
        return None
    for probe in range(index + 1, len(lines)):
        if lines[probe].strip() == "---":
            return index + 1, probe + 2
    return None


def frontmatter(text: str) -> Tuple[Optional[dict], int]:
    """Return ``(mapping, line after the closing '---')``, or ``(None, 0)`` when absent.

    The block opens on line 1 or right after a leading HTML comment block. A block that is
    present but does not parse raises :class:`YamlError` — absent and malformed are not the
    same thing, and a gate that treats them alike hides the defect.
    """
    span = _frontmatter_span(text)
    if span is None:
        return None, 0
    open_line, after = span
    body = "\n".join(text.split("\n")[open_line : after - 2])
    parsed = load_yaml(body, source="frontmatter")
    if parsed is None:
        parsed = {}
    if not isinstance(parsed, dict):
        raise YamlError("the frontmatter is not a mapping", open_line, "frontmatter")
    return parsed, after


def _closes_fence(line: str, fence: str) -> bool:
    """Whether ``line`` closes a code fence opened by ``fence``: a leading run of the same
    fence character at least as long as the opener, and nothing after it — a closing fence
    carries no info string, so a line like ```` ```js ```` opens nothing and closes nothing."""
    stripped = line.strip()
    run = len(stripped) - len(stripped.lstrip(fence[0]))
    return run >= len(fence) and not stripped[run:].strip()


def _content_lines(
    text: str, blank_code_spans: bool, keep_comments: bool = False
) -> List[Tuple[int, str]]:
    """Every line with fences and frontmatter blanked, numbers kept. HTML comments are
    blanked too unless ``keep_comments`` is set — the generated-block scan keeps them, since
    its own markers are comments, and relies on fence blanking alone to hide a marker quoted
    inside a fenced example."""
    lines = text.split("\n")
    span = _frontmatter_span(text)
    after = span[1] if span else 0
    out: List[Tuple[int, str]] = []
    fence = None
    in_comment = False
    for index, raw in enumerate(lines):
        lineno = index + 1
        if after and lineno < after:
            out.append((lineno, ""))
            continue
        line = raw
        if in_comment:
            end = line.find("-->")
            if end == -1:
                out.append((lineno, ""))
                continue
            line = " " * (end + 3) + line[end + 3 :]
            in_comment = False
        if fence is not None:
            out.append((lineno, ""))
            # Close only on a run of the same fence character at least as long as the
            # opener (CommonMark): a ``` line must not close a ```` block, or a marker
            # quoted inside the longer fence would leak out as live content.
            if _closes_fence(line, fence):
                fence = None
            continue
        match = _FENCE_RE.match(line.lstrip())
        if match:
            fence = match.group(1)
            out.append((lineno, ""))
            continue
        if keep_comments:
            if blank_code_spans:
                line = _CODE_SPAN_RE.sub(lambda m: " " * len(m.group(0)), line)
            out.append((lineno, line))
            continue
        while True:
            open_at = line.find("<!--")
            if open_at == -1:
                break
            close_at = line.find("-->", open_at + 4)
            if close_at == -1:
                line = line[:open_at]
                in_comment = True
                break
            line = line[:open_at] + " " * (close_at + 3 - open_at) + line[close_at + 3 :]
        if blank_code_spans:
            line = _CODE_SPAN_RE.sub(lambda m: " " * len(m.group(0)), line)
        out.append((lineno, line))
    return out


def strip_noncontent(text: str) -> List[Tuple[int, str]]:
    """Lines with fences, inline code, HTML comments and frontmatter blanked."""
    return _content_lines(text, True)


def headings(text: str) -> List[Tuple[int, int, str, str]]:
    """Every ATX heading outside fenced code, as ``(line, level, text, slug)``."""
    out: List[Tuple[int, int, str, str]] = []
    for lineno, line in _content_lines(text, False):
        match = _HEADING_RE.match(line)
        if match:
            title = match.group(2).strip().rstrip("#").strip()
            out.append((lineno, len(match.group(1)), title, slugify(title)))
    return out


def explicit_anchors(text: str) -> set:
    """Every ``<a id="…">`` and ``<a name="…">`` target in the document."""
    return set(_ANCHOR_RE.findall(text))


def _heading_span(
    found: List[Tuple[int, int, str, str]], index: int, total: int
) -> Tuple[int, int]:
    """The half-open span of one heading: from it to the next heading of the same or higher level."""
    lineno, level = found[index][0], found[index][1]
    for later_line, later_level, _, _ in found[index + 1 :]:
        if later_level <= level:
            return lineno, later_line
    return lineno, total + 1


def section_slice(text: str, slug: str) -> Optional[Tuple[int, int]]:
    """The half-open line span ``[start, end)`` of the section a heading slug opens."""
    found = headings(text)
    total = len(text.split("\n"))
    for index, entry in enumerate(found):
        if entry[3] == slug:
            return _heading_span(found, index, total)
    return None


def table_rows(text: str) -> List[Tuple[int, List[str], List[str]]]:
    """Every pipe-table data row as ``(line, header cells, row cells)``."""
    rows: List[Tuple[int, List[str], List[str]]] = []
    lines = _content_lines(text, False)
    index = 0
    while index < len(lines) - 1:
        lineno, line = lines[index]
        nxt = lines[index + 1][1]
        if "|" in line and _is_separator(nxt):
            header = _split_cells(line)
            index += 2
            while index < len(lines):
                row_line, row_text = lines[index]
                if "|" not in row_text or not row_text.strip():
                    break
                rows.append((row_line, header, _split_cells(row_text)))
                index += 1
            continue
        index += 1
    return rows


def _is_separator(line: str) -> bool:
    stripped = line.strip()
    if "|" not in stripped or not stripped:
        return False
    for cell in _split_cells(stripped):
        if not re.match(r"^:?-{1,}:?$", cell):
            return False
    return True


def _split_cells(line: str) -> List[str]:
    stripped = line.strip()
    if stripped.startswith("|"):
        stripped = stripped[1:]
    if stripped.endswith("|"):
        stripped = stripped[:-1]
    return [cell.strip() for cell in stripped.split("|")]


_WAIVER_RE = re.compile(r"<!--\s*sdd-check:\s*allow\s+([^>]*?)\s*-->")


def waivers(text: str) -> set:
    """Every family named in an ``<!-- sdd-check: allow a, b -->`` comment in a file."""
    named = set()
    for match in _WAIVER_RE.finditer(text):
        for name in match.group(1).split(","):
            token = name.strip()
            if token:
                named.add(token)
    return named


#: Every RFC-2119 keyword as a whole upper-case word. The ``NOT`` forms come first so the
#: longer one wins, and the word boundary keeps ``MAY`` out of ``MAYBE``.
KEYWORD_RE = re.compile(
    r"\b(MUST NOT|MUST|SHALL NOT|SHALL|SHOULD NOT|SHOULD|REQUIRED|RECOMMENDED|MAY|OPTIONAL)\b"
)

#: A modal in lower case binds nothing, which in a specification is the defect.
LOWER_MODAL_RE = re.compile(r"\b(must|shall|should|may not)\b")

#: Grammar a keyword cannot have: it is a verb, never a noun, and never doubled.
MALFORMED_RE = re.compile(r"\b(MUST to|are MUST|is MUST|MUST MUST|NOT NOT|SHOULD MUST)\b")

_SENTENCE_END_RE = re.compile(r"[.!?;](?=\s|$)")
_LIST_ITEM_RE = re.compile(r"^(?:[-*+]\s|\d+[.)]\s)")
_TASK_BOX_RE = re.compile(r"^\[[ xX]\]\s*")
#: A bold label opens a field — ``**Implements:** REQ-…`` — so it is markup, not prose.
_FIELD_LABEL_RE = re.compile(r"^\*\*[^*]+:\*\*\s*")
_INLINE_LINK_RE = re.compile(r"!?\[([^\]]*)\]\([^)]*\)")
_REFERENCE_LINK_RE = re.compile(r"\[([^\]]*)\]\[[^\]]*\]")
_EDGE_UNDERSCORE_RE = re.compile(r"(?<![A-Za-z0-9])_+|_+(?![A-Za-z0-9])")


def sentences(text: str) -> List[Tuple[int, str]]:
    """Every prose sentence, as ``(the line it starts on, the sentence)``.

    A sentence ends at ``.``, ``!``, ``?`` or ``;`` followed by whitespace or the end of the
    line. A blank line, a heading and the start of a list item also end the one being built,
    so a title never runs into the paragraph beneath it. A bullet, a task box and a bold
    field label are markup that introduces prose, so they are dropped rather than read as
    words. Fences, inline code, HTML comments and the frontmatter are already blank, because
    the scan is :func:`strip_noncontent`.
    """
    out: List[Tuple[int, str]] = []
    state = {"parts": [], "start": 0}

    def flush():
        joined = " ".join(part for part in state["parts"] if part).strip()
        if joined:
            out.append((state["start"], joined))
        state["parts"] = []
        state["start"] = 0

    def open_at(lineno):
        if not state["parts"]:
            state["start"] = lineno

    for lineno, line in strip_noncontent(text):
        stripped = line.strip()
        if not stripped or _HEADING_RE.match(stripped):
            flush()
            continue
        if _LIST_ITEM_RE.match(stripped):
            flush()
            stripped = _TASK_BOX_RE.sub("", _LIST_ITEM_RE.sub("", stripped, 1), 1)
        stripped = _FIELD_LABEL_RE.sub("", stripped, 1)
        if not stripped:
            continue
        position = 0
        for match in _SENTENCE_END_RE.finditer(stripped):
            chunk = stripped[position : match.end()].strip()
            position = match.end()
            open_at(lineno)
            if chunk:
                state["parts"].append(chunk)
            flush()
        tail = stripped[position:].strip()
        if tail:
            open_at(lineno)
            state["parts"].append(tail)
    flush()
    return out


def keyword_sentences(text: str) -> List[Tuple[int, str]]:
    """The sentences that carry at least one RFC-2119 keyword."""
    return [pair for pair in sentences(text) if KEYWORD_RE.search(pair[1])]


def normalise_sentence(sentence: str) -> str:
    """One sentence reduced to what it says, so two wordings of it compare equal.

    Lower case; link and image syntax replaced by the text a reader sees; code ticks and
    emphasis markers dropped; runs of whitespace collapsed; trailing punctuation removed.
    """
    text = _INLINE_LINK_RE.sub(r"\1", sentence)
    text = _REFERENCE_LINK_RE.sub(r"\1", text)
    text = text.replace("`", "")
    text = _EDGE_UNDERSCORE_RE.sub("", text)
    text = re.sub(r"[*~]+", "", text)
    text = re.sub(r"\s+", " ", text).strip().lower()
    return text.rstrip(" .,;:!?")


# ---------------------------------------------------------------------------
# Descriptor
# ---------------------------------------------------------------------------

DEFAULT_PATHS = {
    "requirements": "docs/requirements",
    "specifications": "docs/specifications",
    "adr": "docs/adr",
}


def _as_list(value) -> list:
    if isinstance(value, list):
        return value
    return []


def _as_str(value, fallback: str = "") -> str:
    if value is None:
        return fallback
    if isinstance(value, bool):
        return "true" if value else "false"
    return str(value)


def _is_string_list(value) -> bool:
    """Whether an optional list-of-strings key is absent or has that shape."""
    return value is None or (isinstance(value, list) and all(isinstance(v, str) for v in value))


def _article(word: str) -> str:
    """``"an"`` before a word that opens on a vowel sound, ``"a"`` otherwise."""
    return "an" if word[:1].lower() in "aeiou" else "a"


def _top_level_key_line(text: str, key: str) -> int:
    """The 1-based line number of an unindented ``key:`` mapping entry, or ``1``."""
    pattern = re.compile(r"^%s\s*:" % re.escape(key))
    for index, line in enumerate(text.split("\n")):
        if pattern.match(line):
            return index + 1
    return 1


def _key_line(text: str, dotted: str, value: str = "") -> int:
    """The 1-based line of a nested descriptor key such as ``paths.adr`` — with or without
    the ``sdd:`` wrapper — or, for a sequence key, of the item carrying ``value``. Falls
    back to the outermost key found, then to ``1``."""
    lines = text.split("\n")
    position, parent_indent, found = 0, -1, 1
    for segment in dotted.split("."):
        pattern = re.compile(r"^(\s*)%s\s*:" % re.escape(segment))
        hit = None
        #: Below the first segment only a direct child matches, so check.rfc2119 is never
        #: taken for check.families.rfc2119, whichever comes first.
        child_indent = None
        for index in range(position, len(lines)):
            line = lines[index]
            if not line.strip() or line.lstrip().startswith("#"):
                continue
            indent = len(line) - len(line.lstrip())
            if parent_indent >= 0 and indent <= parent_indent:
                break
            if parent_indent >= 0:
                if child_indent is None:
                    child_indent = indent
                elif indent != child_indent:
                    continue
            match = pattern.match(line)
            if match:
                hit = (index, len(match.group(1)))
                break
        if hit is None:
            return found
        position, parent_indent = hit[0] + 1, hit[1]
        found = hit[0] + 1
    if value:
        for index in range(position, len(lines)):
            line = lines[index]
            if not line.strip():
                continue
            if len(line) - len(line.lstrip()) <= parent_indent:
                break
            if line.lstrip().startswith("-") and value in line:
                return index + 1
    return found


def _reject_escaping_paths(root: Path, data: dict, text: str) -> None:
    """Reject a path-bearing descriptor key that points outside the repository.

    Every command reads through ``paths.*``, ``traceability`` and the ``check.*`` paths, and
    ``generate`` writes through them; an absolute path, or one that climbs out with ``..``,
    would let a read or a write land outside the tree the gate governs. The check is
    lexical — a symlink the maintainer put in their own tree is theirs to follow — and runs
    when the descriptor loads, for every command, so a bad path fails the load (exit 2) the
    way a missing descriptor does rather than mid-run. The error names the offending key's
    own line.
    """
    root_abs = os.path.abspath(str(root))

    def check(rel, key, item=False):
        rel = _as_str(rel)
        if not rel:
            return
        anchor = _key_line(text, key, rel if item else "")
        if os.path.isabs(rel):
            raise YamlError(
                "%s must be a path inside the repository, not the absolute '%s'" % (key, rel),
                anchor,
                DESCRIPTOR_REL,
            )
        target = os.path.abspath(os.path.join(root_abs, rel))
        try:
            inside = os.path.commonpath([target, root_abs]) == root_abs
        except ValueError:
            inside = False
        if not inside:
            raise YamlError(
                "%s escapes the repository: '%s'" % (key, rel), anchor, DESCRIPTOR_REL
            )

    paths = data.get("paths")
    if isinstance(paths, dict):
        for pkey, pval in paths.items():
            check(pval, "paths.%s" % _as_str(pkey))
    check(data.get("traceability"), "traceability")
    check_block = data.get("check")
    if isinstance(check_block, dict):
        check(check_block.get("script"), "check.script")
        changelog = check_block.get("changelog")
        if isinstance(changelog, dict):
            check(changelog.get("path"), "check.changelog.path")
        for croot in _as_list(check_block.get("code_roots")):
            check(croot, "check.code_roots", item=True)
        check(check_block.get("probes_catalogue"), "check.probes_catalogue")


class Descriptor:
    """`docs/.sdd.yaml`, with a default for every key the schema gives one."""

    def __init__(self, root: Path, data: dict):
        self.root = Path(root)
        self.data = data
        #: The profile as written, and the one it reads as: `full` and `lightweight` are
        #: `formal` (a file-form requirements index is allowed on formal).
        self.profile_written = _as_str(data.get("profile"), "formal") or "formal"
        self.profile = PROFILE_ALIASES.get(self.profile_written, self.profile_written)
        self.req_style = _as_str(data.get("req_style"), "area-prefixed") or "area-prefixed"
        self.req_areas = [_as_str(a) for a in _as_list(data.get("req_areas"))]
        self.req_gap = data.get("req_gap", 10)
        self.excluded_areas = [_as_str(a) for a in _as_list(data.get("excluded_areas"))]
        kinds = [_as_str(k) for k in _as_list(data.get("doc_kinds"))]
        self.doc_kinds = kinds or list(DEFAULT_KINDS)
        if self.profile == "informative" and "constitution" not in self.doc_kinds:
            self.doc_kinds.append("constitution")
        self.default_mode = _as_str(data.get("default_mode"), "spec-first") or "spec-first"
        paths = data.get("paths")
        self.paths = dict(DEFAULT_PATHS)
        if isinstance(paths, dict):
            for key, value in paths.items():
                self.paths[_as_str(key)] = _as_str(value)
        self.traceability = _as_str(
            data.get("traceability"), "docs/specifications/traceability.yaml"
        )
        self.build_entrypoint = _as_str(data.get("build_entrypoint"), "make")
        self.ci_target = _as_str(data.get("ci_target"), "ci")
        self.spec_check_target = _as_str(data.get("spec_check_target"), "spec-check")
        self.use_probes = bool(data.get("use_probes", False))
        self.use_strands = bool(data.get("use_strands", False))
        self.upstream = data.get("upstream", "")
        self.ground_truth = data.get("ground_truth", "")
        hooks = data.get("hooks")
        self.hooks = hooks if isinstance(hooks, dict) else {}
        agents = data.get("agents")
        self.agents = agents if isinstance(agents, dict) else {}
        check = data.get("check")
        self.check = check if isinstance(check, dict) else {}
        self.script = _as_str(self.check.get("script"), "scripts/sdd-check.py")
        self.check_version = _as_str(self.check.get("version"), "")
        links = self.check.get("links")
        links = links if isinstance(links, dict) else {}
        self.links_exclude = [_as_str(g) for g in _as_list(links.get("exclude"))]
        changelog = self.check.get("changelog")
        changelog = changelog if isinstance(changelog, dict) else {}
        self.changelog_path = _as_str(changelog.get("path"), "CHANGELOG.md")
        max_words = changelog.get("max_words", 35)
        #: A wrong-shape value is reported by the descriptor family, never silently replaced.
        self.shape_problems: List[str] = []
        if max_words is None or isinstance(max_words, bool) or not isinstance(max_words, int):
            self.shape_problems.append(
                "check.changelog.max_words must be an integer, not '%s'" % _as_str(max_words)
            )
            max_words = 35
        self.changelog_max_words = max_words
        self.code_roots_valid = _is_string_list(self.check.get("code_roots"))
        if not self.code_roots_valid:
            self.shape_problems.append("check.code_roots must be a list of strings")
        self.code_roots = [_as_str(r) for r in _as_list(self.check.get("code_roots"))]
        if not _is_string_list(self.check.get("test_globs")):
            self.shape_problems.append("check.test_globs must be a list of strings")
        globs = [_as_str(g) for g in _as_list(self.check.get("test_globs"))]
        self.test_globs = globs or list(DEFAULT_TEST_GLOBS)
        self.probes_catalogue = _as_str(self.check.get("probes_catalogue"), "")
        rfc2119 = self.check.get("rfc2119")
        if rfc2119 is not None and not isinstance(rfc2119, dict):
            self.shape_problems.append("check.rfc2119 must be a mapping")
        rfc2119 = rfc2119 if isinstance(rfc2119, dict) else {}
        self.rfc2119_sections = (
            _as_str(rfc2119.get("sections"), "section-sign") or "section-sign"
        )
        families = self.check.get("families")
        self.families = {}
        if isinstance(families, dict):
            for key, value in families.items():
                self.families[_as_str(key)] = _as_str(value)

    # -- loading ----------------------------------------------------------
    @classmethod
    def load(cls, root: Path) -> "Descriptor":
        path = Path(root) / DESCRIPTOR_REL
        if not path.is_file():
            raise FileNotFoundError(DESCRIPTOR_REL)
        # utf-8-sig, not utf-8: a byte-order mark (Windows editors, PowerShell redirection)
        # would otherwise survive on the first key — `﻿sdd` — the unwrap would miss it,
        # and every field would silently fall back to its default.
        text = path.read_text(encoding="utf-8-sig", errors="replace")
        data = load_yaml(text, source=DESCRIPTOR_REL)
        if isinstance(data, dict) and "sdd" in data:
            sdd = data["sdd"]
            if not isinstance(sdd, dict):
                # The tool cannot configure itself from a wrong-shape `sdd:` key: every
                # field would silently fall back to its default and the run would produce
                # a cascade of misleading findings instead of one clear failure. Fail
                # closed, once, naming the file and the line — the same contract a
                # missing or unparseable descriptor already carries.
                raise YamlError(
                    "the sdd: key must be a mapping, not %s" % type(sdd).__name__,
                    _top_level_key_line(text, "sdd"),
                    DESCRIPTOR_REL,
                )
            data = sdd
        if not isinstance(data, dict):
            raise YamlError("the descriptor is not a mapping", 1, DESCRIPTOR_REL)
        _reject_escaping_paths(Path(root), data, text)
        return cls(Path(root), data)

    # -- derived ----------------------------------------------------------
    def resolve(self, rel: str) -> Path:
        return self.root / rel

    def constitution(self) -> str:
        """The informative profile's one binding document (methodology §1a)."""
        return _as_str(self.paths.get("constitution"), DEFAULT_CONSTITUTION) or DEFAULT_CONSTITUTION

    def severity(self, family: str) -> str:
        value = self.families.get(family)
        if value in SEVERITIES:
            return value
        return DEFAULT_SEVERITY.get(family, "error")

    def req_pattern(self) -> "re.Pattern":
        if self.req_style == "flat-numeric":
            return re.compile(r"REQ-\d{3,}")
        return re.compile(r"REQ-[A-Z][A-Z0-9]*-\d{3,}")

    def is_file_form(self, rel: str) -> bool:
        return Path(rel).suffix != "" or self.resolve(rel).is_file()

    def requirements_index_path(self) -> Path:
        rel = self.paths.get("requirements", DEFAULT_PATHS["requirements"])
        if self.is_file_form(rel):
            return self.resolve(rel)
        return self.resolve(rel) / "README.md"

    def requirements_dir(self) -> Optional[Path]:
        rel = self.paths.get("requirements", DEFAULT_PATHS["requirements"])
        if self.is_file_form(rel):
            return None
        return self.resolve(rel)

    def specifications_index_path(self) -> Path:
        rel = self.paths.get("specifications", DEFAULT_PATHS["specifications"])
        if self.is_file_form(rel):
            return self.resolve(rel)
        return self.resolve(rel) / "README.md"

    def adr_index_path(self) -> Path:
        rel = self.paths.get("adr", DEFAULT_PATHS["adr"])
        if self.is_file_form(rel):
            return self.resolve(rel)
        return self.resolve(rel) / "README.md"

    def specification_files(self) -> List[Path]:
        rel = self.paths.get("specifications", DEFAULT_PATHS["specifications"])
        target = self.resolve(rel)
        if self.is_file_form(rel):
            return [target] if target.is_file() else []
        if not target.is_dir():
            return []
        # `_template.md` is the copy /sdd-scaffold leaves for the author to fill in. It
        # declares `kind: specification` but names no specification, so it is not one.
        return sorted(
            p for p in target.rglob("*.md") if p.name != "_template.md" and p.is_file()
        )

    def docs_roots(self) -> List[Path]:
        roots = [self.root / "docs"]
        seen = {"docs"}
        for key, rel in self.paths.items():
            if key == "plans":
                # paths.plans is no longer read (a NOTE says so); it never adds a root.
                continue
            normal = str(Path(rel).as_posix())
            if normal in seen or normal.startswith("docs/"):
                continue
            seen.add(normal)
            roots.append(self.resolve(rel))
        return roots

    def ground_truth_sources(self) -> List[str]:
        if isinstance(self.ground_truth, list):
            return [_as_str(s) for s in self.ground_truth if _as_str(s)]
        one = _as_str(self.ground_truth)
        return [one] if one else []

    def upstream_relations(self) -> Dict[str, dict]:
        if isinstance(self.upstream, dict):
            return {
                _as_str(name): body
                for name, body in self.upstream.items()
                if isinstance(body, dict)
            }
        name = _as_str(self.upstream)
        # The scalar form names no relation, so it takes the generic name "upstream"
        # itself (never the repo string — an unpredictable key any caller deriving a
        # path or message from the relation name would have to special-case) and the
        # implied role, matching references/cross-repo-gap.md's documented shape.
        return {"upstream": {"repo": name, "role": "consumed"}} if name else {}


# ---------------------------------------------------------------------------
# Records
# ---------------------------------------------------------------------------

RECORD_KEYS = (
    "id",
    "title",
    "canonical",
    "status",
    "implementation",
    "packages",
    "tests",
    "probes",
    "operations",
    "draft_reason",
)
RECORD_LIST_KEYS = ("packages", "tests", "probes", "operations")


@dataclasses.dataclass
class Record:
    """One traceability-map record."""

    id: str = ""
    title: str = ""
    canonical: str = ""
    status: str = ""
    implementation: str = ""
    packages: List[str] = dataclasses.field(default_factory=list)
    tests: List[str] = dataclasses.field(default_factory=list)
    probes: List[str] = dataclasses.field(default_factory=list)
    operations: List[str] = dataclasses.field(default_factory=list)
    draft_reason: str = ""
    unknown_keys: List[str] = dataclasses.field(default_factory=list)
    line: int = 0
    raw: dict = dataclasses.field(default_factory=dict)

    def enforced(self) -> bool:
        return self.implementation in ENFORCED

    def evidence(self) -> List[str]:
        return list(self.packages) + list(self.tests) + list(self.operations)


def _sequence_line_numbers(text: str, key: str) -> List[int]:
    """The line number of every ``- `` item directly under a top-level key."""
    lines = text.split("\n")
    start = None
    for index, line in enumerate(lines):
        if re.match(r"^%s\s*:\s*(#.*)?$" % re.escape(key), line):
            start = index + 1
            break
    if start is None:
        return []
    numbers: List[int] = []
    item_indent = None
    for index in range(start, len(lines)):
        line = lines[index]
        if not line.strip() or line.lstrip().startswith("#"):
            continue
        indent = len(line) - len(line.lstrip())
        if indent == 0:
            break
        match = re.match(r"^\s*-\s", line)
        if match and (item_indent is None or indent == item_indent):
            item_indent = indent
            numbers.append(index + 1)
    return numbers


def load_map(desc: Descriptor) -> List[Record]:
    """Read the traceability map. Raises ``FileNotFoundError`` or ``YamlError``."""
    path = desc.resolve(desc.traceability)
    if not path.is_file():
        raise FileNotFoundError(desc.traceability)
    text = path.read_text(encoding="utf-8-sig", errors="replace")
    data = load_yaml(text, source=desc.traceability)
    entries = data.get("requirements") if isinstance(data, dict) else None
    if not isinstance(entries, list):
        return []
    numbers = _sequence_line_numbers(text, "requirements")
    records: List[Record] = []
    for index, entry in enumerate(entries):
        lineno = numbers[index] if index < len(numbers) else 0
        if not isinstance(entry, dict):
            # Fail closed: a non-mapping item (a lone scalar in the list, a stray `-`)
            # would otherwise become an id-less Record, keep the list non-empty, and let
            # `generate` rewrite every index block from records that have nothing in them.
            raise YamlError(
                "requirements item %d is not a mapping" % (index + 1),
                lineno or _top_level_key_line(text, "requirements"),
                desc.traceability,
            )
        record = Record(line=lineno, raw=entry)
        record.id = _as_str(entry.get("id"))
        record.title = _as_str(entry.get("title"))
        record.canonical = _as_str(entry.get("canonical"))
        record.status = _as_str(entry.get("status"))
        record.implementation = _as_str(entry.get("implementation"))
        record.draft_reason = _as_str(entry.get("draft_reason"))
        for key in RECORD_LIST_KEYS:
            setattr(record, key, [_as_str(v) for v in _as_list(entry.get(key))])
        record.unknown_keys = [_as_str(k) for k in entry.keys() if _as_str(k) not in RECORD_KEYS]
        records.append(record)
    return records


# ---------------------------------------------------------------------------
# Context
# ---------------------------------------------------------------------------


class Context:
    """What every family reads: the root, the descriptor, the records, cached files."""

    def __init__(
        self,
        root: Path,
        desc: Descriptor,
        records: List[Record],
        changelog_all: bool = False,
    ):
        self.root = Path(root)
        self.desc = desc
        self.records = records
        self.records_by_id = {r.id: r for r in records if r.id}
        self.changelog_all = changelog_all
        self._texts: Dict[str, str] = {}
        #: Why a file could not be read or decoded, keyed like ``_texts``.
        self.read_problems: Dict[str, str] = {}
        self._docs_files: Optional[List[Path]] = None
        #: The documents marked `kind: plan` that no family reads.
        self.skipped_plans: List[Path] = []
        self._git_ok: Optional[bool] = None
        self._ignored: Optional[List[str]] = None

    # -- files ------------------------------------------------------------
    def abs(self, path) -> Path:
        candidate = Path(path)
        return candidate if candidate.is_absolute() else self.root / candidate

    def rel(self, path) -> str:
        candidate = self.abs(path)
        try:
            return candidate.relative_to(self.root).as_posix()
        except ValueError:
            return candidate.as_posix()

    def read(self, path) -> str:
        """The file's text (BOM dropped, line endings normalised to ``\n``). A file that
        cannot be read reads as empty and one that is not UTF-8 is decoded with
        replacement characters, and either way the reason lands in :attr:`read_problems`
        so a family can report it rather than treat the file as empty."""
        key = str(self.abs(path))
        if key not in self._texts:
            try:
                raw = Path(key).read_bytes()
            except OSError as exc:
                self.read_problems[key] = "cannot be read (%s)" % (exc.strerror or exc)
                self._texts[key] = ""
                return ""
            try:
                text = raw.decode("utf-8-sig")
            except UnicodeDecodeError as exc:
                self.read_problems[key] = "is not valid UTF-8 (byte %d)" % exc.start
                text = raw.decode("utf-8-sig", errors="replace")
            self._texts[key] = text.replace("\r\n", "\n").replace("\r", "\n")
        return self._texts[key]

    def read_problem(self, path) -> Optional[str]:
        """Why ``path`` could not be read or decoded, or ``None``."""
        self.read(path)
        return self.read_problems.get(str(self.abs(path)))

    def docs_files(self) -> List[Path]:
        """Every markdown document under the docs roots, less what git ignores and less plans.

        A git-ignored file (a working plan, a scratch note) is not part of the repository, and a
        document marked `kind: plan` is a working file that happens to be committed, so no family
        reads either. Without git every file is read: the gate would rather read too
        much than quietly miss a document.
        """
        if self._docs_files is None:
            listing = self.git("ls-files", "--cached", "--others", "--exclude-standard", "-z")
            kept = None if listing is None else {rel for rel in listing.split("\0") if rel}
            if kept is not None:
                # A file committed before its directory was ignored is still tracked; the ignore
                # rule that now matches it keeps it out all the same.
                ignored = self.git("ls-files", "--cached", "--ignored", "--exclude-standard", "-z") or ""
                kept -= {rel for rel in ignored.split("\0") if rel}
            found: List[Path] = []
            for root in self.desc.docs_roots():
                if root.is_file() and root.suffix == ".md":
                    # A file-form path: a single-file requirements index, or the constitution.
                    if kept is None or self.rel(root) in kept:
                        found.append(root)
                    continue
                if not root.is_dir():
                    continue
                for path in sorted(root.rglob("*.md")):
                    if not path.is_file() or ".git" in path.parts:
                        continue
                    if kept is not None and self.rel(path) not in kept:
                        continue
                    found.append(path)
            seen = set()
            unique = []
            for path in found:
                if str(path) in seen:
                    continue
                seen.add(str(path))
                # A plan is a temporary working file, committed or not: no family reads it, unless it
                # sits where requirements, specifications or ADRs live, which doc-kinds refuses.
                if doc_kind(self, path) == "plan" and not self.in_normative_folder(path):
                    self.skipped_plans.append(path)
                    continue
                unique.append(path)
            self._docs_files = unique
        return self._docs_files

    def in_normative_folder(self, path) -> bool:
        path = Path(path)
        for key in ("requirements", "specifications", "adr"):
            where = self.desc.resolve(_as_str(self.desc.paths.get(key, DEFAULT_PATHS.get(key, ""))))
            if path == where or where in path.parents:
                return True
        return False

    def ignored(self, path) -> bool:
        """Whether git ignores ``path`` and does not track it: a file no clean checkout has."""
        if self._ignored is None:
            listing = self.git("ls-files", "--others", "--ignored", "--exclude-standard", "--directory", "-z")
            self._ignored = [entry for entry in (listing or "").split("\0") if entry]
        rel = self.rel(path)
        return any(
            rel == entry.rstrip("/") or (entry.endswith("/") and rel.startswith(entry))
            for entry in self._ignored
        )

    # -- git --------------------------------------------------------------
    def _run_git(self, args) -> Optional[str]:
        try:
            proc = subprocess.run(
                ["git", "-C", str(self.root)] + [str(a) for a in args],
                stdout=subprocess.PIPE,
                stderr=subprocess.DEVNULL,
                check=False,
            )
        except (OSError, ValueError):
            return None
        if proc.returncode != 0:
            return None
        return proc.stdout.decode("utf-8", "replace")

    def git(self, *args) -> Optional[str]:
        """Run a git command in the repository, or return ``None`` when git cannot serve."""
        if self._git_ok is None:
            top = self._run_git(("rev-parse", "--show-toplevel"))
            self._git_ok = False
            if top is not None and top.strip():
                try:
                    self._git_ok = Path(top.strip()).resolve() == self.root.resolve()
                except OSError:
                    self._git_ok = False
        if not self._git_ok:
            return None
        return self._run_git(args)

    # -- severity ---------------------------------------------------------
    def level(self, family: str) -> str:
        return "ERROR" if self.desc.severity(family) == "error" else "WARN"


# ---------------------------------------------------------------------------
# Families
# ---------------------------------------------------------------------------


def _frontmatter_of(ctx: Context, report: "Report", family: str, path: Path):
    """``(mapping, reported)`` — the frontmatter, or ``None`` with a parse failure reported."""
    try:
        front, _ = frontmatter(ctx.read(path))
    except YamlError as exc:
        report.add(
            family,
            ctx.level(family),
            ctx.rel(path),
            "frontmatter does not parse: %s" % exc.message,
        )
        return None, True
    return front, False


def doc_kind(ctx: Context, path) -> Optional[str]:
    """The ``kind:`` a document declares, or ``None`` when it declares none.

    A quiet reader: a frontmatter block that does not parse is not a kind, and the family
    that owns the file reports the parse failure through :func:`_frontmatter_of`.
    """
    try:
        front, _ = frontmatter(ctx.read(path))
    except YamlError:
        return None
    if not isinstance(front, dict):
        return None
    return _as_str(front.get("kind")) or None


def kind_zone(kind: str) -> str:
    """The zone a kind sits in: ``normative``, ``upstream`` or ``informative``.

    Any kind that is neither one of the three normative kinds nor ``upstream`` is
    informative, so a kind a repository adds to ``doc_kinds`` joins the informative zone
    and carries no status axis.
    """
    if kind in NORMATIVE_KINDS:
        return "normative"
    if kind == "upstream":
        return "upstream"
    return "informative"


#: Where a document lives implies its kind when its frontmatter declares none.
_LOCATION_KIND = (
    ("specifications", "specification"),
    ("requirements", "requirement"),
    ("adr", "adr"),
)


def effective_kind(ctx: Context, path) -> str:
    """The kind every family but ``doc-kinds`` treats a document as: its own declared
    ``kind:``, or — when it declares none — the kind implied by which ``paths.*`` it
    sits under (otherwise ``guide``). ``doc-kinds`` keeps reading :func:`doc_kind`
    directly, so a missing declaration is still reported at its own severity.

    ``specifications`` is checked before ``requirements``: in a registry-model
    repository, where ``paths.requirements`` names a single file and the requirements
    live in the same directory as the specifications (or ``paths.requirements`` and
    ``paths.specifications`` name the same directory outright), a kind-less document is
    a specification, never a requirement — only the registry file itself, matched
    exactly, ever infers ``requirement``.
    """
    kind = doc_kind(ctx, path)
    if kind:
        return kind
    rel = ctx.rel(path)
    for key, implied in _LOCATION_KIND:
        configured = Path(ctx.desc.paths.get(key, DEFAULT_PATHS.get(key, ""))).as_posix()
        if configured and (rel == configured or rel.startswith(configured + "/")):
            return implied
    return "guide"


def _waived(ctx: Context, report: "Report", family: str, path) -> bool:
    """Whether a file waives one family, recording the waiver so the summary can count it."""
    if family not in waivers(ctx.read(path)):
        return False
    report.waived.setdefault(family, []).append(ctx.rel(path))
    return True


def check_descriptor(ctx: Context, report: "Report") -> None:
    """The descriptor is internally consistent and matches the tree and the tool."""
    desc = ctx.desc
    level = ctx.level("descriptor")

    def add(message):
        report.add("descriptor", level, DESCRIPTOR_REL, message)

    text = ctx.read(ctx.root / DESCRIPTOR_REL)

    def note(key, message):
        report.add("descriptor", "NOTE", "%s:%d" % (DESCRIPTOR_REL, _key_line(text, key)), message)

    if desc.profile not in PROFILES:
        add("profile: '%s' is not %s" % (desc.profile_written, " | ".join(PROFILES)))
    elif desc.profile_written == "lightweight":
        note(
            "profile",
            "profile: lightweight is read as formal with a file-form requirements index; write formal",
        )
    informative = desc.profile == "informative"
    if desc.req_style not in ("area-prefixed", "flat-numeric"):
        add("req_style: '%s' is not area-prefixed | flat-numeric" % desc.req_style)
    elif desc.req_style == "area-prefixed":
        if not desc.req_areas:
            add("req_areas: an area-prefixed repository declares at least one area")
    elif desc.req_areas:
        add("req_areas: a flat-numeric repository declares no areas")
    overlap = sorted(set(desc.excluded_areas) & set(desc.req_areas))
    if overlap:
        add("excluded_areas must be disjoint from req_areas: %s" % ", ".join(overlap))
    for key in ("requirements", "specifications", "adr"):
        rel = desc.paths.get(key, "")
        if not rel:
            if not informative:
                add("paths.%s is not declared" % key)
            continue
        if desc.is_file_form(rel) and key not in ("requirements", "specifications"):
            add("paths.%s: only requirements and specifications may name a file" % key)
            continue
        # On the informative profile requirements, specifications and the map are optional.
        if not informative and not desc.resolve(rel).exists():
            add("paths.%s: '%s' does not exist" % (key, rel))
    if "plans" in desc.paths:
        note("paths.plans", "paths.plans is no longer read; delete the line")
    if informative and "constitution" in desc.paths and not desc.resolve(desc.constitution()).is_file():
        add("paths.constitution: '%s' is not a file" % desc.constitution())
    if not desc.traceability:
        if not informative:
            add("traceability is not declared")
    elif not informative and not desc.resolve(desc.traceability).is_file():
        add("traceability: '%s' does not exist" % desc.traceability)
    if desc.check_version != __version__:
        add(
            "check.version '%s' does not equal the tool's version %s"
            % (desc.check_version, __version__)
        )
    for family, value in desc.families.items():
        if family in RETIRED_FAMILIES:
            report.add(
                "descriptor",
                "NOTE",
                "%s:%d" % (DESCRIPTOR_REL, _key_line(text, "check.families.%s" % family)),
                "check.families.%s: the %s family was retired in 0.7.0; delete the line"
                % (family, family),
            )
        elif family not in FAMILIES:
            add("check.families: unknown family '%s'" % family)
        elif value not in SEVERITIES:
            add("check.families.%s: '%s' is not error | warn | off" % (family, value))
    if desc.default_mode not in MODES:
        add("default_mode: '%s' is not %s" % (desc.default_mode, " | ".join(MODES)))
    for problem in desc.shape_problems:
        add(problem)
    if desc.rfc2119_sections not in RFC2119_SECTIONS:
        text = ctx.read(ctx.root / DESCRIPTOR_REL)
        report.add(
            "descriptor",
            level,
            "%s:%d" % (DESCRIPTOR_REL, _key_line(text, "check.rfc2119.sections")),
            "check.rfc2119.sections: '%s' is not %s"
            % (desc.rfc2119_sections, " | ".join(RFC2119_SECTIONS)),
        )
    if desc.code_roots_valid:
        for rel in desc.code_roots:
            if not desc.resolve(rel).exists():
                add("check.code_roots: '%s' does not exist" % rel)
    # A repository may extend doc_kinds; an extra kind is read as informative. The four
    # normative kinds carry status vocabularies, so they cannot be dropped.
    missing_kinds = [kind for kind in NORMATIVE_KINDS if kind not in desc.doc_kinds]
    if missing_kinds:
        add("doc_kinds must keep the normative kinds; missing: %s" % ", ".join(missing_kinds))


def check_map_schema(ctx: Context, report: "Report", level: Optional[str] = None) -> None:
    """Every record carries the fields the schema requires, with values in vocabulary.

    ``level`` overrides the family's configured severity — ``generate``'s write-free
    preflight forces ``ERROR``. An unknown record key is a ``WARN`` either way.
    """
    desc = ctx.desc
    level = level or ctx.level("map-schema")
    pattern = desc.req_pattern()
    seen: Dict[str, int] = {}
    for record in ctx.records:
        where = desc.traceability
        if record.line:
            where = "%s:%d" % (desc.traceability, record.line)

        def add(message, anchor=where, at=level):
            report.add("map-schema", at, anchor, message)

        if not record.id:
            add("a record carries no id")
        elif not pattern.fullmatch(record.id):
            add("id '%s' does not match the repository's %s style" % (record.id, desc.req_style))
        else:
            if record.id in seen:
                add("duplicate id %s, first seen at line %d" % (record.id, seen[record.id]))
            else:
                seen[record.id] = record.line
            if desc.req_style == "area-prefixed":
                area = record.id.split("-")[1]
                if area in desc.excluded_areas:
                    add("id %s uses the excluded area '%s'" % (record.id, area))
                elif area not in desc.req_areas:
                    add("id %s uses an area that is not declared in req_areas: '%s'" % (record.id, area))
        for key in ("title", "canonical", "status", "implementation"):
            if not getattr(record, key):
                add("%s: %s is required" % (record.id or "a record", key))
        if record.status and record.status not in STATUS:
            add("%s: status '%s' is not %s" % (record.id, record.status, " | ".join(STATUS)))
        if record.implementation and record.implementation not in IMPLEMENTATION:
            add(
                "%s: implementation '%s' is not %s"
                % (record.id, record.implementation, " | ".join(IMPLEMENTATION))
            )
        if record.implementation == "retired" and record.status and record.status != "deprecated":
            add(
                "%s: implementation 'retired' belongs only to a deprecated requirement, not '%s'"
                % (record.id, record.status)
            )
        if record.canonical:
            path_part, _, anchor_part = record.canonical.partition("#")
            if not path_part or not anchor_part:
                add("%s: canonical must be path#anchor, not '%s'" % (record.id, record.canonical))
        for key in RECORD_LIST_KEYS:
            value = record.raw.get(key)
            if value is None:
                continue
            if not isinstance(value, list) or any(not isinstance(v, str) for v in value):
                add("%s: %s must be a list of strings" % (record.id, key))
        for key in record.unknown_keys:
            if key == "plans":
                add(
                    "%s: unknown key 'plans' — the plan axis is retired from the record schema"
                    % record.id,
                    at="WARN",
                )
            else:
                add("%s: unknown key '%s'" % (record.id, key), at="WARN")


def _identifier_re(identifier: str) -> "re.Pattern":
    """Match one identifier on its own boundaries, so REQ-FOUND-0011 is not REQ-FOUND-001."""
    return re.compile(r"(?<![0-9A-Za-z_-])%s(?![0-9A-Za-z_-])" % re.escape(identifier))


def _anchor_span(text: str, fragment: str) -> Optional[Tuple[int, int]]:
    """The section an explicit ``<a id="…">`` sits in."""
    if fragment not in explicit_anchors(text):
        return None
    lines = text.split("\n")
    anchor_line = 0
    for index, line in enumerate(lines):
        if fragment in _ANCHOR_RE.findall(line):
            anchor_line = index + 1
            break
    if not anchor_line:
        return None
    found = headings(text)
    # An anchor that sits alone — nothing on its own line but the tag — and immediately
    # above a heading names that heading's section. An anchor line carrying trailing
    # prose is not a floating label for the next heading; it is content of whatever
    # section encloses it, so it falls through to the enclosing-section lookup below.
    anchor_bare = not _ANCHOR_TAG_RE.sub("", lines[anchor_line - 1]).strip()
    if anchor_bare:
        for index, entry in enumerate(found):
            if entry[0] <= anchor_line:
                continue
            between = range(anchor_line + 1, entry[0])
            if all(not lines[number - 1].strip() for number in between):
                return _heading_span(found, index, len(lines))
            break
    enclosing = None
    for index, entry in enumerate(found):
        if entry[0] <= anchor_line:
            enclosing = index
        else:
            break
    if enclosing is None:
        return 1, len(lines) + 1
    return _heading_span(found, enclosing, len(lines))


def _implements_marker(text: str, span: Tuple[int, int], identifier: str) -> bool:
    """Whether the section's back-edge to ``identifier`` is present: an explicit
    ``**Implements:**`` line inside the section, or the identifier named — on an
    identifier-boundary match — in the section's own heading text."""
    pattern = _identifier_re(identifier)
    start, end = span
    for lineno, line in _content_lines(text, False):
        if lineno == start and pattern.search(line):
            return True
        if start <= lineno < end and "**Implements:**" in line and pattern.search(line):
            return True
    return False


def _probe_catalogue_ids(ctx: Context) -> Optional[set]:
    """Every PROBE id a catalogue heading carries, or ``None`` when none is configured."""
    rel = ctx.desc.probes_catalogue
    if not rel:
        return None
    found = set()
    for _, _, title, _ in headings(ctx.read(ctx.desc.resolve(rel))):
        found.update(re.findall(r"PROBE-\d+", title))
    return found


def check_map_to_tree(ctx: Context, report: "Report") -> None:
    """Every record resolves onto the tree: canonical section, evidence paths, probes."""
    desc = ctx.desc
    level = ctx.level("map-to-tree")
    catalogue = _probe_catalogue_ids(ctx)
    for record in ctx.records:
        anchor = record.id or "%s:%d" % (desc.traceability, record.line)

        def add(message):
            report.add("map-to-tree", level, anchor, message)

        path_part, _, fragment = record.canonical.partition("#")
        if path_part:
            target = desc.resolve(path_part)
            if not target.is_file():
                add("canonical file missing: %s" % path_part)
            elif fragment:
                text = ctx.read(target)
                span = section_slice(text, fragment)
                if span is None:
                    span = _anchor_span(text, fragment)
                if span is None:
                    add(
                        "canonical anchor '#%s' resolves to no heading slug and no explicit anchor in %s"
                        % (fragment, path_part)
                    )
                elif record.id and not _implements_marker(text, span, record.id):
                    add(
                        "the section at '#%s' carries no '**Implements:** %s' line and "
                        "its heading does not name %s either"
                        % (fragment, record.id, record.id)
                    )
        for rel in record.packages:
            if not desc.resolve(rel).exists():
                add("missing package path: %s" % rel)
        for key in ("tests", "operations"):
            for rel in getattr(record, key):
                target = desc.resolve(rel)
                if not target.exists():
                    add("missing %s path: %s" % (key, rel))
                elif not target.is_file():
                    add(
                        "%s %s entry must be a file, not a directory: %s"
                        % (_article(key), key, rel)
                    )
        for probe in record.probes:
            if catalogue is not None:
                if probe not in catalogue:
                    add("%s resolves to no heading in %s" % (probe, desc.probes_catalogue))
            elif not any(probe in ctx.read(desc.resolve(rel)) for rel in record.tests):
                add("%s is named by no catalogue and by none of the record's tests" % probe)
        if record.enforced() and not record.evidence():
            add(
                "%s is '%s' and carries no evidence: packages, tests or operations"
                % (record.id, record.implementation)
            )


_STABILITY_HEADERS = ("stability", "status")
_IMPLEMENTATION_HEADERS = ("implementation", "impl.", "impl")


def _cell_text(cell: str) -> str:
    return cell.replace("`", "").replace("*", "").strip()


def _index_columns(header: List[str]) -> Tuple[Optional[int], Optional[int], List[str]]:
    """``(stability column, implementation column, the axes the table names no column for)``.

    An axis is found by header text. An axis the header does not name falls back to its
    position — second-to-last for stability, last for implementation — but only where the
    table has room for both axes beside the identifier and the title, and only onto a column
    the other axis has not already claimed. A narrower table is reported, never guessed at:
    comparing a title cell against a status vocabulary is a finding about nothing.
    """
    stability = None
    implementation = None
    for index, name in enumerate(header):
        lowered = _cell_text(name).lower()
        if lowered in _STABILITY_HEADERS and stability is None:
            stability = index
        elif lowered in _IMPLEMENTATION_HEADERS and implementation is None:
            implementation = index
    width = len(header)
    unnamed: List[str] = []
    if stability is None:
        candidate = width - 2
        if width >= 4 and candidate != implementation:
            stability = candidate
        else:
            unnamed.append("Stability")
    if implementation is None:
        candidate = width - 1
        if width >= 4 and candidate != stability:
            implementation = candidate
        else:
            unnamed.append("Implementation")
    return stability, implementation, unnamed


def _index_tables(text: str):
    """Every pipe table as ``(header line, header cells, [(line, row cells)])``."""
    tables: List[Tuple[int, List[str], List[Tuple[int, List[str]]]]] = []
    previous = None
    for lineno, header, cells in table_rows(text):
        if previous is None or lineno != previous + 1:
            tables.append((lineno - 2, header, []))
        tables[-1][2].append((lineno, cells))
        previous = lineno
    return tables


def _detail_files(requirements_dir: Path, record_id: str) -> List[Path]:
    """The detail files a record's id owns: ``<id>.md`` and ``<id>-*.md``, never a longer
    id that merely shares the prefix. A plain ``glob(id + "*")`` lets ``REQ-FOUND-001`` claim
    ``REQ-FOUND-0011.md`` and write one record's status into another's file."""
    prefix = record_id + "-"
    return [
        path
        for path in sorted(requirements_dir.glob(record_id + "*.md"))
        if path.stem == record_id or path.stem.startswith(prefix)
    ]


def check_index_sync(ctx: Context, report: "Report") -> None:
    """The requirements index, the detail files and the map agree on both status axes."""
    desc = ctx.desc
    level = ctx.level("index-sync")
    index_path = desc.requirements_index_path()
    index_rel = ctx.rel(index_path)
    if not index_path.is_file():
        report.add("index-sync", level, index_rel, "the requirements index is missing")
        return
    pattern = desc.req_pattern()
    rows = []
    unnamed_axes: List[Tuple[int, str]] = []
    for header_line, header, table in _index_tables(ctx.read(index_path)):
        stability, implementation, unnamed = _index_columns(header)
        carries_ids = False
        for lineno, cells in table:
            identifier = None
            for cell in cells:
                match = pattern.search(cell)
                if match:
                    identifier = match.group(0)
                    break
            if identifier is None:
                continue
            carries_ids = True
            rows.append(
                (
                    lineno,
                    identifier,
                    cells[stability] if stability is not None and stability < len(cells) else None,
                    cells[implementation]
                    if implementation is not None and implementation < len(cells)
                    else None,
                )
            )
        if carries_ids:
            unnamed_axes.extend((header_line, axis) for axis in unnamed)
    for header_line, axis in unnamed_axes:
        report.add(
            "index-sync",
            "WARN",
            "%s:%d" % (index_rel, header_line),
            "the index table names no %s column" % axis,
        )
    if not rows:
        report.add(
            "index-sync",
            level,
            index_rel,
            "the requirements index parses to zero rows carrying a REQ id",
        )
        return

    seen = set()
    for lineno, identifier, stability, implementation in rows:
        anchor = "%s:%d" % (index_rel, lineno)
        if identifier in seen:
            report.add("index-sync", level, anchor, "%s has more than one index row" % identifier)
            continue
        seen.add(identifier)
        record = ctx.records_by_id.get(identifier)
        if record is None:
            report.add(
                "index-sync",
                level,
                anchor,
                "%s has an index row but is missing from traceability" % identifier,
            )
            continue
        if stability is not None and _cell_text(stability).lower() != record.status.lower():
            report.add(
                "index-sync",
                level,
                anchor,
                "%s: the index Stability cell '%s' does not equal the record status '%s'"
                % (identifier, _cell_text(stability), record.status),
            )
        if (
            implementation is not None
            and _cell_text(implementation).lower() != record.implementation.lower()
        ):
            report.add(
                "index-sync",
                level,
                anchor,
                "%s: the index Implementation cell '%s' does not equal the record implementation '%s'"
                % (identifier, _cell_text(implementation), record.implementation),
            )
    for record in ctx.records:
        if record.id and record.id not in seen:
            report.add(
                "index-sync", level, record.id, "%s has a record but is missing from the index" % record.id
            )

    requirements_dir = desc.requirements_dir()
    if requirements_dir is None:
        report.add(
            "index-sync",
            "NOTE",
            index_rel,
            "the detail-file check is skipped: a file-form requirements index keeps the requirements in one file",
        )
    else:
        for record in ctx.records:
            if not record.id:
                continue
            for detail in _detail_files(requirements_dir, record.id):
                detail_rel = ctx.rel(detail)
                front, reported = _frontmatter_of(ctx, report, "index-sync", detail)
                if front is None:
                    if not reported:
                        report.add(
                            "index-sync",
                            level,
                            detail_rel,
                            "%s: the detail file carries no frontmatter" % record.id,
                        )
                    continue
                for key, expected in (
                    ("status", record.status),
                    ("implementation", record.implementation),
                ):
                    actual = _as_str(front.get(key))
                    if actual.lower() != expected.lower():
                        report.add(
                            "index-sync",
                            level,
                            detail_rel,
                            "%s: the detail file says %s '%s'; the record says '%s'"
                            % (record.id, key, actual, expected),
                        )

    for spec in desc.specification_files():
        front, _ = _frontmatter_of(ctx, report, "index-sync", spec)
        if not front or "requirements" not in front:
            continue
        declared = set(_as_str(item) for item in _as_list(front.get("requirements")))
        owned = set()
        for record in ctx.records:
            if not record.id or not record.canonical:
                continue
            if ctx.rel(desc.resolve(record.canonical.partition("#")[0])) == ctx.rel(spec):
                owned.add(record.id)
        if declared != owned:
            report.add(
                "index-sync",
                "WARN",
                ctx.rel(spec),
                "frontmatter requirements: %s does not equal the records whose canonical points here: %s"
                % (sorted(declared), sorted(owned)),
            )


def _walk_code(ctx: Context, roots: List[Path], skip: set):
    """Every file under the code roots. The only exclusions are the pruned directories,
    the descriptor's own document paths, and a file that does not decode as UTF-8."""
    for root in roots:
        if not root.is_dir():
            if root.is_file():
                yield root
            continue
        for current, dirnames, filenames in os.walk(str(root)):
            here = Path(current)
            dirnames[:] = sorted(
                name
                for name in dirnames
                if name not in NESTED_PRUNED and ctx.rel(here / name) not in skip
            )
            for name in sorted(filenames):
                path = here / name
                if ctx.rel(path) not in skip:
                    yield path


def _is_pruned(rel: str, skip: set) -> bool:
    """Whether a repository-relative path sits inside a pruned or documented directory."""
    parts = rel.split("/")
    if any(part in NESTED_PRUNED for part in parts):
        return True
    prefix = ""
    for part in parts:
        prefix = part if not prefix else prefix + "/" + part
        if prefix in skip:
            return True
    return False


def _code_skip_set(desc: "Descriptor") -> set:
    """What a code scan never reads: the documents, the map, and the vendored gate.

    The gate is a vendored artefact, not this repository's code, so the identifiers in
    its own baseline fixture are not citations this repository has to answer for.
    """
    skip = set(Path(rel).as_posix() for rel in desc.paths.values())
    skip.update(ROOT_PRUNED)
    skip.add(Path(desc.traceability).as_posix())
    if desc.script:
        skip.add(Path(desc.script).as_posix())
    return skip


def _code_files(ctx: Context, roots: List[Path], skip: set) -> List[Path]:
    """The candidate files, from git where the repository is a work tree.

    Git knows what the repository keeps and what it ignores, so a scratch directory a
    developer never committed is not read as code. Without git the whole tree is walked,
    because the gate would rather read too much than quietly miss a citation.
    """
    listing = ctx.git("ls-files", "--cached", "--others", "--exclude-standard", "-z")
    if listing is None:
        return list(_walk_code(ctx, roots, skip))
    root_rels = [ctx.rel(root) for root in roots]
    found: List[Path] = []
    seen = set()
    for rel in listing.split("\0"):
        if not rel or rel in seen or _is_pruned(rel, skip):
            continue
        under = any(
            base in ("", ".") or rel == base or rel.startswith(base + "/") for base in root_rels
        )
        if not under:
            continue
        seen.add(rel)
        found.append(ctx.root / rel)
    return sorted(found)


def check_tree_to_map(ctx: Context, report: "Report") -> None:
    """Every identifier the code cites names a record, and a test names a tested record."""
    desc = ctx.desc
    level = ctx.level("tree-to-map")
    pattern = re.compile(
        r"(?<![0-9A-Za-z_-])(%s)(?![0-9A-Za-z_-])" % desc.req_pattern().pattern
    )
    if not desc.code_roots_valid:
        report.skip("tree-to-map", "check.code_roots is not a list of strings")
        return
    # A configured root that does not exist is a descriptor finding; scanning the rest is
    # still honest, but scanning nothing and calling it a pass is not.
    roots = [desc.resolve(rel) for rel in desc.code_roots if desc.resolve(rel).exists()]
    if desc.code_roots and not roots:
        report.skip("tree-to-map", "no configured code root exists")
        return
    if not desc.code_roots:
        roots = [ctx.root]
    skip = _code_skip_set(desc)
    for path in _code_files(ctx, roots, skip):
        try:
            text = path.read_text(encoding="utf-8")
        except (OSError, UnicodeDecodeError):
            continue
        if "REQ-" not in text:
            continue
        rel = ctx.rel(path)
        is_test = any(fnmatch.fnmatch(path.name, glob) for glob in desc.test_globs)
        seen = set()
        for lineno, line in enumerate(text.split("\n"), 1):
            for match in pattern.finditer(line):
                token = match.group(1)
                if token in seen:
                    continue
                seen.add(token)
                anchor = "%s:%d" % (rel, lineno)
                record = ctx.records_by_id.get(token)
                if record is None:
                    report.add("tree-to-map", level, anchor, "unknown identifier cited: %s" % token)
                elif is_test and not record.tests:
                    report.add(
                        "tree-to-map",
                        "WARN",
                        anchor,
                        "this test cites %s, whose record lists no tests" % token,
                    )


def check_doc_kinds(ctx: Context, report: "Report") -> None:
    """Every document declares a known kind and carries the status axis that kind owns."""
    desc = ctx.desc
    level = ctx.level("doc-kinds")
    paths = ctx.docs_files()
    if not paths:
        report.skip("doc-kinds", "no markdown documents")
        return
    examined = 0
    for path in paths:
        problem = ctx.read_problem(path)
        if problem:
            # An unreadable document is not an empty one: every family would otherwise
            # read it as blank and pass it, so the gate says so once, loudly.
            examined += 1
            report.add("doc-kinds", "ERROR", ctx.rel(path), "the document %s" % problem)
            continue
        if _waived(ctx, report, "doc-kinds", path):
            continue
        examined += 1
        anchor = ctx.rel(path)
        front, reported = _frontmatter_of(ctx, report, "doc-kinds", path)
        if front is None:
            if not reported:
                report.add("doc-kinds", level, anchor, "the frontmatter declares no kind")
            continue
        kind = _as_str(front.get("kind"))
        if not kind:
            report.add("doc-kinds", level, anchor, "the frontmatter declares no kind")
            continue
        if kind == "plan":
            report.add("doc-kinds", "ERROR", anchor, "a plan does not belong where requirements, specifications "
                       "or ADRs live; move it out, or give the document its own kind")
            continue
        if kind not in desc.doc_kinds:
            report.add(
                "doc-kinds", level, anchor, "kind '%s' is not declared in doc_kinds" % kind
            )
            continue
        zone = kind_zone(kind)
        if zone == "upstream":
            if front.get("state") is None and front.get("status") is None:
                continue
            if front.get("status") is not None:
                report.add(
                    "doc-kinds",
                    "ERROR",
                    anchor,
                    "an upstream document names its axis state:, never status:",
                )
            state = _as_str(front.get("state"))
            if state and state not in UPSTREAM_STATE:
                report.add(
                    "doc-kinds",
                    "ERROR",
                    anchor,
                    "state '%s' is not %s" % (state, " | ".join(UPSTREAM_STATE)),
                )
        elif zone == "normative":
            key, vocabulary = KIND_VOCABULARY[kind]
            value = _as_str(front.get(key))
            if value and value not in vocabulary:
                report.add(
                    "doc-kinds",
                    "ERROR",
                    anchor,
                    "%s '%s' is not %s" % (key, value, " | ".join(vocabulary)),
                )
        elif front.get("status") is not None:
            report.add(
                "doc-kinds",
                "WARN",
                anchor,
                "an informative kind carries no status axis, so status: does not belong here",
            )
    if not examined:
        report.skip("doc-kinds", "every document is waived")


def _section_heading(desc: "Descriptor") -> Callable[[str], bool]:
    """The predicate for a heading that opens a normative section, by the convention
    ``check.rfc2119.sections`` names. The match is lexical and needs no record: the
    requirement-id form accepts any token of the repository's REQ pattern, on identifier
    boundaries, so REQ-FOUND-0011 is not REQ-FOUND-001. An unknown value is the
    descriptor family's error; here it reads as the default."""
    requirement = re.compile(
        r"(?<![0-9A-Za-z_-])(?:%s)(?![0-9A-Za-z_-])" % desc.req_pattern().pattern
    )
    convention = desc.rfc2119_sections
    if convention == "requirement-id":
        return lambda heading: bool(requirement.search(heading))
    if convention == "either":
        return lambda heading: "§" in heading or bool(requirement.search(heading))
    return lambda heading: "§" in heading


#: How the partial-skip line names each convention.
_SECTION_LABEL = {"requirement-id": "requirement-id", "either": "§ or requirement-id"}


def _spec_sections(
    report: "Report", anchor: str, text: str, is_section: Callable[[str], bool]
) -> bool:
    """Warn on every normative section with no keyword. ``False`` when the document has none."""
    found = headings(text)
    marked = [index for index, entry in enumerate(found) if is_section(entry[2])]
    if not marked:
        return False
    total = len(text.split("\n"))
    content = strip_noncontent(text)
    for index in marked:
        start, end = _heading_span(found, index, total)
        carries = any(
            KEYWORD_RE.search(line) for lineno, line in content if start < lineno < end
        )
        if not carries:
            report.add(
                "rfc2119",
                "WARN",
                "%s:%d" % (anchor, found[index][0]),
                "the section '%s' is normative and carries no RFC-2119 keyword" % found[index][2],
            )
    return True


def _specification_rules(
    report: "Report", anchor: str, text: str, is_section: Callable[[str], bool]
) -> bool:
    """The three rules that apply inside a specification. ``False`` when it has no section."""
    has_sections = _spec_sections(report, anchor, text, is_section)
    for lineno, line in strip_noncontent(text):
        malformed = MALFORMED_RE.search(line)
        if malformed:
            report.add(
                "rfc2119",
                "ERROR",
                "%s:%d" % (anchor, lineno),
                "'%s' is a malformed RFC-2119 form" % malformed.group(0),
            )
    for lineno, sentence in sentences(text):
        if KEYWORD_RE.search(sentence):
            continue
        modal = LOWER_MODAL_RE.search(sentence)
        if modal:
            report.add(
                "rfc2119",
                "WARN",
                "%s:%d" % (anchor, lineno),
                "the lower-case modal '%s' binds nothing here; write the keyword in upper case"
                % modal.group(0),
            )
    return has_sections


def check_rfc2119(ctx: Context, report: "Report") -> None:
    """A keyword binds only where the specification says so, and it is well formed."""
    level = ctx.level("rfc2119")
    paths = ctx.docs_files()
    if not paths:
        report.skip("rfc2119", "no markdown documents")
        return
    sectionless: List[str] = []
    examined = 0
    is_section = _section_heading(ctx.desc)
    for path in paths:
        if _waived(ctx, report, "rfc2119", path):
            continue
        # A document that declares no kind is read as the kind its location implies, so
        # the rule still applies to it; `doc-kinds` is the family that reports the
        # missing declaration.
        kind = effective_kind(ctx, path)
        examined += 1
        anchor = ctx.rel(path)
        text = ctx.read(path)
        if kind == "specification":
            if not _specification_rules(report, anchor, text, is_section):
                sectionless.append(anchor)
            continue
        at = "ERROR" if kind in RFC2119_CITING_KINDS else level
        for lineno, line in strip_noncontent(text):
            words = KEYWORD_RE.findall(line)
            if not words:
                continue
            report.add(
                "rfc2119",
                at,
                "%s:%d" % (anchor, lineno),
                "the RFC-2119 keyword %s does not belong in %s %s document; the specification "
                "owns the normative prose"
                % (", ".join(sorted(set(words))), _article(kind), kind),
            )
    if not examined:
        # A family that read no document did not run, whatever the reason. Every
        # document resolves to an effective kind now (its own, or its location's), so
        # the only way here is every document being waived.
        report.skip("rfc2119", "every document is waived")
        return
    if sectionless:
        report.skip(
            "rfc2119",
            "the %s section rule found no section in %s"
            % (_SECTION_LABEL.get(ctx.desc.rfc2119_sections, "§"), ", ".join(sectionless)),
            partial=True,
        )


#: A sentence shorter than this says too little to be a normative statement twice over.
ONE_HOME_MIN_WORDS = 6


#: A line whose content, once notional leading list/quote markers are gone, opens with
#: a blockquote marker.
_BLOCKQUOTE_RE = re.compile(r"^\s*>")


def _blank_blockquotes(text: str) -> str:
    """``text`` with every blockquoted line blanked (line count unchanged, so callers
    that track line numbers still get the right ones).

    A blockquote is a note, not a normative statement, so ``one-home`` must not read one
    as a sentence — quoting a specification's own wording to explain it would otherwise
    read as a second, duplicate home for it. ``rfc2119`` keeps reading blockquotes as
    ordinary prose; only the sentence extraction ``one-home`` uses is filtered.
    """
    return "\n".join("" if _BLOCKQUOTE_RE.match(line) else line for line in text.split("\n"))


def check_one_home(ctx: Context, report: "Report") -> None:
    """A normative sentence lives in one specification section and nowhere else."""
    level = ctx.level("one-home")
    paths = ctx.docs_files()
    if not paths:
        report.skip("one-home", "no markdown documents")
        return
    homes: Dict[str, List[Tuple[str, int]]] = {}
    others: List[Path] = []
    specifications = 0
    for path in paths:
        if _waived(ctx, report, "one-home", path):
            continue
        if effective_kind(ctx, path) != "specification":
            others.append(path)
            continue
        specifications += 1
        anchor = ctx.rel(path)
        for lineno, sentence in keyword_sentences(_blank_blockquotes(ctx.read(path))):
            normalised = normalise_sentence(sentence)
            if len(normalised.split()) < ONE_HOME_MIN_WORDS:
                continue
            homes.setdefault(normalised, []).append((anchor, lineno))
    if not specifications and not others:
        report.skip("one-home", "every document is waived")
        return
    if not specifications:
        report.skip("one-home", "no specification document to own a normative sentence")
        return
    for places in homes.values():
        if len(places) < 2:
            continue
        report.add(
            "one-home",
            level,
            "%s:%d" % places[0],
            "this normative sentence also stands at %s; a normative statement has one home"
            % "; ".join("%s:%d" % place for place in places[1:]),
        )
    for path in others:
        anchor = ctx.rel(path)
        for lineno, sentence in sentences(_blank_blockquotes(ctx.read(path))):
            normalised = normalise_sentence(sentence)
            if len(normalised.split()) < ONE_HOME_MIN_WORDS:
                continue
            places = homes.get(normalised)
            if places:
                report.add(
                    "one-home",
                    level,
                    "%s:%d" % (anchor, lineno),
                    "duplicated normative prose: the specification owns this at %s:%d" % places[0],
                )


#: A target that opens with one of these leaves the repository, so the gate cannot follow it.
LINK_SCHEMES = ("http:", "https:", "mailto:", "ftp:", "tel:", "data:")

_INLINE_TARGET_RE = re.compile(r"""\]\(\s*<?([^)<>\s]*)>?(?:\s+["'][^)]*)?\s*\)""")
_REFERENCE_DEFINITION_RE = re.compile(r"^ {0,3}\[([^\]]+)\]:\s*<?([^\s>]+)>?")


def _link_targets(text: str) -> List[Tuple[int, str]]:
    """Every inline-link target and reference definition, as ``(line, target)``."""
    found: List[Tuple[int, str]] = []
    for lineno, line in strip_noncontent(text):
        definition = _REFERENCE_DEFINITION_RE.match(line)
        if definition:
            found.append((lineno, definition.group(2)))
        for match in _INLINE_TARGET_RE.finditer(line):
            found.append((lineno, match.group(1)))
    return found


def _fragment_targets(text: str) -> set:
    """Every name a ``#fragment`` may resolve to: heading slugs and explicit anchors."""
    return set(entry[3] for entry in headings(text)) | explicit_anchors(text)


def _inside_root(ctx: Context, resolved: Path) -> bool:
    """Whether a resolved target still sits inside the repository the gate is checking."""
    root = os.path.abspath(str(ctx.root))
    try:
        return os.path.commonpath([os.path.abspath(str(resolved)), root]) == root
    except ValueError:
        return False


def _link_files(ctx: Context) -> List[Path]:
    """Every document the links family reads: the docs tree, AGENTS.md and README.md."""
    found = list(ctx.docs_files())
    seen = set(str(path) for path in found)
    for name in ("AGENTS.md", "README.md"):
        path = ctx.root / name
        if path.is_file() and str(path) not in seen:
            seen.add(str(path))
            found.append(path)
    return found


def check_links(ctx: Context, report: "Report") -> None:
    """Every link that stays inside the repository resolves onto a file and a fragment."""
    desc = ctx.desc
    level = ctx.level("links")
    paths = _link_files(ctx)
    if not paths:
        report.skip("links", "no markdown documents")
        return
    kept = [
        path
        for path in paths
        if not any(fnmatch.fnmatch(ctx.rel(path), glob) for glob in desc.links_exclude)
    ]
    if not kept:
        report.skip("links", "every document is excluded by check.links.exclude")
        return
    examined = 0
    for path in kept:
        if _waived(ctx, report, "links", path):
            continue
        examined += 1
        anchor = ctx.rel(path)
        for lineno, raw in _link_targets(ctx.read(path)):
            target = raw.strip()
            if not target:
                continue
            lowered = target.lower()
            if lowered.startswith("//") or any(lowered.startswith(s) for s in LINK_SCHEMES):
                continue
            where = "%s:%d" % (anchor, lineno)
            if target.startswith("/"):
                report.add(
                    "links",
                    level,
                    where,
                    "a host-absolute target resolves to nothing in a repository: %s" % target,
                )
                continue
            rel_part, _, fragment = target.partition("#")
            rel_part = urllib.parse.unquote(rel_part)
            fragment = urllib.parse.unquote(fragment)
            if rel_part:
                resolved = Path(os.path.normpath(str(path.parent / rel_part)))
                if not _inside_root(ctx, resolved):
                    report.add(
                        "links",
                        level,
                        where,
                        "the target resolves outside the repository: %s" % rel_part,
                    )
                    continue
                if not resolved.exists():
                    report.add("links", level, where, "no such file: %s" % rel_part)
                    continue
                if ctx.ignored(resolved):
                    report.add(
                        "links",
                        level,
                        where,
                        "the target is git-ignored, so no clean checkout has it: %s" % rel_part,
                    )
                    continue
                if (resolved.suffix == ".md" and doc_kind(ctx, resolved) == "plan"
                        and effective_kind(ctx, path) in NORMATIVE_KINDS + ("constitution",)):
                    report.add("links", level, where, "a durable document cites a plan, which is temporary: %s" % rel_part)
                    continue
                if not fragment or resolved.suffix != ".md":
                    continue
                text = ctx.read(resolved)
            else:
                if not fragment:
                    continue
                text = ctx.read(path)
            if fragment not in _fragment_targets(text):
                report.add(
                    "links",
                    level,
                    where,
                    "the fragment '#%s' matches no heading slug and no explicit anchor in %s"
                    % (fragment, rel_part or path.name),
                )
    if not examined:
        report.skip("links", "every document is waived")


#: A bullet that argues its case belongs in the commit body, not in the changelog.
RATIONALE_CONNECTORS = (" because ", " so that ", " in order to ")
#: More backticked names than this is an inventory, not a change.
CHANGELOG_MAX_TOKENS = 4

_BULLET_RE = re.compile(r"^- +(.*)$")
#: A nested bullet is a bullet of its own, so it ends the top-level one and is not linted.
_NESTED_BULLET_RE = re.compile(r"^\s+[-*+]\s")
_SECOND_SENTENCE_RE = re.compile(r"[.!?] +[A-Z]")
_CODE_TOKEN_RE = re.compile(r"`[^`]+`")


def _changelog_bullets(lines: List[Tuple[int, str]]) -> List[Tuple[int, str]]:
    """Every top-level bullet in a slice of a changelog, its continuation lines joined.

    A nested bullet is a bullet in its own right, so it closes the one above it and is not
    folded into it; the house rule is one flat line per bullet.
    """
    found: List[Tuple[int, str]] = []
    open_at = 0
    parts: List[str] = []

    def flush():
        if parts:
            found.append((open_at, " ".join(parts).strip()))
        del parts[:]

    for lineno, line in lines:
        stripped = line.strip()
        bullet = _BULLET_RE.match(line)
        if bullet:
            flush()
            open_at = lineno
            parts.append(bullet.group(1).strip())
            continue
        if not stripped or _HEADING_RE.match(stripped) or _NESTED_BULLET_RE.match(line):
            flush()
            continue
        if parts:
            parts.append(stripped)
    flush()
    return found


def check_changelog(ctx: Context, report: "Report") -> None:
    """Every changelog bullet is one short sentence that states the change and stops."""
    desc = ctx.desc
    level = ctx.level("changelog")
    rel = desc.changelog_path
    path = desc.resolve(rel)
    if not path.is_file():
        report.add("changelog", "ERROR", rel, "check.changelog.path names no file: %s" % rel)
        return
    if _waived(ctx, report, "changelog", path):
        report.skip("changelog", "the changelog is waived")
        return
    text = ctx.read(path)
    found = headings(text)
    total = len(text.split("\n"))
    content = _content_lines(text, False)
    sections = [
        (index, entry) for index, entry in enumerate(found) if entry[2].strip().startswith("[")
    ]
    unreleased = [
        pair for pair in sections if pair[1][2].strip().lower().startswith("[unreleased]")
    ]
    if not unreleased:
        report.add("changelog", "NOTE", rel, "no Unreleased section to lint")
    chosen = sections if ctx.changelog_all else unreleased
    bullets: List[Tuple[int, str]] = []
    for index, _entry in chosen:
        start, end = _heading_span(found, index, total)
        bullets.extend(
            _changelog_bullets([pair for pair in content if start < pair[0] < end])
        )
    if not bullets:
        report.skip("changelog", "no bullet to lint in the section", partial=True)
        return
    for lineno, bullet in bullets:
        where = "%s:%d" % (rel, lineno)
        words = len(bullet.split())
        if words > desc.changelog_max_words:
            report.add(
                "changelog",
                level,
                where,
                "the bullet runs to %d words; the budget is %d words"
                % (words, desc.changelog_max_words),
            )
        if _SECOND_SENTENCE_RE.search(bullet):
            report.add("changelog", level, where, "a bullet is one sentence, and this is two")
        for connector in RATIONALE_CONNECTORS:
            if connector in bullet:
                report.add(
                    "changelog",
                    level,
                    where,
                    "'%s' is rationale; the bullet states the change and stops"
                    % connector.strip(),
                )
                break
        tokens = _CODE_TOKEN_RE.findall(bullet)
        if len(tokens) > CHANGELOG_MAX_TOKENS:
            report.add(
                "changelog",
                level,
                where,
                "%d backticked names make this an inventory; the budget is %d"
                % (len(tokens), CHANGELOG_MAX_TOKENS),
            )


def check_generated(ctx: Context, report: "Report") -> None:
    """Every generated block in the tree equals what ``generate`` would write.

    A hand edit inside the markers, an opening marker with no closing marker, and a
    block name outside :data:`GENERATED_BLOCKS` are each an error; a repository with no
    generated blocks anywhere has nothing for this family to check.
    """
    level = ctx.level("generated")
    found_any = False
    for path in _link_files(ctx):
        text = ctx.read(path)
        blocks = generated_blocks(text)
        if not blocks:
            if _MARKER_TOKEN in text:
                # No block, but a stray closer or a near-miss opener is still a finding.
                for lineno, message in generated_marker_problems(text):
                    found_any = True
                    report.add("generated", level, "%s:%d" % (ctx.rel(path), lineno), message)
            continue
        found_any = True
        anchor = ctx.rel(path)
        lines = text.split("\n")
        for lineno, message in generated_marker_problems(text):
            report.add("generated", level, "%s:%d" % (anchor, lineno), message)
        for name, start, end in blocks:
            if end == 0:
                report.add(
                    "generated", level, "%s:%d" % (anchor, start), "unclosed generated block '%s'" % name
                )
                continue
            if name not in GENERATED_BLOCKS:
                report.add(
                    "generated", level, "%s:%d" % (anchor, start), "unknown block '%s'" % name
                )
                continue
            expected = _expected_block(ctx, name, path)
            actual = lines[start:end - 1]
            if actual != expected:
                report.add(
                    "generated",
                    level,
                    "%s:%d" % (anchor, start),
                    "hand-edited or stale generated block — run sdd-check generate",
                )
    if not found_any:
        report.skip("generated", "no generated blocks")


def check_draft_reason(ctx: Context, report: "Report") -> None:
    """A requirement that is draft and built owes a reason for the wording."""
    level = ctx.level("draft-reason")
    for record in ctx.records:
        if record.status == "draft" and record.enforced() and not record.draft_reason:
            report.add(
                "draft-reason",
                level,
                record.id or ctx.desc.traceability,
                "%s is draft and enforced, so it owes a draft_reason" % record.id,
            )


# --- family registry (later families are inserted above this banner) -------

CHECKS = {
    "descriptor": check_descriptor,
    "map-schema": check_map_schema,
    "map-to-tree": check_map_to_tree,
    "index-sync": check_index_sync,
    "tree-to-map": check_tree_to_map,
    "doc-kinds": check_doc_kinds,
    "rfc2119": check_rfc2119,
    "one-home": check_one_home,
    "links": check_links,
    "changelog": check_changelog,
    "generated": check_generated,
    "draft-reason": check_draft_reason,
}


# ---------------------------------------------------------------------------
# Report
# ---------------------------------------------------------------------------


@dataclasses.dataclass
class Finding:
    """One reported defect. ``level`` is ERROR, WARN or NOTE; a NOTE never fails a run."""

    family: str
    level: str
    anchor: str
    message: str
    #: On a line changed since the ref given to ``check --changed-since``.
    new: bool = False


class Report:
    """Everything a run has to say, and the exit code it earns."""

    def __init__(self):
        self.findings: List[Finding] = []
        self.families_run: List[str] = []
        self.families_skipped: Dict[str, str] = {}
        #: The families that ran nothing at all, as opposed to one skipped rule.
        self.fully_skipped = set()
        #: Waived file per family, filled by the prose lints a later build adds.
        self.waived: Dict[str, List[str]] = {}
        self.link_exclusions: List[str] = []
        self.record_count = 0
        self.profile = "formal"
        self.fatal_message: Optional[str] = None
        #: The ref of ``--changed-since``, and whether only the findings new since it are printed.
        self.since: Optional[str] = None
        self.new_only = False
        self.plans_skipped = 0

    @classmethod
    def fatal(cls, message: str) -> "Report":
        """A run that could not configure itself: nothing ran, the exit code is 2."""
        report = cls()
        report.fatal_message = message
        return report

    def add(self, family: str, level: str, anchor: str, message: str) -> None:
        self.findings.append(Finding(family, level, anchor, message))

    def skip(self, family: str, reason: str, partial: bool = False) -> None:
        """Record that a family did not run, or — with ``partial`` — that one rule did not."""
        self.families_skipped[family] = reason
        if not partial:
            self.fully_skipped.add(family)

    def mark_run(self, family: str) -> None:
        """Record that a family ran, keeping the list in FAMILIES order."""
        self.families_skipped.pop(family, None)
        self.fully_skipped.discard(family)
        if family not in self.families_run:
            self.families_run.append(family)
            self.families_run.sort(key=FAMILIES.index)

    def errors(self) -> int:
        return len([f for f in self.findings if f.level == "ERROR"])

    def warnings(self) -> int:
        return len([f for f in self.findings if f.level == "WARN"])

    def exit_code(self) -> int:
        if self.fatal_message is not None:
            return 2
        if self.errors():
            return 1
        # A run in which no family ran verified nothing — every family off, or --only naming
        # an off family. That is a configuration failure, not a clean pass.
        if not self.families_run:
            return 2
        return 0

    def _ordered(self) -> List[Finding]:
        order = {name: index for index, name in enumerate(FAMILIES)}
        return sorted(self.findings, key=lambda f: order.get(f.family, len(FAMILIES)))

    def render(self, root) -> str:
        if self.fatal_message is not None:
            # Exit 2: nothing ran, so there are no finding lines and no family lines.
            return "\n".join(
                [
                    "sdd-check %s · %s" % (__version__, root),
                    "sdd-check: FAILED — %s" % self.fatal_message,
                ]
            )
        lines = [
            "sdd-check %s · %s · profile %s · %d REQ records"
            % (__version__, root, self.profile, self.record_count)
        ]
        for finding in self._ordered():
            if self.new_only and not finding.new:
                continue
            lines.append(
                "[%s] %s%s %s: %s"
                % (finding.family, finding.level, " NEW" if finding.new else "", finding.anchor, finding.message)
            )
        errors, warnings = self.errors(), self.warnings()
        since = ""
        if self.since is not None:
            fresh = len([f for f in self.findings if f.new and f.level in ("ERROR", "WARN")])
            since = " (%d new since %s)" % (fresh, self.since)
        if not self.families_run:
            # Exit 2: nothing was verified, so the summary must never read as a pass.
            reasons: List[str] = []
            for name in FAMILIES:
                reason = self.families_skipped.get(name)
                if reason and reason not in reasons:
                    reasons.append(reason)
            lines.append(
                "sdd-check: FAILED — no family ran (%s)" % ("; ".join(reasons) or "none planned")
            )
        elif errors:
            lines.append("sdd-check: FAILED — %d errors, %d warnings%s" % (errors, warnings, since))
        else:
            lines.append(
                "sdd-check: OK — %d checks, 0 errors, %d warnings%s"
                % (len(self.families_run), warnings, since)
            )
        skipped = [
            "%s (%s)" % (name, self.families_skipped[name])
            for name in FAMILIES
            if name in self.families_skipped
        ]
        lines.append(
            "families run: %s; skipped: %s"
            % (", ".join(self.families_run) or "none", "; ".join(skipped) or "none")
        )
        for family in FAMILIES:
            waived = self.waived.get(family)
            if waived:
                lines.append("waived: %s (%d files)" % (family, len(waived)))
        if self.plans_skipped:
            lines.append("plans: %d left alone (kind: plan)" % self.plans_skipped)
        if self.link_exclusions:
            lines.append("link exclusions: %s" % ", ".join(self.link_exclusions))
        return "\n".join(lines)


def _load_descriptor(root: Path) -> Tuple[Optional[Descriptor], Optional[str]]:
    """``(descriptor, None)``, or ``(None, message)`` in the words ``check`` itself uses."""
    try:
        return Descriptor.load(root), None
    except FileNotFoundError:
        return None, "%s: not found — run /sdd-scaffold to create it" % DESCRIPTOR_REL
    except YamlError as exc:
        return None, "%s: %s" % (exc.where(), exc.message)
    except OSError as exc:
        return None, "%s: cannot be read (%s)" % (DESCRIPTOR_REL, exc)


def _load_records_or_report(desc: Descriptor, report: "Report") -> Tuple[List[Record], bool]:
    """Load the traceability map, reporting exactly the ``map-schema`` finding ``check``
    reports on the same failure. Returns ``(records, map_ok)``.

    On the informative profile the map is optional: an absent or empty one is no records, not
    a failure. A map that is there and broken is still reported.
    """
    informative = desc.profile == "informative"
    if informative and (not desc.traceability or not desc.resolve(desc.traceability).is_file()):
        return [], True
    try:
        records = load_map(desc)
    except FileNotFoundError:
        report.add("map-schema", "ERROR", desc.traceability, "the traceability map is missing")
        return [], False
    except YamlError as exc:
        report.add(
            "map-schema",
            "ERROR",
            exc.where(),
            "the traceability map does not parse: %s" % exc.message,
        )
        return [], False
    except OSError as exc:
        report.add(
            "map-schema", "ERROR", desc.traceability, "the traceability map cannot be read (%s)" % exc
        )
        return [], False
    if not records:
        if informative:
            return [], True
        report.add(
            "map-schema", "ERROR", desc.traceability, "the traceability map yields zero records"
        )
        return [], False
    return records, True


def _baseline(root: Path, ref: str, only: Optional[List[str]], changelog_all: bool) -> Optional[List[Finding]]:
    """What the gate reported where HEAD left ``ref``, their merge base: the same run on an export of
    that commit. ``None`` when git cannot find ``ref`` or the merge base here."""

    def run(*args, where: Path = root) -> Optional[bytes]:
        try:
            proc = subprocess.run(["git", "-C", str(where)] + list(args), stdout=subprocess.PIPE,
                                  stderr=subprocess.DEVNULL, check=False)
        except (OSError, ValueError):
            return None
        return proc.stdout if proc.returncode == 0 else None

    def text(raw: Optional[bytes]) -> str:
        return (raw or b"").decode("utf-8", "replace").strip()

    base, top = text(run("merge-base", ref, "HEAD")), text(run("rev-parse", "--show-toplevel"))
    # Exported from the top of the repository: from a subdirectory, git archives that subdirectory alone.
    archive = run("archive", "--format=tar", base, where=Path(top)) if base and top else None
    if not archive:
        return None
    prefix = text(run("rev-parse", "--show-prefix"))
    with tempfile.TemporaryDirectory() as tmp:
        with tarfile.open(fileobj=io.BytesIO(archive)) as tar:
            if hasattr(tarfile, "data_filter"):
                tar.extractall(tmp, filter="data")
            else:  # Python before 3.12; the archive is git's own export of this repository
                tar.extractall(tmp)
        return run_check(Path(tmp) / prefix, only, changelog_all).findings


def _same_finding(finding: Finding) -> Tuple[str, str, str, str]:
    """A finding without its line numbers, so one that only moved is the same finding."""
    anchor, message = (re.sub(r":\d+", "", text) for text in (finding.anchor, finding.message))
    return finding.family, finding.level, anchor, message


def mark_new(report: "Report", root: Path, ref: str, only: Optional[List[str]], changelog_all: bool) -> bool:
    """Mark each finding the gate did not report at the merge base of ``ref`` and HEAD: what the change
    added, wherever it shows (a changed line, a whole file, a record, a link a deletion broke). False when
    the merge base cannot be read, which is a configuration failure."""
    base = _baseline(root, ref, only, changelog_all)
    if base is None:
        return False
    before = collections.Counter(_same_finding(f) for f in base)
    for finding in report.findings:
        key = _same_finding(finding)
        if before[key]:
            before[key] -= 1
        else:
            finding.new = True
    report.since = ref
    return True


def run_check(root: Path, only: Optional[List[str]], changelog_all: bool) -> Report:
    """Run the enabled families against a repository and return the report."""
    root = Path(root)
    if only is not None:
        unknown = [name for name in only if name not in FAMILIES]
        if unknown:
            return Report.fatal("unknown family '%s' in --only" % unknown[0])
    desc, message = _load_descriptor(root)
    if message:
        return Report.fatal(message)

    report = Report()
    report.profile = desc.profile
    report.link_exclusions = list(desc.links_exclude)

    planned: List[str] = []
    for family in FAMILIES:
        if desc.profile == "informative" and family in INFORMATIVE_SKIPPED:
            report.skip(family, "profile informative")
        elif desc.severity(family) == "off":
            report.skip(family, "off")
        elif only is not None and family not in only:
            report.skip(family, "not selected")
        elif family not in CHECKS:
            report.skip(family, "not implemented in this build")
        else:
            planned.append(family)

    records, map_ok = _load_records_or_report(desc, report)
    report.record_count = len(records)

    ctx = Context(root, desc, records, changelog_all)
    for family in planned:
        if not map_ok and family in RECORD_FAMILIES:
            report.skip(family, "map unavailable")
            continue
        CHECKS[family](ctx, report)
        if family not in report.fully_skipped:
            report.families_run.append(family)
    if not map_ok:
        # The map failure is a map-schema finding whatever the configuration says, so the
        # summary has to show map-schema as a family that ran.
        report.mark_run("map-schema")
    report.plans_skipped = len(ctx.skipped_plans)
    return report


# ---------------------------------------------------------------------------
# Generators — the derived indexes and the requirement detail-file status lines
# ---------------------------------------------------------------------------


def _quiet_frontmatter(text: str) -> dict:
    """The frontmatter mapping, or ``{}`` when it is absent or does not parse.

    The generators read many documents besides the ones their own family owns; a parse
    failure there is someone else's finding, not a reason for ``generate`` to crash.
    """
    try:
        front, _ = frontmatter(text)
    except YamlError:
        return {}
    return front if isinstance(front, dict) else {}


def _relative_link(from_file: Path, to_file: Path, fragment: str = "") -> str:
    """The target a link in ``from_file`` needs to reach ``to_file`` (optionally ``#fragment``)."""
    rel = Path(os.path.relpath(str(to_file), start=str(Path(from_file).parent))).as_posix()
    return "%s#%s" % (rel, fragment) if fragment else rel


def _requirement_detail_file(ctx: Context, record_id: str) -> Optional[Path]:
    """The requirement detail file a record's id names, or ``None`` when there is none."""
    requirements_dir = ctx.desc.requirements_dir()
    if requirements_dir is None or not requirements_dir.is_dir():
        return None
    matches = _detail_files(requirements_dir, record_id)
    return matches[0] if matches else None


def _heading_for_fragment(text: str, fragment: str) -> Optional[str]:
    """The text of the heading a canonical ``#fragment`` names, however it is anchored."""
    for _, _, title, slug in headings(text):
        if slug == fragment:
            return title
    span = _anchor_span(text, fragment)
    if span:
        start, end = span
        for lineno, _, title, _ in headings(text):
            if start <= lineno < end:
                return title
    return None


def _spec_cell(ctx: Context, index_path: Path, record: Record) -> str:
    """The requirements-index Spec cell for one record: a code-styled link to its section."""
    path_part, _, fragment = record.canonical.partition("#")
    if not path_part:
        return ""
    target = ctx.desc.resolve(path_part)
    if not target.is_file():
        return ""
    text = ctx.read(target)
    front = _quiet_frontmatter(text)
    spec_name = _as_str(front.get("spec")) or target.stem.upper()
    heading_text = _heading_for_fragment(text, fragment) if fragment else None
    if heading_text:
        match = re.search(r"§\S+", heading_text)
        label = "%s %s" % (spec_name, match.group(0) if match else heading_text)
    else:
        label = spec_name
    return "[`%s`](%s)" % (label, _relative_link(index_path, target, fragment))


def render_requirements_index(ctx: Context, home: Optional[Path] = None) -> str:
    """The ``requirements-index`` generated table: one row per record, in map order.

    ``home`` is the file the block will sit in; links are relative to it. It defaults to
    the canonical requirements-index path, so a direct call (a test, or a block that
    really does sit at the canonical location) needs nothing extra.
    """
    home = home if home is not None else ctx.desc.requirements_index_path()
    lines = ["| ID | Title | Spec | Stability | Implementation |", "|---|---|---|---|---|"]
    for record in ctx.records:
        if not record.id:
            continue
        detail = _requirement_detail_file(ctx, record.id)
        if detail is not None:
            id_cell = "[%s](%s)" % (record.id, _relative_link(home, detail))
        else:
            id_cell = record.id
        lines.append(
            "| %s | %s | %s | %s | %s |"
            % (
                id_cell,
                record.title,
                _spec_cell(ctx, home, record),
                record.status.capitalize(),
                record.implementation,
            )
        )
    return "\n".join(lines)


def _listed_specifications(ctx: Context) -> List[Tuple[Path, str]]:
    """``(path, spec name)`` for every document the specifications index lists."""
    listed = []
    for path in ctx.desc.specification_files():
        if effective_kind(ctx, path) != "specification":
            continue
        front = _quiet_frontmatter(ctx.read(path))
        listed.append((path, _as_str(front.get("spec")) or path.stem.upper()))
    return listed


def render_specifications_index(ctx: Context, home: Optional[Path] = None) -> str:
    """The ``specifications-index`` generated table: one row per specification document.

    ``home`` is the file the block will sit in; links are relative to it (see
    :func:`render_requirements_index`).
    """
    desc = ctx.desc
    home = home if home is not None else desc.specifications_index_path()
    lines = ["| Spec | Topic | Status | Mode |", "|---|---|---|---|"]
    for path, spec_name in _listed_specifications(ctx):
        front = _quiet_frontmatter(ctx.read(path))
        title = ""
        for _, level, text, _ in headings(ctx.read(path)):
            if level == 1:
                title = text
                break
        topic = title.split(" — ", 1)[1] if " — " in title else title
        status = _as_str(front.get("status")).capitalize()
        mode = _as_str(front.get("mode")) or desc.default_mode
        lines.append(
            "| [`%s`](%s) | %s | %s | %s |"
            % (spec_name, _relative_link(home, path), topic, status, mode)
        )
    return "\n".join(lines)


_ADR_NAME_RE = re.compile(r"^(ADR-|\d{4}-)")
# The label may sit inside Markdown emphasis, colon inside or outside it: `- Amends: X`,
# `- **Amends:** X` and `- **Amends**: X` all render alike. The empty alternative lets the
# closing (?P=em) match nothing when there is no emphasis. The value may start on the next
# line, so it may be empty here.
_TRACE_REF_RE = re.compile(
    r"^\s*-\s*(?P<em>\*\*|__|\*|_|)(?P<label>Resolves|Amends)"
    r"(?:\s*:\s*(?P=em)|(?P=em)\s*:)\s*(?P<value>.*?)\s*$",
    re.IGNORECASE,
)
# What ends a list item's first paragraph: a blank line, the next item (nested or not),
# a heading or a code fence. Any other line continues it, indented or lazy.
_ITEM_END_RE = re.compile(r"^\s*$|^\s*(?:[-*+]|\d+[.)])\s|^\s{0,3}(?:#|```|~~~)")


def _traceability_refs(text: str) -> str:
    """The ``Resolves:`` / ``Amends:`` values an ADR body's Traceability list carries."""
    span = section_slice(text, "traceability")
    if span is None:
        return "—"
    start, end = span
    lines = text.split("\n")
    last = min(end, len(lines) + 1)
    refs = []
    for lineno in range(start, last):
        match = _TRACE_REF_RE.match(lines[lineno - 1])
        if not match:
            continue
        # A wrapped bullet keeps its continuation lines: the value is the item's whole
        # first paragraph, joined with single spaces.
        parts = [match.group("value")] if match.group("value") else []
        for follow in range(lineno + 1, last):
            line = lines[follow - 1]
            if _ITEM_END_RE.match(line):
                break
            parts.append(line.strip())
        if parts:
            refs.append("%s: %s" % (match.group("label").capitalize(), " ".join(parts)))
    return "; ".join(refs) if refs else "—"


def _listed_adrs(ctx: Context) -> List[Path]:
    """Every decision record the ADR index lists, in path order."""
    desc = ctx.desc
    adr_dir = desc.resolve(desc.paths.get("adr", DEFAULT_PATHS["adr"]))
    if not adr_dir.is_dir():
        return []
    return [
        path
        for path in sorted(adr_dir.glob("*.md"))
        if path.is_file() and _ADR_NAME_RE.match(path.name)
    ]


def render_adr_index(ctx: Context, home: Optional[Path] = None) -> str:
    """The ``adr-index`` generated table: one row per ADR document, in path order.

    ``home`` is accepted for the same reason the other two renderers take it (see
    :func:`render_requirements_index`); the table carries no links today, but a future
    one is written relative to it rather than to the canonical path.
    """
    desc = ctx.desc
    home = home if home is not None else desc.adr_index_path()
    lines = ["| ID | Title | Status | Date | Resolves / amends |", "|---|---|---|---|---|"]
    for path in _listed_adrs(ctx):
        # Absent or unparseable frontmatter is still a decision record: a row with
        # `—` placeholders, never a silently omitted line.
        front = _quiet_frontmatter(ctx.read(path))

        def cell(key, capitalize=False):
            value = _as_str(front.get(key))
            if not value:
                return "—"
            return value.capitalize() if capitalize else value

        lines.append(
            "| %s | %s | %s | %s | %s |"
            % (
                cell("id"),
                cell("title"),
                cell("status", capitalize=True),
                cell("date"),
                _traceability_refs(ctx.read(path)),
            )
        )
    return "\n".join(lines)


#: One renderer per known block name, each ``(ctx, home) -> str``.
_RENDERERS = {
    "requirements-index": render_requirements_index,
    "specifications-index": render_specifications_index,
    "adr-index": render_adr_index,
}


def _expected_block(ctx: Context, name: str, home: Path) -> List[str]:
    """The lines a generated block holds when current: one blank line, the table, one
    blank line — the padding convention ``generate`` writes and ``check_generated``
    verifies, in one place so the two can never drift apart."""
    return [""] + _RENDERERS[name](ctx, home).split("\n") + [""]


#: Derived from GENERATED_OPEN itself, so the marker text has one source, not two.
_GENERATED_OPEN_PREFIX, _GENERATED_OPEN_SUFFIX = GENERATED_OPEN.split("%s")
_GENERATED_OPEN_RE = re.compile(
    "^%s(\\S+)%s$" % (re.escape(_GENERATED_OPEN_PREFIX), re.escape(_GENERATED_OPEN_SUFFIX))
)


#: The token every generated-block marker carries; a line with it that is not an exact
#: marker is a near miss, never silently ignored.
_MARKER_TOKEN = "sdd:generated"


def _marker_view(text: str) -> List[str]:
    """The lines the marker scan reads: fences blanked (a quoted example is not a live
    block), inline code blanked (prose that names a marker in backticks is not one), and
    HTML comments kept, since the markers are comments. Line numbers are preserved."""
    return [line for _lineno, line in _content_lines(text, True, keep_comments=True)]


def _marker_kind(line: str) -> Tuple[str, str]:
    """``("open", name)``, ``("close", "")``, ``("near", "")`` or ``("", "")``.

    A line indented four or more columns is an indented code block, not a marker, and is
    never read as one — nor reported as a near miss.
    """
    expanded = line.expandtabs(4)
    if len(expanded) - len(expanded.lstrip(" ")) >= 4:
        return "", ""
    stripped = line.strip()
    match = _GENERATED_OPEN_RE.match(stripped)
    if match:
        return "open", match.group(1)
    if stripped == GENERATED_CLOSE:
        return "close", ""
    if _MARKER_TOKEN in stripped:
        return "near", ""
    return "", ""


def generated_marker_problems(text: str) -> List[Tuple[int, str]]:
    """Every malformed marker line as ``(line, message)``: a line naming ``sdd:generated``
    that is neither an exact opening nor an exact closing marker, and a closing marker no
    opening marker precedes. (An opener with no closer is reported by
    :func:`generated_blocks` itself, with a closing line of ``0``.)"""
    problems: List[Tuple[int, str]] = []
    inside = False
    for index, line in enumerate(_marker_view(text)):
        kind, _name = _marker_kind(line)
        if kind == "open":
            inside = True
        elif kind == "close":
            if inside:
                inside = False
            else:
                problems.append((index + 1, "a closing generated marker with no opening marker"))
        elif kind == "near":
            problems.append(
                (
                    index + 1,
                    "malformed generated marker — the line names %s but is neither %s nor %s"
                    % (_MARKER_TOKEN, GENERATED_OPEN % "<name>", GENERATED_CLOSE),
                )
            )
    return problems


def generated_blocks(text: str) -> List[Tuple[str, int, int]]:
    """Every generated block as ``(name, opening marker line, closing marker line)``.

    A block whose opening marker has no matching closing marker is reported with a
    closing line of ``0``, so a caller can tell "malformed" from "well formed but stale"
    without a second return shape. The closing-marker scan stops the moment another
    opening marker is encountered, so an opener is unclosed the instant no closer
    appears before either the next opener or end of file — it can never annex a later
    block's closer. The scan resumes right there, so a well-formed block that follows
    an unclosed one is still found and generated normally.
    """
    # Match markers on a fence-blanked view (see _marker_view), so a marker pair quoted
    # inside a fenced example is not mistaken for a live block. Line numbers are preserved,
    # so the indices still address the raw text every caller slices.
    lines = _marker_view(text)
    found: List[Tuple[str, int, int]] = []
    index = 0
    while index < len(lines):
        kind, name = _marker_kind(lines[index])
        if kind != "open":
            index += 1
            continue
        start = index + 1
        end = 0
        probe = index + 1
        while probe < len(lines):
            probe_kind, _ = _marker_kind(lines[probe])
            if probe_kind == "close":
                end = probe + 1
                break
            if probe_kind == "open":
                break
            probe += 1
        found.append((name, start, end))
        index = end if end else probe
    return found


_STATUS_LINE_RE = "^(%s\\s*:\\s*)(\\S+)(\\s*(?:#.*)?)$"


def _rewrite_status_lines(text: str, record: Record) -> str:
    """Rewrite a requirement detail file's ``status:`` and ``implementation:`` values in
    place, keeping an inline ``# comment`` and every other byte untouched."""
    span = _frontmatter_span(text)
    if span is None:
        return text
    open_line, after = span
    lines = text.split("\n")
    values = {"status": record.status, "implementation": record.implementation}
    for index in range(open_line, after - 2):
        for key, value in values.items():
            match = re.match(_STATUS_LINE_RE % re.escape(key), lines[index])
            if match:
                lines[index] = "%s%s%s" % (match.group(1), value, match.group(3))
                break
    return "\n".join(lines)


def _diff(anchor: str, before: List[str], after: List[str]) -> List[str]:
    return list(
        difflib.unified_diff(
            before, after, fromfile="%s (current)" % anchor, tofile="%s (generated)" % anchor, lineterm=""
        )
    )


#: A markdown inline link, captured as ``(text, target)``; the target may be ``<…>``-wrapped
#: and may carry a title.
_CELL_LINK_RE = re.compile(r"!?\[([^\]]*)\]\(\s*<?([^)<>\s]*)>?(?:\s+[^)]*)?\)")
_ADR_ID_RE = re.compile(r"ADR-\d+")
#: A 0.5.x template placeholder: an id cell that is exactly one ``<…>`` token.
_PLACEHOLDER_RE = re.compile(r"<[^<>]+>")


def _row_identity(name: str, desc: "Descriptor", cells: List[str]) -> Tuple[str, set]:
    """``(display key, identity tokens)`` for one index row — what the row refers to, not
    how its first cell happens to be formatted.

    Link markup, backticks and asterisks are ignored. A requirements row is the REQ id
    found in its first cell — only the first: a hand-written row such as
    ``| Legacy login | superseded by REQ-X |`` names no requirement of its own, and
    reading a later cell would let regeneration delete it as if it were that record's
    row. A row whose first cell names no id is keyed on its text. An ADR row is the ``ADR-N`` id in its first cell. A specifications row —
    and an ADR row whose cell names no ``ADR-N`` id — is its cell text or its link
    target's file stem, compared without case.
    """
    if not cells:
        return "", set()
    stems = set()

    def keep_text(match):
        target = urllib.parse.unquote(match.group(2).split("#", 1)[0])
        if target:
            stems.add(Path(target).stem.lower())
        return match.group(1)

    text = _cell_text(_CELL_LINK_RE.sub(keep_text, cells[0]))
    if name == "requirements-index":
        match = desc.req_pattern().search(text)
        if match:
            return match.group(0), {match.group(0)}
        return text, {text}
    if name == "adr-index":
        match = _ADR_ID_RE.search(text)
        if match:
            return match.group(0), {match.group(0).lower()}
    tokens = {text.lower()} | stems if text else set(stems)
    return text, tokens


def _document_tokens(ctx: "Context", name: str) -> set:
    """The identity tokens of every document a specifications or ADR block lists — the
    names and file stems a hand-written row may use to refer to a document that exists."""
    tokens = set()
    if name == "specifications-index":
        for path, spec_name in _listed_specifications(ctx):
            tokens.update({spec_name.lower(), path.stem.lower(), path.name.lower()})
    elif name == "adr-index":
        for path in _listed_adrs(ctx):
            tokens.update({path.stem.lower(), path.name.lower()})
            match = _ADR_ID_RE.search(path.stem)
            if match:
                tokens.add(match.group(0).lower())
            identifier = _as_str(_quiet_frontmatter(ctx.read(path)).get("id"))
            if identifier:
                tokens.add(identifier.lower())
    return tokens


def _block_layout(content: List[str]):
    """Split a block's lines into its pipe tables and every other non-blank line.

    Returns ``(tables, stray)``: each table is ``(header offset, header cells, [(row
    offset, row cells)])``, and ``stray`` is the offset of every non-blank line that is not
    part of a table — a note, a blockquote, a bullet, a heading, a comment. Offsets index
    ``content``. The scan reads the raw lines, not a comment-blanked view, so an HTML
    comment inside the block is stray content too.
    """
    tables = []
    stray: List[int] = []
    index = 0
    total = len(content)
    while index < total:
        line = content[index]
        if not line.strip():
            index += 1
            continue
        if "|" in line and index + 1 < total and _is_separator(content[index + 1]):
            header = _split_cells(line)
            rows = []
            probe = index + 2
            while probe < total and "|" in content[probe] and content[probe].strip():
                rows.append((probe, _split_cells(content[probe])))
                probe += 1
            tables.append((index, header, rows))
            index = probe
            continue
        stray.append(index)
        index += 1
    return tables, stray


def _is_placeholder_cell(cell: str) -> bool:
    return bool(_PLACEHOLDER_RE.fullmatch(_cell_text(cell)))


def _block_refusals(
    name: str,
    desc: "Descriptor",
    current: List[str],
    new: List[str],
    documents: Optional[set] = None,
) -> List[Tuple[int, str]]:
    """Everything regenerating one block would destroy, as ``(offset in current, reason)``.

    Refused: a non-blank line that is not part of a table; a column the current header
    carries and the generated header does not; a row whose id cell carries ``<``/``>``
    markup other than a whole-cell placeholder; and a row that refers to no record (the
    requirements index) or no document (the other two). A row that refers to something
    present is regenerated, however its cells are formatted. A placeholder row — an id
    cell that is exactly one ``<…>`` token, as 0.5.x READMEs carried — is not protected.
    ``documents`` adds the identity tokens of the documents a specifications or ADR block
    lists (see :func:`_document_tokens`).
    """
    refusals: List[Tuple[int, str]] = []
    tables, stray = _block_layout(current)
    new_tables, _ = _block_layout(new)
    for offset in stray:
        refusals.append(
            (
                offset,
                "this line is not part of the table and regeneration would remove it — "
                "move it outside the markers",
            )
        )
    generated_columns = set()
    known = set(documents or ())
    for _offset, header, rows in new_tables:
        generated_columns.update(_cell_text(cell).lower() for cell in header)
        for _row_offset, cells in rows:
            known.update(_row_identity(name, desc, cells)[1])
    missing = "no record in the map" if name == "requirements-index" else "no matching document"
    for header_offset, header, rows in tables:
        for cell in header:
            column = _cell_text(cell)
            if column and column.lower() not in generated_columns:
                refusals.append(
                    (
                        header_offset,
                        "column '%s' is not in the generated table and would be removed"
                        % column,
                    )
                )
        for row_offset, cells in rows:
            if not cells or _is_placeholder_cell(cells[0]):
                continue
            key, tokens = _row_identity(name, desc, cells)
            if "<" in cells[0] or ">" in cells[0]:
                refusals.append(
                    (
                        row_offset,
                        "row %s: the id cell carries '<' or '>' markup that regeneration "
                        "would remove" % (key or cells[0]),
                    )
                )
                continue
            if not tokens & known:
                refusals.append((row_offset, "row %s has %s" % (key or cells[0], missing)))
    return sorted(refusals)


_BOM = b"\xef\xbb\xbf"


def _strict_text_problem(path: Path) -> Optional[str]:
    """Why ``generate`` must not rewrite ``path``: it cannot be read, or it is not UTF-8
    (a BOM is allowed). ``None`` when it is safe to rewrite."""
    try:
        path.read_bytes().decode("utf-8-sig")
    except OSError as exc:
        return "cannot be read (%s)" % (exc.strerror or exc)
    except UnicodeDecodeError as exc:
        return "is not valid UTF-8 (byte %d)" % exc.start
    return None


def _write_preserving_eol(path: Path, new_text: str) -> None:
    """Write ``new_text`` (which uses ``\n``) keeping the file's dominant line terminator
    and its byte-order mark, so regenerating one row never rewrites every line ending in
    the file — neither on a CRLF file, nor on an LF file with one stray CRLF line."""
    try:
        raw = path.read_bytes()
    except OSError:
        raw = b""
    crlf = raw.count(b"\r\n") * 2 > raw.count(b"\n")
    encoding = "utf-8-sig" if raw.startswith(_BOM) else "utf-8"
    with open(path, "w", encoding=encoding, newline="\r\n" if crlf else "\n") as handle:
        handle.write(new_text)


def generate(root: Path, verify: bool = False) -> Tuple[int, List[str]]:
    """Rewrite every generated block and requirement detail-file status line from the map.

    Returns ``(exit code, lines)`` — the paths written when ``verify`` is false, or a
    unified diff per stale block when it is true. Two guarantees make a non-``--verify`` run
    safe to script: the map is validated write-free first (a ``map-schema`` error leaves every
    record-derived block and detail-file status line as it is, names each skipped block and the
    detail-file directory — nothing, with a file-form requirements path and no requirements-index
    block — still regenerates the blocks that read no record, and exits 1 in every case), and a
    hand-written index row with no record in the map is refused, not deleted — the whole run
    writes nothing when any row would vanish. A descriptor that is missing or
    does not parse fails exactly as ``check`` fails it.
    """
    root = Path(root)
    desc, message = _load_descriptor(root)
    if message:
        return 2, Report.fatal(message).render(root).split("\n")

    # Write-free preflight: never rewrite a document from a map that fails its own schema.
    # Forced to ERROR whatever the configured severity — write-safety is not a knob. Only
    # an ERROR blocks, and only the record-derived output: an unknown record key stays the
    # WARN `check` reports, and the blocks that read no record are still regenerated.
    preflight = Report()
    records, map_ok = _load_records_or_report(desc, preflight)
    ctx = Context(root, desc, records)
    if map_ok:
        check_map_schema(ctx, preflight, level="ERROR")
    records_blocked = bool(preflight.errors())
    warnings = [
        "[map-schema] %s %s: %s" % (f.level, f.anchor, f.message) for f in preflight.findings
    ]

    messages: List[str] = []
    diff_lines: List[str] = []
    refusals: List[str] = []
    new_texts: Dict[Path, str] = {}
    #: Every file the run would rewrite, in both modes (``--verify`` fills no new_texts).
    targets: List[Path] = []
    stale = False
    skipped = False

    any_block = False
    for path in _link_files(ctx):
        text = ctx.read(path)
        blocks = generated_blocks(text)
        anchor = ctx.rel(path)
        if _MARKER_TOKEN in text:
            for lineno, message in generated_marker_problems(text):
                messages.append("%s:%d: skipped — %s" % (anchor, lineno, message))
                skipped = True
        if not blocks:
            continue
        any_block = True
        for name, start, end in blocks:
            if end == 0:
                messages.append(
                    "%s:%d: skipped — unclosed generated block '%s'" % (anchor, start, name)
                )
                skipped = True
            elif name not in GENERATED_BLOCKS:
                messages.append(
                    "%s:%d: skipped — unknown block '%s'" % (anchor, start, name)
                )
                skipped = True
        known = [(n, s, e) for n, s, e in blocks if e and n in GENERATED_BLOCKS]
        if not known:
            continue
        file_lines = text.split("\n")
        changed = False
        for name, start, end in sorted(known, key=lambda item: item[1], reverse=True):
            if records_blocked and name in RECORD_BLOCKS:
                messages.append(
                    "%s:%d: skipped — block '%s' left as it is, because the traceability map "
                    "has errors" % (anchor, start, name)
                )
                skipped = True
                continue
            new_content = _expected_block(ctx, name, path)
            current_content = file_lines[start:end - 1]
            if current_content == new_content:
                continue
            changed = True
            documents = _document_tokens(ctx, name)
            for offset, reason in _block_refusals(
                name, desc, current_content, new_content, documents
            ):
                refusals.append(
                    "%s:%d: refused — %s; nothing written" % (anchor, start + 1 + offset, reason)
                )
            if verify:
                stale = True
                diff_lines.extend(_diff(anchor, current_content, new_content))
            else:
                file_lines = file_lines[:start] + new_content + file_lines[end - 1 :]
        if changed and path not in targets:
            targets.append(path)
        if changed and not verify:
            candidate = "\n".join(file_lines)
            if candidate != text:
                new_texts[path] = candidate

    requirements_dir = desc.requirements_dir()
    # A map error exits 1 through records_blocked even when no requirements-index block
    # or detail file exists to be named as skipped (a file-form requirements path).
    if records_blocked and requirements_dir is not None and requirements_dir.is_dir():
        messages.append(
            "%s: skipped — requirement detail-file status lines left as they are, because the "
            "traceability map has errors" % ctx.rel(requirements_dir)
        )
        skipped = True
    elif requirements_dir is not None and requirements_dir.is_dir():
        for record in ctx.records:
            if not record.id:
                continue
            for detail in _detail_files(requirements_dir, record.id):
                base = new_texts.get(detail, ctx.read(detail))
                updated = _rewrite_status_lines(base, record)
                if updated == base:
                    continue
                if detail not in targets:
                    targets.append(detail)
                if verify:
                    stale = True
                    diff_lines.extend(
                        _diff(ctx.rel(detail), base.split("\n"), updated.split("\n"))
                    )
                else:
                    new_texts[detail] = updated

    for path in targets:
        # The text above was decoded leniently; a file that is not strict UTF-8 would lose
        # its undecodable bytes to replacement characters on rewrite, so it is refused.
        problem = _strict_text_problem(path)
        if problem:
            refusals.append(
                "%s: refused — the file %s, and rewriting it would destroy those bytes; "
                "nothing written" % (ctx.rel(path), problem)
            )
    if not any_block:
        messages.append("sdd-check: no generated blocks found")
    if verify:
        # The same refusal lines a writing run prints, so a dry run never promises a write
        # the real run would refuse.
        failed = stale or skipped or records_blocked or bool(refusals)
        return (1 if failed else 0), warnings + messages + refusals + diff_lines
    if refusals:
        # A dropped row is data loss: abort the whole run, write nothing, name every row.
        return 1, warnings + messages + refusals
    written: List[str] = []
    for path in new_texts:
        try:
            _write_preserving_eol(path, new_texts[path])
        except OSError as exc:
            # Say exactly where the run stopped: what is already on disk, and what is not.
            return 1, warnings + messages + written + [
                "%s: write failed (%s); the %d file(s) listed above were written, "
                "no later file was" % (ctx.rel(path), exc.strerror or exc, len(written))
            ]
        ctx._texts[str(ctx.abs(path))] = new_texts[path]
        written.append(ctx.rel(path))
    return (1 if skipped or records_blocked else 0), warnings + messages + sorted(written)


def run_generate(root: Path, verify: bool) -> int:
    """CLI wiring for ``generate``. The three generated blocks are not families and are
    always regenerated together, so there is no ``--only`` for this command."""
    code, lines = generate(root, verify)
    for line in lines:
        print(line)
    return code


# ---------------------------------------------------------------------------
# The baseline fixture — the minimal passing repository the unit tests and the
# selftest share, one source for both
# ---------------------------------------------------------------------------

#: The minimal passing repository the unit tests and the selftest build on.
_BASELINE_FILES = {
    "docs/.sdd.yaml": """sdd:
  profile: full

  req_style: area-prefixed
  req_areas: [FOUND]
  req_gap: 10
  excluded_areas: [BENCH]

  doc_kinds: [requirement, specification, adr, guide, analysis, operations, reference, upstream]

  default_mode: spec-first

  paths:
    requirements: docs/requirements
    specifications: docs/specifications
    adr: docs/adr

  traceability: docs/specifications/traceability.yaml

  build_entrypoint: make
  ci_target: ci
  spec_check_target: spec-check

  use_probes: false
  use_strands: false

  upstream: ""

  ground_truth: "the specifications in docs/specifications"

  check:
    script: scripts/sdd-check.py
    version: "@VERSION@"
    links:
      exclude: []
    changelog:
      path: CHANGELOG.md
      max_words: 35
    code_roots: []
    test_globs: ["*_test.py"]
    probes_catalogue: ""
    families:
      descriptor: error
      map-schema: error
      map-to-tree: error
      index-sync: error
      tree-to-map: warn
      doc-kinds: warn
      rfc2119: warn
      one-home: error
      links: error
      changelog: warn
      generated: error
      draft-reason: off

  hooks:
    stop_nudge: true
""",
    "docs/requirements/README.md": """---
kind: guide
---

# Requirements

Each row links to the requirement it names. The traceability map owns both status axes.

<!-- sdd:generated requirements-index -->

| ID | Title | Spec | Stability | Implementation |
|---|---|---|---|---|
| [REQ-FOUND-001](REQ-FOUND-001.md) | Environment boundary | [`SPEC-ENV §1`](../specifications/env.md#1--boundary-req-found-001) | Draft | shipped |

<!-- /sdd:generated -->
""",
    "docs/requirements/REQ-FOUND-001.md": """---
kind: requirement
id: REQ-FOUND-001
title: Environment boundary
status: draft
implementation: shipped
---

# REQ-FOUND-001 — Environment boundary

The service reads its configuration from the environment when it starts.

## Acceptance criteria

- A start with every declared variable set is accepted.
- A start with a declared variable absent is refused, and the refusal names the variable.

## Out of scope

- Reloading the configuration while the service runs.

**Canonical:** [SPEC-ENV §1](../specifications/env.md#1--boundary-req-found-001)
""",
    "docs/specifications/README.md": """---
kind: guide
---

<!-- sdd-check: allow rfc2119, one-home -->

# Specifications

Each specification owns the normative prose for the requirements it lists.

<!-- sdd:generated specifications-index -->

| Spec | Topic | Status | Mode |
|---|---|---|---|
| [`SPEC-ENV`](env.md) | Environment | Draft | spec-first |

<!-- /sdd:generated -->
""",
    "docs/specifications/env.md": """---
kind: specification
spec: SPEC-ENV
status: draft
mode: spec-first
requirements: [REQ-FOUND-001]
---

# SPEC-ENV — Environment

This document owns how the service reads its environment.

## §1 — Boundary (REQ-FOUND-001)

**Implements:** REQ-FOUND-001

The service MUST read every declared variable from the environment when it starts.

The service MUST refuse to start when a declared variable is absent.
""",
    "docs/specifications/traceability.yaml": """# traceability.yaml — the machine-readable REQ to spec to code to test map.
requirements:
  - id: REQ-FOUND-001
    title: Environment boundary
    canonical: docs/specifications/env.md#1--boundary-req-found-001
    status: draft
    implementation: shipped
    packages:
      - src/env
    tests:
      - tests/env_test.py
""",
    "docs/adr/README.md": """---
kind: guide
---

# Decision records

<!-- sdd:generated adr-index -->

| ID | Title | Status | Date | Resolves / amends |
|---|---|---|---|---|

<!-- /sdd:generated -->
""",
    "docs/development-process.md": """---
kind: guide
---

# Development process

Write the specification first, then the code, then the tests that cite the requirement.
""",
    "src/env/__init__.py": "",
    "tests/env_test.py": '''def test_req_found_001_boundary():
    """REQ-FOUND-001: the service refuses to start when a declared variable is absent."""
    assert True
''',
    "CHANGELOG.md": """# Changelog

## [Unreleased]

### Added
- Config: the service reads every declared variable from the environment when it starts.
""",
    "AGENTS.md": """# Agent guide

Read [the development process](docs/development-process.md) before changing anything here.
""",
}


#: The pin follows the tool, so bumping ``__version__`` never leaves the fixture unclean.
_BASELINE_FILES["docs/.sdd.yaml"] = _BASELINE_FILES["docs/.sdd.yaml"].replace(
    "@VERSION@", __version__
)


def write_baseline(root: Path) -> None:
    """Write the minimal passing repository the tests and the selftest run against."""
    root = Path(root)
    for rel, text in _BASELINE_FILES.items():
        path = root / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(text, encoding="utf-8")


# ---------------------------------------------------------------------------
# Context bundle — sdd-methodology.md §10
# ---------------------------------------------------------------------------

_CONTEXT_HEADINGS = (
    "Index row",
    "Traceability record",
    "Canonical section",
    "Acceptance criteria",
    "Tests citing it",
    "Open strands",
)


def _index_row(ctx: Context, req_id: str) -> Optional[str]:
    index_path = ctx.desc.requirements_index_path()
    if not index_path.is_file():
        return None
    text = ctx.read(index_path)
    lines = text.split("\n")
    pattern = _identifier_re(req_id)
    for lineno, _header, cells in table_rows(text):
        if any(pattern.search(cell) for cell in cells):
            return lines[lineno - 1].strip()
    return None


def _record_dump(record: Record) -> str:
    lines = [
        "id: %s" % record.id,
        "title: %s" % record.title,
        "canonical: %s" % record.canonical,
        "status: %s" % record.status,
        "implementation: %s" % record.implementation,
    ]
    for key in ("packages", "tests", "probes", "operations"):
        value = getattr(record, key)
        if value:
            lines.append("%s: %s" % (key, ", ".join(value)))
    if record.draft_reason:
        lines.append("draft_reason: %s" % record.draft_reason)
    return "\n".join(lines)


def _canonical_section(ctx: Context, record: Record) -> Optional[str]:
    if not record.canonical:
        return None
    path_part, _, fragment = record.canonical.partition("#")
    if not path_part:
        return None
    target = ctx.desc.resolve(path_part)
    if not target.is_file():
        return None
    text = ctx.read(target)
    span = section_slice(text, fragment) if fragment else None
    if span is None and fragment:
        span = _anchor_span(text, fragment)
    if span is None:
        return None
    start, end = span
    lines = text.split("\n")
    end = min(end, len(lines) + 1)
    body = "\n".join(lines[start - 1 : end - 1]).strip("\n")
    return "%s\n\n%s" % (ctx.rel(target), body) if body else ctx.rel(target)


def _acceptance_criteria(ctx: Context, req_id: str) -> Optional[str]:
    detail = _requirement_detail_file(ctx, req_id)
    if detail is None:
        return None
    text = ctx.read(detail)
    span = section_slice(text, "acceptance-criteria")
    if span is None:
        return None
    start, end = span
    lines = text.split("\n")
    end = min(end, len(lines) + 1)
    content = "\n".join(lines[start:end - 1]).strip("\n")
    return content or None


def _tests_citing(ctx: Context, req_id: str) -> Optional[str]:
    desc = ctx.desc
    roots: List[Path] = []
    for rel in desc.code_roots:
        target = desc.resolve(rel)
        if target.exists():
            roots.append(target)
    if not desc.code_roots:
        roots = [ctx.root]
    skip = _code_skip_set(desc)
    pattern = _identifier_re(req_id)
    found = []
    for path in _code_files(ctx, roots, skip):
        if not any(fnmatch.fnmatch(path.name, glob) for glob in desc.test_globs):
            continue
        try:
            text = path.read_text(encoding="utf-8")
        except (OSError, UnicodeDecodeError):
            continue
        if pattern.search(text):
            found.append(ctx.rel(path))
    return "\n".join(sorted(set(found))) if found else None


def _open_strands(ctx: Context, req_id: str) -> Optional[str]:
    found = []
    for path in ctx.desc.specification_files():
        for lineno, line in enumerate(ctx.read(path).split("\n"), 1):
            if "STRAND-" in line and req_id in line:
                found.append("%s:%d: %s" % (ctx.rel(path), lineno, line.strip()))
    return "\n".join(found) if found else None


def context_bundle(ctx: Context, req_id: str) -> str:
    """A requirement's context bundle: index row, record, canonical section, acceptance
    criteria, citing tests and open strands — sdd-methodology.md §10.

    :data:`_CONTEXT_HEADINGS` is the one home for the heading order; this just supplies
    each heading's content.
    """
    record = ctx.records_by_id.get(req_id)
    content = {
        "Index row": _index_row(ctx, req_id),
        "Traceability record": _record_dump(record) if record else None,
        "Canonical section": _canonical_section(ctx, record) if record else None,
        "Acceptance criteria": _acceptance_criteria(ctx, req_id),
        "Tests citing it": _tests_citing(ctx, req_id),
        "Open strands": _open_strands(ctx, req_id),
    }
    out: List[str] = []
    for title in _CONTEXT_HEADINGS:
        out.append(title)
        out.append(content[title] if content[title] else "none")
        out.append("")
    return "\n".join(out).rstrip("\n")


def run_context(root: Path, requirement: str) -> int:
    """CLI wiring for ``context <REQ>``.

    A missing or unparseable descriptor fails exactly as ``check`` fails it (exit 2). A
    map that will not load is reported through the same ``map-schema`` finding
    ``generate`` reports (exit 1) — that is a configuration failure, not "no such
    requirement". Only a real unknown id, with the map loaded, is `no record` (exit 2).
    """
    root = Path(root)
    desc, message = _load_descriptor(root)
    if message:
        print(Report.fatal(message).render(root))
        return 2
    report = Report()
    report.profile = desc.profile
    records, map_ok = _load_records_or_report(desc, report)
    if not map_ok:
        report.record_count = len(records)
        report.mark_run("map-schema")
        print(report.render(root))
        return report.exit_code()
    ctx = Context(root, desc, records)
    if requirement not in ctx.records_by_id:
        print("sdd-check: FAILED — no record for %s" % requirement)
        return 2
    print(context_bundle(ctx, requirement))
    return 0


# ---------------------------------------------------------------------------
# Selftest — runs the tool against the baseline fixture it carries, both clean and
# mutated one rule at a time
# ---------------------------------------------------------------------------


@dataclasses.dataclass
class SelftestCase:
    name: str
    mutate: Callable[[Path], None]
    check: Callable[["Report", Path], Optional[str]]


def _mutate_edit(rel: str, old: str, new: str) -> Callable[[Path], None]:
    def apply(root: Path) -> None:
        path = root / rel
        text = path.read_text(encoding="utf-8")
        if old not in text:
            raise AssertionError("selftest fixture drift: %r not in %s" % (old, rel))
        path.write_text(text.replace(old, new, 1), encoding="utf-8")

    return apply


def _mutate_write(rel: str, text: str) -> Callable[[Path], None]:
    def apply(root: Path) -> None:
        path = root / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(text, encoding="utf-8")

    return apply


def _mutate_all(*mutations: Callable[[Path], None]) -> Callable[[Path], None]:
    def apply(root: Path) -> None:
        for mutation in mutations:
            mutation(root)

    return apply


def _finding_check(family: str, fragment: str) -> Callable[["Report", Path], Optional[str]]:
    def check(report: "Report", root: Path) -> Optional[str]:
        hits = [f for f in report.findings if f.family == family and fragment in f.message]
        if hits:
            return None
        return "no %s finding containing %r" % (family, fragment)

    return check


_DEAD_LINK_DOC = """---
kind: reference
---

# Selftest dead link

See [dead](nope-does-not-exist.md) for detail.
"""


def _check_link_in_every_paths_dir(report: "Report", root: Path) -> Optional[str]:
    missing = []
    for rel in (
        "docs/requirements/_selftest-link-check.md",
        "docs/specifications/_selftest-link-check.md",
        "docs/adr/_selftest-link-check.md",
    ):
        hits = [
            f
            for f in report.findings
            if f.family == "links" and "no such file" in f.message and f.anchor.startswith(rel)
        ]
        if not hits:
            missing.append(rel)
    if missing:
        return "no dead-link finding for %s" % ", ".join(missing)
    return None


def _check_link_exclusion_printed(report: "Report", root: Path) -> Optional[str]:
    rendered = report.render(root)
    if "link exclusions:" not in rendered:
        return "no 'link exclusions:' line in the report"
    if "docs/specifications/*.md" not in rendered:
        return "the report does not name the configured exclusion"
    return None


SELFTEST_CASES: Tuple[SelftestCase, ...] = (
    SelftestCase(
        "descriptor-version-pin",
        _mutate_edit(
            "docs/.sdd.yaml", '    version: "%s"' % __version__, '    version: "0.0.0-selftest"'
        ),
        _finding_check("descriptor", "does not equal the tool's version"),
    ),
    SelftestCase(
        "map-empty",
        _mutate_write(
            "docs/specifications/traceability.yaml",
            "# traceability.yaml — the machine-readable REQ to spec to code to test map.\n"
            "requirements: []\n",
        ),
        _finding_check("map-schema", "zero records"),
    ),
    SelftestCase(
        "map-duplicate-id",
        _mutate_edit(
            "docs/specifications/traceability.yaml",
            "      - tests/env_test.py\n",
            "      - tests/env_test.py\n"
            "  - id: REQ-FOUND-001\n"
            "    title: Duplicate\n"
            "    canonical: docs/specifications/env.md#1--boundary-req-found-001\n"
            "    status: draft\n"
            "    implementation: shipped\n",
        ),
        _finding_check("map-schema", "duplicate id"),
    ),
    SelftestCase(
        "map-excluded-area",
        _mutate_edit(
            "docs/specifications/traceability.yaml", "id: REQ-FOUND-001", "id: REQ-BENCH-001"
        ),
        _finding_check("map-schema", "excluded area"),
    ),
    SelftestCase(
        "map-retired-not-deprecated",
        _mutate_edit(
            "docs/specifications/traceability.yaml",
            "    implementation: shipped",
            "    implementation: retired",
        ),
        _finding_check("map-schema", "belongs only to a deprecated requirement"),
    ),
    SelftestCase(
        "canonical-anchor-missing",
        _mutate_edit(
            "docs/specifications/traceability.yaml",
            "docs/specifications/env.md#1--boundary-req-found-001",
            "docs/specifications/env.md#nonexistent-anchor",
        ),
        _finding_check("map-to-tree", "resolves to no heading slug and no explicit anchor"),
    ),
    SelftestCase(
        "implements-marker-missing",
        _mutate_all(
            # The baseline heading names the id too (a valid back-edge on its own), so
            # neutralize its case first — the slug (lower-cased already) still resolves
            # the same section, but the case-sensitive heading match no longer fires.
            _mutate_edit("docs/specifications/env.md", "(REQ-FOUND-001)", "(req-found-001)"),
            _mutate_edit("docs/specifications/env.md", "**Implements:** REQ-FOUND-001\n\n", ""),
        ),
        _finding_check("map-to-tree", "carries no '**Implements:**"),
    ),
    SelftestCase(
        "package-path-missing",
        _mutate_edit(
            "docs/specifications/traceability.yaml", "      - src/env", "      - src/nonexistent"
        ),
        _finding_check("map-to-tree", "missing package path"),
    ),
    SelftestCase(
        "no-evidence",
        _mutate_edit(
            "docs/specifications/traceability.yaml",
            "    packages:\n      - src/env\n    tests:\n      - tests/env_test.py\n",
            "",
        ),
        _finding_check("map-to-tree", "carries no evidence"),
    ),
    SelftestCase(
        "index-row-orphan",
        _mutate_edit(
            "docs/requirements/README.md",
            "| Environment boundary |",
            "| Environment boundary |\n"
            "| REQ-FOUND-002 | Orphan | — | Draft | shipped |",
        ),
        _finding_check("index-sync", "missing from traceability"),
    ),
    SelftestCase(
        "index-impl-mismatch",
        _mutate_edit(
            "docs/requirements/README.md", "Draft | shipped |", "Draft | landed |"
        ),
        _finding_check("index-sync", "does not equal the record implementation"),
    ),
    SelftestCase(
        "detail-file-mismatch",
        _mutate_edit("docs/requirements/REQ-FOUND-001.md", "status: draft", "status: stable"),
        _finding_check("index-sync", "the detail file says"),
    ),
    SelftestCase(
        "doc-kind-missing",
        _mutate_edit("docs/development-process.md", "kind: guide\n", ""),
        _finding_check("doc-kinds", "declares no kind"),
    ),
    SelftestCase(
        "upstream-uses-status",
        _mutate_edit(
            "docs/development-process.md", "kind: guide", "kind: upstream\nstatus: proposed"
        ),
        _finding_check("doc-kinds", "never status"),
    ),
    SelftestCase(
        "rfc2119-in-requirement",
        _mutate_edit(
            "docs/requirements/REQ-FOUND-001.md",
            "The service reads its configuration from the environment when it starts.",
            "The service MUST read its configuration from the environment when it starts.",
        ),
        _finding_check("rfc2119", "does not belong in a requirement document"),
    ),
    SelftestCase(
        "rfc2119-requirement-section-empty",
        _mutate_all(
            _mutate_edit(
                "docs/.sdd.yaml",
                '    probes_catalogue: ""',
                '    probes_catalogue: ""\n    rfc2119:\n      sections: requirement-id',
            ),
            # No section sign, so only the requirement-id convention opens this section.
            _mutate_edit(
                "docs/specifications/env.md",
                "## §1 — Boundary (REQ-FOUND-001)",
                "## Boundary (REQ-FOUND-001)",
            ),
            _mutate_edit(
                "docs/specifications/env.md",
                "The service MUST read every declared variable from the environment when it "
                "starts.\n\nThe service MUST refuse to start when a declared variable is absent.",
                "The service reads every declared variable from the environment when it starts.",
            ),
        ),
        _finding_check("rfc2119", "is normative and carries no RFC-2119 keyword"),
    ),
    SelftestCase(
        "rfc2119-malformed",
        _mutate_edit(
            "docs/specifications/env.md",
            "The service MUST refuse to start when a declared variable is absent.\n",
            "The service MUST refuse to start when a declared variable is absent.\n\n"
            "The clause MUST to apply.\n",
        ),
        _finding_check("rfc2119", "malformed RFC-2119 form"),
    ),
    SelftestCase(
        "one-home-duplicate",
        _mutate_edit(
            "docs/specifications/env.md",
            "The service MUST refuse to start when a declared variable is absent.\n",
            "The service MUST refuse to start when a declared variable is absent.\n\n"
            "The service MUST read every declared variable from the environment when it starts.\n",
        ),
        _finding_check("one-home", "a normative statement has one home"),
    ),
    SelftestCase(
        "link-dead",
        _mutate_edit(
            "AGENTS.md",
            "Read [the development process](docs/development-process.md) before changing anything here.\n",
            "Read [the development process](docs/development-process.md) before changing anything here.\n"
            "\nSee [missing](docs/does-not-exist.md) for more.\n",
        ),
        _finding_check("links", "no such file"),
    ),
    SelftestCase(
        "link-fragment-dead",
        _mutate_edit(
            "AGENTS.md",
            "Read [the development process](docs/development-process.md) before changing anything here.\n",
            "Read [the development process](docs/development-process.md) before changing anything here.\n"
            "\nSee [bad anchor](docs/development-process.md#nonexistent) for more.\n",
        ),
        _finding_check("links", "matches no heading slug"),
    ),
    SelftestCase(
        "link-in-every-paths-dir",
        _mutate_all(
            _mutate_write("docs/requirements/_selftest-link-check.md", _DEAD_LINK_DOC),
            _mutate_write("docs/specifications/_selftest-link-check.md", _DEAD_LINK_DOC),
            _mutate_write("docs/adr/_selftest-link-check.md", _DEAD_LINK_DOC),
        ),
        _check_link_in_every_paths_dir,
    ),
    SelftestCase(
        "link-exclusion-printed",
        _mutate_edit(
            "docs/.sdd.yaml", "      exclude: []", '      exclude: ["docs/specifications/*.md"]'
        ),
        _check_link_exclusion_printed,
    ),
    SelftestCase(
        "changelog-too-long",
        _mutate_edit(
            "CHANGELOG.md",
            "- Config: the service reads every declared variable from the environment when it starts.\n",
            "- Config: the service reads every single one of the many declared configuration "
            "variables from the surrounding process environment when it starts up normally, in "
            "the exact order they were originally declared in the descriptor, and it refuses to "
            "start otherwise.\n",
        ),
        _finding_check("changelog", "the budget is"),
    ),
    SelftestCase(
        "generated-hand-edit",
        _mutate_edit(
            "docs/requirements/README.md", "Environment boundary", "Environment boundary, hand-edited"
        ),
        _finding_check("generated", "hand-edited or stale"),
    ),
    SelftestCase(
        "unknown-req-cited-in-code",
        _mutate_write("src/env/extra.py", "# implements REQ-FOUND-099\n"),
        _finding_check("tree-to-map", "unknown identifier cited"),
    ),
    SelftestCase(
        "draft-reason-missing",
        _mutate_edit("docs/.sdd.yaml", "draft-reason: off", "draft-reason: warn"),
        _finding_check("draft-reason", "owes a draft_reason"),
    ),
)


def selftest() -> int:
    """Run the tool against the baseline fixture it carries: clean, then each mutation
    case — at least one per family — printing ``PASS`` or ``FAIL`` per case."""
    with tempfile.TemporaryDirectory() as holder:
        base_root = Path(holder)
        write_baseline(base_root)
        baseline_report = run_check(base_root, only=None, changelog_all=False)
    loud = [f for f in baseline_report.findings if f.level in ("ERROR", "WARN")]
    if loud or baseline_report.exit_code() != 0:
        print("selftest: the baseline fixture is not clean")
        for finding in loud:
            print("  [%s] %s %s: %s" % (finding.family, finding.level, finding.anchor, finding.message))
        return 1

    failed: List[str] = []
    for case in SELFTEST_CASES:
        with tempfile.TemporaryDirectory() as holder:
            root = Path(holder)
            write_baseline(root)
            try:
                case.mutate(root)
            except (OSError, subprocess.SubprocessError) as exc:
                # A mutation that fails must fail only this one case — never abort the
                # whole run with a raw traceback after the cases before it printed PASS.
                print("FAIL %s — %s" % (case.name, exc))
                failed.append(case.name)
                continue
            report = run_check(root, only=None, changelog_all=False)
            reason = case.check(report, root)
        if reason is None:
            print("PASS %s" % case.name)
        else:
            print("FAIL %s — %s" % (case.name, reason))
            failed.append(case.name)

    total = len(SELFTEST_CASES)
    if failed:
        print("selftest: FAILED — %d of %d" % (len(failed), total))
        return 1
    print("selftest: OK — %d cases" % total)
    return 0


def run_selftest(root: Path) -> int:
    """CLI wiring for ``selftest``. ``root`` is accepted for shape but unused: the
    selftest always builds and mutates its own baseline fixture, never the caller's tree."""
    return selftest()


# ---------------------------------------------------------------------------
# Command line
# ---------------------------------------------------------------------------

COMMANDS = ("check", "generate", "context", "selftest")

USAGE = """usage: sdd-check [check|generate|context|selftest] [options]

  check [--root DIR] [--only fam[,fam]] [--changelog-all] [--changed-since REF [--new-only]]
  generate [--root DIR] [--verify]
  context <REQ> [--root DIR]
  selftest [--root DIR]
  --version

sdd-check is the shared drift gate. It is one Python file, vendored into a repository by
/sdd-scaffold and run by the spec-check build target, so CI needs no plugin. It checks
the traceability chain in both directions, lints the prose rules that can be checked
mechanically, regenerates the derived indexes, prints a requirement's context bundle,
and tests itself.
"""


def main(argv=None) -> int:
    """Parse the command line and run one command. Returns the process exit code."""
    argv = list(sys.argv[1:] if argv is None else argv)
    argv = [str(item) for item in argv]
    if not argv:
        argv = ["check"]
    if argv[0] in ("--version", "-V"):
        print(__version__)
        return 0
    if argv[0] in ("--help", "-h"):
        print(USAGE.rstrip())
        return 0
    if argv[0].startswith("-"):
        command, rest = "check", argv
    else:
        command, rest = argv[0], argv[1:]
    if command not in COMMANDS:
        print("sdd-check: unknown command '%s' — see --help" % command)
        return 2

    root = Path.cwd()
    only: Optional[List[str]] = None
    changelog_all = False
    verify = False
    since: Optional[str] = None
    new_only = False
    positional: List[str] = []
    index = 0
    while index < len(rest):
        arg = rest[index]
        if arg == "--root":
            index += 1
            if index >= len(rest):
                print("sdd-check: --root needs a directory")
                return 2
            root = Path(rest[index])
        elif arg.startswith("--root="):
            root = Path(arg.split("=", 1)[1])
        elif arg == "--only":
            index += 1
            if index >= len(rest):
                print("sdd-check: --only needs a family list")
                return 2
            only = [name.strip() for name in rest[index].split(",") if name.strip()]
        elif arg.startswith("--only="):
            only = [name.strip() for name in arg.split("=", 1)[1].split(",") if name.strip()]
        elif arg == "--changelog-all":
            changelog_all = True
        elif arg == "--changed-since":
            index += 1
            if index >= len(rest):
                print("sdd-check: --changed-since needs a ref")
                return 2
            since = rest[index]
        elif arg.startswith("--changed-since="):
            since = arg.split("=", 1)[1]
        elif arg == "--new-only":
            new_only = True
        elif arg == "--verify":
            verify = True
        elif arg.startswith("-"):
            print("sdd-check: unknown option '%s'" % arg)
            return 2
        else:
            positional.append(arg)
        index += 1

    if only is not None and command != "check":
        print("sdd-check: --only applies to 'check' only, not '%s'" % command)
        return 2
    if verify and command != "generate":
        print("sdd-check: --verify applies to 'generate' only, not '%s'" % command)
        return 2
    if changelog_all and command != "check":
        print("sdd-check: --changelog-all applies to 'check' only, not '%s'" % command)
        return 2
    if (since is not None or new_only) and command != "check":
        print("sdd-check: --changed-since and --new-only apply to 'check' only, not '%s'" % command)
        return 2
    if new_only and since is None:
        print("sdd-check: --new-only needs --changed-since <ref>")
        return 2
    if only is not None:
        unknown = [name for name in only if name not in FAMILIES]
        if unknown:
            print("sdd-check: unknown family '%s' in --only" % unknown[0])
            return 2
    if positional and command in ("generate", "selftest"):
        print("sdd-check: %s takes no positional argument ('%s')" % (command, positional[0]))
        return 2

    if command == "generate":
        return run_generate(root, verify)
    if command == "context":
        if len(positional) != 1:
            print("sdd-check: context takes exactly one requirement id")
            return 2
        return run_context(root, positional[0])
    if command == "selftest":
        return run_selftest(root)
    if positional:
        print("sdd-check: check takes no positional argument ('%s')" % positional[0])
        return 2
    report = run_check(root, only, changelog_all)
    if since is not None and report.fatal_message is None:
        if not mark_new(report, root, since, only, changelog_all):
            report = Report.fatal("--changed-since: '%s' and HEAD have no merge base git can find here" % since)
        report.new_only = new_only
    print(report.render(root))
    return report.exit_code()


if __name__ == "__main__":
    sys.exit(main())
