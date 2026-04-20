# openEHR Base Types — Digest

**Scope:** Cross-cutting base types — identifiers, object references, symbolic constants, and built-in utility interfaces — shared by all openEHR component models.
**Component:** BASE
**Document:** base_types
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/BASE/development/base_types.html
**Markdown URL:** https://specifications.openehr.org/releases/BASE/development/base_types.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/base-foundation_types, openehr://guides/specs/base-resource
**Keywords:** UID, UUID, OBJECT_ID, OBJECT_VERSION_ID, ARCHETYPE_ID, TEMPLATE_ID, OBJECT_REF, VERSION_TREE_ID, identifiers, definitions, builtins, base types

---

## Purpose

Defines the `org.openehr.base_types` package: a minimal set of identifier classes, reference classes, symbolic definition holders, and built-in utility interfaces that every other openEHR specification (RM, AM, SM, TERM, QUERY, ITS) imports and reuses. Its role is to fix, once and for a single place, how openEHR names things (UIDs, archetype IDs), how one identified object points to another (`OBJECT_REF` and specialisations), and which ambient host-environment services (locale, math, quantity conversion) every model may assume.

## Scope

- In: identification package (`UID` and its UUID / ISO_OID / INTERNET_ID forms; `OBJECT_ID` hierarchy; `OBJECT_VERSION_ID` with `VERSION_TREE_ID`; structured `ARCHETYPE_ID` / `TEMPLATE_ID` / `TERMINOLOGY_ID`; `GENERIC_ID`; `OBJECT_REF` and specialisations `PARTY_REF`, `LOCATABLE_REF`); definitions package (symbolic constants and enumerations such as `VALIDITY_KIND`, `VERSION_STATUS`); builtins package (host-environment utility interfaces `Env`, `Locale`, `Math`, `Quantity_converter`, `Statistical_evaluator`).
- Out: foundation primitive/ordered/container types (BASE `foundation_types`), resource/document packaging (BASE `resource`), change-control and versioning mechanics (`RM/common`), value types such as `CODE_PHRASE` / `DV_IDENTIFIER` (`RM/data_types`), service contracts such as `TERMINOLOGY_SERVICE` (`RM/support`).

## Key Classes / Constructs

- `UID` — abstract root of the UID hierarchy; concrete forms are `UUID`, `ISO_OID`, `INTERNET_ID`.
- `OBJECT_ID` — abstract root for all openEHR identifiers; basis of identifier equality.
- `OBJECT_VERSION_ID` — three-part `{object_uid}::{creating_system_id}::{version_tree_id}` identifier of a single `VERSION`.
- `ARCHETYPE_ID` / `TEMPLATE_ID` — structured, human-readable identifiers for archetypes and templates.
- `OBJECT_REF` — typed `{namespace, type, id}` reference; specialised as `PARTY_REF`, `LOCATABLE_REF`.
- `BASIC_DEFINITIONS` / `OPENEHR_DEFINITIONS` — holders of symbolic constants used across the models.
- `VALIDITY_KIND` / `VERSION_STATUS` — enumerations for optionality and version lifecycle state.
- `Env` / `Locale` / `Math` / `Quantity_converter` — built-in interfaces giving models uniform access to host services.

## Relations to Other Specs

- Depends on: `BASE/foundation_types` (assumed primitive and container types).
- Consumed by: `BASE/resource` (authored resource packaging), `RM/support` (wraps these identifiers in service-facing classes such as `TERMINOLOGY_SERVICE`), `RM/common`, `RM/ehr`, `RM/demographic`, `RM/data_structures`, `RM/data_types`, `AM/ADL2` / `AM/AOM2` (archetype and template identifier syntax), `TERM` (terminology identifiers), `QUERY/AQL` (identifier literals), and all `ITS-*` serialisations.

## Architectural Placement

Sits immediately above `foundation_types` within the BASE component and below every other openEHR component. It is the first layer at which openEHR-specific semantics appear (identifier syntax, reference semantics, environment-access contracts), isolating the rest of the stack from concrete UID registries, locale handling, and unit-conversion back-ends.

## When to Read the Full Spec

Open the full document for identifier grammar and parsing rules (`OBJECT_VERSION_ID` three-part form, `VERSION_TREE_ID` trunk/branch numbering, `ARCHETYPE_ID` axes), for the exact contract of a builtin interface when wiring a host environment, or when deciding which `OBJECT_ID` subtype to mint in a new persistence layer. For per-class attribute, function, and invariant detail prefer the `type_specification_get` tool (BMM-backed, authoritative, class-scoped) over the rendered HTML.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/BASE/development/base_types.html
- Full spec (Markdown): https://specifications.openehr.org/releases/BASE/development/base_types.md
- Related digests: specs/base-foundation_types, specs/base-resource
