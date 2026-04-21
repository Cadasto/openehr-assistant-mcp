# Compositions Modified in a Time Window — with Committer — AQL Example

**Pattern:** audit-trail retrieval via `VERSION[latest_version]` + `commit_audit` navigation
**Demonstrates:** the canonical `VERSION v[latest_version] CONTAINS COMPOSITION c` idiom, `v/commit_audit/time_committed/value` and `v/commit_audit/committer/name` projection, answering "which compositions were touched between X and Y and by whom"
**Inputs:** `$from` (ISO 8601), `$to` (ISO 8601)
**Related:** `openehr://guides/specs/rm-common` (VERSIONED_OBJECT, AUDIT_DETAILS), `openehr://guides/specs/query-AQL`
**Source:** openEHR Discourse — [AQL query for compositions that have been created/updated within a time period](https://discourse.openehr.org/t/aql-query-for-compositions-that-have-been-created-updated-within-a-time-period/2458) (B. Haarbrandt, PLRI, Oct 2024)

---

```aql
SELECT
    c/uid/value AS composition_uid,
    v/commit_audit/time_committed/value AS committed_at,
    v/commit_audit/committer/name AS committer_name,
    v/commit_audit/change_type/value AS change_type
FROM EHR e
    CONTAINS VERSION v[latest_version]
        CONTAINS COMPOSITION c
WHERE v/commit_audit/time_committed/value >= $from
    AND v/commit_audit/time_committed/value < $to
ORDER BY v/commit_audit/time_committed/value DESC
```

## Notes

- **`VERSION v[latest_version]`** binds to the latest version of every versioned object the planner sees. `[all_versions]` is the alternative when you want full history.
- Composition time (`c/context/start_time`) is when the clinical event happened; commit time (`v/commit_audit/time_committed`) is when it entered the CDR — they can differ by hours or days.
- `change_type` distinguishes `creation` / `modification` / `amendment` / `deletion` / `attestation` per openEHR terminology.
- `CONTAINS VERSION` semantics are **not fully settled across engines** — EHRbase 2.x rewrote its query engine (2024) and earlier threads may not run as written. Verify against your target engine's current docs before relying on this shape.
