# Coded Text Matching a Terminology Value Set — AQL Example

**Pattern:** `MATCHES TERMINOLOGY(...)` against a SNOMED CT value set URI
**Demonstrates:** `matches` binary operator with a `TERMINOLOGY('expand', …, …)` right-hand operand, value-set expansion resolved server-side (e.g. via Ontoserver), spec-normative idiom for code-based filtering without listing every member manually
**Inputs:** `$ehrId`
**Related:** `openehr://guides/specs/query-AQL` (MATCHES + TERMINOLOGY), `openehr://guides/specs/term-SupportTerminology`
**Source:** openEHR Discourse — [Support for AQL `matches` and `TERMINOLOGY` in EHRbase](https://discourse.openehr.org/t/support-for-aql-matches-and-terminoolgy-in-ehrbase/3789) (Mar 2023)

---

```aql
SELECT
    e/ehr_id/value AS ehr_id,
    c/uid/value AS composition_uid,
    ev/data[at0001]/items[at0002]/value/value AS diagnosis_text,
    ev/data[at0001]/items[at0002]/value/defining_code/code_string AS diagnosis_code
FROM EHR e
    CONTAINS COMPOSITION c
        CONTAINS EVALUATION ev[openEHR-EHR-EVALUATION.problem_diagnosis.v1]
WHERE e/ehr_id/value = $ehrId
    AND ev/data[at0001]/items[at0002]/value/defining_code/code_string MATCHES
        TERMINOLOGY('expand', 'hl7.org/fhir/r4', 'http://snomed.info/sct?fhir_vs=isa/50697003')
```

## Notes

- **`TERMINOLOGY('expand', system, url)`** delegates value-set expansion to an external terminology service (Ontoserver, FHIR TS). The `isa/50697003` URL parameter here selects descendants of the SNOMED CT concept "Disorder of cardiovascular system" — one filter captures the whole sub-hierarchy.
- **Alternative `matches {…}` literal list** — `MATCHES {'386661006', '195967001'}` — is always portable; the `TERMINOLOGY(...)` form is spec-normative but its **resolve time** (query-compile vs query-execute) and **cache behaviour** are engine-dependent. Verify before relying on it.
- Value-set URI grammar follows FHIR ValueSet conventions; the second argument identifies the terminology system binding.
- Pairs well with stored queries: expansion fetched once, cached, reused for the life of the stored query.
