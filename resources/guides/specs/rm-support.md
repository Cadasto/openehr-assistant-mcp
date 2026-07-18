# openEHR Support Information Model — Digest

**Scope:** Foundation package of the Reference Model defining the terminology-service and measurement-service interfaces and the environment-access mixin; historically also home to identifiers and assumed primitive types, now relocated to BASE.
**Component:** RM
**Document:** support
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/RM/development/support.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/development/support.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/rm-common, openehr://guides/specs/rm-data_types
**Keywords:** TERMINOLOGY_SERVICE, TERMINOLOGY_ACCESS, CODE_SET_ACCESS, MEASUREMENT_SERVICE, EXTERNAL_ENVIRONMENT_ACCESS, code sets, terminology access, identifiers (relocated to BASE), assumed types (relocated to BASE)

---

## Purpose

Defines the cross-cutting service-access types that every other Reference Model package builds on: the proxy interfaces for externally supplied terminology and measurement services, and the `EXTERNAL_ENVIRONMENT_ACCESS` mixin through which RM classes reach them — without hard-coding any concrete terminology product, OID registry, or persistence technology. In the development release, the material this package historically also carried — the identifier class hierarchy (`OBJECT_ID`, `OBJECT_REF` and friends), the definitions constants, and the assumed primitive/library/date-time types — has been relocated to the BASE component (`BASE/base_types` identification and definitions packages, `BASE/foundation_types`); the corresponding chapters here are retained only as pointers.

## Scope

- In: the `EXTERNAL_ENVIRONMENT_ACCESS` mixin; the terminology package (terminology-service and code-set interface classes plus the `OPENEHR_TERMINOLOGY_GROUP_IDENTIFIERS` / `OPENEHR_CODE_SET_IDENTIFIERS` constants); the measurement package (units validation and conversion proxy); rules for how terms and codes are used in the openEHR RM.
- Out: identifier and reference classes (`OBJECT_ID` / `OBJECT_REF` hierarchies) and definitions constants — moved to `BASE/base_types`; assumed primitive, library, and date/time types — moved to `BASE/foundation_types`; concrete terminology implementations and bindings (supplied externally); data-type semantics such as `CODE_PHRASE`, `DV_CODED_TEXT` (in `RM/data_types`); change-control and version-tree mechanics (in `RM/common`); EHR/demographic content classes.

## Key Classes / Constructs

- `EXTERNAL_ENVIRONMENT_ACCESS` — mixin that gives RM classes uniform access to the terminology and measurement service proxies.
- `TERMINOLOGY_SERVICE` — proxy interface yielding `TERMINOLOGY_ACCESS` and `CODE_SET_ACCESS` handles; the contract by which RM classes reach an external terminology.
- `TERMINOLOGY_ACCESS` / `CODE_SET_ACCESS` — per-terminology and per-code-set proxy handles used to validate and look up codes at runtime.
- `MEASUREMENT_SERVICE` — proxy interface for unit validation and quantity conversion.
- `OPENEHR_TERMINOLOGY_GROUP_IDENTIFIERS` / `OPENEHR_CODE_SET_IDENTIFIERS` — constants identifying the internal openEHR terminology groups and code sets required by the RM.
- Relocated (see `BASE/base_types`): the `OBJECT_ID` hierarchy (`UID_BASED_ID`, `HIER_OBJECT_ID`, `OBJECT_VERSION_ID` with `VERSION_TREE_ID`, `ARCHETYPE_ID`, `TEMPLATE_ID`, `TERMINOLOGY_ID`, `GENERIC_ID`), the `UID` forms (`UUID`, `ISO_OID`, `INTERNET_ID`), and the `OBJECT_REF` hierarchy (`PARTY_REF`, `LOCATABLE_REF`, `ACCESS_GROUP_REF`).
- Relocated (see `BASE/foundation_types`): assumed primitive types (`Integer`, `Real`, `String`, `Boolean`, `Character`), library container types, and date/time types.

## Relations to Other Specs

- Depends on: `BASE/foundation_types` (primitive and library types) and `BASE/base_types` (identifiers and references that this package's earlier releases defined locally).
- Consumed by: `RM/common`, `RM/ehr`, `RM/demographic`, `RM/data_structures`, `RM/data_types` (terminology and measurement access when validating `CODE_PHRASE` codes and quantity units), `AM` (terminology-service contract), `TERM` (code-set and terminology-group identifiers), and the `ITS-*` serialisations.

## Architectural Placement

Sits at the bottom of the RM stack: every other RM, AM and service-model package imports it, and it imports nothing from openEHR itself. It is the boundary across which identifier syntax, reference semantics, and terminology/measurement service contracts are fixed, isolating the rest of the model from concrete identifier registries and terminology products.

## When to Read the Full Spec

Open the full document when wiring a concrete terminology/measurement back-end to the `TERMINOLOGY_SERVICE` / `MEASUREMENT_SERVICE` proxy contract, when resolving which openEHR code sets and terminology groups the RM requires (`OPENEHR_CODE_SET_IDENTIFIERS`, `OPENEHR_TERMINOLOGY_GROUP_IDENTIFIERS`), or when checking how terms and codes are meant to be used inside RM data. For identifier parsing and minting (`OBJECT_VERSION_ID` three-part form, `VERSION_TREE_ID` branch numbering, `UID_BASED_ID` subtypes) consult `BASE/base_types`, where those classes now live.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/RM/development/support.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/development/support.md
- Related digests: specs/rm-common, specs/rm-data_types
