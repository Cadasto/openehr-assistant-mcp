# Multi-OBSERVATION Vital Signs — AND-Containment — AQL Example

**Pattern:** parallel AND-containment across multiple archetypes in a single composition
**Demonstrates:** retrieving body temperature, body weight, and height together so each result row carries all three measurements from the same composition; contrast with OR-containment (alternative entries) and default containment (either order)
**Inputs:** `$ehrId`
**Related:** `openehr://guides/specs/query-AQL` (containment), `openehr://guides/aql/syntax` (AND/OR on CONTAINS)
**Source:** openEHR Discourse — [AQL querying data from several archetypes](https://discourse.openehr.org/t/aql-querying-data-from-several-archetypes/4430) (S. Ljosland Bakke, I. McNicoll, Sep 2023)

---

```aql
SELECT
    c/uid/value AS composition_uid,
    c/context/start_time/value AS measured_at,
    t/data[at0002]/events[at0003]/data[at0001]/items[at0004]/value/magnitude AS temperature_c,
    w/data[at0002]/events[at0003]/data[at0001]/items[at0004]/value/magnitude AS weight_kg,
    h/data[at0001]/events[at0002]/data[at0003]/items[at0004]/value/magnitude AS height_cm
FROM EHR e
    CONTAINS COMPOSITION c
        CONTAINS (
            OBSERVATION t[openEHR-EHR-OBSERVATION.body_temperature.v2]
            AND OBSERVATION w[openEHR-EHR-OBSERVATION.body_weight.v2]
            AND OBSERVATION h[openEHR-EHR-OBSERVATION.height.v2]
        )
WHERE e/ehr_id/value = $ehrId
ORDER BY c/context/start_time/value DESC
```

## Notes

- **AND-containment** means the composition must contain all three — one row per composition with all three magnitudes populated. Compositions missing any of the three are excluded.
- **OR-containment** `(A OR B OR C)` would return a row whenever at least one is present (with the other two `NULL`). Use OR for "any vital sign" shapes; AND for "full vital-set" shapes.
- **Default containment** (no parentheses, just `CONTAINS A CONTAINS B`) expresses nested containment (B within A), not sibling.
- Pattern also works with EVALUATION for "retrieve the patient's current problem list" — swap OBSERVATION for EVALUATION and the archetype IDs.
- Node-ids (`at0001`/`at0002`/etc.) come from the specific archetype versions — validate against the deployed OPT.
