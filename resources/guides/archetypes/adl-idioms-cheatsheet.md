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

**ADL 1.4 defaults:** unstated `occurrences` = `{1..1}`; unstated `existence` = `{1..1}`.

**Consistency rule (VCOC):** the sum of sibling occurrences ranges must fit inside the container's cardinality interval.

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

### Ordinal vs scale rating leaf (DV_ORDINAL vs DV_SCALE)

Ordered rating scale, coded `symbol` per rung (codes from `term_definitions`). Pick by rung **value** type:
- **DV_ORDINAL** — integer rungs (`value` Integer): Apgar, pain `0/+/++/+++`.
- **DV_SCALE** (RM ≥ 1.1.0) — non-integer rungs (`value` Real): modified Borg CR10 with a `0.5` step.

**Idiom:** value/symbol pairs as a `<value>|[local::<at-code>]` list.

```cadl
value matches { DV_ORDINAL matches {            -- integer rungs
    0|[local::at0010], 1|[local::at0011], 2|[local::at0012], 3|[local::at0013] } }
value matches { DV_SCALE matches {              -- Real rungs, incl. 0.5
    0.0|[local::at0020], 0.5|[local::at0021], 1.0|[local::at0022] } }   -- ...
```

> ADL 1.4 has no profiled `C_DV_SCALE`; the form mirrors DV_ORDINAL with Real values — confirm your toolchain's serialization. **Caveat:** DV_SCALE needs RM 1.1.0-aware tooling; keep DV_ORDINAL for integer-only scales and existing data.

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

**Worked example (event `state` + observation `protocol`):** `openehr://examples/archetypes/openEHR-EHR-OBSERVATION.blood_pressure.v2` — subject state on the **event** (Position, Confounding factors), method/instrument facts in the observation `protocol` (Cuff size, Location, Method).

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
