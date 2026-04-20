# openEHR Demographic Information Model — Digest

**Scope:** Generalised model of parties (people, organisations, groups, devices) and their roles, identities, contacts, and relationships.
**Component:** RM
**Document:** demographic
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/RM/development/demographic.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/development/demographic.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-common
**Keywords:** demographic, PARTY, ACTOR, PERSON, ORGANISATION, ROLE, CAPABILITY, PARTY_RELATIONSHIP, PARTY_IDENTITY, PMI

---

## Purpose

Defines the generic structures required to represent the real-world parties referenced from (or associated with) clinical records: persons, organisations, groups, agents/devices, and the roles they perform. The demographic model is designed to sit alongside the EHR as an independent service that can either stand alone or adapt an existing Patient Master Index (PMI).

## Scope

- In: party hierarchy (`ACTOR` / `ROLE`); party identities, contacts, and addresses; capabilities and credentials; directed relationships between parties; archetyping hooks on `details` attributes; versioning of parties via the shared change-control machinery; domain-neutral structural patterns.
- Out: the clinical record itself (`RM/ehr`); the change-control classes themselves (`RM/common`); data-type values inside archetyped structures (`RM/data_types`); service APIs (`SM`); identifier formats (`RM/support`).

## Key Classes / Constructs

- `PARTY` (abstract) — root of the demographic hierarchy; carries `uid`, `identities`, `contacts`, `relationships`, `reverse_relationships`, and archetyped `details`.
- `ACTOR` (abstract) — any real-world entity capable of taking on roles; carries `languages` and references to its `roles`.
- `PERSON` — human individuals (patients, clinicians, citizens).
- `ORGANISATION` — legal entities, healthcare providers, departments.
- `GROUP` — named collection of actors (e.g. care team).
- `AGENT` — devices, software systems, automated agents.
- `ROLE` — competency/function performed by an Actor; has its own identities and relationships; references its `performer` (ACTOR), holds `capabilities` and `time_validity`.
- `PARTY_IDENTITY` — self-declared or socially assigned names (legal name, alias, trading name); has `purpose` and archetyped `details`. (Government- or organisation-issued identifiers go in `PARTY.details`, not here.)
- `CONTACT` / `ADDRESS` — contact methods (phone, email, postal) with archetyped `details` and `time_validity`.
- `CAPABILITY` — professional qualifications and credentials held by a ROLE, with `time_validity`.
- `PARTY_RELATIONSHIP` — directed relationship (e.g. "patient of", "employed by"); `source`, `target`, archetyped `details`, optional `time_validity`.
- `VERSIONED_PARTY` — versioned container for a PARTY's history (same pattern as `VERSIONED_COMPOSITION`).

## Relations to Other Specs

- Depends on: `RM/common` (`VERSIONED_OBJECT`, `ORIGINAL_VERSION`, `AUDIT_DETAILS`, `CONTRIBUTION`, `LOCATABLE`, `PATHABLE`), `RM/data_types` (archetyped `details` attribute values), `RM/support` (identifiers, terminology interface).
- Consumed by: `RM/ehr` via `PARTY_PROXY` subtypes (`PARTY_SELF`, `PARTY_IDENTIFIED`, `PARTY_RELATED`) as the clinical-side reference mechanism; `SM` platform services that expose demographic CRUD; archetypes (`AM`) that constrain the `details` slots.

## Architectural Placement

The Demographic IM is the deliberately decoupled peer of the EHR IM: clinical content holds only lightweight `PARTY_PROXY` references so that the clinical record remains maximally subject-anonymous, while full demographic data live in a separate versioned service. The `EHR Index` maps `ehr_id` to demographic subject identifier when integration is needed.

## When to Read the Full Spec

Consult the full spec when implementing the `ACTOR` / `ROLE` split with shared vs role-specific identities, when modelling capability credentials and their temporal validity, when designing a PMI adapter (external-identifier placement in `PARTY.details` vs `PARTY_IDENTITY`), when serialising parties for EHR Extract inclusion, or when deciding which demographic attributes (age, sex, ethnicity) belong in the clinical record as Observations rather than in the demographic service.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/RM/development/demographic.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/development/demographic.md
- Related digests: specs/rm-ehr, specs/rm-common, specs/rm-support
