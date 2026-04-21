# Encounter with Optional RM Attributes (FLAT JSON) — Example Payload

**Pattern:** FLAT composition carrying optional RM attributes via underscore-prefix keys
**Demonstrates:** `ctx/_end_time` (context end time), `ctx/_location`, `ctx/_participation:n|function|name|id|id_scheme|id_namespace` (repeated context participations), `dv_quantity/_normal_range/lower|magnitude` + `.../_normal_range/upper|magnitude` (reference-range RM attributes on a DV_QUANTITY), `element/_uid` (node UID), `element/_link:n|type|meaning|target` (outbound links). All represent RM attributes *not* defined by the template — hence the underscore prefix.
**MIME type:** `application/openehr.wt.flat+json`
**Template:** assumes a minimal `encounter.v0` template with a single observation holding a DV_QUANTITY element
**Related:** `openehr://guides/specs/its-rest-simplified_formats`, `openehr://guides/specs/rm-ehr` (for EVENT_CONTEXT attributes)
**Source:** EHRbase documentation — [simSDT RM Mapping](https://docs.ehrbase.org/docs/EHRbase/Explore/Simplified-data-template/Flat-Reference)

---

```json
{
  "ctx/language": "en",
  "ctx/territory": "US",
  "ctx/composer_name": "Dr. A. Smith",
  "ctx/time": "2026-04-21T09:30:00Z",
  "ctx/_end_time": "2026-04-21T10:15:00Z",
  "ctx/_location": "microbiology lab 2",
  "ctx/_participation:0|function": "requester",
  "ctx/_participation:0|name": "Dr. Marcus Johnson",
  "ctx/_participation:0|id": "199",
  "ctx/_participation:0|id_scheme": "HOSPITAL-NS",
  "ctx/_participation:0|id_namespace": "HOSPITAL-NS",
  "ctx/_participation:1|function": "performer",
  "ctx/_participation:1|name": "Nurse J. Doe",
  "ctx/_participation:1|id": "842",

  "encounter/observation:0/any_event:0/measurement|magnitude": 65.9,
  "encounter/observation:0/any_event:0/measurement|unit": "mg/dL",
  "encounter/observation:0/any_event:0/measurement/_normal_range/lower|magnitude": 60.0,
  "encounter/observation:0/any_event:0/measurement/_normal_range/lower|unit": "mg/dL",
  "encounter/observation:0/any_event:0/measurement/_normal_range/upper|magnitude": 99.0,
  "encounter/observation:0/any_event:0/measurement/_normal_range/upper|unit": "mg/dL",

  "encounter/observation:0/_uid": "9fcc1c70-9349-444d-b9cb-8fa817697f5e",
  "encounter/observation:0/_link:0|type": "problem",
  "encounter/observation:0/_link:0|meaning": "problem related note",
  "encounter/observation:0/_link:0|target": "ehr://ehr.network/347a5490-7d8b-4db1-9d3f-7f1e3d2b2b91"
}
```

## Notes

- **Underscore prefix rule** — keys starting with `_` access optional RM attributes that the template does not define structurally. `_end_time`, `_location`, `_uid`, `_link`, `_participation`, `_normal_range` are all examples.
- **Repeated metadata uses the same `:n` indexing** as clinical data: `_participation:0`, `_participation:1`, …
- **Reference ranges** attach to the parent `DV_QUANTITY` element via `.../element/_normal_range/lower|...` and `.../upper|...`. Both bounds are themselves `DV_QUANTITY` instances.
- **`_link`** follows the same `|type`/`|meaning`/`|target` structure as a standard `LINK`; `|target` is an `ehr://…` / `archetype://…` URI or any openEHR-supported locator.
- **FLAT is not lossless** for some RM attributes — `DV_QUANTITY.magnitude_status`, `.other_reference_ranges`, `DV_CODED_TEXT.preferred_term`, multi-identifier `PARTY_IDENTIFIED`. For those, use `|raw` (see the `flat_raw_escape_hatch` example) or commit as canonical JSON.
- **Engine divergences to watch**: `ctx/origin` auto-derivation from `ctx/time` (Better yes, EHRbase not documented), `openEHR-AUDIT_DETAILS` header handling with FLAT POST (Better honours, EHRbase ignores — SEC CR opened Dec 2025).
