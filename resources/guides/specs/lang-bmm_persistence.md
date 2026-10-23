# BMM Persistence Format — Digest

**Scope:** Persistence model (`P_BMM`, version 2.4) for BMM schemas, serialised as ODIN (`.bmm`), JSON (`.bmm.json`) or YAML (`.bmm.yaml`).
**Component:** LANG
**Document:** bmm_persistence
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/bmm_persistence.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/bmm_persistence.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/lang-bmm, openehr://guides/specs/lang-odin
**Keywords:** BMM, persistence, serialisation, .bmm, .bmm.json, .bmm.yaml, ODIN, JSON, YAML, file format, P_BMM, schema, functions, constants, invariants, P_BMM_INTERFACE

---

## Purpose

Defines the persistence model `P_BMM` used to serialise Basic Meta-Model (BMM) schemas. ODIN (`.bmm`) is the historical default and the most widely tool-supported format; JSON (`.bmm.json`, the primary format of the `bmm-publisher` tooling) and YAML (`.bmm.yaml`) are also in use, under the same serialisation rules. The `P_BMM_*` classes are a simplified, symbolically referenced form of the `BMM_*` classes, so schemas can be authored by hand, diffed under version control, and loaded by BMM parsers into in-memory `BMM_*` models. P_BMM carries its own version, currently 2.4, which captures a class's full feature set: properties, functions with parameters, constants, and invariants. It is an approximate replacement for UML XMI for data-only models, supporting open and closed generic types, container types, and multiple inheritance.

## Scope

- In: the `P_BMM_*` object graph rooted at a `P_BMM_SCHEMA`: schema identification metadata, schema inclusion, package/class hierarchy, primitive and complex class definitions, pure interfaces, enumerations, generic parameters, property meta-types, functions, constants, invariants, ancestor expressions, and container cardinality; the rules shared by the ODIN, JSON and YAML forms; and the two-stage load pipeline (parse `P_BMM_*` -> instantiate `BMM_*`).
- Out: the abstract BMM meta-model semantics themselves (see `LANG/bmm`), ODIN lexical grammar (see `LANG/odin`), ADL/AOM persistence (see `AM/*`), archetype or template formalisms, query/serialisation of instance data, and any particular reference-model schema content such as openEHR RM or FHIR.

## Key Classes / Constructs

- `bmm_version` — P_BMM version the schema conforms to (current: 2.4; older schemas state e.g. `2.3`).
- `rm_publisher` / `schema_name` / `rm_release` — triple that uniquely identifies a schema and forms its canonical `schema_id`.
- `schema_revision` / `schema_lifecycle_state` / `schema_description` — governance metadata: revision tag, lifecycle state, and human-readable documentation.
- `includes` — keyed list of other schema ids transitively merged at load time to form a composite model.
- `packages` — recursive `P_BMM_PACKAGE` tree grouping classes under dotted package paths; carries `name` and nested `classes` references.
- `primitive_types` — keyed table of `P_BMM_CLASS` entries for built-in primitive types (Integer, String, Boolean, etc.) referenced by properties.
- `class_definitions` — keyed table of `P_BMM_CLASS` entries defining the domain classes, each with `name`, `ancestors`/`ancestor_defs`, `is_abstract`, `generic_parameter_defs`, `properties` (`P_BMM_SINGLE_PROPERTY`, `P_BMM_CONTAINER_PROPERTY`, `P_BMM_GENERIC_PROPERTY`, open variants), and the 2.4 features below; enumerations use `P_BMM_ENUMERATION_INTEGER` / `P_BMM_ENUMERATION_STRING`.
- `functions` / `constants` / `invariants` (P_BMM 2.4) — `P_BMM_FUNCTION` with keyed `parameters` (single, open, container, generic variants) and an optional `result` (none = procedure); `P_BMM_CONSTANT` with `type` and literal `value`; invariants keyed by tag, holding assertion text (typically BEL) that P_BMM treats as an opaque string. Tools that read only structure may ignore all three.
- `P_BMM_INTERFACE` — pure interface: a class-like definition declaring only functions, with no state.
- Format idioms — the concrete subtype of an ambiguous value is always stated: ODIN type marker `(P_BMM_SINGLE_PROPERTY)`, JSON `"_type": "P_BMM_SINGLE_PROPERTY"`, YAML tag `!P_BMM_SINGLE_PROPERTY`; collections are keyed by name.
- Note: archetype-facing hints such as `archetype_parent_class` and `archetype_data_value_parent_class` seen in openEHR RM schema files are not part of this spec — they belong to the AOM profile configuration defined in `AM/AOM2` (Reference Model Adaptation).

## Relations to Other Specs

- Depends on: `LANG/bmm` (abstract meta-model whose `BMM_*` classes are mirrored by `P_BMM_*`), `LANG/odin` (default textual syntax for the object graph and cardinality ranges; JSON and YAML are alternatives), and `BASE/foundation_types` for primitive type names referenced from `primitive_types`.
- Consumed by: `ITS-BMM` (the openEHR BMM schemas are authored in P_BMM form), `AM/ADL2` and `AM/AOM2` tooling (archetype editors and validators load RM schemas from `.bmm`), reference-implementation parsers that drive model-driven code generation, and any governance tooling that diffs or releases versioned schemas.

## Architectural Placement

Sits alongside `LANG/odin` within the language layer: `bmm_persistence` is the file-format binding of the abstract `LANG/bmm` meta-model, serialised via ODIN, JSON or YAML. It is the canonical interchange used by archetype tools to discover the classes, properties, and closures of whichever reference model (openEHR RM, FHIR, CIMI, custom) they operate against.

## When to Read the Full Spec

Consult the full document when implementing a `.bmm` parser or writer, when authoring or editing a schema by hand and needing exact attribute names and cardinality rules for `P_BMM_*` constructs, when resolving generic or open-type property definitions, when reading or writing the 2.4 `functions`, `constants` and `invariants` blocks or the JSON/YAML forms, when wiring schema `includes` and lifecycle state transitions, or when debugging schema merge and validation state transitions during the two-stage load pipeline.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/bmm_persistence.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/bmm_persistence.md
- Related digests: specs/lang-bmm, specs/lang-odin
