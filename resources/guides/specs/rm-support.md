# openEHR Support Information Model — Digest

**Scope:** Foundation package of the Reference Model defining identifiers, object references, the terminology-service interface, and assumed primitive types used throughout openEHR.
**Component:** RM
**Document:** support
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/RM/development/support.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/development/support.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-common, openehr://guides/specs/rm-data_types
**Keywords:** OBJECT_ID, OBJECT_VERSION_ID, HIER_OBJECT_ID, ARCHETYPE_ID, TEMPLATE_ID, OBJECT_REF, PARTY_REF, TERMINOLOGY_SERVICE, identifiers, UID, UUID, primitive types

---

## Purpose

Defines the cross-cutting types that every other Reference Model package builds on: the identifier class hierarchy (meaningful vs. opaque identifiers), structured references to identified objects, the proxy interfaces for externally supplied terminology and measurement services, and the set of primitive types (`Integer`, `Real`, `String`, `Boolean`, `Character`, `Date`, `Time`, etc.) that the RM assumes exist in the implementation environment. The package exists to give every RM and AM construct a single, consistent way of naming things, referencing other things, and interrogating terminology — without hard-coding any concrete terminology product, OID registry, or persistence technology.

## Scope

- In: `OBJECT_ID` hierarchy (opaque `UID_BASED_ID` chain and structured `ARCHETYPE_ID` / `TEMPLATE_ID`); `OBJECT_REF` hierarchy for typed references between objects; identification constants; terminology and code-set interface classes; measurement-service proxy; assumed primitive type set; and global identification constants used across RM/AM/TERM.
- Out: Concrete terminology implementations and bindings (supplied externally); concrete OID/IRI registries; data-type semantics such as `CODE_PHRASE`, `DV_CODED_TEXT`, quantity semantics (in `RM/data_types`); change-control and version-tree mechanics (in `RM/common`); EHR/demographic content classes.

## Key Classes / Constructs

- `OBJECT_ID` — abstract root of the identifier hierarchy; basis of equality for all openEHR identifiers.
- `UID_BASED_ID` — abstract parent for identifiers whose leading segment is a `UID` (UUID / ISO OID / Internet domain).
- `HIER_OBJECT_ID` — concrete UID-based identifier with optional hierarchical extension, used for `VERSIONED_OBJECT.uid` and similar top-level objects.
- `OBJECT_VERSION_ID` — three-part identifier `{object_uid}::{creating_system_id}::{version_tree_id}` that uniquely labels one `VERSION` within a versioned container.
- `VERSION_TREE_ID` — `trunk_version[.branch_number.branch_version]` structure for trunk and branch numbering.
- `UID` / `UUID` / `ISO_OID` / `INTERNET_ID` — abstract UID and its three concrete forms (randomly generated, ISO-registered, DNS-based).
- `ARCHETYPE_ID` / `TEMPLATE_ID` — structured, human-readable identifiers for archetypes and templates (multi-axial for `ARCHETYPE_ID`).
- `GENERIC_ID` — escape hatch for externally scheme-qualified identifiers that do not match any standard form.
- `TERMINOLOGY_ID` / `CODE_PHRASE`-adjacent access — identifies a terminology/code-set; the `CODE_PHRASE` value class itself lives in `RM/data_types`.
- `OBJECT_REF` — typed reference `{namespace, type, id}` pointing to any identified object; specialised as `PARTY_REF`, `LOCATABLE_REF`, `ACCESS_GROUP_REF`.
- `TERMINOLOGY_SERVICE` — proxy interface yielding `TERMINOLOGY_ACCESS` and `CODE_SET_ACCESS` handles; the contract by which RM classes reach an external terminology.
- `MEASUREMENT_SERVICE` — proxy interface for unit validation and quantity conversion.
- `OPENEHR_TERMINOLOGY_GROUP_IDENTIFIERS` / `OPENEHR_CODE_SET_IDENTIFIERS` — constants identifying the internal openEHR terminology groups and code sets required by the RM.
- `EXTERNAL_ENVIRONMENT_ACCESS` — mixin that gives RM classes uniform access to the terminology and measurement service proxies.

## Relations to Other Specs

- Depends on: nothing within RM — this is the foundation package. Relies only on assumed primitive types defined at the BASE level.
- Consumed by: `RM/common` (change-control uses `HIER_OBJECT_ID`, `OBJECT_VERSION_ID`, `OBJECT_REF`, `PARTY_REF`), `RM/ehr`, `RM/demographic`, `RM/data_structures`, `RM/data_types` (embeds `TERMINOLOGY_ID` in `CODE_PHRASE`), `AM` (archetype/template identifiers, terminology-service contract), `TERM` (terminology codes), `QUERY` (identifier literals in AQL), and the `ITS-*` serialisations.

## Architectural Placement

Sits at the bottom of the RM stack: every other RM, AM and service-model package imports it, and it imports nothing from openEHR itself. It is the boundary across which identifier syntax, reference semantics, and terminology/measurement service contracts are fixed, isolating the rest of the model from concrete identifier registries and terminology products.

## When to Read the Full Spec

Open the full document when parsing or minting an `OBJECT_VERSION_ID` (three-part form, branch numbering via `VERSION_TREE_ID`), when implementing identifier equality and scheme-detection rules on `UID_BASED_ID` subtypes, when deciding between `HIER_OBJECT_ID`, `OBJECT_VERSION_ID` and `GENERIC_ID` for a persistence layer, or when wiring a concrete terminology/measurement back-end to the `TERMINOLOGY_SERVICE` / `MEASUREMENT_SERVICE` proxy contract.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/RM/development/support.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/development/support.md
- Related digests: specs/rm-common, specs/rm-data_types
