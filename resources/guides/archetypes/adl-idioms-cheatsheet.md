# Minimal ADL Idiom Cheat Sheet

**Scope:** Normative idioms for writing and reviewing ADL constraint trees **without changing semantics**  
**Related:** `openehr://guides/archetypes/adl-syntax`, `openehr://guides/archetypes/rules`  
**Keywords:** ADL, constraint, syntax, idioms, cheat sheet, minimal, fast, QA

---

## 0. Core Mental Model

- Archetype = constraints on **Reference Model (RM) objects**
- Constraint tree = **C_OBJECT** + **C_ATTRIBUTE**
- Leaves constrain **DV_\*** types and SHOULD never be semantically empty

---

## 1. Root Node Correctness

**Rule:** Root constraint MUST match the declared RM type.

> OBSERVATION archetype → OBSERVATION root  
> CLUSTER archetype → CLUSTER root

Never substitute a more "convenient" RM type.

---

## 2. Attribute Constraint Idiom
**Canonical form:** `<rm_attribute> matches { <constraint> }`

- RM attribute names MUST match the RM exactly
- Never invent or alias attribute names

---

## 3. Occurrences vs Cardinality

- **occurrences** → how many times an object node may appear
- **cardinality** → how many children a container attribute may hold

**Rule:** Never interchange them.

**Container Cardinality Default:** Containers (CLUSTER, ITEM_LIST, items/events) should default to `1..*` (at least one child) — empty containers have no semantic meaning. Constrain only when clinically justified.

---

## 4. Optionality: Prefer Existence Over Structure
- Optional: `existence = 0..1`
- Mandatory: `existence = 1..1`

Avoid encoding optionality through structural contortions.

---

## 5. Canonical Leaf Idioms

### Free text leaf
Use DV_TEXT for narrative.
**Idiom:** keep it lightly constrained unless universal limits exist.

### Coded leaf
Use DV_CODED_TEXT when values come from a value set.
**Idiom:** define a value set and bind it (don’t rely on free text).

### Quantity leaf
Use DV_QUANTITY when you have magnitude + units.
**Idiom:** constrain units and/or magnitude where clinically universal.

---

## 6. Value Sets (ac-codes)

- Define value sets explicitly
- Reference them consistently from coded nodes
- Never mix free text and coded semantics

---

## 7. Path Stability Is Sacred

- Do not restructure for aesthetics
- Path changes usually imply a **major version bump**

---

## 8. Slot Idioms

- Slots MUST express intent
- Prefer constrained slots over wildcards
- Document expected archetype families

---

## 9. data / protocol / state Separation

- **data** = what was observed
- **protocol** = how it was observed
- **state** = relevant subject state

Never encode workflow ordering.

---
## 10. "Syntax Fix ≠ Semantic Change"

When fixing ADL:
- Preserve concept scope
- Preserve paths
- Preserve meaning and semantics

Only repair formal or syntactic defects.

---

## 11. Final Micro-Check

- Parses?
- Valid RM attributes?
- Term definitions complete?
- Occurrences/cardinality correct?
- Slots constrained?
