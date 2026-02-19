# openEHR Archetype Structural Constraint Guide

**URI:** `openehr://guides/archetypes/structural-constraints`  
**Version:** 1.1.0  
**Scope:** Normative guidance for existence, cardinality, occurrences, and slots  
**Keywords:** cardinality, existence, occurrences, slots, constraints

---

## 1. Modelling Principle

> Constrain only what is universally and clinically true.

Archetypes optimise for reuse and safety, not local workflows.

---

## 2. Existence

**Existence** (AOM 1.4) constrains `C_ATTRIBUTE` — whether an attribute value must be present.

- Mandatory only when intrinsic to the concept
- Optional by default

**Note:** Existence applies to attributes. For object-level optionality, use **occurrences**.

---

## 3. Cardinality & Occurrences

**Cardinality** (on container attributes like `items`, `events`) defines how many children the container may hold.

- **Default for containers:** `1..*` (at least one child; empty containers are semantically invalid)
- Single vs repeating reflects real-world semantics
- Avoid `0..*` defaults (an empty container has no meaning)
- Upper bounds must be clinically justified

**Occurrences** (on object nodes) defines how many times an object may appear in its parent; separate from cardinality.

---

## 4. Slots

Use slots when:
- Content varies by context
- Multiple domain-specific implementations exist
- Reuse across specialisations is expected

Constraints:
- Constrain by archetype type and purpose
- Avoid unconstrained slots
- Document intended usage

---

## 5. Clusters vs Elements

- **CLUSTER** = inseparable group
- **ELEMENT** = atomic value
- Never use clusters as generic containers

---

## 6. Avoid Over-Constraint

Do not encode:
- UI layout
- Workflow
- Local business rules
- Template logic

These belong in **templates**.

---

## 7. Structural Anti-Patterns

- Everything mandatory
- Deep nesting without semantics
- Slots as modelling shortcuts

---

## 8. Review Questions

- Is this universally true?
- Does this limit reuse?
- Should this be a template concern?

---
