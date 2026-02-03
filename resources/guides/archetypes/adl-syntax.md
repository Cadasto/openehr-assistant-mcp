# openEHR Archetype ADL & Syntax Guide

**Scope:** Correct and idiomatic use of ADL and the Archetype Model (AM)
**Applies to:** ADL 1.4 / ADL 2 archetypes
**Keywords:** ADL, archetype, syntax, guide, reference, formal, constraint, AM, terms, structure, path, definition

---

## Purpose of ADL

The Archetype Definition Language (ADL) is a **formal constraint language** used to express:
- constraints on the openEHR Reference Model (RM)
- clinical semantics via archetype terms
- computable structure with stable paths

ADL is **not** a programming language and **not** a data serialization format.

---

## Archetype Model Fundamentals

An archetype constrains:
- RM classes (e.g. COMPOSITION, OBSERVATION, CLUSTER, ELEMENT, ITEM_TREE)
- Attributes of those classes
- Occurrences and cardinalities
- Data value types (DV_*)

**Rule:**
> Every constraint must be valid with respect to the openEHR Archetype Object Model (AOM) and the underlying RM.

### AOM Constraint Types (AOM 1.4)

The Archetype Object Model defines a hierarchy of constraint types:

- **C_OBJECT** — abstract constraint on any object node; has `rm_type_name`, `occurrences`, and `node_id`
- **C_COMPLEX_OBJECT** — constraint on a complex RM type with attributes
- **C_PRIMITIVE_OBJECT** — constraint on primitive types (String, Integer, Boolean, Date, etc.)
- **C_ATTRIBUTE** — constraint on an attribute; has `rm_attribute_name` and `existence`
  - **C_SINGLE_ATTRIBUTE** — single-valued attribute (exactly one child)
  - **C_MULTIPLE_ATTRIBUTE** — container attribute with `cardinality`
- **ARCHETYPE_SLOT** — placeholder for other archetypes via `include`/`exclude` assertions
- **ARCHETYPE_INTERNAL_REF** — reuse constraint from elsewhere in same archetype via `target_path` (ADL: `use_node`)
- **CONSTRAINT_REF** — reference to an ac-code for external terminology constraints

---

## Archetype Sections and Their Meaning

### Header Section

Includes:
- archetype identifier
- original language
- description and metadata

**Rules:**
- Archetype ID must follow naming conventions
- Version suffix must reflect semantic compatibility

---

### Definition Section

The `definition` section contains the **formal constraint tree**.

- Root node must correspond to the declared RM type
- All constraints must follow RM attribute semantics
- Use `C_OBJECT`, `C_ATTRIBUTE`, `C_SINGLE_ATTRIBUTE`, `C_MULTIPLE_ATTRIBUTE`, `C_COMPLEX_OBJECT`, `ARCHETYPE_SLOT` correctly

---

### Ontology / Terminology Section

Defines:
- archetype terms (`at-codes`) in `term_definitions`
- constraint definitions (`ac-codes`) in `constraint_definitions`
- term bindings to external terminologies in `term_bindings`
- constraint bindings (terminology queries) in `constraint_bindings`

**Rules:**
- Every node identifier (`atNNNN`) must have a term definition with text and description.
- Every constraint code (`acNNNN`) must have a constraint definition explaining its meaning.
- Term bindings map at-codes to external terminology codes (global or path-based).
- Constraint bindings map ac-codes to terminology queries (e.g., "any subtype of hepatitis").

### Invariant Section (ADL 1.4)

The optional `invariant` (or `rules`) section contains first-order predicate logic assertions that apply across the entire archetype. Use for constraints that cannot be expressed within the block structure of the definition section, such as:
- Cross-node relationships
- Mathematical formulae between values
- Conditional constraints

Example:
```adl
invariant
    speed_validity: /speed[at0002]/kilometres/magnitude = /speed[at0004]/miles/magnitude * 1.6
```

---

## Constraint Syntax and Idioms

### RM Attribute Constraints

- Use RM attribute names exactly as defined
- Do not invent or alias RM attributes
- Respect attribute multiplicity

**Incorrect:**
```adl
value matches {
  DV_TEXT
}
```
**Correct:**
```adl
value matches {
  DV_TEXT matches {*}
}
```

### Occurrences vs Cardinality vs Existence (AOM 1.4)

- **existence** — applies to attributes; indicates whether the attribute value is mandatory (`1..1`) or optional (`0..1`); range is always within `0..1`
- **occurrences** — applies to object nodes (C_OBJECT); indicates how many times the object may appear under its owning attribute
- **cardinality** — applies to container attributes (C_MULTIPLE_ATTRIBUTE); indicates how many children the container may hold

**Rule:**
> Never confuse occurrences with cardinality. Use existence for attribute-level optionality.

### Internal References (use_node)

The `use_node` construct (AOM: `ARCHETYPE_INTERNAL_REF`) allows reusing a constraint defined elsewhere in the same archetype by referencing its path:

```adl
use_node CLUSTER[at0010] /items[at0005]
```

This avoids duplication and ensures consistency when the same structure appears multiple times.

### Leaf Node Constraints

Leaf nodes must constrain:
- the RM type (e.g. DV_QUANTITY)
- optionally its internal attributes (units, magnitude, code)

Avoid unconstrained leaf nodes unless justified.

---

## Archetype Paths and Identifiers

- Paths are derived from the constraint tree
- Paths must be stable across versions
- Avoid structural refactoring that breaks paths unless versioning rules are followed

**Rule:**
> Path stability is more important than aesthetic structure.

---

## Slot Syntax and Semantics

Slots (allow_archetype, include, exclude) must:
- Clearly state intent
- Be constrained whenever possible
- Reference valid archetype identifiers
- Avoid unconstrained wildcard slots.

---

## ADL Style and Readability

Although ADL is machine-readable, it should also be:
- Human-readable
- Consistently indented
- Structured to reflect semantics

Instructions:
- Group related constraints
- Avoid deeply nested structures without semantic justification

---

## Versioning and Syntax Changes

- Syntax-only changes (formatting, comments) → patch version
- Constraint changes affecting interpretation → minor/major version
- Structural refactoring → major version

---

## Common ADL Syntax Anti-Patterns

- Invalid RM attribute names
- Missing term definitions
- Unconstrained DV_* usage everywhere
- Misuse of matches {*} as a default
- Encoding template logic in archetypes

---

## Validation Expectations

All archetypes must:
- Parse successfully with an ADL parser
- Validate against the target RM
- Preserve semantic paths

> Syntax correctness is a prerequisite for all higher-level modelling quality.

---
