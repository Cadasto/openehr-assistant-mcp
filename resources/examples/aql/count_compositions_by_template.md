# Count Compositions by Template — AQL Example

**Pattern:** aggregation with `COUNT`
**Demonstrates:** `COUNT(*)`, grouping conceptually (client-side — AQL has no `GROUP BY`), `archetype_details/template_id` projection
**Inputs:** `$ehrId`
**Related:** `openehr://guides/specs/query-AQL` (aggregates section), `openehr://guides/aql/syntax`

---

```aql
SELECT
    c/archetype_details/template_id/value AS template_id,
    COUNT(*) AS composition_count
FROM EHR e
    CONTAINS COMPOSITION c
WHERE e/ehr_id/value = $ehrId
```

## Notes

- **AQL has no `GROUP BY`** — engines that implement this spec-normative aggregate usually group *implicitly* by the non-aggregated columns in `SELECT`. Behaviour when mixing aggregates and plain projections is not fully settled across engines; verify before relying on it.
- `COUNT(*)` returns `0` for empty result sets; `MIN`/`MAX`/`SUM`/`AVG` return `NULL`.
- Spec-normative aggregates: `COUNT`, `MIN`, `MAX`, `SUM`, `AVG`. In practice `SUM` and `AVG` coverage varies — check your target engine (see `openehr://guides/aql/syntax` Functions and Aggregates section).
- To count per RM class rather than template, project `c/archetype_details/archetype_id/value`.
