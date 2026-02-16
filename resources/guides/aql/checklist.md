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

- [ ] Containment chain matches RM hierarchy (EHR → COMPOSITION → content; COMPOSITION can contain SECTION, OBSERVATION, EVALUATION, ACTION, ADMIN_ENTRY, etc.).
- [ ] Contained RM type matches concept (not only ENTRY: e.g. SECTION, or OBSERVATION, EVALUATION, ACTION as appropriate).
- [ ] Archetype-id restriction applied in containment for each targeted node.
- [ ] Multiple or alternative containments use AND/OR correctly; NOT CONTAINS used for exclusions where needed (see openehr://guides/aql/syntax).
- [ ] Containment is minimal (no unnecessary nodes).

---

## 3. Path Correctness (Archetype Paths)

Paths in AQL are **archetype paths** (or RM class attribute paths), grounded in the archetype definition and constraints; segments and predicates are tightly coupled to RM class properties. See openehr://guides/archetypes/adl-syntax where applicable.

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
- [ ] LIMIT/OFFSET values bounded and parameterized.

---

## 7. Safety and Robustness

- [ ] All external values are parameters (no string concatenation).
- [ ] Parameter names consistent and documented.
- [ ] Optional/missing nodes handled via EXISTS (identified path) or containment choices.

---

## 8. Engine Compatibility

- [ ] Query uses only features supported by the target engine (docs/tests).
- [ ] Functions/aggregates verified (do not assume beyond COUNT/MIN/MAX).
- [ ] Text/pattern operators verified (matches support varies).
- [ ] Tested with representative data and edge cases.

---

## 9. Performance Heuristics

- [ ] Archetype constraints applied early in containment.
- [ ] Identity and time constraints narrow the search.
- [ ] Avoid deep unconstrained paths and broad OR filters.
- [ ] Projection minimized to reduce payload and server work.

---
