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

The "read me first" document of the openEHR specification program, and the shortest path from "I've never heard of openEHR" to "I understand the component layout." It orients newcomers — standards bodies, academics, open-source communities, and vendors — to how the pieces fit together: the Reference Model, Archetype Model, Service Model, terminology integration, ITS bindings, and the conformance framework. It frames openEHR's core commitment: separate generic, invariant information structure (software-implemented) from variable domain content (modelled at runtime by clinicians and subject matter experts), with real-world phenomena captured in external terminologies. It also positions the program against ISO RM/ODP viewpoints, ISO 13606, and HL7 CDA, and sketches the deployment topologies these separations enable.

## Scope

- In: overall specification program structure; multi-level (two-level in practice) modelling paradigm; the component map (BASE, LANG, RM, AM, SM, QUERY, TERM, ITS, CNF, PROC, CDS); global semantic principles (time, language, identification, versioning, privacy); deployment tiers from persistence to presentation; separation-of-concerns principles (ontological separation, separation of responsibilities, separation of viewpoints); relationship to adjacent standards (ISO 13606, ISO 18308, HL7 CDA, ISO 13940, ISO RM/ODP).
- Out: normative class definitions (see individual RM/AM/SM docs); wire formats (`ITS-REST`, `ITS-XML`, `ITS-JSON`); AQL grammar (`QUERY`); conformance criteria (`CNF`); terminology content (`TERM`); archetype authoring rules (AM).

## Key Classes / Constructs

This is an overview, so the section is *concepts*, not classes:

- **Two-level (multi-level) modelling** — only the stable Reference Model is implemented in software; archetypes and templates express domain semantics at runtime, dramatically shrinking deployed codebases.
- **Reference Model (RM)** — invariant information structure: `COMPOSITION`, `SECTION`, entry types, data types, demographics, versioning primitives.
- **Archetype** — reusable, maximal-dataset constraint on RM classes, authored in ADL 1.4 or ADL 2; an epistemological artefact describing a single clinical concept.
- **Template & OPT** — use-case-specific composition of archetypes producing an Operational Template: a flattened, deployment-ready artefact driving forms, schemas, and messages.
- **Clinical-investigation ontology** — `OBSERVATION` (facts), `EVALUATION` (opinions/diagnoses), `INSTRUCTION` (orders/plans), `ACTION` (execution record), `ADMIN_ENTRY`; mirrors the observe-hypothesise-prescribe-act loop.
- **Instruction State Machine (ISM)** — standard lifecycle for orders and their resulting actions, enabling process tracking across systems.
- **Demographic separation** — EHR content carries no identity by default; PARTY references resolve through an external demographic/identity service, with configurable pseudonymity levels.
- **Deployment tiers** — persistence → back-end services (EHR, demographic, terminology, definitions, query) → virtual EHR → application logic → presentation; spans desktop EMRs, regional shared-care, and national summary EHRs.
- **Component map** — BASE, LANG (BMM, ODIN, EL), RM, AM, SM, QUERY, TERM, PROC, CDS, ITS, CNF as the canonical program partition.
- **Versioned change control** — `CONTRIBUTION` groups atomic, additive change-sets across versioned objects; no destructive edits.
- **Terminology integration** — archetypes act as the semantic gateway to SNOMED CT, LOINC, ICD, ICPC, and ISO/IETF vocabularies via an abstract Terminology Service.
- **Ontological separation** — information models, domain-content models, and reality ontologies (terminologies) are kept distinct, each evolving on its own cadence.

## Relations to Other Specs

- Depends on: `BASE/foundation_types`, `BASE/base_types` (primitives, identifiers, assumed_types used throughout the tour).
- Introduces: `RM/*` (EHR, common, data_structures, data_types, demographic, support, integration), `AM/*` (ADL 1.4, ADL 2, AOM, OPT), `SM/openehr_platform` (EHR, definitions, query, terminology, admin services), `QUERY/AQL`, `TERM` (openEHR Terminology + external bindings), `LANG/BMM`, `LANG/ODIN`, `LANG/Expression_Language`, `ITS-REST`, `ITS-JSON`, `ITS-XML`, `CNF`, `PROC/Task_Planning`, `CDS/GDL`.
- External profiles / alignments: ISO 13606, ISO 18308, ISO 13940 (continuity of care), HL7 CDA, ISO RM/ODP five viewpoints.

## Architectural Placement

This is the meta-document that positions every other openEHR spec: it does not define a single class but provides the map by which readers locate the right component for any given concern — RM for information structure, AM for archetype formalism, SM for abstract service contracts, QUERY for AQL, TERM for terminology integration, ITS for wire bindings, CNF for conformance. It sits at the apex of BASE and establishes the vocabulary (multi-level modelling, separation of viewpoints, separation of responsibilities) used across the rest of the program.

## When to Read the Full Spec

Read this document first when onboarding to openEHR — before any RM, AM, or SM deep dive. Also return to it when positioning openEHR against ISO 13606 / HL7 FHIR / CDA for a stakeholder, when designing a deployment topology across provider, regional, or national tiers, when justifying the two-level modelling split and the EHR/demographic split to an architecture review, when scoping a vendor-neutral procurement, or when you need the canonical citation for the ontological, responsibility, and viewpoint separations that underpin openEHR's long-term interoperability claim.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/BASE/development/architecture_overview.html
- Full spec (Markdown): https://specifications.openehr.org/releases/BASE/development/architecture_overview.md
- Related digests: specs/rm-ehr, specs/sm-openehr_platform, specs/rm-common
