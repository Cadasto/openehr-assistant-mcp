# Operational Template 2 (OPT 2) — Digest

**Scope:** Compiled, deployment-ready flattened artefact produced from an ADL2 template plus its referenced archetypes and overlays for use by operational EHR systems.
**Component:** AM
**Document:** OPT2
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/OPT2.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/OPT2.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/am2-ADL2, openehr://guides/specs/am2-AOM2
**Keywords:** OPT, OPT 2, operational template, flattening, compiled artefact, AOM 2, ADL 2, template overlay, component terminologies, slot filler

---

## Purpose

Defines the Operational Template 2 (OPT 2) — the single, inheritance-flattened, slot-resolved, reference-expanded artefact that a compiler produces from an ADL 2 top-level template, its referenced archetypes, and its template overlays. The OPT is the canonical executable form consumed by production EHR runtimes and by downstream generators that emit schemas, APIs, data-binding classes, UI forms, and validation rules. It guarantees that what is deployed has been validated once, is free of unresolved references, and can be serialised into multiple machine-readable formats without re-running a source compiler.

## Scope

- In: OPT compilation inputs (top-level template, referenced archetypes, template overlays), the flattening transformations applied, structural rules (no specialisation parent, resolved archetype references with versions, no sibling-order markers, inlined `use_node` copies, resolved or closed slots, removal of `existence matches {0}` nodes), the `component_terminologies` aggregate, raw-vs-profiled OPT distinction, and the `.opt`, `.optx`, `.optj` serialisation extensions.
- Out: ADL 2 source syntax (`AM/ADL2`), the object model the OPT conforms to (`AM/AOM2`), authoring-time template semantics (`AM/OPT2` is the output, not the authoring model), legacy OPT 1.4 XML-schema format, terminology-service protocols, runtime validation algorithms against data instances, and any specific downstream artefact (JSON Schema, XSD, OpenAPI) that a tool may derive from an OPT.

## Key Classes / Constructs

- `OPERATIONAL_TEMPLATE` — AOM 2 root class of the flattened artefact; a self-contained archetype with no parent and fully resolved references.
- `TEMPLATE` — authoring-time top-level artefact whose compilation (with its referenced archetypes and overlays) yields the OPT.
- `TEMPLATE_OVERLAY` — specialisation fragment inside a template that further constrains a referenced archetype; merged into the OPT during flattening.
- Flattening process — expands `use_node` references, resolves or closes slots, substitutes slot fillers, drops zero-existence nodes, removes sibling-order markers, and consolidates overlays.
- Constraint and terminology inheritance — deepest specialisation identifiers surface at the root; all referenced archetype `terminology` sections are merged into `component_terminologies`.
- Raw vs profiled OPT — raw OPT carries every language, binding, and annotation; profiled OPTs are derived by selectively stripping languages, bindings, annotations, or substituting terminologies for a deployment target.

## Relations to Other Specs

- Depends on: `AM/AOM2` (object-model classes the OPT is an instance of), `AM/ADL2` (source syntax of templates, overlays and archetypes being compiled), `AM/aom2_profile` for constraint semantics, and `RM/common` plus the domain RM packages whose classes are constrained by the contained archetype nodes.
- Consumed by: `ITS-*` implementation technology specifications that serialise OPTs (`.optx` XML, `.optj` JSON), `SM` platform services that validate and store committed content against a deployed OPT, `QUERY/AQL` tooling that resolves paths and archetype ids against the operational set, and downstream generators producing schemas, UI forms, and data-binding classes.

## Architectural Placement

Sits at the end of the AM 2 toolchain: ADL 2 source (archetypes + template + overlays) is parsed and validated into AOM 2 in-memory structures, and the OPT is the compiled output of that pipeline. It is therefore the hand-off point between design-time knowledge assets and run-time openEHR platforms, and the stable input contract for every artefact-transformation tool downstream of the compiler.

## When to Read the Full Spec

Read the full document when implementing an OPT compiler or serialiser, defining a profiled-OPT build step (language/binding stripping, terminology substitution), deciding how to represent closed slots and removed nodes in your OPT persistence, or diagnosing discrepancies between an ADL 2 source set and its operational flattened form — in particular around `use_node` expansion, slot-filler inlining, and `component_terminologies` aggregation.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/OPT2.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/OPT2.md
- Related digests: specs/am2-ADL2, specs/am2-AOM2
