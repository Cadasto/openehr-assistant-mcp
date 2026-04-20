# openEHR Platform Service Model — Digest

**Scope:** Abstract service interfaces (EHR, Demographic, Definitions, Query, Terminology, Message, Admin, …) that define the nominal "native API" of an openEHR platform.
**Component:** SM
**Document:** openehr_platform
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/SM/development/openehr_platform.html
**Markdown URL:** https://specifications.openehr.org/releases/SM/development/openehr_platform.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-demographic
**Keywords:** SM, platform, service model, I_EHR_SERVICE, I_QUERY_SERVICE, I_DEFINITION_ADL2, UPDATE_VERSION, CALL_STATUS, REST

---

## Purpose

Defines the coarse-grained service interfaces that any openEHR platform implementation should expose, in a protocol-independent form. The model specifies operations, pre/post-conditions, status codes, list-handling, and version-update semantics for EHR, demographic, definition, query, terminology, message, and administrative services. Concrete protocol bindings (such as `ITS-REST`) are adapters over this nominal "native API".

## Scope

- In: abstract interface specifications for the core platform services; shared call-status mechanism; cursor-style list handling; `UPDATE_VERSION<T>` update semantics for versioned resources; 5-tier deployment architecture; the minimal-implementation profile.
- Out: wire formats and HTTP mappings (those live in `ITS-REST`, `ITS-XML`, `ITS-JSON`); the data models being served (`RM/*`, archetype formalism in `AM`); terminology content itself (`TERM`); conformance rules (`CNF`).

## Key Classes / Constructs

- `I_EHR_SERVICE` — create / retrieve EHRs; existence checks by `ehr_id` or subject.
- `I_EHR` — per-EHR access to `status`, `directory`, `compositions`, `contributions`.
- `I_EHR_STATUS` — get/update `EHR_STATUS`; toggle `is_queryable` / `is_modifiable`.
- `I_EHR_DIRECTORY` — versioned folder tree CRUD.
- `I_EHR_COMPOSITION` — versioned `COMPOSITION` CRUD, including get-at-time and get-at-version.
- `I_EHR_CONTRIBUTION` — commit a batch of updates as a single `CONTRIBUTION`.
- `I_DEMOGRAPHIC_SERVICE` — CRUD for parties and relationships; uses `UV_PARTY`, `UV_PARTY_RELATIONSHIP`.
- `I_EHR_INDEX` — cross-reference between `ehr_id` and demographic subject identifier(s).
- `I_QUERY_SERVICE` — execute registered or ad-hoc AQL queries; returns `RESULT_SET`.
- `I_DEFINITION_ADL2` / `I_DEFINITION_ADL14` / `I_DEFINITION_QUERY` — upload, retrieve, validate, list, and delete archetypes, templates, OPTs, and stored queries.
- `I_TERMINOLOGY_SERVICE` — terminology lookup, subsumption, value-set validation.
- `I_EHR_EXTRACT_SERVICE` / `I_TDD_SERVICE` — EHR Extract and Template Data Document import/export.
- `I_SUBJECT_PROXY_SERVICE` — unified subject-focused view across heterogeneous back-ends (openEHR, FHIR, HL7v2).
- `I_ADMIN_SERVICE` / `I_ADMIN_ARCHIVE` / `I_ADMIN_DUMP_LOAD` — counts, physical delete, archive, export/load.
- `UPDATE_VERSION<T>` — carries `preceding_version_uid`, `lifecycle_state`, optional `attestations`, payload `data`, and `UPDATE_AUDIT`; concrete subtypes `UV_COMPOSITION`, `UV_FOLDER`, `UV_PARTY`.
- `CALL_STATUS` — cross-interface status type with codes such as `success`, `auth_failure`, `precondition_violation`, `version_mismatch`, `ehr_id_does_not_exist`.

## Relations to Other Specs

- Depends on: `RM/ehr`, `RM/demographic`, `RM/common` (change control, `VERSIONED_OBJECT`, `ORIGINAL_VERSION`), `RM/data_types`, `AM` (definition artefacts handled by the Definitions service), `QUERY` (AQL executed by `I_QUERY_SERVICE`), `TERM`.
- Consumed by: `ITS-REST` (concrete HTTP binding), `ITS-XML` / `ITS-JSON` (serialisation), `CNF` (conformance assertions against these interfaces), implementer platforms.

## Architectural Placement

The Platform Service Model sits between the Reference Model plus archetype formalism and the wire-level ITS specifications: it is the API contract of an openEHR system. The associated 5-tier deployment architecture (persistence → back-end services → virtual EHR → application logic → presentation) orients implementers, and the minimal-system profile (EHR repository + archetype repository + optional terminology + demographic/identity service) bounds what "openEHR compliant" means at the platform level.

## When to Read the Full Spec

Consult the full spec when implementing a platform adapter, when mapping REST (or other protocol) endpoints to the native operations, when handling non-`success` `CALL_STATUS` codes, when composing contributions from multiple `UPDATE_VERSION<T>` payloads, when implementing the `I_SUBJECT_PROXY_SERVICE` federation pattern across FHIR/HL7v2 back-ends, or when reconciling this model's abstract operations with the concrete binding in `ITS-REST`.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/SM/development/openehr_platform.html
- Full spec (Markdown): https://specifications.openehr.org/releases/SM/development/openehr_platform.md
- Related digests: specs/rm-ehr, specs/rm-demographic, specs/rm-common
