# Paginated Compositions with Total Count — Two-Query Pattern — AQL Example

**Pattern:** data query + companion `COUNT(*)` query for paged REST responses
**Demonstrates:** `OFFSET n LIMIT m` pagination, stable ordering for deterministic paging, the "run once for total, cache, run data query per page" idiom used in production REST wrappers
**Inputs:** `$ehrId`, `$pageSize` (int), `$pageOffset` (int, zero-based)
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/aql/principles` (stable ordering)
**Source:** openEHR Discourse — [Pagination with total count specifically in EHRbase](https://discourse.openehr.org/t/pagination-with-total-count-specifically-in-ehrbase/5761) (P. Skillen, H. Reise, Sep–Oct 2024)

---

**Data query** (fetched per page):

```aql
SELECT
    c/uid/value AS composition_uid,
    c/name/value AS composition_name,
    c/archetype_details/template_id/value AS template_id,
    c/context/start_time/value AS recorded_at
FROM EHR e
    CONTAINS COMPOSITION c
WHERE e/ehr_id/value = $ehrId
ORDER BY c/context/start_time/value DESC, c/uid/value ASC
OFFSET $pageOffset LIMIT $pageSize
```

**Count query** (fetched once per result set, typically for the first page only):

```aql
SELECT COUNT(*) AS total
FROM EHR e
    CONTAINS COMPOSITION c
WHERE e/ehr_id/value = $ehrId
```

## Notes

- **Always pair `LIMIT` with `ORDER BY`** — without explicit ordering the spec offers no stability guarantee, so page 2 could repeat rows from page 1 or skip some. The UID secondary sort is defensive against equal `start_time`.
- **Run COUNT once, cache it** — many REST wrappers cache the total against the query signature and session, then re-run the lightweight data query per page request. Running COUNT on every page doubles server work for no UX gain.
- **`FETCH` is equivalent to `LIMIT`** per the AQL spec; `TOP` is deprecated. Prefer `LIMIT`/`OFFSET` for portability.
- The two queries **must share the same `WHERE` clause** — any drift and your paging arithmetic lies to the client.
- For large result sets consider cursor-style paging (last-seen UID) instead of OFFSET — OFFSET reads and discards rows it skips.
