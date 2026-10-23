# openEHR Common Information Model — Digest

**Scope:** Shared abstractions underpinning locatability, change control, provenance, and party reference across the Reference Model.
**Component:** RM
**Document:** common
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/RM/development/common.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/development/common.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-data_types, openehr://guides/specs/rm-support
**Keywords:** LOCATABLE, PATHABLE, PARTY_PROXY, VERSIONED_OBJECT, CONTRIBUTION, AUDIT_DETAILS, ATTESTATION, FEEDER_AUDIT, change control, authored resource

---

## Purpose

Defines the abstract patterns reused across every higher-level openEHR Reference Model package: pathable/locatable structure, linking, archetype identity, demographic reference by proxy, feeder-system provenance, and the change-control machinery (versioned containers, versions, contributions, audit trails, attestations). These abstractions exist so that consistency, indelibility, traceability, and safe distributed sharing of health information are solved once and inherited uniformly by the EHR, Demographic, EHR Extract, and Data Structures packages.

## Scope

- In: root locatability types (`LOCATABLE`, `PATHABLE`), archetype identity (`ARCHETYPED`), inter-object links (`LINK`), party reference proxies (`PARTY_PROXY` hierarchy) and `PARTICIPATION`, feeder-system audit (`FEEDER_AUDIT`), the directory package (versioned `FOLDER` structures), change-control container/version model (`VERSIONED_OBJECT`, `VERSION`, `ORIGINAL_VERSION`, `IMPORTED_VERSION`), commit metadata (`CONTRIBUTION`, `AUDIT_DETAILS`, `ATTESTATION`, `REVISION_HISTORY`), the tags package (lightweight key/value annotations on committed items), and the legacy ADL 1.4 authored-resource descriptors.
- Out: concrete clinical containers (see `RM/ehr`), party detail modelling (see `RM/demographic`), data value types (see `RM/data_types`), identifiers and terminology interface (see `RM/support`), archetype/template formalisms (`AM`), query (`QUERY`), service APIs (`SM`), and the ADL 2 / BASE replacement of authored-resource descriptors (retained here only for ADL 1.4 compatibility). The design of any "openEHR converter" for feeder-system transformation is explicitly out of scope.

## Key Classes / Constructs

- `PATHABLE` / `LOCATABLE` — abstract ancestor providing parent navigation and path-based child lookup; its subtype adds archetype-node identity, runtime `name`, optional `archetype_details`, `feeder_audit`, and `links`.
- `ARCHETYPED` — marks a `LOCATABLE` as the root of an archetyped structure; carries `archetype_id`, `template_id`, and `rm_version`.
- `LINK` — typed, named semantic link between two `LOCATABLE` instances, referenced by `DV_EHR_URI`.
- `FEEDER_AUDIT` / `FEEDER_AUDIT_DETAILS` — preserves the origin system, provider, subject, version, and original content of data imported from non-openEHR feeders.
- `PARTY_PROXY` / `PARTY_IDENTIFIED` / `PARTY_RELATED` — abstract reference to a demographic party with optional `external_ref`; `PARTY_IDENTIFIED` adds a `name` and formal `identifiers` for non-subject parties; `PARTY_RELATED` adds a coded `relationship` to the subject of care.
- `PARTY_SELF` — proxy denoting the record subject; three schemes are supported: `external_ref` set nowhere, set once in `EHR_STATUS.subject`, or set on every instance.
- `PARTICIPATION` — generic model of a party's involvement in an activity (function, mode, time) where the participation kind is not fixed at design time.
- `FOLDER` / `VERSIONED_FOLDER` (directory package) — versioned, archetypable folder structures whose items are references to other versioned objects, enabling multiple classification (episodes, problem groups).
- Tags package — lightweight key/optional-value annotations associated with previously committed items to support tag-based search and AQL querying.
- `VERSIONED_OBJECT` / `ORIGINAL_VERSION` / `IMPORTED_VERSION` — change-control container owning the version history of one logical item; concrete `VERSION` subtypes for locally authored versus copied-in content (`ORIGINAL_VERSION` owns optional `attestations` and a `lifecycle_state`).
- `CONTRIBUTION` / `AUDIT_DETAILS` / `ATTESTATION` — atomic commit bundle grouping the `VERSION` references of one change act; each commit's `AUDIT_DETAILS` records system id, committer, time, change type, and description; `ATTESTATION` specialises it with proof (signature), reason, and attested view.
- `AUTHORED_RESOURCE`, `RESOURCE_DESCRIPTION`, `RESOURCE_DESCRIPTION_ITEM`, `TRANSLATION_DETAILS` — metadata descriptors for ADL 1.4 authored artefacts (original language, translations, lifecycle, IP, purpose).

## Relations to Other Specs

- Depends on: `BASE/base_types` (identifiers such as `HIER_OBJECT_ID`, `OBJECT_VERSION_ID`, `OBJECT_REF`), `RM/support` (terminology access), and `RM/data_types` (`DV_TEXT`, `DV_CODED_TEXT`, `DV_DATE_TIME`, `DV_EHR_URI` used in audits, links, and attestations).
- Consumed by: `RM/ehr` (every versioned EHR part and committed composition), `RM/demographic` (versioned parties and roles), `RM/data_structures` and `RM/ehr_extract` (locatable content), `AM` (archetype identity propagated via `ARCHETYPED`), and `SM` / `ITS-*` (platform contribution and version-retrieval semantics).

## Architectural Placement

One of the generic RM packages (with `data_structures`, `data_types`, and `support`): it sits above `support` and `data_types` and below the domain packages (`ehr`, `demographic`, `ehr_extract`, `integration`). It is the canonical place where cross-cutting concerns — pathing, archetyping, provenance, and versioning — are normalised, so that every committable object in the RM inherits the same change-control and locatability contract.

## When to Read the Full Spec

Consult the full document when implementing commit pipelines (`CONTRIBUTION` + `VERSION` ordering, `preceding_version_uid`, `lifecycle_state`), designing attestation or digital-signature flows, mapping a non-openEHR feeder into `FEEDER_AUDIT`, or deciding between `PARTY_SELF`, `PARTY_IDENTIFIED`, and `PARTY_RELATED` for subject/participation references. For per-class attribute, function, and invariant detail use the BMM-backed `type_specification_get` tool rather than the rendered HTML.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/RM/development/common.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/development/common.md
- Related digests: specs/rm-ehr, specs/rm-data_types, specs/rm-support, specs/rm-demographic
