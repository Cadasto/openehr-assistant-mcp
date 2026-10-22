---
kind: specification
spec: SPEC-TOOLS
status: draft
mode: implementation-aligned
---

# SPEC-TOOLS — knowledge tools

> **Normative.** Uses RFC-2119 keywords: **MUST/SHALL** (absolute), **SHOULD** (strong, exceptions need a reason),
> **MAY** (optional). Statements without a keyword are *informative*. Don't implement informative text as a
> requirement; don't relax normative text into a suggestion. *Implementation-aligned:* each section states what
> the shipped tools do, and a change to a tool updates its section in the same pull request.

## §1 — CKM archetype and template retrieval

<a id="ckm-retrieval-req-f-001"></a>

The server MUST expose the tools `ckm_archetype_search`, `ckm_archetype_get`, `ckm_template_search` and
`ckm_template_get`, answering from the openEHR Clinical Knowledge Manager (CKM).

Both search tools MUST score each match against the search keywords, return at most `maxResults` items, and
report the match count in `total`.

`ckm_archetype_get` MUST serialise an archetype as `adl`, `xml` or `mindmap`, and MUST default to `adl`.

Every request to CKM MUST go through the one CKM client that SPEC-ENGINEERING §1 describes.

**Implements:** REQ-F-001

## §2 — Implementation guides and ADL idioms

<a id="guides-req-f-002"></a>

The server MUST expose the tools `guide_search`, `guide_get` and `guide_adl_idiom_lookup`, answering from the
guides bundled with the server.

A guide MUST be addressed by category and name. The categories are `archetypes`, `templates`, `aql`,
`simplified_formats`, `specs` (per-document specification digests) and `howto` (toolchain how-tos).

`guide_adl_idiom_lookup` MUST return the ADL idiom snippets that match a modelling pattern.

`guide_search` and `examples_search` MUST share one query tokenizer, so the two tools cannot rank the same query
differently.

**Implements:** REQ-F-002

## §3 — Worked examples

<a id="examples-req-f-003"></a>

The server MUST expose the tools `examples_search` and `examples_get`, answering from the curated worked
examples bundled with the server.

An example MUST be addressed by kind and name. The kinds are `aql`, `flat`, `structured` and `archetypes`
(gold-standard ADL archetypes).

**Implements:** REQ-F-003

## §4 — Terminology resolution

<a id="terminology-req-f-004"></a>

The server MUST expose the tool `terminology_resolve`, which resolves an openEHR terminology identifier, code or
rubric, optionally narrowed to one group.

It MUST answer from the openEHR terminology bundled with the server and MUST NOT call a remote terminology
service.

**Implements:** REQ-F-004

## §5 — Type specifications

<a id="type-specifications-req-f-005"></a>

The server MUST expose the tools `type_specification_search` and `type_specification_get`, answering from the
BMM schemas bundled with the server, which cover at least the Reference Model, Archetype Model and BASE
components.

`type_specification_get` MUST return a class's attributes, functions and invariants.

`type_specification_search` MUST return every match, neither capped nor paginated, so its `total` MUST equal the
number of items.

**Implements:** REQ-F-005

## §6 — Tool schema contract

<a id="tool-schemas-req-n-009"></a>

Every published tool MUST declare a closed input schema: `additionalProperties: false`, optional enum parameters
nullable, and numeric parameters bounded, so an out-of-range value is rejected rather than clamped.

A search tool MUST return its results in an `{items, total}` envelope that is itself closed, with both members
`required`.

A tool's output MUST conform to the `outputSchema` it declares.

CI MUST check both halves of this contract, input and output, for every published tool before a change merges.

**Implements:** REQ-N-009
