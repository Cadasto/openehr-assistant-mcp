# Basic Meta-Model (BMM) — Digest

**Scope:** Human- and machine-readable meta-model for defining openEHR object models (RM, AM, BASE) as a compact, computable alternative to UML/XMI.
**Component:** LANG
**Document:** bmm
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/bmm.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/bmm.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/lang-bmm_persistence, openehr://guides/specs/lang-odin
**Keywords:** BMM, basic meta-model, object model, RM, AM, type specification, computable model

---

## Purpose

Defines a meta-model ("a model of object models") for describing the interface layer of object-oriented information and computational models in a formalism that is both human-readable and fully machine-processable. BMM exists to give openEHR Reference Model, Archetype Model, and BASE specifications a single computable representation that can be loaded at runtime to validate archetypes against their RM, validate data against operational templates, drive code generation, and interconvert with other modelling formalisms — a role for which UML/XMI proved insufficient due to weak generic type support, non-standardised XMI serialisation across tools, and brittle qualified attributes.

## Scope

- In: meta-classes for models, packages, classes (simple, generic, enumerated), types (simple, generic, container, indexed-container, tuple, parameter, routine, signature), features (properties, constants, singletons, functions, procedures, variables, parameters), literal value wrappers, assertions/invariants, and a small embedded expression meta-model (`EL_*`) covering literals, feature/variable references, operators, predicates, agents, decision tables, and case tables.
- Out: concrete persistence syntax for BMM schemas (delegated to `LANG/bmm_persistence`, typically ODIN-serialised); archetype/template formalisms themselves (`AM/ADL2`, `AM/AOM2`); runtime query (`QUERY/AQL`); service interfaces (`SM`); concrete implementation of any particular Reference Model — BMM only *describes* such models.

## Key Classes / Constructs

BMM is itself a meta-model, so the entities below are meta-classes whose instances describe user models (e.g. the openEHR RM). Per-meta-class structural detail is best retrieved on demand via the server's `type_specification_get` tool, which is the canonical per-class lookup mechanism and is itself backed by BMM-described schemas.

- `BMM_MODEL` — top-level container of a complete model: owns packages, class definitions, schema metadata, and merge/include resolution.
- `BMM_CLASS` — base meta-class for definitional class entries; specialised into `BMM_SIMPLE_CLASS`, `BMM_GENERIC_CLASS`, and `BMM_ENUMERATION`.
- `BMM_PROPERTY` — instantiable class feature holding state; split into `BMM_UNITARY_PROPERTY`, `BMM_CONTAINER_PROPERTY`, and `BMM_INDEXED_CONTAINER_PROPERTY` for single, collection, and keyed-collection semantics.
- `BMM_TYPE` — abstract root of the type hierarchy; specialised via `BMM_UNITARY_TYPE`, `BMM_SIMPLE_TYPE`, `BMM_MODEL_TYPE`, and `BMM_PARAMETER_TYPE` (formal generic parameter).
- `BMM_GENERIC_TYPE` — effective type obtained by binding a `BMM_GENERIC_CLASS` with concrete parameter substitutions, enabling `List<T>`, `Interval<T>`, and similar constructs with proper type-safe polymorphism.
- `BMM_CONTAINER_TYPE` / `BMM_INDEXED_CONTAINER_TYPE` — first-class meta-types for list/set/bag collections and hash/dictionary collections, avoiding dependence on concrete library implementations.
- `BMM_ROUTINE` — base for callable features, specialised as `BMM_FUNCTION` (value-returning, operator-aliasable) and `BMM_PROCEDURE` (state-changing, `Status`-returning); uses `BMM_SIGNATURE`/`BMM_PARAMETER` for formal signatures.
- `BMM_ASSERTION` / expression meta-classes (`EL_EXPRESSION`, `EL_FEATURE_REF`, `EL_OPERATOR`, `EL_CONDITION_CHAIN`, `EL_AGENT`, …) — formal Design-by-Contract invariants and pre/post-conditions, and the embedded expression sub-model used to state them.

## Relations to Other Specs

- Depends on: `LANG/odin` (default persistence syntax for serialised schemas), foundational `BASE` type taxonomy (Any, primitive types), and general `BASE` identification conventions.
- Consumed by: `LANG/bmm_persistence` (serialisation format), `AM/AOM2` and `AM/ADL2` (archetype constraints are typed against a `BMM_MODEL`), `RM/*` (each Reference Model package is distributed as a BMM schema set), `QUERY/AQL` (path and type resolution against the RM's BMM), and tooling such as the `type_specification_get` MCP resource that resolves type queries against BMM instances.

## Architectural Placement

BMM sits in the LANG layer alongside ODIN and cADL, immediately below AM and RM: it is the computable schema formalism in which every openEHR object model is declared, and the substrate against which archetype/template validation, AQL path typing, and code-generation pipelines operate. This positions BMM as the single shared type-system "ground truth" across the stack and makes it the format the openehr-assistant-mcp server's `type_specification_get` resolves against.

## When to Read the Full Spec

Consult the full document when building a BMM loader or validator, implementing type-conformance or generic-parameter substitution rules, authoring a new Reference Model or extension schema, integrating BMM-driven validation of archetypes/templates, or designing interconversion between BMM and UML/XSD/JSON-Schema where exact conformance and invariant semantics matter.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/bmm.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/bmm.md
- Related digests: specs/lang-bmm_persistence, specs/lang-odin
