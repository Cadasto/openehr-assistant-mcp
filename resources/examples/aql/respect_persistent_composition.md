# ReSPECT Persistent Composition — OR-Containment + Name Predicates + Deep CLUSTER — AQL Example

**Pattern:** real-world heterogeneous retrieval across parallel entry types with deep CLUSTER navigation
**Demonstrates:** (a) OR-containment across four alternative entry types under a single COMPOSITION, (b) name-predicate disambiguation `items[at0002, 'What I most value']` for templates that runtime-clone an at-code under different names, (c) deep multi-level CLUSTER navigation into the `protocol` slot for authorship metadata, (d) persistent-composition "latest singleton" pattern with defensive `ORDER BY ... LIMIT 1`
**Inputs:** `$ehrId`
**Related:** `openehr://guides/specs/query-AQL`, `openehr://guides/specs/rm-ehr` (COMPOSITION.category), `openehr://guides/specs/am2-ADL2` (name-predicate semantics)
**Source:** freshEHR / NHS Scotland National Digital Platform tutorial — [Querying NDP ReSPECT](https://freshehr.github.io/dhi-proms/dhis/DHIS3-querying-ndp-respect/) (I. McNicoll)

---

```aql
SELECT
    c/uid/value AS composition_uid,
    t/data[at0001]/items[at0003]/value/value AS cpr_decision,
    t/data[at0001]/items[at0002]/value/value AS cpr_decision_date,
    g/data[at0001]/items[at0002, 'What I most value']/value/value AS what_i_most_value,
    g/data[at0001]/items[at0002, 'What I most fear']/value/value AS what_i_most_fear,
    r/data[at0001]/items[at0002]/value/value AS clinical_focus,
    r/data[at0001]/items[at0003]/value/value AS clinical_guidance,
    h/protocol[at0015]/items[openEHR-EHR-CLUSTER.practitioner_cc.v0, 'Signing clinician']
        /items[openEHR-EHR-CLUSTER.practitioner_role_cc.v0]
        /items[at0007, 'Designation']/value/items[at0007, 'Designation']/value/value
        AS signing_clinician_role,
    h/protocol[at0015]/items[openEHR-EHR-CLUSTER.practitioner_cc.v0, 'Signing clinician']
        /items[openEHR-EHR-CLUSTER.name_cc.v0]
        /items[at0009]/value/items[at0009]/value/value
        AS signing_clinician_name
FROM EHR e
    CONTAINS COMPOSITION c[openEHR-EHR-COMPOSITION.report.v1]
        CONTAINS (
            EVALUATION t[openEHR-EHR-EVALUATION.cpr_decision_uk.v0]
            OR EVALUATION g[openEHR-EHR-EVALUATION.about_me.v0]
            OR ACTION h[openEHR-EHR-ACTION.service.v0]
            OR EVALUATION r[openEHR-EHR-EVALUATION.recommendation.v1]
        )
WHERE e/ehr_id/value = $ehrId
    AND c/name/value = 'ReSPECT_v3-6-7'
ORDER BY c/context/start_time/value DESC
OFFSET 0 LIMIT 1
```

## Notes

- **OR-containment across four entry types** — each row has *at most one* of `t`/`g`/`h`/`r` populated; the other three are `NULL`. A flattened projection like this returns one row per matched entry, which the client must regroup by composition uid.
- **Name-predicate disambiguation** — `items[at0002, 'What I most value']` and `items[at0002, 'What I most fear']` pick two runtime clones of the same at-code by their `name/value`. Spec-legal but implementation-fragile: **EHRbase has historically handled multi-name at-codes inconsistently** — before committing to this idiom in new templates, prefer specialised at-codes (`at0002.1`, `at0002.2`). For existing templates you cannot change (like ReSPECT), this is the pattern.
- **Deep CLUSTER navigation** into `protocol[at0015]` traces two levels of clinician metadata — this is what "full authorship provenance" looks like in AQL.
- **Persistent-composition singleton pattern** — ReSPECT is a patient-level singleton resuscitation-wishes form (`category = persistent`). The trailing `ORDER BY ... DESC OFFSET 0 LIMIT 1` is defensive: pick the most recent if duplicates ever exist.
- Tested in freshEHR's NHS Scotland National Digital Platform tutorials; node IDs match the ReSPECT v3 template.
