# Problem Diagnoses with Severity — AQL Example

**Pattern:** `EVALUATION` projection with `DV_CODED_TEXT` leaves
**Demonstrates:** selecting from an `EVALUATION` archetype, projecting typed text (`defining_code/code_string`) alongside plain text, three-column result shape
**Inputs:** `$ehrId`
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/specs/rm-ehr` (for `EVALUATION` semantics)

---

```aql
SELECT
    ev/data[at0001]/items[at0002]/value/value AS diagnosis_text,
    ev/data[at0001]/items[at0002]/value/defining_code/code_string AS diagnosis_code,
    ev/data[at0001]/items[at0002]/value/defining_code/terminology_id/value AS terminology,
    ev/data[at0001]/items[at0005]/value AS severity,
    ev/data[at0001]/items[at0077]/value AS date_of_onset
FROM EHR e
    CONTAINS EVALUATION ev[openEHR-EHR-EVALUATION.problem_diagnosis.v1]
WHERE e/ehr_id/value = $ehrId
```

## Notes

- `DV_CODED_TEXT` carries both display text (`value/value`) and the `defining_code` — project the code if you plan to compare or aggregate; project the text if you plan to display.
- `value/defining_code/terminology_id/value` reveals which external terminology the code comes from (SNOMED CT, ICD-10, etc.).
- No `ORDER BY` here because the caller is expected to group client-side; add one if you page results.
