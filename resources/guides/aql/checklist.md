# openEHR AQL Design & Review Checklist

**Purpose:** Pre-flight checklist for writing and reviewing AQL
**Related:** openehr://guides/aql/principles, openehr://guides/aql/syntax
**Keywords:** AQL, checklist, design, review, containment, path, filter, validation

---

## 1. Clinical Intent

- [ ] Clinical concept(s) clearly stated (which measurement, assessment, or action).
- [ ] Target archetype/template(s) identified; **deployed OPT/templates** on the target system are known (AQL containment and projection depend on them).
- [ ] Expected cardinality known (single latest, time series, counts, cohort).

---

## 2. Containment Correctness

- [ ] Containment chain matches RM hierarchy (EHR → COMPOSITION → content; see openehr://guides/aql/syntax, FROM and CONTAINS).
- [ ] Contained RM type matches concept (not only ENTRY: e.g. SECTION, or OBSERVATION, EVALUATION, ACTION as appropriate).
- [ ] Archetype-id restriction applied in containment for each targeted node.
- [ ] Multiple or alternative containments use AND/OR correctly; NOT CONTAINS used for exclusions where needed (see openehr://guides/aql/syntax).
- [ ] Containment is minimal (no unnecessary nodes).

---

## 3. Path Correctness (Archetype Paths)

Path semantics and predicate forms: see openehr://guides/aql/syntax (Paths and Predicates).

- [ ] Every path starts from a declared alias.
- [ ] Node-id predicates (`[atNNNN]`) used on repeating segments; paths validated against deployed template.
- [ ] Leaf endpoints match RM types (DV_QUANTITY magnitude/units, DV_CODED_TEXT code_string, etc.).
- [ ] No reliance on display labels unless validated against the deployed template.

---

## 4. Filtering Logic

- [ ] Identity constraint when appropriate (`e/ehr_id/value = $ehrId`).
- [ ] Time semantics chosen and consistent (composition vs event time).
- [ ] Time windows half-open (`>= $from` and `< $to`).
- [ ] Code filters use canonical code_string paths, not name text.
- [ ] Existence checks use `EXISTS` with an identified path (unary operator) where data may be missing.

---

## 5. Projection and Result Shape

- [ ] Only necessary fields projected; avoid wide object returns.
- [ ] Every projection has a stable `AS` alias.
- [ ] Expected output schema documented (column names and types).

---

## 6. Ordering and Pagination

- [ ] ORDER BY present when using LIMIT/OFFSET for stable paging.
- [ ] Tie-breaker included if primary sort can collide (e.g. UID).
- [ ] LIMIT/OFFSET bounded integer literals (no `$parameters`); runtime paging goes through the query API (`offset`/`fetch`).

---

## 7. Safety and Robustness

- [ ] All external values are parameters (no string concatenation).
- [ ] Parameter names consistent and documented.
- [ ] Optional/missing nodes handled via EXISTS (identified path) or containment choices.

---

## 8. Engine Compatibility

- [ ] Query uses only features supported by the target engine (docs/tests).
- [ ] Functions/aggregates verified against the engine — spec core list in openehr://guides/aql/syntax (Functions and Aggregates); COUNT, MIN, MAX are safest; any other function is an engine extension.
- [ ] Text/pattern operators — MATCHES and LIKE are spec-normative; IN is engine-dependent.
- [ ] Tested with representative data and edge cases.

---

## 9. Performance Heuristics

- [ ] Archetype constraints applied early in containment.
- [ ] Identity and time constraints narrow the search.
- [ ] Avoid deep unconstrained paths and broad OR filters.
- [ ] Projection minimized to reduce payload and server work.

---

## 10. Stored Query Governance

- [ ] Stored queries versioned explicitly; deprecate old versions with migration notes.
- [ ] Template version governance active — template changes can invalidate existing query paths.

---

## 11. Operational Readiness

- [ ] Query timeout thresholds and result row limits configured.
- [ ] Regression and conformance test packs cover syntax, semantics, results, performance, and engine compatibility.

---

## 12. Versioning Queries (if applicable)

- [ ] `CONTAINS VERSION` predicate stated explicitly (`[LATEST_VERSION]` / `[ALL_VERSIONS]`) and engine support verified (see openehr://guides/aql/syntax, VERSION in FROM).
- [ ] VERSIONED_OBJECT included only when its attributes (uid, owner_id, time_created) are projected or filtered.
- [ ] Audit projections use the canonical paths listed in openehr://guides/aql/syntax (VERSION in FROM).

---
