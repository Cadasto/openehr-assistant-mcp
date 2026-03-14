# openEHR Demographic Information Model

**Scope:** Architecture-level summary of the Demographic IM — party hierarchy, identities, relationships, versioning, and EHR separation
**Related:** openehr://guides/rm/ehr-information-model
**Keywords:** demographic, PARTY, PERSON, ORGANISATION, ROLE, PARTY_PROXY, identity, PMI, privacy

---

## Purpose

The Demographic IM defines a generalised model of **parties** (people, organisations, groups, devices) and their roles and relationships. It specifies a **demographic service** that can be standalone or wrap an existing Patient Master Index (PMI).

Key design aims:
- **Separation from the EHR** — clinical content carries only optional references to the demographic service via PARTY_PROXY.
- **Archetype-driven** — all entity attributes expressed via generic, archetypable structures.
- **Versioned** — VERSIONED_PARTY for audit, indelibility, and historical reconstruction.
- **Serialisation safety** — each PARTY is self-contained for unambiguous EHR Extract inclusion.

---

## Party Hierarchy

```
PARTY (abstract)
+-- ACTOR (abstract) -- real-world entities capable of taking on roles
|   +-- PERSON
|   +-- ORGANISATION
|   +-- GROUP        -- named collection of actors (e.g. care team)
|   +-- AGENT        -- devices, software systems
+-- ROLE             -- competency/function performed by an Actor
```

- **ACTOR** subtypes exist independently; each may have zero or more ROLEs.
- **ROLE** is a PARTY subtype with its own identities, contacts, and relationships. Carries `performer` (reference to ACTOR), `capabilities` (CAPABILITY with credentials and time validity), `time_validity`.
- **ACTOR** carries `languages` (preferred communication) and `roles` (references to ROLE version containers).

---

## Identities, Contacts, and Addresses

| Structure | Purpose |
|-----------|---------|
| **PARTY_IDENTITY** | Names owned by the party (legal name, alias, trading name). Has `purpose` and archetyped `details`. |
| **CONTACT** | Contact method (home phone, work email). Has `purpose`, `time_validity`, and one or more ADDRESSes. |
| **ADDRESS** | Electronic or geographic address with archetyped `details`. |

**Important:** State-given or organisation-issued identifiers (national patient number, provider number) are stored in archetyped `PARTY.details`, NOT in PARTY_IDENTITY. PARTY_IDENTITY is for self-declared or socially assigned names only.

---

## Party Relationships

**PARTY_RELATIONSHIP** models directed relationships (e.g. "patient of", "employed by", "family member of"):

- `source` (originating party), `target`, optional `time_validity`, archetyped `details`.
- Source party's `relationships` list contains the relationship by value; target's `reverse_relationships` list references it (prevents serialisation cycles).
- Stored as part of the source PARTY data and versioned with it.

---

## Versioning

All PARTY subtypes (including ROLEs) use the same **VERSIONED_OBJECT** pattern as EHR Compositions:

- Each PARTY lives in a **VERSIONED_PARTY** container with a globally stable uid.
- Changes create new **ORIGINAL_VERSION** entries; **AUDIT_DETAILS** records who, when, and what type of change.
- **Contributions** group related party changes into change-sets.
- **Lifecycle states** apply as in the EHR model: `complete`, `incomplete`, `deleted`, `inactive`, `abandoned`.

---

## Archetyping

All major classes have archetypable structures:

- `PARTY.details` — all attributes of a person, organisation, role, etc.
- `PARTY_IDENTITY.details` — name structure (given/family/title).
- `ADDRESS.details` — address structure (street, country).
- `CAPABILITY.credentials` — professional qualifications and registration.
- `PARTY_RELATIONSHIP.details` — relationship-specific details.

The demographic service is **domain-neutral**: the RM defines structural patterns; archetypes define the specific models used in context (national patient registration, clinical staff directory, etc.).

---

## EHR/Demographic Separation

The EHR and demographic models are designed for **complete separation**:

- Clinical content references parties via **PARTY_PROXY** subtypes (see openehr://guides/rm/ehr-information-model).
- The primary EHR-to-subject link is in **EHR_STATUS.subject** (PARTY_SELF).
- The **EHR Index** service maintains the cross-reference between ehr_ids and demographic subject identifiers.
- Some clinically relevant demographic attributes (age, sex, ethnicity) appear in the EHR as OBSERVATION or ADMIN_ENTRY data — observed/reported in clinical context, distinct from administrative identity.

---

## PMI Integration

The openEHR demographic model can serve as a **wrapper or adapter** over an existing PMI:

- Adds standardised versioning, auditing, and openEHR-compatible party structures.
- External identifiers (hospital number, national ID) stored in archetyped `PARTY.details`.
- Extensible without changing the RM.

---

## References

- Demographic Information Model: <https://specifications.openehr.org/releases/RM/development/demographic.html>
- Common Information Model: <https://specifications.openehr.org/releases/RM/development/common.html>
- EHR Information Model: <https://specifications.openehr.org/releases/RM/development/ehr.html>
