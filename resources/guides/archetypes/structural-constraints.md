# openEHR Archetype Structural Constraint Guide

**Purpose:** Provide guidance on how to use archetype constraints to achieve clinically meaningful structure
**Keywords:** cardinality, existence, occurrences, slots, structural, constraints, rules, structure, ADL

---

## Design Philosophy

Archetypes should:
- Be **clinically safe**
- Remain **maximally reusable**
- Avoid encoding local workflow assumptions

> Constrain only what is universally true.

---

## Existence (Mandatory vs Optional)

In AOM 1.4, **existence** is a constraint on `C_ATTRIBUTE` indicating whether the attribute's value must be present. 

**Note:** Existence applies to attributes, not objects. For object-level optionality, use **occurrences**.

### Mandatory Elements (`existence = 1..1`)

Use only when:
- The data item is *intrinsic* to the concept
- Absence would invalidate the record

**Example:**  
`Systolic value` in a blood pressure measurement

---

### Optional Elements (`existence = 0..1`)

Default choice for:
- Contextual qualifiers
- Supporting or conditional data

---

## Cardinality and Occurrences

### Single vs Multiple

- Use single occurrences when the real-world concept is singular
- Use multiple occurrences only when repetition is clinically meaningful

**Avoid:**
- `0..*` as a default
- Artificial upper bounds without rationale

---

### Upper Bounds

Upper bounds should:
- Reflect real-world constraints
- Be clinically justified
- Avoid “magic numbers”

---

## Slots and Archetype Reuse

### When to Use Slots

Slots are appropriate when:
- The content varies by context
- Multiple domain-specific implementations exist
- Reuse is expected across specialisations

---

### Slot Constraints

- Constrain slots by **archetype type and purpose**
- Avoid unconstrained slots unless absolutely necessary
- Document intended slot usage clearly

---

## Clusters vs Elements

- Use **CLUSTER** for logically grouped sub-concepts
- Use **ELEMENT** for atomic data values
- Do not use clusters as generic containers

---

## Avoiding Over-Constraint

**Do not encode:**
- UI layout assumptions
- Workflow sequencing
- Local business rules
- Template-level decisions

Those belong in **templates**, not archetypes.

---

## Structural Anti-Patterns

- Making everything mandatory
- Excessive nesting without semantic value
- Deep hierarchies to compensate for poor concept scoping
- Using slots to bypass modelling decisions

---

## Review Questions

- Could this constraint prevent legitimate reuse?
- Is this constraint universally true?
- Would a template be a better place for this rule?

---
