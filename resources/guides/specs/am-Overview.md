# Archetype Technology Overview — Digest

**Scope:** AM component tour; entry point for the archetype, template, and OPT formalism of the ADL 2 generation (Identification, ADL 2, AOM 2, OPT 2, AQL).
**Component:** AM
**Document:** Overview
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/Overview.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/Overview.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/am-ADL1.4, openehr://guides/specs/am-AOM1.4, openehr://guides/specs/am2-ADL2, openehr://guides/specs/am2-AOM2, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am-Identification
**Keywords:** AM, archetype, template, OPT, ADL, AOM, two-level modelling, technology overview

---

## Purpose
The Archetype Technology Overview is the introductory map of the openEHR Archetype Model (AM) component. It situates archetypes as a mechanism for adding domain semantics to information models while avoiding endless growth and maintenance of the Reference Model itself. It motivates the two-level modelling approach by contrasting the small, invariant Reference Model (50–100 classes) with the astronomical cardinality of clinical observations, analytes, and terminology concepts that domain content must express. It also orients the reader across the AM's sibling documents and signposts the syntactic (ADL), semantic (AOM), and deployment (OPT) layers that follow.

## Scope
- In: conceptual definition of archetypes, templates, operational templates, specialisation, and terminology binding; positioning of the AM stack relative to the Reference Model and instance data; enumeration of downstream AM specifications; governance, library, and repository concepts; archetype HRID and namespace rationale.
- Out: normative ADL syntax (ADL 2), the abstract object model (AOM 2), operational template semantics (OPT 2), the legacy ADL 1.4 / AOM 1.4 documents (not discussed by the Overview), path grammar and AQL, RM class content, and concrete XML/JSON/ODIN serialisations.

## Key Classes / Constructs
- Archetype — topic/theme-based domain content model expressed as constraints over a Reference Model class (e.g. an Apgar result archetype).
- Template — use-case-specific assembly of archetypes that composes, narrows constraints, removes unused elements, and sets defaults.
- Operational Template (OPT) — fully flattened and substituted form of a template, the runtime artefact driving validation and code generation.
- Specialisation, differential form, and flat form — child archetypes express only deltas from the parent's flat form; the lineage's flat forms are what data must conform to.
- Archetype slot, `use_node`, and fill mechanics — slots declare extension points constrained by archetype-ID assertions; `use_node` reuses an internal node; template composition fills slots with concrete archetypes.
- Compilation — a compiler parses, validates and flattens whole specialisation lineages top-down (parents and suppliers first), then generates operational templates from templates.
- Archetype Object Model (AOM) — syntax-independent formal expression of archetype semantics, serialised by ADL and consumed by tooling.
- Terminology integration — bindings at archetype and template level link node identifiers to external terminology concepts and value sets, anchoring the semantic stack.
- Archetype library, repository, and custodian namespace — governance constructs supporting coexisting publishers via HRIDs of the form `namespace::RM-class.semantic-entity.vN`.

## Relations to Other Specs
- Identification — archetype and template HRID grammar, versioning, referencing, and lifecycle.
- ADL 2 — the normative abstract syntax for archetypes, templates and terminology binding.
- AOM 2 — the normative, syntax-independent structural model of archetypes and templates.
- OPT 2 — operational template semantics. ADL 1.4 OPTs have no AM specification; their legacy XML schema sits in ITS-XML.
- ADL 1.4 and AOM 1.4 — legacy AM documents the Overview does not cover; see their own digests.
- Cross-component: BASE foundation and base types underpin primitive constraints; QUERY/AQL reuses archetype paths; ITS-XML and ITS-JSON provide XML/JSON schemas; RM specifications supply the classes archetypes constrain.

## Architectural Placement
The AM sits between the Reference Model (domain-invariant data carriers) and instance data, introducing two intermediate layers: archetypes as reusable domain-topic constraints and templates/OPTs as use-case assemblies. Within AM, the Overview is the tier-zero document; ADL and AOM specifications define syntax and semantics at the authoring tier, and OPT defines the deployment tier consumed by EHR platforms, AQL engines, form generators, and message mappers.

## When to Read the Full Spec
Read the full Overview when you need the canonical rationale for two-level modelling, when explaining the archetype compilation toolchain (validate, flatten, generate OPTs), when explaining archetype governance and namespacing to stakeholders, or when locating the correct sibling specification for a deeper question about syntax, object model, identification, or operational templates. For implementation-level detail — parser rules, flattening algorithms, path grammar, or class-level semantics — jump directly to the relevant ADL, AOM, OPT, or Identification digest rather than this tour.

## References
- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/Overview.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/Overview.md
- Related digests: specs/am-ADL1.4, specs/am-AOM1.4, specs/am2-ADL2, specs/am2-AOM2, specs/am2-OPT2, specs/am-Identification
