# openEHR EHR Information Model — Digest

**Scope:** The top-level EHR container and the composition/entry hierarchy that holds all clinical and administrative content.
**Component:** RM
**Document:** ehr
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/RM/development/ehr.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/development/ehr.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-common, openehr://guides/specs/rm-data_structures, openehr://guides/specs/rm-data_types
**Keywords:** EHR, COMPOSITION, ENTRY, OBSERVATION, EVALUATION, INSTRUCTION, ACTION, ISM, versioning, CONTRIBUTION

---

## Purpose

Defines the `EHR` object — the record-level container for one subject — and the clinical content packages (`Composition`, `Content`, `Navigation`, `Entry`) from which all committed clinical and administrative material is built. Establishes the distinction between the clinical record and the demographic identity of its subject, and specifies the structural framework that supports two-level modelling.

## Scope

- In: EHR container and versioned parts (`EHR_ACCESS`, `EHR_STATUS`, compositions, folders, tags); composition categories (`event`, `persistent`, `episodic`) and event context; section navigation; entry subtypes and the Instruction State Machine (ISM); EHR/demographic separation; subject reference via `PARTY_PROXY`.
- Out: data types (see `RM/data_types`), archetype formalism (see `AM`), terminology binding (see `TERM`), query (`QUERY`), serialisation (`ITS-*`), and change-control mechanics (defined in `RM/common`, used here).

## Key Classes / Constructs

- `EHR` — root container; holds `ehr_id`, `system_id`, `time_created`, and references to versioned parts.
- `EHR_STATUS` — subject link (`PARTY_SELF`, optional `external_ref`), `is_queryable` / `is_modifiable` flags, extension slot.
- `EHR_ACCESS` — pluggable access-control settings.
- `VERSIONED_COMPOSITION` — versioned container for one composition's history.
- `COMPOSITION` — atomic clinical/administrative document with `category` (event/persistent/episodic), `composer`, `language`, `territory`, `content`, optional `context`.
- `EVENT_CONTEXT` — encounter metadata: start/end time, setting, healthcare facility, participations.
- `SECTION` — navigation container grouping Entries.
- `ENTRY` and subtypes — `OBSERVATION`, `EVALUATION`, `INSTRUCTION`, `ACTION`, `ADMIN_ENTRY`.
- `ISM_TRANSITION` / `INSTRUCTION_DETAILS` — workflow state on Instructions/Actions; carries terminology codes for `current_state` (INITIAL, PLANNED, SCHEDULED, ACTIVE, SUSPENDED, COMPLETED, ABORTED, CANCELLED, EXPIRED).
- `FOLDER` — hierarchical index; _references_ compositions by episode/problem rather than containing them.

## Relations to Other Specs

- Depends on: `RM/common` (change control: `VERSIONED_OBJECT`, `CONTRIBUTION`, `AUDIT_DETAILS`, `ATTESTATION`, `PARTY_PROXY` subtypes; `LOCATABLE` / `PATHABLE`), `RM/data_structures` (content-level structures like `ITEM_TREE`, `HISTORY`), `RM/data_types` (values inside Entries), `RM/support` (identifiers, terminology interface).
- Consumed by: `AM` (archetypes constrain these classes), `QUERY` (AQL paths traverse the `COMPOSITION` tree), `SM` (platform services expose EHR lifecycle operations), `ITS-REST` (wire-format mapping).

## Architectural Placement

The EHR IM is the anchor point of the Reference Model: every committed clinical record is a `COMPOSITION` within an `EHR`, and the entry subtypes encode a clinical-investigation ontology (observe → evaluate → plan → act) that upstream archetypes specialise. It sits directly above the shared change-control and data-structure packages, and below the archetype layer.

## When to Read the Full Spec

Reach for the full spec when implementing the ISM state machine (valid transitions, terminology codes), change-control lifecycle (`ORIGINAL_VERSION` vs `IMPORTED_VERSION`, `lifecycle_state`, 3-part `HIER_OBJECT_ID`), folder/composition reference semantics, or the detailed EHR/demographic privacy options (no `external_ref`; `external_ref` in `EHR_STATUS` only; `external_ref` on every `PARTY_SELF`).

## References

- Full spec (HTML): https://specifications.openehr.org/releases/RM/development/ehr.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/development/ehr.md
- Related digests: specs/rm-common, specs/rm-data_structures, specs/rm-data_types, specs/rm-support, specs/rm-demographic
