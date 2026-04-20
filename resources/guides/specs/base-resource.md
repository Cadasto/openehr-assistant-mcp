# openEHR Resource Model — Digest

**Scope:** Authored-resource metadata model covering authorship, IP, language translation, lifecycle, and path-based annotations shared by archetypes, templates, and terminology subsets.
**Component:** BASE
**Document:** resource
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/BASE/development/resource.html
**Markdown URL:** https://specifications.openehr.org/releases/BASE/development/resource.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/base-foundation_types, openehr://guides/specs/am-Identification
**Keywords:** AUTHORED_RESOURCE, RESOURCE_DESCRIPTION, RESOURCE_DESCRIPTION_ITEM, TRANSLATION_DETAILS, RESOURCE_ANNOTATIONS, original_language, translations, copyright, licence, ip_acknowledgements, annotations, authored resource

---

## Purpose

Defines the abstract structure and metadata contract for any openEHR "authored resource" — artefacts created by a human (or tool-on-behalf-of-human) author that need formal identification, description, intellectual-property declaration, original-language recording, and multi-language translation. It factors out of the Archetype, Template, and Terminology-Subset specifications the common descriptive envelope so all authored artefacts share one metadata shape. This lets tooling (repositories, CKMs, editors, validators) treat archetypes and other authored artefacts uniformly for indexing, search, localisation, and IP attribution.

## Scope

- In: the abstract `AUTHORED_RESOURCE` container; its `original_language`, `translations`, `description`, `is_controlled`, and `annotations` attributes; language-keyed descriptive meta-data (`RESOURCE_DESCRIPTION`, `RESOURCE_DESCRIPTION_ITEM`) including purpose, use, misuse, keywords, `copyright`, `licence`, `ip_acknowledgements`, and custodian details; translator provenance and QA (`TRANSLATION_DETAILS`); and path-keyed annotation overlays (`RESOURCE_ANNOTATIONS`).
- Out: concrete archetype, template, or terminology-subset formalisms (covered by `AM/ADL2`, `AM/OPT2`, `AM/aom2`, and terminology specs); identifiers and globally unique archetype IDs (covered by `AM/Identification`); the underlying foundation types (`Interval`, `List`, `ISO8601_DATE`); and change-control of authored artefacts (covered by `RM/common` versioning and by repository/CKM tooling).

## Key Classes / Constructs

- `AUTHORED_RESOURCE` — abstract root of any authored artefact; owns `original_language`, `translations`, `description`, `is_controlled`, and `annotations`.
- `RESOURCE_DESCRIPTION` — metadata block holding author/organisation/contact, `lifecycle_state`, `copyright`, `licence`, `ip_acknowledgements`, `other_contributors`, and per-language `details`.
- `RESOURCE_DESCRIPTION_ITEM` — per-language narrative: `purpose`, `use`, `misuse`, `keywords`, `original_resource_uri`, plus free `other_details`.
- `TRANSLATION_DETAILS` — provenance of a translation: translator, accreditation, organisation, version-last-translated, and `other_details` QA hooks.
- `RESOURCE_ANNOTATIONS` — path-addressed remark overlay (`items: Hash<String, Hash<String, String>>`) attaching non-normative notes to nodes of the host artefact, per language.

## Relations to Other Specs

- Depends on: `BASE/foundation_types` (for `Hash`, `List`, `String` primitives) and `BASE/base_types` (identifiers, `TERMINOLOGY_CODE`-style support consumed by lifecycle and language codes).
- Consumed by: `AM/ADL2` and `AM/aom2` (archetypes inherit `AUTHORED_RESOURCE`), `AM/OPT2` (operational templates), terminology subset / refset specifications, and any CKM-style governance tooling that surfaces description/IP metadata. Legacy `RM/common` retains an ADL 1.4 mirror of these descriptors for backward compatibility.

## Architectural Placement

Lives in the BASE layer, above `foundation_types` and below the Archetype Model and Terminology specs. It is the only place in openEHR where authored-artefact description, IP, translation, and annotation semantics are normalised, so every higher-level authoring specification inherits a single, canonical metadata envelope rather than redefining its own.

## When to Read the Full Spec

Read the full `.md`/HTML when you need invariants (e.g. the `translations` language-key constraint, `original_language` presence rules), exact attribute types, or detailed semantics of `lifecycle_state`, `ip_acknowledgements`, `RESOURCE_ANNOTATIONS` path addressing, or the annotation inheritance behaviour in specialised archetypes. For per-class attribute lists, cardinalities, and invariants use `type_specification_get` (BMM-backed) rather than this digest.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/BASE/development/resource.html
- Full spec (Markdown): https://specifications.openehr.org/releases/BASE/development/resource.md
- Related digests: specs/base-foundation_types, specs/am-Identification, specs/rm-common
