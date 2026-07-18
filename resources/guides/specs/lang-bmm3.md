# Basic Meta-Model 3 (BMM3) — Digest

**Scope:** Development line of the Basic Meta-Model (v3): a restructured, semantically richer meta-model adding an expression meta-model, tuple/signature meta-types, and range-constrained classes; specification currently PAUSED.
**Component:** LANG
**Document:** bmm3
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/bmm3.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/bmm3.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/lang-bmm, openehr://guides/specs/lang-bmm_persistence, openehr://guides/specs/lang-EL
**Keywords:** BMM3, basic meta-model, expression meta-model, EL, decision table, tuple type, signature, enumeration, meta-classes

---

## Purpose

BMM3 is the v3 development line of the Basic Meta-Model — "a model of object models" intended as an approximate replacement for UML/XMI that is human-readable and writable, and supports open and closed generic types, container types, and multiple inheritance. Beyond the stable BMM's class/type core, BMM3 restructures the model into fine-grained sub-packages and adds a first-order-predicate-logic expression meta-model, so that class invariants, routine pre/post-conditions, decision tables, and computable expressions can be represented directly in model schemas. The document is in the PAUSED state: it records v3 developments (versions 3.0.0 and 3.1.0) but is not being actively progressed; the stable `LANG/bmm` spec plus `LANG/bmm_persistence` remain the operative pair for current tooling.

## Scope

- In: package structure `org.openehr.lang.bmm` with `model_access` (schema load/reload reflection interface) and `core` sub-packages — `model` (models and packages), `entity` (classes and types, including enumerations in the `range_constrained` sub-package), `feature` (constants, routines, properties), `literal_value`, and `expression`; type meta-types and conformance rules (simple, generic, container, indexed container, tuple, signature, status); class semantics (simple, generic, range-constrained/enumerated, abstract, primitive-type classes, invariants, inheritance); feature semantics (constants, singletons, unitary vs container writable properties, functions and procedures with pre/post-conditions, creators and converters, variables); literal values including container literals and literal tuples; the expression meta-model (literals, value generators, feature and type references, agents, variables, attached/defined predicates, decision tables, operator expressions, tuples).
- Out: serialised schema form (see `LANG/bmm_persistence` — `P_BMM_*` classes); the stable BMM v2 documentation (`LANG/bmm`); the standalone Expression Language and Basic Expression Language specs (`LANG/EL`, `LANG/BEL`), which share the expression meta-model lineage; archetype formalisms (`AM`); any concrete Reference Model content.

## Key Classes / Constructs

- `BMM_MODEL` — top-level container of a complete model: owns packages, class definitions, schema metadata.
- `BMM_SIMPLE_CLASS` / `BMM_GENERIC_CLASS` / `BMM_ENUMERATION` — definitional class variants, including range-constrained enumeration types (e.g. `BMM_ENUMERATION_INTEGER`).
- `BMM_UNITARY_PROPERTY` / `BMM_CONTAINER_PROPERTY` / `BMM_INDEXED_CONTAINER_PROPERTY` — property meta-classes for single, collection, and keyed-collection semantics.
- `BMM_SIMPLE_TYPE`, `BMM_MODEL_TYPE`, `BMM_PARAMETER_TYPE`, `BMM_GENERIC_TYPE`, `BMM_CONTAINER_TYPE`, `BMM_INDEXED_CONTAINER_TYPE` — the type meta-type family with formal conformance rules.
- `BMM_TUPLE_TYPE` / `BMM_SIGNATURE` / `BMM_STATUS_TYPE` — built-in meta-types for tuples, routine signatures, and procedure status results.
- `BMM_ROUTINE`, `BMM_FUNCTION`, `BMM_PROCEDURE`, `BMM_PARAMETER`, `BMM_CONSTANT`, `BMM_SINGLETON` — callable and static class features, with differential and flat feature views.
- `BMM_ASSERTION` / `BMM_LITERAL_VALUE` — Design-by-Contract invariants and typed literal value wrappers.
- Expression meta-classes — `EL_EXPRESSION`, `EL_LITERAL`, `EL_FEATURE_REF`, `EL_AGENT`, `EL_PREDICATE`, `EL_OPERATOR`, `EL_CONDITION_CHAIN`, `EL_DECISION_TABLE`, `EL_TUPLE` — sufficient for first-order predicate logic over model entities.

Per-meta-class attribute detail is best retrieved via the BMM-backed `type_specification_get` tool rather than duplicated here.

## Relations to Other Specs

- Evolves: `LANG/bmm` (stable BMM v2 — rm_access/core/persistence packages); BMM3 renames `rm_access` to `model_access` and splits `core` into five sub-packages.
- Depends on / paired with: `LANG/bmm_persistence` (`org.openehr.lang.bmm_persistence` package for serialised schemas in ODIN, JSON, or XML).
- Shares lineage with: `LANG/EL` and `LANG/BEL` — the `expression` sub-package is the meta-model side of the openEHR expression-language work, also reused by `AM/AOM2` rules.
- Describes: `RM/*`, `BASE`, and `AM` object models when those are published as BMM schemas.

## Architectural Placement

BMM3 occupies the same LANG-layer slot as stable BMM: the computable schema formalism below AM and RM against which archetype validation, AQL path typing, and code generation can operate. Its expression meta-model additionally positions it as the semantic foundation for invariants and rules across the specifications. Because the document is PAUSED, implementers should treat it as a design reference for where BMM is heading, not as a current conformance target.

## When to Read the Full Spec

Read the full document when implementing or evaluating next-generation model tooling that needs tuple types, routine signatures, enumeration/range-constrained classes, or computable invariants; when mapping the EL expression meta-classes used by AOM2 rules back to their meta-model definitions; or when reconciling differences between a v2 (`LANG/bmm`) schema consumer and v3 concepts such as `model_access` and the sub-packaged core.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/bmm3.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/bmm3.md
- Related digests: specs/lang-bmm, specs/lang-bmm_persistence, specs/lang-EL
