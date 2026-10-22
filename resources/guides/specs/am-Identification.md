# Archetype and Template Identification — Digest

**Scope:** Formal model of identifiers, versioning, and lifecycle for archetypes, templates, and terminology subsets shared across ADL 1.4 and ADL 2.
**Component:** AM
**Document:** Identification
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/Identification.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/Identification.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/am-Overview, openehr://guides/specs/am-ADL1.4, openehr://guides/specs/am2-ADL2, openehr://guides/specs/base-resource, openehr://guides/specs/base-base_types
**Keywords:** archetype id, template id, HRID, MRID, physical id, version, lifecycle, namespace, ADL 2 semver, ADL 1.4

---

## Purpose

Defines the normative identification scheme that lets archetypes, templates, and terminology subsets be unambiguously named, versioned, referenced, and tracked through their lifecycle by authoring tools, governance platforms (CKM), query engines (AQL), and runtime APIs (REST). It harmonises the legacy ADL 1.4 identifier form with the richer ADL 2 form, introduces semantic versioning, and pins the terminology for human-readable and machine-readable references that travel in archetype bodies, template manifests, and composition `archetype_node_id` paths.

## Scope

- In: the human-readable identifier (HRID) grammar (`namespace`, `rm_publisher`, `rm_closure`, `rm_class`, `concept_id`, version) and the machine `uid`; the three reference forms — interface (`ihrid_ref`, major only), specific interface (`sihrid_ref`, major+minor), and physical (`phrid_ref`, full `M.N.P[-modifier.B]`); semantic versioning rules with `-alpha` and `-rc` modifiers; the `lifecycle_state` property and its state set; namespace policy (reverse-DNS) and ontology-sourced concept ids; and the identification of derived operational artefacts via configuration structures.
- Out: ADL grammar (`AM/ADL1.4`, `AM/ADL2`); the archetype object models (`AM/AOM1.4`, `AM/AOM2`); Reference Model class and attribute definitions (`RM/*`); governance workflow in CKM; AQL surface syntax; and REST resource URI design, all of which consume but do not redefine these identifiers.

## Key Classes / Constructs

- `ARCHETYPE_HRID` (AOM2) — human-readable id; attributes `namespace`, `rm_publisher`, `rm_package` (the grammar's `rm_closure`), `rm_class`, `concept_id`, `release_version`, `version_status`, `build_count`.
- `ARCHETYPE_ID` — `BASE/base_types` identifier whose grammar the HRID simplifies; typed by `ARCHETYPED.archetype_id` in RM data (a root `LOCATABLE.archetype_node_id` holds its string form).
- `qualified_rm_class_name` — the `rm_publisher '-' rm_closure '-' rm_class` triple (e.g. `openEHR-EHR-OBSERVATION`, `ISO-ISO13606-ENTRY`).
- `release_version` — semver `M.N.P`; non-release ids add `-alpha` or `-rc.B`, where the build number B increments at every commit; `AUTHORED_ARCHETYPE.build_uid` is a UUID reassigned on every content change.
- `ihrid_ref` / `sihrid_ref` / `phrid_ref` — interface (major-only), specific-interface (major+minor), and physical (full-version) reference forms selecting latest matching release vs. exact artefact.
- `lifecycle_state` — macro-states `unmanaged`, `development`, `release_candidate`, `published`, `deprecated`, `rejected`; drives version-modifier derivation (`-alpha`, `-rc`).
- `uid` — GUID machine identifier (`AUTHORED_RESOURCE.uid`), assigned when a custodian organisation takes the artefact under management; replaced when transfer or forking changes the HRID.
- Configuration structures (`archetype_config`, `template_config`, `subset_config`, `rm_release`) — operational-form manifests recording the concrete source-artefact revisions compiled in.

Exact attribute signatures and invariants are best retrieved via `type_specification_get` rather than duplicated here.

## Relations to Other Specs

- Consumed by: `AM/ADL1.4` and `AM/ADL2` (archetype header syntax), `AM/AOM1.4` and `AM/AOM2` (identifier fields on `ARCHETYPE` and `AUTHORED_RESOURCE`), `RM/common` (resource metadata), CKM (governance and publication states), AQL (`archetype_id` predicates), and openEHR REST (resource paths and ETag semantics).
- Depends on: `BASE/base_types` (prerequisite; `ARCHETYPE_ID` grammar) and `BASE/resource` (`AUTHORED_RESOURCE`).
- Aligns with: semantic versioning 2.0 conventions for the `M.N.P[-modifier.B]` form.

## Architectural Placement

Identification sits at the intersection of AM, BASE, and governance: it is the only contract that every openEHR layer — authoring, validation, repository, query, and runtime — must share to refer to the same knowledge artefact unambiguously. It is deliberately lightweight, syntax-independent, and version-agnostic so that ADL 1.4 tooling, ADL 2 tooling, and operational-template consumers can interoperate through a single identifier vocabulary.

## When to Read the Full Spec

Read the full specification when designing archetype or template repositories, implementing ADL parsers that must round-trip identifiers, building CKM-compatible governance workflows, defining REST URI schemes for knowledge artefacts, coding version-resolution logic (latest-major, latest-minor, pinned), or migrating legacy ADL 1.4 ids into ADL 2 namespaced form. Also consult it whenever designing AQL or compositional references that must bind to a specific release rather than a moving major-version head.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/Identification.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/Identification.md
- Related digests: specs/am-Overview, specs/am2-AOM2, specs/base-base_types, specs/base-resource
