# AQL Idioms Cheat Sheet

**Purpose:** Fast reference for writing and reviewing AQL
**Related:** openehr://guides/aql/syntax, openehr://guides/aql/principles
**Keywords:** AQL, idioms, cheat sheet, containment, path, parameter, pattern

---

## Canonical Skeleton

Containment follows RM hierarchy; COMPOSITION can contain SECTION or ENTRY types (OBSERVATION, EVALUATION, ACTION, etc.). Use AND/OR/NOT CONTAINS when needed (see openehr://guides/aql/syntax).

```aql
SELECT <projections>
FROM EHR e
CONTAINS COMPOSITION c
  CONTAINS <RM_TYPE> x[<archetype_id>]
WHERE <filters>
ORDER BY <sort>
LIMIT <n> OFFSET <m>
```

---

## Identity Constraint (EHR-Scoped)

```aql
WHERE e/ehr_id/value = $ehrId
```

---

## Archetype Restriction (Contained Node)

```aql
CONTAINS OBSERVATION o[openEHR-EHR-OBSERVATION.blood_pressure.v1]
CONTAINS SECTION s[openEHR-EHR-SECTION.adhoc.v1]
```

Apply early in containment for selectivity. Containment targets SECTION, OBSERVATION, EVALUATION, ACTION, etc., as per RM and deployed templates.

---

## “Latest” Pattern

```aql
ORDER BY c/context/start_time/value DESC, c/uid/value ASC
LIMIT 1
```

Tie-breaker (e.g. UID) ensures determinism when timestamps collide.

---

## Time Window (Half-Open)

```aql
WHERE c/context/start_time/value >= $from
  AND c/context/start_time/value < $to
```

---

## DV_CODED_TEXT Filter

```aql
WHERE x/.../value/defining_code/code_string = $code
```

Set variant (verify engine support): `IN $codes`.

---

## DV_QUANTITY Projection

```aql
SELECT
  x/.../value/magnitude AS magnitude,
  x/.../value/units     AS units
```

---

## Existence Guard

```aql
WHERE EXISTS x/.../value
```

EXISTS is a unary operator (operand is an identified path). Prefer EXISTS over null comparisons.

---

## Counting Compositions

```aql
SELECT COUNT(c/uid/value) AS n
FROM EHR e CONTAINS COMPOSITION c
WHERE e/ehr_id/value = $ehrId
```

---

## Projection Discipline

Project only what you need: identifiers (e/ehr_id/value, c/uid/value), time (composition or event time), leaf values (magnitude, code_string, text). Avoid returning full RM subtrees unless required.

---

## Archetype Path and Node-Id Rule

Paths in AQL are **archetype paths**: grounded in the archetype definition and constraints; segments and predicates are tied to RM class properties. See openehr://guides/archetypes/adl-syntax for path semantics. Every repeating segment must use a node id (at-code from the archetype).

✅ `events[at0006]/data[at0003]/items[at0004]`  
⚠ `events/data/items`

---

## Parameter Naming

- `$ehrId` (UUID string)
- `$from`, `$to` (temporal)
- `$code`, `$codes` (string / list)
- `$limit`, `$offset` (integers)

Keep names consistent for predictable execution.

---

## Micro Check Before Run

- Deployed OPT/templates known; paths and archetype ids valid against them?
- All aliases declared in FROM and used in SELECT/WHERE/ORDER BY?
- Node ids on every repeated path segment (archetype path correctness)?
- Parameters for all variable inputs; no string interpolation?
- ORDER BY present when using LIMIT for stable results?

---
