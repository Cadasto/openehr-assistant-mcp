# BMM Persistence Format — Digest

**Scope:** Human-readable ODIN-based serialisation format (`P_BMM`) for persisting BMM schemas as `.bmm` files.
**Component:** LANG
**Document:** bmm_persistence
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/bmm_persistence.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/bmm_persistence.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/lang-bmm, openehr://guides/specs/lang-odin
**Keywords:** BMM, persistence, serialisation, .bmm, ODIN, file format, P_BMM, schema

---

## Purpose

Defines the on-disk persistence model `P_BMM` used to serialise Basic Meta-Model (BMM) schemas into `.bmm` files expressed in ODIN syntax. The format complements the abstract BMM meta-model by specifying a concrete, human-readable, and tool-neutral file layout that can be authored by hand, diffed under version control, and loaded by BMM parsers into in-memory `BMM_*` models. It exists because BMM consumers need an interchange format richer than UML XMI yet simpler than MOF for generic types, multiple inheritance, and container types.

## Scope

- In: the ODIN object graph rooted at a `P_BMM_SCHEMA`, including schema identification metadata, schema inclusion, package/class hierarchy, primitive and complex class definitions, generic parameters, property meta-types, ancestor expressions, and container cardinality. Defines naming, case-sensitivity, and the two-stage load pipeline (parse `P_BMM_*` -> instantiate `BMM_*`).
- Out: the abstract BMM meta-model semantics themselves (see `LANG/bmm`), ODIN lexical grammar (see `LANG/odin`), ADL/AOM persistence (see `AM/*`), archetype or template formalisms, query/serialisation of instance data, and any particular reference-model schema content such as openEHR RM or FHIR.

## Key Classes / Constructs

- `bmm_version` — required BMM meta-model version the schema conforms to (e.g. `2.3`).
- `rm_publisher` / `schema_name` / `rm_release` — triple that uniquely identifies a schema and forms its canonical `schema_id`.
- `schema_revision` / `schema_lifecycle_state` / `schema_description` — governance metadata: revision tag, lifecycle state, and human-readable documentation.
- `includes` — keyed list of other schema ids transitively merged at load time to form a composite model.
- `packages` — recursive `P_BMM_PACKAGE` tree grouping classes under dotted package paths; carries `name` and nested `classes` references.
- `primitive_types` — keyed table of `P_BMM_CLASS` entries for built-in primitive types (Integer, String, Boolean, etc.) referenced by properties.
- `class_definitions` — keyed table of `P_BMM_CLASS` entries defining the domain classes, each with `name`, `ancestors`/`ancestor_defs`, `is_abstract`, `generic_parameter_defs`, and `properties` (`P_BMM_SINGLE_PROPERTY`, `P_BMM_CONTAINER_PROPERTY`, `P_BMM_GENERIC_PROPERTY`, and enumeration variants).
- `archetype_parent_class` / `archetype_data_value_parent_class` / `archetype_rm_closure_packages` — AOM-facing hints that tell archetype tooling which RM classes are archetypeable roots and which packages form the archetyping closure.

## Relations to Other Specs

- Depends on: `LANG/bmm` (abstract meta-model whose `BMM_*` classes are mirrored by `P_BMM_*`), `LANG/odin` (concrete textual syntax for the object graph and cardinality ranges), and `BASE/foundation_types` for primitive type names referenced from `primitive_types`.
- Consumed by: `AM/ADL2` and `AM/AOM2` tooling (archetype editors and validators load RM schemas from `.bmm`), reference-implementation parsers that drive model-driven code generation, and any governance tooling that diffs or releases versioned schemas.

## Architectural Placement

Sits alongside `LANG/odin` within the language layer: `bmm_persistence` is the file-format binding of the abstract `LANG/bmm` meta-model, serialised via ODIN. It is the canonical interchange used by archetype tools to discover the classes, properties, and closures of whichever reference model (openEHR RM, FHIR, CIMI, custom) they operate against.

## When to Read the Full Spec

Consult the full document when implementing a `.bmm` parser or writer, when authoring or editing a schema by hand and needing exact attribute names and cardinality rules for `P_BMM_*` constructs, when resolving generic or open-type property definitions, when wiring schema `includes` and lifecycle state transitions, or when debugging how archetype tooling resolves `archetype_parent_class` and RM closure packages for a new reference model.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/bmm_persistence.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/bmm_persistence.md
- Related digests: specs/lang-bmm, specs/lang-odin
