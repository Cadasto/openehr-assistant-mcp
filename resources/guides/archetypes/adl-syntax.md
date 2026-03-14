# openEHR Archetype ADL & Syntax Guide

**Scope:** Correct and idiomatic use of ADL and the Archetype Object Model (AOM)
**Related:** openehr://guides/archetypes/adl-idioms-cheatsheet, openehr://guides/archetypes/structural-constraints, openehr://guides/archetypes/rules
**Keywords:** ADL, archetype, syntax, guide, reference, formal, constraint, AM, AOM, terms, structure, path, definition, lint

---

## Purpose of ADL

ADL is a **formal constraint language** expressing:
- constraints on the openEHR Reference Model (RM)
- clinical semantics via archetype terms
- computable structure with stable paths

ADL is not a programming language or data serialization format.

---

## Archetype Model Fundamentals

An archetype constrains RM classes, attributes, occurrences/cardinalities, and data types (DV_*).

> Every constraint must be valid against the AOM and underlying RM.

### AOM Constraint Types (AOM 1.4)

- **C_OBJECT** — abstract; has `rm_type_name`, `occurrences`, `node_id`
- **C_COMPLEX_OBJECT** — complex RM type with attributes
- **C_PRIMITIVE_OBJECT** — primitive types (String, Integer, Date, etc.)
- **C_ATTRIBUTE** — attribute constraint; has `rm_attribute_name`, `existence`
    - **C_SINGLE_ATTRIBUTE** — single-valued (one child)
    - **C_MULTIPLE_ATTRIBUTE** — container with `cardinality`
- **ARCHETYPE_SLOT** — placeholder via `include`/`exclude` assertions
- **ARCHETYPE_INTERNAL_REF** — reuse constraint from elsewhere in same archetype via `target_path` (ADL: `use_node`)
- **CONSTRAINT_REF** — reference to ac-code for external terminology

---

## Archetype Sections

### Header
- Archetype identifier, original language, description/metadata
- ID must follow naming conventions; version reflects semantic compatibility

### Definition
- Formal constraint tree
- Root node matches declared RM type
- All constraints follow RM attribute semantics

### Ontology / Terminology
- `term_definitions`: at-codes with text and description
- `constraint_definitions`: ac-codes explaining value set meaning
- `term_bindings`: at-codes → external terminology codes
- `constraint_bindings`: ac-codes → terminology queries

### Invariant 
Optional section for first-order predicate logic assertions (cross-node relationships, formulae, conditional constraints):

```adl
invariant
    speed_validity: /speed[at0002]/km/magnitude = /speed[at0004]/miles/magnitude * 1.6
```

---

## Constraint Syntax

### RM Attributes
- Use RM attribute names exactly as defined
- Do not invent or alias attributes

**Incorrect:**
```adl
value matches { DV_TEXT }
```
**Correct:**
```adl
value matches { DV_TEXT matches {*} }
```

### Existence vs Occurrences vs Cardinality

- **existence** — attributes; mandatory (`1..1`) or optional (`0..1`)
- **occurrences** — object nodes; how many times object may appear
- **cardinality** — container attributes; how many children allowed

> Never confuse occurrences with cardinality.

### Internal References (use_node)

Reuse constraints from elsewhere in same archetype:
```adl
use_node CLUSTER[at0010] /items[at0005]
```

### Leaf Nodes
Constrain RM type and optionally internal attributes (units, magnitude, code). Avoid unconstrained leaves.

---

## Paths and Identifiers

- Paths derived from constraint tree
- Must be stable across versions

> Path stability is more important than aesthetic structure.

---

## Slots

Slots (allow_archetype, include, exclude) must:
- Clearly state intent
- Constrain whenever possible
- Reference valid archetype identifiers
- Avoid unconstrained wildcards

---

## ADL Style

- Human-readable, consistently indented
- Group related constraints
- Avoid deep nesting without semantic justification

---

## Versioning

- Syntax-only changes → patch
- Constraint changes → minor/major
- Structural refactoring → major

---

## Anti-Patterns

- Invalid RM attribute names
- Missing term definitions
- Unconstrained DV_* everywhere
- `matches {*}` as default
- Template logic in archetypes

---

## Validation

All archetypes must:
- Parse successfully
- Validate against RM
- Preserve semantic paths

> Syntax correctness is prerequisite for modelling quality.

---
