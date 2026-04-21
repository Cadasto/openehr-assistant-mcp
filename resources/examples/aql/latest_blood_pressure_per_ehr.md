# Latest Blood Pressure per EHR — AQL Example

**Pattern:** latest-per-patient with deterministic tie-breaker
**Demonstrates:** `ORDER BY ... DESC + LIMIT 1`, archetype-path projection for `DV_QUANTITY`, composition-time ordering, UID tie-breaker for determinism
**Inputs:** `$ehrId` (UUID string)
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/aql/idioms-cheatsheet`

---

```aql
SELECT
    c/uid/value AS composition_uid,
    c/context/start_time/value AS start_time,
    o/data[at0001]/events[at0006]/data[at0003]/items[at0004]/value/magnitude AS systolic,
    o/data[at0001]/events[at0006]/data[at0003]/items[at0005]/value/magnitude AS diastolic,
    o/data[at0001]/events[at0006]/data[at0003]/items[at0004]/value/units AS unit
FROM EHR e
    CONTAINS COMPOSITION c
        CONTAINS OBSERVATION o[openEHR-EHR-OBSERVATION.blood_pressure.v1]
WHERE e/ehr_id/value = $ehrId
ORDER BY c/context/start_time/value DESC, c/uid/value ASC
LIMIT 1
```

## Notes

- `LIMIT 1` without an `ORDER BY` is non-deterministic per spec — always pair them.
- Adding `c/uid/value ASC` as a secondary sort gives a stable ordering when two compositions share the same `start_time`.
- The `at0001 / at0006 / at0003 / at0004` node-id predicates are from `openEHR-EHR-OBSERVATION.blood_pressure.v1` — validate against the deployed OPT before running.
