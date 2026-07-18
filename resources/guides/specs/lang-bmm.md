# Basic Meta-Model (BMM) — Digest

**Scope:** Human- and machine-readable meta-model for defining openEHR object models (RM, AM, BASE) as a compact, computable alternative to UML/XMI.
**Component:** LANG
**Document:** bmm
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/bmm.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/bmm.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/lang-bmm3, openehr://guides/specs/lang-bmm_persistence, openehr://guides/specs/lang-odin
**Keywords:** BMM, basic meta-model, object model, RM, AM, type specification, computable model, P_BMM, schema

---

## Purpose

Defines a meta-model ("a model of object models") for describing the interface layer of object-oriented information models in a formalism that is both human-readable and fully machine-processable. BMM exists to give openEHR Reference Model, Archetype Model, and BASE specifications a single computable representation that can be loaded at runtime to validate archetypes against their RM, validate data against operational templates, drive code generation, and interconvert with other modelling formalisms — a role for which UML/XMI proved insufficient due to weak generic type support, non-standardised XMI serialisation across tools, and brittle qualified attributes. BMM is not a required formalism for implementing openEHR; it is the format used by tools such as the ADL Workbench and LinkEHR.

## Scope

- In: the `rm_access` package (application-facing interface for schema load/reload, used as a reflection library); the `core` package (in-memory meta-classes for models, classes, types, and properties, with single and multiple inheritance and differential vs flat property sets); the class/type distinction and the property meta-type quartet (`BMM_SIMPLE_TYPE`, `BMM_OPEN_TYPE`, `BMM_GENERIC_TYPE`, `BMM_CONTAINER_TYPE`); the `persistence` package concept — simplified `P_BMM_*` classes serialised as `.bmm` schema files with inclusion and re-use.
- Out: the `P_BMM_*` class definitions and serialised syntax themselves (delegated to `LANG/bmm_persistence`); the richer BMM v3 development line — sub-packaged core, expression meta-model, decision tables (`LANG/bmm3`, currently PAUSED); archetype/template formalisms (`AM/ADL2`, `AM/AOM2`); runtime query (`QUERY/AQL`); concrete implementation of any particular Reference Model — BMM only *describes* such models.

## Key Classes / Constructs

BMM is itself a meta-model, so the entities below are meta-classes whose instances describe user models (e.g. the openEHR RM). Per-meta-class structural detail is best retrieved on demand via the server's `type_specification_get` tool, which is itself backed by BMM-described schemas.

- `BMM_MODEL_ELEMENT` — top-level abstract ancestor introducing features common to every BMM element, such as `documentation`.
- `BMM_CLASSIFIER` — abstract parent of anything usable as the type of a property (classes, types, generic parameters); defines `type_name`, `type_signature`, and `conformance_type_name` semantics.
- `BMM_TYPE` — descendant of `BMM_CLASSIFIER` defining the semantics of instance types.
- `BMM_CLASS` — definitional class entry; exposes `properties` (differential set) and `flat_properties` (inheritance-flattened set); supports single and multiple inheritance as an acyclic graph.
- `BMM_PROPERTY<T: BMM_TYPE>` — class property whose meta-type is one of the four property meta-types.
- `BMM_SIMPLE_TYPE` / `BMM_OPEN_TYPE` / `BMM_GENERIC_TYPE` / `BMM_CONTAINER_TYPE` — the design-time type forms: a simple class, a generic parameter (`T`, `U`), a bound generic (`Interval<Time>`), and a linear container (`List<T>`, `Hash<T,U>`).
- `P_BMM_*` classes (persistence package) — simplified, symbolically-referenced counterparts of the `BMM_*` classes enabling human-authorable, includable schema files; specified in full in `LANG/bmm_persistence`.

## Relations to Other Specs

- Depends on: `LANG/odin` (historical default persistence syntax for serialised schemas — JSON, YAML, or XML may also be used), foundational `BASE` type taxonomy (Any, primitive types).
- Consumed by: `LANG/bmm_persistence` (P_BMM serialisation format), `AM/AOM2` and `AM/ADL2` (archetype constraints are typed against a BMM model), `RM/*` (each Reference Model package is distributed as a BMM schema set), `QUERY/AQL` (path and type resolution against the RM's BMM), and tooling such as the `type_specification_get` MCP resource that resolves type queries against BMM instances.
- Succeeded by (in development): `LANG/bmm3` — the BMM v3 line restructures core into model/entity/feature/literal_value/expression sub-packages and adds an expression meta-model; its status is PAUSED.

## Architectural Placement

BMM sits in the LANG layer alongside ODIN and the expression languages, immediately below AM and RM: it is the computable schema formalism in which every openEHR object model is declared, and the substrate against which archetype/template validation, AQL path typing, and code-generation pipelines operate. A schema-reading component resolves `P_BMM_*` file inclusions into a fully-resolved in-memory `BMM_*` object structure — the "BMM model" — that applications use via the `rm_access` reflection interface.

## When to Read the Full Spec

Consult the full document when building a BMM schema loader or validator, implementing the class/type distinction or generic-parameter substitution, computing differential vs flat property sets, authoring a new Reference Model or extension schema for use with ADL tooling, or deciding between the stable BMM and the paused BMM3 line for a new tool. For P_BMM file syntax and schema-inclusion mechanics go directly to `LANG/bmm_persistence`.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/bmm.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/bmm.md
- Related digests: specs/lang-bmm3, specs/lang-bmm_persistence, specs/lang-odin
