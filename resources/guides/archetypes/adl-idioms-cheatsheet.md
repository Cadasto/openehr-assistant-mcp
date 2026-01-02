# Minimal ADL Idiom Cheat Sheet
**URI:** openehr://guides/archetypes/adl-idioms-cheatsheet  
**Version:** 1.0.0  
**Purpose:** Fast grounding for writing/reviewing ADL constraint trees without changing semantics
**Related:** Derived from openehr://guides/archetypes/adl-syntax; ties go to adl-syntax.

---

## 0) Mental model
- Archetype = constraints on **RM objects** and their **attributes**
- Tree = **C_OBJECT** (objects) + **C_ATTRIBUTE** (attributes)
- Leaf value types are **DV_\*** and usually need at least light constraints

---

## 1) Root and type correctness
**Idiom:** root node matches the declared RM type.
- If archetype is an OBSERVATION, root constraint must be OBSERVATION (not CLUSTER/ELEMENT).

---

## 2) Constrain attributes by “matches { … }”
**Idiom:** every constrained attribute is expressed as:
`<rm_attribute> matches { <constraint> }`

Avoid inventing attribute names. Use the RM attribute name exactly.

---

## 3) Occurrences vs Cardinality
- **occurrences** = how many times an *object node* may appear
- **cardinality** = how many children an *attribute container* may hold

**Idiom:**
- Use `occurrences` on repeated child objects
- Use `cardinality` on multi-valued attributes (containers)

---

## 4) “Existence” as the default optionality lever
**Idiom:** prefer making nodes optional/mandatory via existence/occurrences rather than overfitting with complex structures.

- Optional: `0..1`
- Mandatory: `1..1`

---

## 5) DV_TEXT / DV_CODED_TEXT / DV_QUANTITY: canonical leaf patterns

### 5.1 Free text leaf
Use DV_TEXT for narrative.
**Idiom:** keep it lightly constrained unless universal limits exist.

### 5.2 Coded leaf
Use DV_CODED_TEXT when values come from a value set.
**Idiom:** define a value set and bind it (don’t rely on free text).

### 5.3 Quantity leaf
Use DV_QUANTITY when you have magnitude + units.
**Idiom:** constrain units and/or magnitude where clinically universal.

---

## 6) Value sets: use ac-codes consistently
**Idiom:**
- Define a value set as an `acNNNN` group
- Reference that set from coded nodes

---

## 7) Paths must stay stable
**Idiom:** don’t restructure the tree just for aesthetics.
- Stable paths are more important than “pretty structure”
- Refactoring that changes paths usually implies a major version bump

---

## 8) Slots: constrain intent, don’t wildcard
**Idiom:** slots should be constrained to a known archetype family/type.

Avoid unconstrained slots unless you truly need “any CLUSTER”.

---

## 9) Protocol vs data vs state: keep semantics clean
**Idiom:**
- `data` = what was observed/recorded
- `protocol` = how it was measured/recorded (method, device, position)
- `state` = relevant state at the time (where applicable)

Don’t encode workflow sequencing.

---

## 10) “Don’t change meaning” rule for syntax fixes
When asked to “fix ADL”, default to:
- Preserve concept scope
- Preserve paths
- Preserve value semantics
  Only fix structural/formal issues.

---

## 11) Micro check before calling it “done”
- Parses?
- All coded nodes have term definitions?
- No invented RM attributes?
- Occurrences/cardinality used correctly?
- Slots constrained?

---
| Version | Date    | Notes           |
| ------- | ------- | --------------- |
| 1.0.0   | 2025-12 | Initial release |