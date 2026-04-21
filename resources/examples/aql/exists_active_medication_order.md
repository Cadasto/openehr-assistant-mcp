# EHRs with an Active Medication Order — AQL Example

**Pattern:** `EXISTS` over a path with an ISM-state filter
**Demonstrates:** `INSTRUCTION` containment, ISM workflow-state filtering, `EXISTS` as a unary operator over an identified path, population-level existence check
**Inputs:** none (population query)
**Related:** `openehr://guides/specs/rm-ehr` (ISM states), `openehr://guides/specs/query-AQL`

---

```aql
SELECT DISTINCT
    e/ehr_id/value AS ehr_id
FROM EHR e
    CONTAINS COMPOSITION c
        CONTAINS INSTRUCTION i[openEHR-EHR-INSTRUCTION.medication_order.v3]
WHERE EXISTS i/activities[at0001]/description[at0002]
    AND i/narrative/value != 'cancelled'
```

## Notes

- `EXISTS` is **unary** — its operand is an identified path. `NOT EXISTS` negates.
- For strict ISM-state filtering against the openEHR terminology (e.g. `active` current_state), use `i/ism_transition/current_state/defining_code/code_string` against the appropriate terminology code (e.g. `"245"` for `active` in the ISM group — verify against `openehr://terminology`).
- `INSTRUCTION.narrative` is the human-readable summary; pairing a code-based filter with a narrative check is defensive — prefer the code check as the authoritative signal.
- Population queries like this should be stored queries with reviewed parameters — see `openehr://guides/aql/checklist` §10.
