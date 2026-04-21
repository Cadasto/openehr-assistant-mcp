# Cross-Composition Join via Shared EHR — AQL Example

**Pattern:** implicit join across two compositions for the same patient via their shared EHR containment
**Demonstrates:** correlating data recorded in separate compositions (e.g. alcohol-use screening and blood-pressure measurement) through the common `EHR e`, parenthesised parallel containments with AND, Cartesian pairing at the composition level
**Inputs:** `$ehrId`
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/specs/rm-ehr`
**Source:** EHRbase documentation — [AQL / Common](https://docs.ehrbase.org/docs/EHRbase/Explore/AQL/Common)

---

```aql
SELECT
    e/ehr_id/value AS ehr_id,
    c1/uid/value AS alcohol_composition_uid,
    c1/context/start_time/value AS alcohol_recorded_at,
    o1/data[at0001]/events[at0002]/data[at0003]/items[at0004]/value AS alcohol_use,
    c2/uid/value AS bp_composition_uid,
    c2/context/start_time/value AS bp_recorded_at,
    o2/data[at0001]/events[at0006]/data[at0003]/items[at0004]/value/magnitude AS systolic
FROM EHR e
    CONTAINS (
        COMPOSITION c1
            CONTAINS OBSERVATION o1[openEHR-EHR-OBSERVATION.alcohol_use.v0]
    )
    AND (
        COMPOSITION c2
            CONTAINS OBSERVATION o2[openEHR-EHR-OBSERVATION.blood_pressure.v1]
    )
WHERE e/ehr_id/value = $ehrId
```

## Notes

- **The join is implicit in `FROM EHR e`** — both containments share the same `e`, so the planner pairs every qualifying `c1` with every qualifying `c2` for that patient. This is a full Cartesian product within an EHR; add time-window constraints if you want "paired in the same visit" rather than "ever recorded for this patient".
- For **temporal correlation** (e.g. BP within 30 days of the alcohol screen), add `WHERE c2/context/start_time/value BETWEEN c1/context/start_time/value AND ...` — but `BETWEEN` is engine-dependent; portable equivalent is `>=` / `<`.
- **Row-explosion risk** — N alcohol compositions × M BP compositions = N×M rows per patient. Consider aggregating or selecting a "latest per archetype" per patient before joining at the client.
- Pattern scales to more than two compositions but planner cost grows quickly; for three-plus, prefer splitting into client-side composition.
