# openEHR AQL Syntax and Grammar

**Scope:** Rules and grammar for writing and validating AQL
**Related:** openehr://guides/aql/principles, openehr://guides/aql/idioms-cheatsheet
**Keywords:** AQL, syntax, grammar, containment, path, predicate, SELECT, FROM, WHERE

**Official specification:** For full syntax, operators, and grammar details, visit the openEHR AQL specification: https://specifications.openehr.org/releases/QUERY/development/AQL — agents should consult it when looking for more information beyond this guide.

---

## Clause Order

AQL has a fixed, SQL-like clause order:

```aql
SELECT <select_list>
FROM <from_expr>
[WHERE <boolean_expr>]
[ORDER BY <order_list>]
[LIMIT <n> [OFFSET <m>]]
```

Clause order is not permutable. Optional clauses (WHERE, ORDER BY, LIMIT) may not be supported by all engines; check engine docs.

---

## Lexical Conventions

- **Keywords:** Case-insensitive (SELECT, select, etc.).
- **Aliases:** Identifiers bound in FROM/CONTAINS (e.g. `e`, `c`, `o`). Use short, role-based names (e = EHR, c = COMPOSITION).
- **Literals:** Strings in single or double quotes (`'text'` or `"text"` per spec); numbers unquoted; prefer parameters for dates/times.
- **Parameters:** `$name` (e.g. `$ehrId`, `$from`, `$to`). Always parameterize variable input; never interpolate untrusted values into AQL.

---

## FROM and CONTAINS (Containment)

Containment constrains openEHR RM containment (not relational joins). The containment hierarchy follows the RM: e.g. EHR contains COMPOSITION; COMPOSITION can contain SECTION, ENTRY (OBSERVATION, EVALUATION, ACTION, ADMIN_ENTRY, etc.), or other content as defined by the RM and archetypes. Examples:

```aql
FROM EHR e
CONTAINS COMPOSITION c
  CONTAINS OBSERVATION o[openEHR-EHR-OBSERVATION.blood_pressure.v1]
```

```aql
FROM EHR e
CONTAINS COMPOSITION c
  CONTAINS SECTION s[openEHR-EHR-SECTION.adhoc.v1]
```

- **Class expression:** RM type + optional alias + optional predicate.
- **Archetype predicate:** Square brackets with archetype id. Apply as early as possible for selectivity.
- **Containment:** Parent CONTAINS child; multiple CONTAINS form a tree.
- **Logical operators on CONTAINS (spec):** AND and OR and parentheses combine multiple containment constraints. Example: same EHR with two composition types — `CONTAINS COMPOSITION c [...] AND COMPOSITION c1 [...]`. Example: same composition with alternative entry types — `CONTAINS (OBSERVATION o [...] OR OBSERVATION o1 [...])`.
- **NOT CONTAINS:** Expresses exclusion (absence of a containment relationship), e.g. COMPOSITION that does NOT CONTAINS a given ENTRY type.

---

## SELECT List

Comma-separated expressions: alias references, path projections, function/aggregate calls, with optional `AS` alias. Use explicit `AS` for every projected column for stable downstream use.

Example:

```aql
SELECT
  c/uid/value AS composition_uid,
  o/data[at0001]/events[at0006]/data[at0003]/items[at0004]/value/magnitude AS systolic
```

---

## Paths and Predicates (Archetype Paths)

AQL uses **archetype paths** (and RM class attribute paths). Paths are grounded in the archetype definition and constraints; path segments and predicates are tightly coupled to RM class properties and the archetype node tree. For archetype path syntax and stable paths, see `openehr://guides/archetypes/adl-syntax` (Paths and Identifiers).

Form: `<alias>/<segment>/<segment>/...`. Segments may have predicates in square brackets. Use **node id** (at-codes from the archetype) on repeating segments for determinism:

- `data[at0001]`, `events[at0006]`, `items[at0004]`
- Avoid unconstrained repeated segments (e.g. `events/data/items` without node ids).

Common value access by RM type: DV_TEXT `.../value/value`; DV_CODED_TEXT `.../value/defining_code/code_string`; DV_QUANTITY `.../value/magnitude`, `.../value/units`; DV_DATE_TIME `.../value` (or `.../value/value` depending on serialization). Verify against the **deployed template** and engine.

---

## WHERE Clause

Boolean expressions with AND, OR, NOT; comparison (=, !=, <, <=, >, >=); IN (engine-dependent); matches (implementation varies); EXISTS (unary operator: operand is an identified path, e.g. `EXISTS o/.../value` or `NOT EXISTS c/content[...]`). Prefer half-open time windows (`>= $from` AND `< $to`) to avoid boundary duplication.

---

## ORDER BY, LIMIT, OFFSET

ORDER BY: comma-separated expressions with optional DESC/ASC. LIMIT n [OFFSET m] for pagination. Do not use LIMIT/OFFSET without ORDER BY when stable paging is required.

---

## Functions and Aggregates

Grammar allows function calls; engine support varies. Commonly portable: COUNT(expr); MIN, MAX often supported; SUM, AVG verify per engine.

---

## Syntax Validation Checklist

- All aliases in SELECT/WHERE/ORDER BY are declared in FROM/CONTAINS.
- Every repeated path segment has a node-id predicate.
- String literals in single or double quotes; parameters used consistently.
- Clause order correct; optional clauses confirmed for target engine.

---
