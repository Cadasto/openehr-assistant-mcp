# Vital Signs — Blood Pressure (STRUCTURED JSON) — Example Payload

**Pattern:** STRUCTURED JSON composition with one `blood_pressure` observation
**Demonstrates:** nested object hierarchy, `ctx` object (not prefixed keys), arrays for every data-bearing node (even cardinality 1), pipe-prefixed keys (`|magnitude`, `|unit`, `|code`, `|value`, `|terminology`) for typed attributes
**MIME type:** `application/openehr.wt.structured+json`
**Paired example:** `openehr://examples/flat/vital_signs_blood_pressure` (same composition, FLAT form)
**Template:** assumes a template with identifier `vital_signs.v0` that includes `openEHR-EHR-OBSERVATION.blood_pressure.v1` as its only Observation
**Related:** `openehr://guides/specs/its-rest-simplified_formats`, `openehr://guides/simplified_formats/rules`

---

```json
{
  "ctx": {
    "language": "en",
    "territory": "US",
    "time": "2026-04-21T09:30:00Z",
    "composer_name": "Dr. A. Smith",
    "category": [{"|code": "433", "|value": "event", "|terminology": "openehr"}],
    "setting": [{"|code": "238", "|value": "other care", "|terminology": "openehr"}]
  },
  "vital_signs": {
    "blood_pressure": [
      {
        "any_event": [
          {
            "time": [{"|value": "2026-04-21T09:28:00Z"}],
            "systolic": [{"|magnitude": 128, "|unit": "mm[Hg]"}],
            "diastolic": [{"|magnitude": 84, "|unit": "mm[Hg]"}],
            "position": [
              {"|code": "at1000", "|value": "Sitting", "|terminology": "local"}
            ]
          }
        ]
      }
    ]
  }
}
```

## Notes

- **Arrays everywhere data sits** — `blood_pressure: [ … ]`, `any_event: [ … ]`, even single-instance attributes like `systolic: [ { … } ]`. This lets clients parse repeating and non-repeating nodes with the same code path.
- **`ctx` is an object**, not a prefixed-key convention like FLAT. Field names inside match the FLAT `ctx/field` suffixes without the `ctx/` prefix.
- **Pipe-prefixed keys** (`|magnitude`, `|unit`, `|code`, `|value`, `|terminology`) appear *inside* object values to carry typed attributes. The pipe is the same marker as in FLAT, just structurally in-key rather than in-path.
- **Empty nodes are omitted** — if a template slot is unfilled, its key is absent from the STRUCTURED payload.
- **Semantic equivalence with FLAT** — the paired FLAT example encodes exactly the same clinical data, round-trip-convertible via the same OPT.
