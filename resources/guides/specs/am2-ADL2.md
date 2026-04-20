# Archetype Definition Language 2 (ADL 2) — Digest

**Scope:** Human-readable, computer-processable syntax for authoring openEHR archetypes and templates with differential specialisation and integrated terminology.
**Component:** AM
**Document:** ADL2
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/ADL2.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/ADL2.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/am2-AOM2, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am-ADL1.4
**Keywords:** ADL, ADL 2, differential specialisation, cADL, dADL, archetype syntax, id-codes, at-codes, path grammar, slot, terminology section

---

## Purpose
ADL 2 defines the concrete textual syntax in which openEHR archetypes and operational template fragments are expressed. It is the human-authored form that serialises the Archetype Object Model 2 (AOM2), pairing a constraint sub-language (cADL) for structural/value constraints with a data sub-language (ODIN/dADL) for metadata, rules, and terminology. The 2.x line supersedes ADL 1.4 by introducing differential specialisation, id-coded node identifiers, a first-class `terminology` section with explicit value sets, and an Xpath-like path grammar aligned with AOM2 and AQL.

## Scope
- In: archetype file structure and keywords; cADL object/attribute node grammar; existence, cardinality and occurrence constraints; primitive leaf constraints (ranges, sets, regex); differential specialisation syntax and node-id dotted extensions; internal/external references (`use_node`, `use_archetype`) and `allow_archetype` slot definitions; archetype path grammar; rules section using the openEHR Expression Language; terminology bindings and value sets.
- Out: the semantic object model (AOM2); operational template composition and flattening (OPT2); persistence/serialisation formats (XML, JSON); AQL query semantics; RM class content (defined by RM specs); tooling and editor behaviour.

## Key Classes / Constructs
- `archetype` / `template` / `template_overlay` header — declares `adl_version`, `rm_release` and archetype HRID; gates parser mode.
- `language` section — mandatory ODIN block listing `original_language` and `translations` using ISO 639-1 codes.
- `description` section — ODIN metadata (purpose, use, misuse, lifecycle state, authors, references) separate from constraint logic.
- `definition` section (cADL) — block-structured constraint tree alternating object nodes `TYPE[id|at-code]` with attribute nodes, using `matches {...}`, `existence`, `cardinality`, `occurrences`.
- Differential specialisation syntax — child archetypes state only added or redefined nodes; node identifiers extend parent codes via dotted suffixes (e.g. `id3.1`, `at0004.2`).
- `allow_archetype` slot — constrained extension point declaring `include`/`exclude` assertions over `archetype_id` for template composition.
- Path grammar — Xpath-like `/attr[node-id]/attr[...]` sequences addressing any node; basis for AQL predicates, template bindings, and rule references.
- `rules` section — invariants and derived-value assertions in the openEHR Expression Language, referencing definition nodes by path.
- `terminology` section (ODIN) — `term_definitions`, `term_bindings`, and a dedicated `value_sets` subsection replacing ADL 1.4's inline `ontology` constructs.

## Relations to Other Specs
- Depends on: `AM/AOM2` (abstract model ADL 2 serialises), `BASE/foundation_types` (primitive types, ISO 8601), `BASE/base_types` (intervals, terminology codes), `AM/Identification` (archetype HRID grammar).
- Consumed by: `AM/OPT2` (operational template generation flattens ADL 2 sources), `QUERY/AQL` (shares the path grammar and node-id predicates), `ITS-Archetype` serialisations (XML/JSON round-tripping), ADL workbenches and governance tooling (ADL Workbench, CKM).

## Architectural Placement
ADL 2 is the authoring-surface layer of the Archetype Model stack: below it sits the semantic AOM2; above it sit OPT2 operational templates and downstream artefacts consumed by RM-typed data and AQL. It is an AM-tier specification and is independent of any particular openEHR RM release, coordinated via the `rm_release` header attribute.

## When to Read the Full Spec
Consult the full specification when implementing an ADL 2 parser or serialiser, reconciling id-code versus at-code variants across ADL 2.0–2.4, resolving differential specialisation merge semantics, authoring slot `include`/`exclude` expressions, handling ODIN escape and Unicode rules, or mapping precise path semantics for tooling that bridges AOM2, OPT2, and AQL.

## References
- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/ADL2.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/ADL2.md
- Related digests: specs/am2-AOM2, specs/am2-OPT2, specs/am-ADL1.4
