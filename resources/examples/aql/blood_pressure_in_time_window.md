# Blood Pressure Observations in a Time Window — AQL Example

**Pattern:** half-open time-window filter
**Demonstrates:** half-open interval (`>= $from AND < $to`) for safe boundary semantics, multi-component `DV_QUANTITY` projection, parameterised inputs
**Inputs:** `$ehrId`, `$from` (ISO 8601), `$to` (ISO 8601)
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/aql/principles`

---

```aql
SELECT
    c/uid/value AS composition_uid,
    c/context/start_time/value AS measured_at,
    o/data[at0001]/events[at0006]/data[at0003]/items[at0004]/value/magnitude AS systolic,
    o/data[at0001]/events[at0006]/data[at0003]/items[at0005]/value/magnitude AS diastolic
FROM EHR e
    CONTAINS COMPOSITION c
        CONTAINS OBSERVATION o[openEHR-EHR-OBSERVATION.blood_pressure.v1]
WHERE e/ehr_id/value = $ehrId
    AND c/context/start_time/value >= $from
    AND c/context/start_time/value < $to
ORDER BY c/context/start_time/value ASC
```

## Notes

- **Half-open windows** (`>= from AND < to`) avoid boundary-double-counting and match standard statistical interval conventions.
- Ordering is explicit even without `LIMIT` — without it, result order is undefined.
- For event-level time (rather than composition-level), use `o/data[at0001]/events[at0006]/time/value` instead of `c/context/start_time/value`.
