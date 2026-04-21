# Cohort — EHRs Having a Specific Diagnosis — AQL Example

**Pattern:** cohort identification (population query, no `ehr_id` filter)
**Demonstrates:** cross-EHR query, parameterised terminology code, `SELECT DISTINCT` over population, `EVALUATION` + `DV_CODED_TEXT`
**Inputs:** `$diagnosisCode` (string, e.g. a SNOMED CT code), `$terminology` (string, e.g. `"SNOMED-CT"`)
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/aql/checklist` (cohort safety)

---

```aql
SELECT DISTINCT
    e/ehr_id/value AS ehr_id
FROM EHR e
    CONTAINS EVALUATION ev[openEHR-EHR-EVALUATION.problem_diagnosis.v1]
WHERE ev/data[at0001]/items[at0002]/value/defining_code/code_string = $diagnosisCode
    AND ev/data[at0001]/items[at0002]/value/defining_code/terminology_id/value = $terminology
```

## Notes

- **No `ehr_id` restriction** — this is a population query that scans across every EHR the caller is authorised to see. Always gate with RBAC at the service layer.
- `SELECT DISTINCT` collapses multiple matches per patient to a single row per `ehr_id`. Drop `DISTINCT` if you want one row per matching composition.
- Parameterising both `code_string` and the terminology id avoids matching the same code from an unrelated vocabulary (e.g. an ICD-10 code that happens to string-equal a SNOMED code).
- Governance: cohort queries are typically stored queries with explicit review — see `openehr://guides/aql/checklist` §10.
