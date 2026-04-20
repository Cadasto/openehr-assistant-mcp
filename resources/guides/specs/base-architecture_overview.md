# openEHR Architecture Overview — Digest

**Scope:** Cross-component tour of openEHR's architecture: specification program structure, multi-level modelling, global semantics, and deployment tiers.
**Component:** BASE
**Document:** architecture_overview
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/BASE/development/architecture_overview.html
**Markdown URL:** https://specifications.openehr.org/releases/BASE/development/architecture_overview.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/sm-openehr_platform
**Keywords:** BASE, architecture, two-level modelling, reference model, archetype, template, composition, platform, deployment, RM/ODP, specification program

---

## Purpose

The "read me first" document of the openEHR specification program. It orients newcomers — standards bodies, academics, open-source communities, and vendors — to how the pieces fit together: the Reference Model, Archetype Model, Service Model, terminology integration, ITS bindings, and the conformance framework. It frames openEHR's core commitment: separate generic information structure (software-implemented) from domain content (modelled at runtime by clinicians), and lay out the deployment topologies this enables.

## Scope

- In: overall specification program structure; multi-level (two-level) modelling paradigm; catalogue of components (BASE, LANG, RM, AM, SM, QUERY, TERM, ITS, CNF, PROC, CDS); global semantic principles (time, language, identification, versioning, privacy); deployment tiers; relationship to adjacent standards (ISO 13606, ISO 18308, HL7 CDA, ISO RM/ODP).
- Out: normative class definitions (see individual RM/AM/SM docs); wire formats (see `ITS-REST`, `ITS-XML`, `ITS-JSON`); AQL grammar (`QUERY`); conformance criteria (`CNF`); terminology content (`TERM`).

## Key Classes / Constructs

Recast as key architectural concepts (this is a tour, not a class-heavy spec):

- **Two-level modelling** — stable Reference Model in software; domain semantics expressed at runtime as archetypes and templates.
- **Reference Model (RM)** — information structure: `COMPOSITION`, `SECTION`, entry subtypes (`OBSERVATION`, `EVALUATION`, `INSTRUCTION`, `ACTION`, `ADMIN_ENTRY`), data types, demographics.
- **Archetype** — reusable maximal-dataset constraint on RM classes, expressed in ADL (1.4 or ADL 2).
- **Template** — use-case-specific composition of archetypes producing the operational data set (OPT).
- **Service Model (SM)** — abstract service interfaces for EHR, demographic, definitions, query, terminology, and admin operations.
- **Versioning & contributions** — all change is additive; `CONTRIBUTION` groups atomic change-sets across versioned objects.
- **Separation of EHR from demographics** — mandatory split for privacy; `EHR_ACCESS` governs policy; pseudonymity levels are configurable.
- **Deployment tiers** — persistence → back-end services → virtual EHR → application logic → presentation, spanning desktop EMRs, regional shared care, and national summary EHRs.

## Relations to Other Specs

- Depends on: `BASE/foundation_types`, `BASE/base_types` (primitive and identification types referenced throughout the tour).
- Introduces: `RM/*` (EHR, common, data_structures, data_types, demographic, support, integration), `AM/*` (ADL, AOM, OPT), `SM/openehr_platform`, `QUERY/AQL`, `TERM`, `LANG/BMM`, `ITS-REST`, `CNF`.
- External profiles / alignments: ISO 13606, ISO 18308, HL7 CDA, ISO RM/ODP five viewpoints.

## Architectural Placement

Sits at the apex of the BASE component as the program-wide overview. It does not define classes itself; instead it is the map that tells the reader which component document to open next for any given concern — RM for information structure, AM for archetype formalism, SM for service contracts, ITS for wire bindings, CNF for conformance.

## When to Read the Full Spec

Read the full document when onboarding to openEHR, when positioning openEHR against ISO 13606 / HL7 FHIR / CDA for a stakeholder, when designing a deployment topology across provider, regional, or national tiers, when justifying the two-level modelling split to an architecture review, or when you need the canonical citation for separation-of-viewpoints and separation-of-responsibilities principles.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/BASE/development/architecture_overview.html
- Full spec (Markdown): https://specifications.openehr.org/releases/BASE/development/architecture_overview.md
- Related digests: specs/rm-ehr, specs/sm-openehr_platform, specs/rm-common
