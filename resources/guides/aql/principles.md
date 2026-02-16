# openEHR AQL Principles

**Scope:** Foundational principles for the Archetype Query Language (AQL)
**Keywords:** AQL, query, principles, semantics, containment, archetype, RM, portability

---

## Semantics-First Querying

AQL is the openEHR standard for querying EHR content. Queries are written against the openEHR Reference Model (RM) and constrained by archetypes and templates, not by storage schema.

- Same query intent can work across compliant repositories
- RM types (EHR, COMPOSITION, OBSERVATION, ACTION, etc.) and archetype identifiers drive semantic selection
- Node identifiers (`atNNNN`) provide stable addressing within archetyped structures
- openEHR archetype path navigation replaces “column” thinking

---

## Two-Pillar Model: Containment and Paths

### Containment defines the candidate set

`FROM … CONTAINS …` expresses RM containment. It is the query’s semantic join plan:

- Walk down RM containment (e.g. EHR → COMPOSITION → SECTION or ENTRY; COMPOSITION can contain SECTION, OBSERVATION, EVALUATION, ACTION, ADMIN_ENTRY, etc., as per RM hierarchy)
- Bind aliases to nodes for projection and filtering
- Constrain archetype ids early to reduce the search space
- Use AND/OR and NOT CONTAINS when multiple or exclusion constraints are needed (see syntax guide)

### Paths define what you read

Once aliases exist, **archetype paths** (and RM class attribute paths) are used to project (SELECT), filter (WHERE), and order (ORDER BY). In AQL, paths are **archetype paths**: they are grounded in the archetype definition and constraints. Path segments and predicates are tightly coupled to RM class properties and to the archetype node tree. See openEHR path syntax and archetype structure in `openehr://guides/archetypes/adl-syntax` (Paths and Identifiers). Node-id predicates on repeated segments provide determinism.

---

## Deployed OPT and Archetypes: Prerequisite Knowledge

Querying with AQL implies **knowledge and awareness of which OPT (Operational Template) and archetypes are deployed** on the target system. Containment and projection depend on them: archetype ids and node ids used in the query must exist in the deployed templates. Before writing or tuning a query, establish which templates/archetypes are in use and validate paths and predicates against those definitions.

---

## Archetypes and Templates as Contract

Archetypes define which RM structures exist, how nodes repeat, RM value types (DV_TEXT, DV_QUANTITY, DV_CODED_TEXT), and stable node ids. AQL uses this by restricting contained nodes (e.g. ENTRY, SECTION) to specific archetype ids and using node-id predicates in path segments. Every path segment and predicate must align with the archetype constraint tree and RM properties.

**Principle:** Use archetype ids and node ids validated against the deployed template set; avoid guessing from human labels.

---

## SQL Similarities and Differences

Similar: SELECT/FROM/WHERE/ORDER BY, predicate logic, parameterization, aggregates (subset).

Different: `CONTAINS` is an RM containment constraint, not a table join. Paths are typed RM navigations, not columns. Bracket predicates are essential to pick intended members in lists. Containment and archetype constraints are the primary selectivity tools.

---

## Storage Abstraction

Implementations can store the same RM content in different physical shapes. AQL avoids coupling clients to storage; portability comes from semantics, not schema.

---

## Engine Reality and Portability

The spec defines AQL; engines implement subsets and extensions. Treat “supported AQL” as the intersection of spec and engine docs/tests. For portable queries: prefer containment and archetype-id constraints; use node-id predicates on all repeating segments; parameterize external inputs; keep functions and text operations behind engine verification.

---

## Design Workflow Summary

1. Define the clinical question (concept, timeframe, cohort, output).
2. Identify archetype/template(s) that encode the concept.
3. Build minimal containment (EHR → COMPOSITION → targeted content: SECTION, OBSERVATION, EVALUATION, ACTION, etc., as per RM).
4. Choose leaf projections; add identity, time, and code filters; add ordering/pagination.
5. Validate syntax, template/path correctness, and engine compatibility.

---

## Quick Recall

- Semantics > schema
- Containment > joins
- Archetype id > name matching
- Node id > ambiguous list traversal
- Parameters > interpolation
- Explicit ordering > implicit ordering

---
