# openEHR Platform Service Model — Digest

**Scope:** Abstract service interfaces (EHR, Demographic, Definitions, Query, Terminology, Message, Admin, …) that define the nominal "native API" of an openEHR platform.
**Component:** SM
**Document:** openehr_platform
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/SM/development/openehr_platform.html
**Markdown URL:** https://specifications.openehr.org/releases/SM/development/openehr_platform.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-demographic
**Keywords:** SM, platform, service model, I_EHR_SERVICE, I_DEFINITION_ADL2, I_SUBJECT_PROXY_SERVICE, UPDATE_VERSION, CALL_STATUS, RESULT_SET, REST

---

## Purpose

Defines the coarse-grained service interfaces that any openEHR platform implementation should expose, in a protocol-independent form. The model specifies operations, pre/post-conditions, status codes, list-handling, and version-update semantics for EHR, demographic, definition, query, terminology, message, and administrative services. Concrete protocol bindings (such as `ITS-REST`) are adapters over this nominal "native API".

## Scope

- In: the logical platform architecture with standardised component naming (Definition, EHR, Demographic, EHR Index, Query, Message, Subject Proxy, Terminology, Admin services); abstract interface call specifications in a functional, nearly-stateless style; shared call-status mechanism (`CALL_STATUS` / `CALL_STATUS_TYPE`); list handling; `UPDATE_VERSION<T>` update semantics for versioned resources; registered-query naming and formalism conventions.
- Out: wire formats and HTTP mappings (those live in `ITS-REST`, `ITS-XML`, `ITS-JSON`); the data models being served (`RM/*`, archetype formalism in `AM`); terminology content itself (`TERM`); conformance rules (`CNF`).

## Key Classes / Constructs

- EHR Service (`I_EHR_SERVICE`) — versioned persistence of EHRs: creation, retrieval, and committal of EHR content.
- Definition Service (`I_DEFINITION_ADL2` / `I_DEFINITION_ADL14`) — upload, retrieve, validate, and list archetypes, templates, and OPTs for both ADL generations, plus registered (stored) AQL queries with reverse-domain qualified names.
- Demographic Service — versioned persistence for demographic data (`DEMOGRAPHIC_SERVICE` component).
- EHR Index Service — records associations of subject identifiers with EHR ids (`EHR_INDEX` component), enabling privacy-preserving EHR persistence keyed only by `ehr_id`.
- Query Service — executes registered or ad-hoc AQL queries; successful execution returns a `RESULT_SET` with column metadata and `RESULT_SET_ROW` rows, windowed via `item_offset` / `items_to_fetch`.
- Message Service — message import/export supporting multiple formats, including EHR Extracts and documents.
- Subject Proxy Service (`I_SUBJECT_PROXY_SERVICE`) — unified subject-focused data access across heterogeneous back-ends, with subject variable naming, data-set and binding specification (`I_DATA_BINDING`).
- Terminology Service — terminology and code-set access for the platform.
- Admin Service — administrative facilities (e.g. back-up) across all installed services.
- `UPDATE_VERSION<T>` — client-side commit structure carrying caller-supplied metadata and an `UPDATE_AUDIT` (a partial `AUDIT_DETAILS` whose `time_committed` and `system_id` are server-generated); concrete subtypes per storable type, e.g. `UV_COMPOSITION` for `COMPOSITION`.
- `CALL_STATUS` — cross-interface status object whose `code` comes from the `CALL_STATUS_TYPE` enumeration (extensible per service); failures are inspected via `last_call_failed()` / `last_call_status()`.

## Relations to Other Specs

- Depends on: `RM/ehr`, `RM/demographic`, `RM/common` (change control, `VERSIONED_OBJECT`, `ORIGINAL_VERSION`), `RM/data_types`, `AM` (definition artefacts handled by the Definitions service), `QUERY` (AQL executed by `I_QUERY_SERVICE`), `TERM`.
- Consumed by: `ITS-REST` (concrete HTTP binding), `ITS-XML` / `ITS-JSON` (serialisation), `CNF` (conformance assertions against these interfaces), implementer platforms.

## Architectural Placement

The Platform Service Model sits between the Reference Model plus archetype formalism and the wire-level ITS specifications: it is the API contract of an openEHR system. It deliberately defines a *formal equivalent* of any real product architecture — standardised component names and logical interface semantics — so that platform procurers, implementers, and conformance assessors can refer unambiguously to the "EHR service", "Admin service", etc., however a vendor actually organises its internals.

## When to Read the Full Spec

Consult the full spec when implementing a platform adapter, when mapping REST (or other protocol) endpoints to the native operations, when handling failure semantics via `last_call_failed()` and `CALL_STATUS` codes, when composing commits from `UPDATE_VERSION<T>` payloads, when implementing the Subject Proxy Service federation pattern (subject variables, data sets, bindings) across heterogeneous back-ends, or when reconciling this model's abstract operations with the concrete binding in `ITS-REST`.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/SM/development/openehr_platform.html
- Full spec (Markdown): https://specifications.openehr.org/releases/SM/development/openehr_platform.md
- Related digests: specs/rm-ehr, specs/rm-demographic, specs/rm-common
