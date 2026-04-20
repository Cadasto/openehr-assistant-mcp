# Object Data Instance Notation (ODIN) — Digest

**Scope:** Human-readable, object-oriented textual syntax for serialising instance data, used across openEHR for ADL archetype meta-sections and BMM model definitions.
**Component:** LANG
**Document:** odin
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/odin.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/odin.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/am2-ADL2, openehr://guides/specs/lang-bmm
**Keywords:** ODIN, notation, JSON-like, dADL, BMM, ADL sections

---

## Purpose

ODIN (Object Data Instance Notation) defines a compact, human-readable serialisation syntax for object-oriented instance data with minimal assumptions about the underlying information model. It exists so that openEHR can express typed object graphs — most notably the non-constraint sections of ADL 2 archetypes (`language`, `description`, `terminology`, `annotations`) and the whole of BMM model files — in a form that is both directly editable by modellers and machine-parseable for tooling. ODIN supersedes the legacy `dADL` syntax used in ADL 1.4 and is the canonical representation whenever an openEHR artefact needs to embed or exchange typed data without resorting to XML or JSON.

## Scope

- In: abstract grammar for object blocks, attribute assignments, primitive leaf types (String, Character, Integer, Real, Boolean, ISO 8601 Date/Time, Duration, URI, coded term `[terminology::code]`), intervals, lists and keyed container tables, type markers (including generics), Xpath-style paths and path-based object references, multi-line strings, comments, document forms (implicit/anonymous/identified), optional `@schema` header, plug-in syntax embedding, and validity rules (unique sibling attribute names `VDATU`, unique container keys `VDOBU`).
- Out: constraint semantics (handled by `cADL` in `AM/ADL2`), model-definition semantics (handled by `LANG/bmm`), terminology code resolution (handled by `RM/support` and `TERM`), wire-format conformance (handled by the `ITS-*` specifications), and concrete XML/JSON mappings (provided by implementation-technology specs rather than by ODIN itself).

## Key Classes / Constructs

- `object_block` — angle-bracket `< … >` delimited instance of a named or anonymous type; carries the attribute set and any optional type marker.
- `attribute_value` — `name = <value>` assignment of a simple or complex value to a sibling-unique attribute name; optional `;` separator.
- Primitive leaf types — quoted `"String"` / `'Char'`, `Integer` / `Real`, `Boolean`, ISO 8601 date/time with `?` wildcards, `Duration`, URI, and coded term literal `[terminology_id::code]`.
- `container_object` — keyed table `[key] = <…>` with `Integer | String | Date` keys (rule `VDOBU`); the ODIN form for `Hash`/`List` of complex objects.
- `type_marker` — optional explicit type in `(TYPE) <…>` or generic form `(List<HOTEL>)`, disambiguating dynamically bound subtypes.
- `interval_value` — ordered-primitive intervals `|N..M|`, `|>N..<M|`, `|>=N|`, `|N ± M|` used for ranges and tolerances.
- `path` / path-reference — Xpath-style `/attr/sub[key]/leaf` navigation; `</hotels["sofitel"]>` form creates a shared-object reference within the document.
- `multi_line_string` / comments — whitespace-normalised multi-line `"…"` strings, `--` line comments, and `(syntax) <# … #>` plug-in blocks for foreign notations.

## Relations to Other Specs

- Depends on: `LANG/basic_meta_model` (primitive type space reused by ODIN literals), `RM/support` (`TERMINOLOGY_ID`, identifier semantics underpinning coded term literals), and ISO 8601 for date/time/duration lexical rules.
- Consumed by: `AM/ADL2` (all non-constraint archetype sections — `language`, `description`, `terminology`, `annotations` — are ODIN), `LANG/bmm` (entire BMM schema files are ODIN), `LANG/aom2` serialised form, and any openEHR tooling exchanging typed object graphs outside XML/JSON.

## Architectural Placement

ODIN sits in the LANG component as the generic instance-serialisation layer beneath the archetype (`AM`) and model-definition (`LANG/bmm`) formalisms; it is the textual carrier that lets higher-level openEHR languages express typed object data without inventing a per-language concrete syntax, and it is orthogonal to the Reference Model, which defines the types ODIN documents instantiate.

## When to Read the Full Spec

Consult the full document when building an ODIN parser or pretty-printer, implementing round-trip conversion to XML/JSON, resolving edge-case lexical rules (multi-line string indentation, UTF-8 escape forms, semicolon handling, interval boundary syntax), validating `VDATU`/`VDOBU` conformance, or designing a plug-in syntax block for embedding a foreign notation (for example cADL) inside an ODIN document.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/odin.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/odin.md
- Related digests: specs/am2-ADL2, specs/lang-bmm
