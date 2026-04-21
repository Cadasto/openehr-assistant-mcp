# Vital Signs — Blood Pressure (FLAT JSON) — Example Payload

**Pattern:** FLAT JSON composition with one `blood_pressure` observation
**Demonstrates:** `ctx/` context fields, flat-path keys rooted at the template id, `|magnitude` / `|unit` pipe suffixes for `DV_QUANTITY`, zero-based `:n` instance indices on repeating events
**MIME type:** `application/openehr.wt.flat+json`
**Paired example:** `openehr://examples/structured/vital_signs_blood_pressure` (same composition, STRUCTURED form)
**Template:** assumes a template with identifier `vital_signs.v0` that includes `openEHR-EHR-OBSERVATION.blood_pressure.v1` as its only Observation
**Related:** `openehr://guides/specs/its-rest-simplified_formats`, `openehr://guides/simplified_formats/principles`

---

```json
{
  "ctx/language": "en",
  "ctx/territory": "US",
  "ctx/time": "2026-04-21T09:30:00Z",
  "ctx/composer_name": "Dr. A. Smith",
  "ctx/category|code": "433",
  "ctx/category|value": "event",
  "ctx/category|terminology": "openehr",
  "ctx/setting|code": "238",
  "ctx/setting|value": "other care",
  "ctx/setting|terminology": "openehr",

  "vital_signs/blood_pressure:0/any_event:0/time": "2026-04-21T09:28:00Z",
  "vital_signs/blood_pressure:0/any_event:0/systolic|magnitude": 128,
  "vital_signs/blood_pressure:0/any_event:0/systolic|unit": "mm[Hg]",
  "vital_signs/blood_pressure:0/any_event:0/diastolic|magnitude": 84,
  "vital_signs/blood_pressure:0/any_event:0/diastolic|unit": "mm[Hg]",
  "vital_signs/blood_pressure:0/any_event:0/position|code": "at1000",
  "vital_signs/blood_pressure:0/any_event:0/position|value": "Sitting",
  "vital_signs/blood_pressure:0/any_event:0/position|terminology": "local"
}
```

## Notes

- **`ctx/` context** carries composition-level metadata. `language` and `territory` are mandatory; most others default server-side when omitted.
- **Root `vital_signs`** is the normalised template id (lowercased, snake-cased). Every FLAT key is fully qualified from this root.
- **`:0` indices** are zero-based. `blood_pressure:0/any_event:0` picks the first BP observation and its first event. Add `:1`, `:2`, … for subsequent instances.
- **Pipe suffixes** (`|magnitude`, `|unit`, `|code`, `|value`, `|terminology`) are typed-attribute accessors — see `openehr://guides/simplified_formats/rules` for the full list.
- **No ELEMENT wrapper** in FLAT — the key ends at the value's attributes.
- To round-trip to canonical JSON or to a STRUCTURED payload, use the same OPT that generated these field identifiers (see `openehr://guides/specs/its-rest-simplified_formats`).
