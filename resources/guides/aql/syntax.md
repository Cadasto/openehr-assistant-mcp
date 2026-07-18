# openEHR AQL Syntax and Grammar

**Scope:** Rules and grammar for writing and validating AQL
**Related:** openehr://guides/aql/principles, openehr://guides/aql/idioms-cheatsheet, openehr://guides/specs/query-AQL
**Keywords:** AQL, syntax, grammar, containment, path, predicate, SELECT, FROM, WHERE

**Official specification:** For full syntax, operators, and grammar details, visit the openEHR AQL specification: https://specifications.openehr.org/releases/QUERY/development/AQL — agents should consult it when looking for more information beyond this guide.

---

## Clause Order

AQL has a fixed, SQL-like clause order:

```aql
SELECT [DISTINCT] <select_list>
FROM <from_expr>
[WHERE <boolean_expr>]
[ORDER BY <order_list>]
[LIMIT <n> [OFFSET <m>]]
```

Clause order is not permutable. `WHERE`, `ORDER BY`, `LIMIT`, and `OFFSET` are optional but **spec-normative**. `TOP <n>` existed in earlier releases and is **deprecated** as of QUERY Release-1.1.0 in favour of `LIMIT … [OFFSET …]` — the two must not coexist in one query. AQL has no `GROUP BY` / `HAVING` clauses.

---

## Lexical Conventions

- **Keywords:** Case-insensitive (SELECT, select, etc.).
- **Aliases:** Identifiers bound in FROM/CONTAINS (e.g. `e`, `c`, `o`). Use short, role-based names (e = EHR, c = COMPOSITION).
- **Literals:** Strings in single or double quotes (`'text'` or `"text"` per spec); numbers unquoted; prefer parameters for dates/times.
- **Parameters:** `$name` (e.g. `$ehrId`, `$from`, `$to`); name = letter followed by letters/digits/underscores. Parameters may also appear inside predicates: `[$archetypeId]`, `[at0003, $nameValue]`, `[ehr_id/value=$ehrUid]`. Always parameterize variable input; never interpolate untrusted values into AQL.
- **Comments:** `--` followed by a space (or line end) introduces a line comment to end of line.

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

## VERSION in FROM (Versioning Constructs)

The AQL grammar defines a dedicated class expression for version-aware queries: `VERSION <alias> [<version_predicate>]`, where the version predicate is `LATEST_VERSION`, `ALL_VERSIONS`, or a standard predicate (e.g. on `commit_audit/...`):

```aql
FROM EHR e
  CONTAINS VERSION v[LATEST_VERSION]
    CONTAINS COMPOSITION c
```

**Spec status:** these constructs exist **in the grammar only** — the specification prose does not define their semantics, nor the default when the predicate is omitted (the grammar makes the predicate optional but no default is specified; implementations commonly default to latest-version-only). For portability: always state `[LATEST_VERSION]` or `[ALL_VERSIONS]` explicitly, and verify engine support before use. `VERSIONED_OBJECT` (and other RM version-container classes) parse as ordinary class expressions; only reference them when projecting container-level attributes (e.g. `vo/trunk_lifecycle_state`). Common VERSION projections: `v/uid/value`, `v/preceding_version_uid/value`, `v/commit_audit/time_committed/value`, `v/commit_audit/change_type`, `v/lifecycle_state/defining_code/code_string`.

---

## SELECT List

Comma-separated expressions: alias references, path projections, function/aggregate calls, literals; with optional `AS` alias. Optional `DISTINCT` modifier (`SELECT DISTINCT …`) filters duplicate rows. Use explicit `AS` for every projected column for stable downstream use. AQL does not support `SELECT *` — columns must be explicit.

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

Node predicate forms defined by the spec (all shortcuts for standard predicates on `archetype_node_id` / `name`):

- `[at0004]` — node id only (= `[archetype_node_id=at0004]`)
- `[at0004, 'Systolic']` or `[at0004 and name/value='Systolic']` — node id plus runtime name; disambiguates sibling nodes cloned from one archetype node
- `[at0004, $nameValue]` — parameterized name variant
- `[at0004, at0005]` or `[at0004, snomed_ct::313267000|optional label|]` — name matched by term code (`terminology_id::code_string|value|`)
- `[at0004 and value/defining_code/terminology_id/value=$tid]` — general criterion (advanced form)

Common value access by RM type: DV_TEXT `.../value/value`; DV_CODED_TEXT `.../value/defining_code/code_string`; DV_QUANTITY `.../value/magnitude`, `.../value/units`; DV_DATE_TIME `.../value` (or `.../value/value` depending on serialization). Verify against the **deployed template** and engine.

---

## WHERE Clause

Spec-normative operators:

- **Comparison:** `=`, `!=`, `<`, `<=`, `>`, `>=`
- **Logical:** `AND`, `OR`, `NOT`
- **EXISTS** — unary prefix; operand is an identified path; returns boolean (`EXISTS o/.../value`, `NOT EXISTS c/content[…]`)
- **MATCHES** — binary infix; right operand is a value list `{…}` (string/date-time/integer/real literals, parameters, or embedded `TERMINOLOGY(…)` calls — items are OR-ed), a URI in braces `{ terminology://… }`, or a bare `TERMINOLOGY(…)` function call (e.g. `code_string MATCHES {'1234', '5678'}`)
- **LIKE** — binary infix; left operand a path to a String (or date/time) value, right operand a quoted pattern or parameter; wildcards `?` (single char) and `*` (zero or more chars); the pattern must match the **entire** value (use `'*…*'` for substring matching); escape literal `?`/`*` with backslash

**Not in spec** (engine-dependent; check docs before use): `IN`, `NULLS FIRST/LAST`.

Prefer half-open time windows (`>= $from` AND `< $to`) to avoid boundary duplication.

---

## ORDER BY, LIMIT, OFFSET

ORDER BY: comma-separated **identified paths**, each optionally followed by `DESC`/`DESCENDING`/`ASC`/`ASCENDING` (default: ascending). Without ORDER BY, result order is undefined by the spec. LIMIT n [OFFSET m] for pagination: `n >= 1`, `m >= 0` (default 0); with `DISTINCT`, LIMIT/OFFSET apply after duplicate removal. Do not use LIMIT/OFFSET without ORDER BY when stable paging is required.

---

## Functions and Aggregates

Spec-normative aggregates: `COUNT`, `MIN`, `MAX`, `SUM`, `AVG`. The spec also defines core single-row built-in functions: string `LENGTH`, `CONTAINS`, `POSITION`, `SUBSTRING`, `CONCAT`, `CONCAT_WS`; numeric `ABS`, `MOD`, `CEIL`, `FLOOR`, `ROUND`; date/time `CURRENT_DATE`, `CURRENT_TIME`, `CURRENT_DATE_TIME` (alias `NOW`), `CURRENT_TIMEZONE`; plus `TERMINOLOGY(operation, service_api, params_uri)` for terminology-server calls (usable as `MATCHES` operand or as a boolean expression). In practice `COUNT`, `MIN`, `MAX` are broadly implemented; engine coverage of the other aggregates and of the single-row functions varies — verify against your target engine before relying on them. Any function beyond this core list is an engine extension.

- `COUNT([DISTINCT] path | *)` — `COUNT(*)` returns 0 on empty; other aggregates return NULL on empty.
- `MIN(path)`, `MAX(path)`, `SUM(path)`, `AVG(path)` — NULL on empty result; arguments are identified paths. `MIN`/`MAX` accept String, date/time, Integer or Real inputs; `SUM`/`AVG` numeric only.

---

## Syntax Validation Checklist

- All aliases in SELECT/WHERE/ORDER BY are declared in FROM/CONTAINS.
- Every repeated path segment has a node-id predicate.
- String literals in single or double quotes; parameters used consistently.
- Clause order correct; optional clauses confirmed for target engine.
- VERSION containments state `[LATEST_VERSION]` / `[ALL_VERSIONS]` explicitly (no reliance on the unspecified default).

---
