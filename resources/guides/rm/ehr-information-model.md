# openEHR EHR Information Model

**Scope:** Architecture-level summary of the EHR IM — structure, composition types, entry types, versioning, change control, and time semantics
**Related:** openehr://guides/templates/principles, openehr://guides/aql/principles
**Keywords:** EHR, RM, COMPOSITION, ENTRY, versioning, CONTRIBUTION, AUDIT, LOCATABLE, time

---

## EHR Structure

An EHR for one subject consists of:

| Part | Description |
|------|-------------|
| **Root EHR object** | Entry point; holds `ehr_id` (stable UUID), `system_id`, `time_created`, references to versioned parts. |
| **EHR_ACCESS** (versioned) | Access control settings; supports pluggable security schemes. |
| **EHR_STATUS** (versioned) | Subject link (`PARTY_SELF`, optional `external_ref`), `is_queryable`/`is_modifiable` flags, `other_details`. |
| **Compositions** (versioned) | Main clinical and administrative content; each in a versioned container. |
| **Folders** (versioned, optional) | Hierarchical structures that _reference_ (not contain) Compositions; for indexing by episode/problem. |
| **Tags** (optional) | Lightweight key/value annotations (`ITEM_TAG`) on any content item; queryable via AQL without re-versioning. |
| **Contributions** | Change-set records grouping Versions committed together in one user operation. |

---

## Composition Types

| Category | Semantics |
|----------|-----------|
| **event** | Records one healthcare event (encounter, test, procedure). Most common; accumulates over time. |
| **persistent** | Ongoing state (problem list, allergies). Single maintained instance, valid for patient lifetime. |
| **episodic** | Scoped to a bounded period of care (e.g. admission). Persistent-like but transitions to inactive when episode ends. |

Every Composition carries: `composer` (PARTY_PROXY), `language`, `territory`, `content` (Sections/Entries), optional `context` (EVENT_CONTEXT with event time, setting, facility, participations).

---

## Entry Types

Clinical content lives in Entry instances. Entry subtypes map to a clinical investigation ontology:

| Type | Role | Examples |
|------|------|---------|
| **OBSERVATION** | Observed phenomena; time-structured via HISTORY/EVENT | Blood pressure, lab result, patient-reported pain |
| **EVALUATION** | Opinions/assessments based on observations | Diagnosis, risk assessment, care plan, goal |
| **INSTRUCTION** | Actionable orders for future performance; ISM state machine | Medication order, referral, investigation request |
| **ACTION** | Records what was performed (ISM_TRANSITION state) | Drug administered, procedure done |
| **ADMIN_ENTRY** | Administrative logistics, not clinical content | Admission, appointment, discharge |

**Negation/status principle:** map statement types to Entry subtypes rather than using status flags. "No allergy" is an Evaluation (exclusion archetype), not a flag on an Observation.

### ISM State Machine

Instructions follow standard states: INITIAL, PLANNED, SCHEDULED, ACTIVE, SUSPENDED, COMPLETED / ABORTED / CANCELLED / EXPIRED (also POSTPONED). Enables querying "all active medications" or "planned interventions" via standardised ISM states.

---

## Versioning and Change Control

### Versioned Objects

Every mutable top-level item (Composition, EHR_STATUS, EHR_ACCESS, FOLDER) lives inside a **VERSIONED_OBJECT** with a stable uid. Each version is:

- **ORIGINAL_VERSION** — locally created; stores data, `lifecycle_state`, `preceding_version_uid`, `commit_audit`.
- **IMPORTED_VERSION** — wraps a received ORIGINAL_VERSION with its own audit.

**Lifecycle states** (openEHR Terminology): `complete`, `incomplete`, `deleted`, `inactive`, `abandoned`.

### Contributions

A **Contribution** groups one or more Version commits into a logical change-set (e.g. a GP encounter creating a Composition and updating the medication list). Atomic commit with shared audit.

### Version Identifiers

3-part UID: `{object_id :: creating_system_id :: version_tree_id}`. Supports distributed versioning, virtual version trees, and merge tracking via `other_input_version_uids`.

### Audit and Attestation

- **AUDIT_DETAILS** on every commit: `system_id`, `committer`, `time_committed`, `change_type` (creation/modification/amendment/deletion/attestation).
- **ATTESTATION** extends audit: `is_pending`, `reason`, `attested_view`, `proof` (digital signature, openPGP/RFC 4880), optional item-level path list.

---

## Time Semantics

| Layer | Attribute | Meaning |
|-------|-----------|---------|
| Observation time | `HISTORY.origin`, `EVENT.time` | When the phenomenon was true (sample time for labs) |
| Healthcare event time | `EVENT_CONTEXT.start_time` | When the clinical encounter occurred |
| Commit time | `AUDIT_DETAILS.time_committed` | When data were committed to the server (server-generated) |
| Domain-specific | Archetyped values within Entries | Date of onset, resolution, medication start, etc. |

For pathology, observation time means the **sample collection time**, not the laboratory processing time.

---

## EHR/Demographic Separation

The EHR contains **no identifying demographic information** by default:

- Subject is `PARTY_SELF` with optional `external_ref` to a demographic service.
- Three privacy levels: (a) no external_ref anywhere (maximally anonymous); (b) external_ref in EHR_STATUS only; (c) external_ref on every PARTY_SELF instance.
- The **EHR Index** service provides the cross-reference between `ehr_id` and demographic subject id when needed.

### PARTY_PROXY Subtypes (Common IM)

| Subtype | Use |
|---------|-----|
| **PARTY_SELF** | Subject of the EHR (patient); optional external_ref. |
| **PARTY_IDENTIFIED** | Other parties (clinician, organisation); human-readable name/identifiers, optional external_ref. |
| **PARTY_RELATED** | Like PARTY_IDENTIFIED but also records relationship to subject (e.g. mother, GP). |

---

## Cross-Cutting Patterns

- **LOCATABLE** — base class; provides `archetype_node_id`, `archetype_details`, `name`, `uid`, `feeder_audit`, `links`.
- **PATHABLE** — path navigation: `item_at_path()`, `items_at_path()`, `path_exists()`.
- **FEEDER_AUDIT** — records provenance of imported/legacy data (originating system audit, identifiers, optional original_content).
- **ITEM_TAG** — key + optional value + target uid + optional target_path; queryable annotations without re-versioning.

---

## References

- EHR Information Model: <https://specifications.openehr.org/releases/RM/development/ehr.html>
- Common Information Model: <https://specifications.openehr.org/releases/RM/development/common.html>
- Architecture Overview: <https://specifications.openehr.org/releases/BASE/development/architecture_overview.html>
