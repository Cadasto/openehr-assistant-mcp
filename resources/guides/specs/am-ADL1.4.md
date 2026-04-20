# Archetype Definition Language 1.4 (ADL 1.4) — Digest

**Scope:** Legacy ADL syntax used by the archetypes currently held in CKM and still in production use.
**Component:** AM
**Document:** ADL1.4
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/ADL1.4.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/ADL1.4.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/am-AOM1.4, openehr://guides/specs/am2-ADL2
**Keywords:** ADL, ADL 1.4, archetype syntax, legacy, cADL, dADL, slot, specialisation

---

## Purpose

Defines the concrete, human-readable textual syntax used to serialise openEHR (and ISO 13606-2) archetypes in the 1.4 generation of the Archetype Model. It specifies the two embedded sublanguages — cADL for constraints and dADL for instance-style data — together with the outer archetype container (header, language, description, definition, ontology, revision history) so that archetypes can be authored, exchanged, diff-ed, and parsed deterministically across tooling. ADL 1.4 is the surface form that underpins the vast majority of archetypes currently published on CKM and consumed by legacy template/OPT pipelines.

## Scope

- In: outer archetype structure (header, identifier, `specialise`, language, description, definition, ontology, revision history); cADL constraint syntax (`matches`/`∈`, `occurrences`, `existence`, `cardinality`, interval and regex primitives, leaf C_PRIMITIVE constraints, `use_node` internal references, `archetype_slot` including/excluding expressions); dADL serialisation of primitive, interval, container, coded-term, and typed-object values; path syntax compatible with XPath; term and constraint code conventions; ontology section layout (`term_definitions`, `constraint_definitions`, `term_bindings`, `constraint_bindings`).
- Out: the abstract Archetype Object Model (see `AM/AOM1.4`); the later ADL 2 / AOM 2 family (see `AM2/ADL2`); operational template and OPT formats; runtime archetype validation rules beyond syntactic well-formedness; reference-model class definitions being constrained (see `RM/*`); archetype governance, identification, and lifecycle policy.

## Key Classes / Constructs

- `archetype` header — identifier, ADL version, optional `specialise` clause and concept code anchoring the artefact.
- `definition` section (cADL block) — root `C_COMPLEX_OBJECT` tree constraining an RM class, using `matches { … }` nesting over attributes and child objects.
- `ontology` section (dADL block) — language-indexed `term_definitions`, `constraint_definitions`, and external `term_bindings` / `constraint_bindings` carrying the natural-language and terminology layer.
- `description` and `language` sections (dADL blocks) — authored-resource metadata, original language, translations, and lifecycle state.
- `ARCHETYPE_SLOT` — named extension point whose `include`/`exclude` assertions match archetype identifiers by regex, enabling template composition and reuse.
- Specialisation path — `specialise` clause plus dotted node codes (e.g. `at0001.1.2`) expressing child archetypes that refine a parent's constraints.
- `cADL` primitive constraints — `C_STRING`, `C_INTEGER`, `C_REAL`, `C_DATE_TIME`, `C_BOOLEAN`, `C_DV_*` wrappers, intervals (`|0..5|`), assumed values, and regex literals.
- `dADL` value syntax — attribute/value pairs in `<…>` with containers keyed by `[key]`, coded terms (`[terminology::code]`), and optional type tags `(TypeName)`, consumed by both the ontology section and instance serialisation.

## Relations to Other Specs

- Depends on: `AM/AOM1.4` (ADL 1.4 is a serialisation of that abstract model), `RM/common` (legacy `AUTHORED_RESOURCE` descriptors surfaced in `description`), `RM/support` (archetype identifiers, terminology identifiers), and the RM packages whose classes the `definition` section constrains.
- Consumed by: archetype repositories and CKM mirrors, ADL 1.4 parsers/validators in AWB and Archie, OPT 1.4 template builders, and migration tooling that lifts 1.4 artefacts into ADL 2 (`AM2/ADL2`) form.

## Architectural Placement

Sits at the syntactic boundary of the Archetype Model layer: above the reference-model classes it constrains, below template/OPT formalisms that compose multiple archetypes, and alongside AOM 1.4 which provides the object-level semantics ADL 1.4 text serialises.

## When to Read the Full Spec

Open the full specification when writing or fixing a grammar-level parser, when disambiguating edge cases in cardinality/existence/occurrences interaction, when resolving path or node-code collisions during specialisation, or when mapping a 1.4 artefact to ADL 2 and needing the canonical syntactic reference rather than the AOM-level semantics.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/ADL1.4.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/ADL1.4.md
- Related digests: specs/am-AOM1.4, specs/am2-ADL2
