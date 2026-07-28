"""MkDocs hook for pages symlinked into the site from the contributor docs.

`pages/install.md` is a symlink to the canonical `docs/install.md`. That file
links to sibling contributor docs which are deliberately *not* part of the
public site, so those relative links would 404 once published. Each one is
rewritten to its GitHub URL.

The rewrite is generic by design — there is no allowlist to fall out of date.
Any relative Markdown link that resolves to neither a page of this site nor a
file in the repository raises `PluginError`, failing the build instead of
silently publishing a broken link.
"""

from __future__ import annotations

import posixpath
import re
from pathlib import Path

from mkdocs.exceptions import PluginError

#: Repository directory the symlinked pages actually live in; relative links
#: inside them resolve against it.
_DOCS_DIR = "docs"

_GITHUB_BLOB = "https://github.com/cadasto/openehr-assistant-mcp/blob/main"

#: Pages that are symlinks into `_DOCS_DIR` rather than native site content.
_SYMLINKED_PAGES = {"install.md"}

_RELATIVE_MD_LINK = re.compile(
    r"\]\("                        # opening "]("
    r"(?!https?:|mailto:|/|#)"     # skip absolute, external and anchor-only links
    r"([^)\s#]+\.md)"              # capture the target path
    r"(#[^)\s]*)?"                 # capture an optional anchor
    r"\)"
)


def on_page_markdown(markdown: str, page, config, files) -> str:
    if page.file.src_uri not in _SYMLINKED_PAGES:
        return markdown

    repo_root = Path(config.config_file_path).parents[2]

    def rewrite(match: re.Match) -> str:
        target, anchor = match.group(1), match.group(2) or ""

        # A target that is a real page of this site stays a site-internal link.
        if files.get_file_from_path(target) is not None:
            return match.group(0)

        repo_path = posixpath.normpath(posixpath.join(_DOCS_DIR, target))
        if repo_path.startswith(".."):
            raise PluginError(
                f"link_fix: {page.file.src_uri} links to {target!r}, which escapes "
                f"the repository root and cannot be rewritten to a GitHub URL."
            )
        if not (repo_root / repo_path).exists():
            raise PluginError(
                f"link_fix: {page.file.src_uri} links to {target!r}, but "
                f"{repo_path} does not exist in the repository."
            )
        return f"]({_GITHUB_BLOB}/{repo_path}{anchor})"

    return _RELATIVE_MD_LINK.sub(rewrite, markdown)
