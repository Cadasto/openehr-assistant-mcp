# FLAT `|raw` Escape Hatch — Example Payload

**Pattern:** embed a canonical-JSON RM fragment under `|raw` to carry attributes that have no pure-FLAT path
**Demonstrates:** the `|raw` suffix as the last-resort mechanism for attributes not expressible via `|magnitude|unit|code|...` + underscore prefixes — in particular `DV_QUANTITY.magnitude_status`, `DV_QUANTITY.normal_status`, `DV_QUANTITY.other_reference_ranges`, `DV_CODED_TEXT.preferred_term`, multi-entry `PARTY_IDENTIFIED.identifiers`, `PARTY_REF.external_ref`. The `|raw` string is a stringified canonical-JSON object; the server parses it as-is into the RM tree.
**MIME type:** `application/openehr.wt.flat+json`
**Template:** assumes a laboratory-result template with a DV_QUANTITY leaf needing reference-range plus status metadata
**Related:** `openehr://guides/specs/its-rest-simplified_formats`, `openehr://guides/simplified_formats/principles`
**Source:** GitHub — [Using the raw attribute in Ehrscape FLAT JSON](https://github.com/inidus/openehr_guides/blob/master/Using%20the%20raw%20attribute%20in%20Ehrscape%20FLAT%20JSON.adoc) (Ian McNicoll / inidus)

---

```json
{
  "ctx/language": "en",
  "ctx/territory": "US",
  "ctx/composer_name": "Lab System",

  "laboratory_report/laboratory_result:0/analyte_name|code": "14646-4",
  "laboratory_report/laboratory_result:0/analyte_name|value": "Cholesterol",
  "laboratory_report/laboratory_result:0/analyte_name|terminology": "LOINC",

  "laboratory_report/laboratory_result:0/result_value/quantity_value|raw": "{\"_type\":\"DV_QUANTITY\",\"magnitude\":7.4,\"units\":\"mmol/l\",\"magnitude_status\":\">=\",\"normal_status\":{\"_type\":\"CODE_PHRASE\",\"terminology_id\":{\"_type\":\"TERMINOLOGY_ID\",\"value\":\"openehr\"},\"code_string\":\"HH\"},\"normal_range\":{\"_type\":\"DV_INTERVAL\",\"lower\":{\"_type\":\"DV_QUANTITY\",\"magnitude\":3.0,\"units\":\"mmol/l\"},\"upper\":{\"_type\":\"DV_QUANTITY\",\"magnitude\":5.0,\"units\":\"mmol/l\"},\"lower_unbounded\":false,\"upper_unbounded\":false},\"other_reference_ranges\":[{\"_type\":\"REFERENCE_RANGE\",\"meaning\":{\"_type\":\"DV_TEXT\",\"value\":\"Age-sex appropriate range\"},\"range\":{\"_type\":\"DV_INTERVAL\",\"lower\":{\"_type\":\"DV_QUANTITY\",\"magnitude\":3.2,\"units\":\"mmol/l\"},\"upper\":{\"_type\":\"DV_QUANTITY\",\"magnitude\":5.2,\"units\":\"mmol/l\"},\"lower_unbounded\":false,\"upper_unbounded\":false}}]}"
}
```

## Notes

- **Rules of thumb**
  - Always include `_type` on every object inside the `|raw` payload — the server uses it to pick the concrete RM class.
  - The embedded value **must be a valid canonical-JSON RM instance** — if it fails validation, the whole composition POST fails.
  - **Polymorphic parents**: when the target element can be several RM types, attach `|raw` to the resolved sub-type path (e.g. `…/result_value/quantity_value|raw`), not the abstract element path. The example above attaches to `quantity_value` under `result_value` rather than `result_value|raw` directly.
- **Attributes that currently require `|raw`** (not expressible in pure FLAT):
  - `DV_QUANTITY.magnitude_status` (e.g. `">="`, `"<"` — magnitude qualifier)
  - `DV_QUANTITY.normal_status` (e.g. `"HH"` high-high — per openEHR normal-status terminology)
  - `DV_QUANTITY.other_reference_ranges` (additional age/sex-banded ranges alongside `_normal_range`)
  - `DV_CODED_TEXT.preferred_term` (no documented FLAT path; Discourse Feb 2026)
  - Multi-entry `PARTY_IDENTIFIED.identifiers` — FLAT can only express a single identifier tuple
  - `PARTY_REF.external_ref` on subject / provider
- **`|raw` is a last resort** — using it bypasses the readability win of FLAT. Prefer template-level remodelling (e.g. switch to `DV_TEXT`) or commit as canonical JSON (`application/openehr.v1+json`) if the payload is systematically rich.
- **Roundtrip hazard**: on GET the server returns canonical JSON or FLAT per the `Accept` header; the `|raw` input form is an input-only convenience.
