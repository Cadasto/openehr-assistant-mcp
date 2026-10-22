# AQL Idioms Cheat Sheet

**Purpose:** Fast reference for writing and reviewing AQL
**Related:** openehr://guides/aql/syntax, openehr://guides/aql/principles
**Keywords:** AQL, idioms, cheat sheet, containment, path, parameter, pattern

---

## Canonical Skeleton

Containment follows RM hierarchy; for contained types and AND/OR/NOT CONTAINS see openehr://guides/aql/syntax (FROM and CONTAINS).

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

Apply early in containment for selectivity.

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

Set variant (spec-normative — list items are OR-ed, parameters allowed):

```aql
WHERE x/.../value/defining_code/code_string MATCHES {'code1', 'code2'}
```

`IN` is not in the spec (engine extension); prefer MATCHES.

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

Every repeating segment must use a node id (at-code from the archetype); path semantics: see openehr://guides/aql/syntax (Paths and Predicates).

✅ `events[at0006]/data[at0003]/items[at0004]`  
⚠ `events/data/items`

---

## Sibling Disambiguation by Name (Node Predicate)

When one archetype node repeats as multiple named runtime siblings, add the name to the node predicate (spec-defined shortcuts):

```aql
items[at0001, 'Systolic']                 -- name/value shortcut
items[at0001 and name/value='Systolic']   -- explicit form
items[at0001, $nameValue]                 -- parameterized
```

---

## Version Audit and History (Verify Engine Support)

Commit audit of current versions:

```aql
SELECT
  c/uid/value AS uid,
  v/commit_audit/time_committed/value AS committed,
  v/commit_audit/change_type/defining_code/code_string AS change_type
FROM EHR e
  CONTAINS VERSION v[LATEST_VERSION]
    CONTAINS COMPOSITION c
```

Full history: `VERSION v[ALL_VERSIONS]`; add `v/preceding_version_uid/value` to chain revisions.

Always state `[LATEST_VERSION]` / `[ALL_VERSIONS]` explicitly and verify engine support (see openehr://guides/aql/syntax, VERSION in FROM). Lifecycle filtering via `v/lifecycle_state/defining_code/code_string` (openEHR terminology: 532 complete, 553 incomplete, 523 deleted, 800 inactive, 801 abandoned) is likewise engine-dependent.

---

## Parameter Naming

- `$ehrId` (UUID string)
- `$from`, `$to` (temporal)
- `$code` (string); for a set, one parameter per item: `MATCHES {$code1, $code2}` (list-valued parameters are engine-specific)
- No `$limit`/`$offset`: LIMIT/OFFSET take integer literals; page at runtime via the query API (`offset`/`fetch`)

Keep names consistent for predictable execution.

---

## Micro Check Before Run

- Deployed OPT/templates known; paths and archetype ids valid against them?
- All aliases declared in FROM and used in SELECT/WHERE/ORDER BY?
- Node ids on every repeated path segment (archetype path correctness)?
- Parameters for all variable inputs; no string interpolation?
- ORDER BY present when using LIMIT for stable results?

---
