# openEHR Archetype Structural Constraint Guidelines
**URI:** guidelines://archetypes/v1/structural-constraints  
**Version:** 1.0.0  
**Scope:** Cardinality, existence, occurrences, slots, and structural constraints

---

## 1. Design Philosophy

Archetypes should:
- Be **clinically safe**
- Remain **maximally reusable**
- Avoid encoding local workflow assumptions

> Constrain only what is universally true.

---

## 2. Existence (Mandatory vs Optional)

### 2.1 Mandatory Elements (`existence = 1..1`)

Use only when:
- The data item is *intrinsic* to the concept
- Absence would invalidate the record

**Example:**  
`Systolic value` in a blood pressure measurement

---

### 2.2 Optional Elements (`existence = 0..1`)

Default choice for:
- Contextual qualifiers
- Supporting or conditional data

---

## 3. Cardinality and Occurrences

### 3.1 Single vs Multiple

- Use single occurrences when the real-world concept is singular
- Use multiple occurrences only when repetition is clinically meaningful

**Avoid:**
- `0..*` as a default
- Artificial upper bounds without rationale

---

### 3.2 Upper Bounds

Upper bounds should:
- Reflect real-world constraints
- Be clinically justified
- Avoid “magic numbers”

---

## 4. Slots and Archetype Reuse

### 4.1 When to Use Slots

Slots are appropriate when:
- The content varies by context
- Multiple domain-specific implementations exist
- Reuse is expected across specialisations

---

### 4.2 Slot Constraints

- Constrain slots by **archetype type and purpose**
- Avoid unconstrained slots unless absolutely necessary
- Document intended slot usage clearly

---

## 5. Clusters vs Elements

- Use **CLUSTER** for logically grouped sub-concepts
- Use **ELEMENT** for atomic data values
- Do not use clusters as generic containers

---

## 6. Avoiding Over-Constraint

**Do not encode:**
- UI layout assumptions
- Workflow sequencing
- Local business rules
- Template-level decisions

Those belong in **templates**, not archetypes.

---

## 7. Structural Anti-Patterns

- Making everything mandatory
- Excessive nesting without semantic value
- Deep hierarchies to compensate for poor concept scoping
- Using slots to bypass modelling decisions

---

## 8. Review Questions

- Could this constraint prevent legitimate reuse?
- Is this constraint universally true?
- Would a template be a better place for this rule?

---

## Revision History

| Version | Date | Notes |
|--------|------|------|
| 1.0.0 | 2025-12 | Initial release |
