# openEHR Archetype ADL & Syntax Guide
**URI:** openehr://guides/archetypes/adl-syntax
**Version:** 1.0.0
**Scope:** Correct and idiomatic use of ADL and the Archetype Model (AM)
**Applies to:** ADL 1.4 / ADL 2 archetypes

---

## 1. Purpose of ADL

The Archetype Definition Language (ADL) is a **formal constraint language** used to express:
- constraints on the openEHR Reference Model (RM)
- clinical semantics via archetype terms
- computable structure with stable paths

ADL is **not** a programming language and **not** a data serialization format.

---

## 2. Archetype Model Fundamentals

An archetype constrains:
- RM classes (e.g. COMPOSITION, OBSERVATION, CLUSTER, ELEMENT, ITEM_TREE)
- Attributes of those classes
- Occurrences and cardinalities
- Data value types (DV_*)

**Rule:**
> Every constraint must be valid with respect to the openEHR Archetype Model and the underlying RM.

---

## 3. Archetype Sections and Their Meaning

### 3.1 Header Section

Includes:
- archetype identifier
- original language
- description and metadata

**Rules:**
- Archetype ID must follow naming conventions
- Version suffix must reflect semantic compatibility

---

### 3.2 Definition Section

The `definition` section contains the **formal constraint tree**.

- Root node must correspond to the declared RM type
- All constraints must follow RM attribute semantics
- Use `C_OBJECT`, `C_ATTRIBUTE`, `C_SINGLE_ATTRIBUTE`, `C_MULTIPLE_ATTRIBUTE`, `C_COMPLEX_OBJECT`, `ARCHETYPE_SLOT` correctly

---

### 3.3 Ontology / Terminology Section

Defines:
- archetype terms (`at-codes`)
- value sets (`ac-codes`)
- term bindings

**Rule:**
> Every coded node (`atNNNN`) must have a term definition.

---

## 4. Constraint Syntax and Idioms

### 4.1 RM Attribute Constraints

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

### 4.2 Occurrences vs Cardinality

- occurrences applies to objects
- cardinality applies to (multi-valued) attributes (containers)

**Rule:**
> Never confuse occurrences with cardinality.

### 4.3 Leaf Node Constraints

Leaf nodes must constrain:
- the RM type (e.g. DV_QUANTITY)
- optionally its internal attributes (units, magnitude, code)

Avoid unconstrained leaf nodes unless justified.

---

## 5. Archetype Paths and Identifiers

- Paths are derived from the constraint tree
- Paths must be stable across versions
- Avoid structural refactoring that breaks paths unless versioning rules are followed

**Rule:**
> Path stability is more important than aesthetic structure.

---

## 6. Slot Syntax and Semantics

Slots (allow_archetype, include, exclude) must:
- Clearly state intent
- Be constrained whenever possible
- Reference valid archetype identifiers
- Avoid unconstrained wildcard slots.

---

## 7. ADL Style and Readability

Although ADL is machine-readable, it should also be:
- Human-readable
- Consistently indented
- Structured to reflect semantics

Instructions:
- Group related constraints
- Avoid deeply nested structures without semantic justification

---

## 8. Versioning and Syntax Changes

- Syntax-only changes (formatting, comments) → patch version
- Constraint changes affecting interpretation → minor/major version
- Structural refactoring → major version

---

## 9. Common ADL Syntax Anti-Patterns

- Invalid RM attribute names
- Missing term definitions
- Unconstrained DV_* usage everywhere
- Misuse of matches {*} as a default
- Encoding template logic in archetypes

---

## 10. Validation Expectations

All archetypes must:
- Parse successfully with an ADL parser
- Validate against the target RM
- Preserve semantic paths

> Syntax correctness is a prerequisite for all higher-level modelling quality.

---
| Version | Date    | Notes           |
| ------- | ------- | --------------- |
| 1.0.0   | 2025-12 | Initial release |
