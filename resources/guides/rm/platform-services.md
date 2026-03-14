# openEHR Platform Services

**Scope:** Summary of the openEHR Platform Service Model — abstract service interfaces, call conventions, and deployment architecture
**Related:** openehr://guides/rm/ehr-information-model, openehr://guides/rm/demographic-model
**Keywords:** platform, service model, SM, API, EHR service, Definitions, Query, REST, interface

---

## Purpose

The Platform Service Model specifies **core platform components in a formal, abstract form**, independent of concrete APIs (REST, SOAP, etc.). Implementations expose these semantics through protocol adapters. The specification defines the nominal "native API" that any adapter reaches.

---

## Services Overview

| Service | Description |
|---------|-------------|
| **Definitions** | Upload and query definition artefacts: archetypes, templates, and registered queries. |
| **EHR** | Versioned persistence for EHRs (compositions, directory, status, contributions). |
| **Demographic** | Versioned persistence for demographic data (parties, relationships). |
| **EHR Index** | Cross-reference between EHR identifiers and demographic subject identifiers. |
| **Query** | Execution of AQL (and other formalisms) over EHR and demographic content. |
| **Terminology** | Access to terminologies and intentional value sets. |
| **Message** | Message import/export; EHR Extracts; Template Data Documents (TDD). |
| **System Log** | IHE ATNA-compliant system log. |
| **Subject Proxy** | Registration of subject-focused data sets providing a "proxy" view across back-ends. |
| **Admin** | Administrative operations (backup, dump/load, archive, physical delete). |

---

## Interface Conventions

### Call Types

- **Queries** — return a value; do not change state.
- **Commands (procedures)** — change state; do not return a computed result.

Specifications use pre-conditions, post-conditions, and exceptions. Implementations map these to REST verbs, RPC, etc.

### Call Status

All interfaces inherit a common status mechanism:

- `last_call_failed()` / `last_call_status()` returning **CALL_STATUS** (code, call name, meaning, message).
- Status codes: `success`, `auth_failure`, `precondition_violation`, `object_version_does_not_exist`, `versioned_object_does_not_exist`, `exception`, `ehr_id_does_not_exist`, `version_mismatch`, etc.

### List Handling

Variable-sized results support cursor-style access: `item_offset` (start position) and `items_to_fetch` (count, 0 = all).

### Version Update Semantics

Calls that create or update versioned objects use **UPDATE_VERSION\<T\>**:

- `preceding_version_uid` (except for first version)
- `lifecycle_state` (complete, incomplete, deleted)
- `attestations` (optional)
- `data` (the content, e.g. COMPOSITION)
- `audit` — UPDATE_AUDIT (change type, description, committer); server fills `time_committed` and `system_id`

Concrete bindings: **UV_COMPOSITION**, **UV_FOLDER**, **UV_PARTY**.

---

## Key Service Interfaces

### Definitions Service

- **I_DEFINITION_ADL2** — ADL2 artefacts (archetypes, templates, OPTs): upload, get, list, delete; validity checks.
- **I_DEFINITION_ADL14** — ADL 1.4 archetypes and OPTs with legacy identifiers.
- **I_DEFINITION_QUERY** — Store and manage registered queries; qualified names (`namespace::query_name` or `namespace::formalism::query_name`); list, delete, validate.

### EHR Service

- **I_EHR_SERVICE** — Create/get EHRs; check existence by ehr_id or subject.
- **I_EHR** — Per-EHR access to status, directory, compositions, contributions.
- **I_EHR_STATUS** — Get/update status; set/clear queryable and modifiable flags.
- **I_EHR_DIRECTORY** — Create/get/update/delete folder structure (versioned).
- **I_EHR_COMPOSITION** — Create, get (latest, at time, at version), update, delete compositions.
- **I_EHR_CONTRIBUTION** — Commit contributions (batch of UPDATE_VERSIONs); list/get by id.

### Query Service

- **I_QUERY_SERVICE** — Execute stored queries (by qualified name and optional version) or ad hoc queries.
- Parameters: execution spec, `row_offset`, `rows_to_fetch`, optional `ehr_ids`.
- Returns **RESULT_SET** (columns, rows, id, creation_time).

### Demographic Service

- **I_DEMOGRAPHIC_SERVICE** — Create party/relationship; per-party get/update/delete (at time or version).
- Uses UV_PARTY and UV_PARTY_RELATIONSHIP for updates.

### EHR Index Service

- **I_EHR_INDEX** — Associates EHR ids with subject identifiers (and optional status/location).
- Supports multiple subject ids per EHR and multiple EHRs per subject.

### Other Services

- **Terminology** (I_TERMINOLOGY_SERVICE) — List terminologies, get terms, subsumption, value-set validation.
- **Message** (I_EHR_EXTRACT_SERVICE, I_TDD_SERVICE) — EHR Extract export/import, Template Data Documents.
- **Subject Proxy** (I_SUBJECT_PROXY_SERVICE) — Unified "proxy" view across back-ends (openEHR, FHIR, HL7v2) for decision support.
- **Admin** (I_ADMIN_SERVICE, I_ADMIN_ARCHIVE, I_ADMIN_DUMP_LOAD) — Contribution/composition counts, physical delete, archive, export/load.

---

## Deployment Architecture

The Architecture Overview describes a **5-tier deployment**:

1. **Persistence** — storage layer.
2. **Back-end services** — EHR, demographics, terminology, archetypes, security.
3. **Virtual EHR** — middleware/APIs.
4. **Application logic** — domain-specific processing.
5. **Presentation** — user interfaces.

**Minimal openEHR system:** EHR repository, archetype repository, terminology (if used), and demographic/identity service. EHR and demographics are separated by design; the EHR Index links them when needed.

---

## References

- Platform Service Model: <https://specifications.openehr.org/releases/SM/development/openehr_platform.html>
- Architecture Overview: <https://specifications.openehr.org/releases/BASE/development/architecture_overview.html>
- Archetype Technology Overview: <https://specifications.openehr.org/releases/AM/development/Overview.html>
