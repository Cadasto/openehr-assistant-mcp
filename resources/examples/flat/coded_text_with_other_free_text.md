# DV_CODED_TEXT with Free-Text `|other` — FLAT Example

**Pattern:** free-text value on a coded element when "Limit to list" is unchecked
**Demonstrates:** the `|other` suffix on a DV_CODED_TEXT element — used when the user's entered text is **not** in the external coded list and the template permits free-text fallback. Contrast with `|value` (which requires a matching defining-code) and `|code` (the external code itself).
**MIME type:** `application/openehr.wt.flat+json`
**Template:** assumes a `body_weight.v2`-based template with a "Confounding factors" DV_CODED_TEXT element that has "Limit to list" unchecked
**Related:** `openehr://guides/specs/its-rest-simplified_formats`, `openehr://guides/simplified_formats/rules`
**Source:** openEHR Discourse — [Flat path `\|other` missing in simplified format for DV_CODED_TEXT](https://discourse.openehr.org/t/flat-path-other-missing-in-simplified-format-for-dv-coded-text/11790) (Feb 2026)

---

### Case A — coded value from the list

```json
{
  "ctx/language": "en",
  "ctx/territory": "US",
  "ctx/composer_name": "Nurse J. Doe",

  "body_weight/any_event:0/weight|magnitude": 78.5,
  "body_weight/any_event:0/weight|unit": "kg",
  "body_weight/any_event:0/confounding_factors|code": "at0104",
  "body_weight/any_event:0/confounding_factors|value": "Recent meal",
  "body_weight/any_event:0/confounding_factors|terminology": "local"
}
```

### Case B — free-text value that is NOT in the list (use `|other`)

```json
{
  "ctx/language": "en",
  "ctx/territory": "US",
  "ctx/composer_name": "Nurse J. Doe",

  "body_weight/any_event:0/weight|magnitude": 78.5,
  "body_weight/any_event:0/weight|unit": "kg",
  "body_weight/any_event:0/confounding_factors|other": "Patient had just returned from a 10km run"
}
```

### Case C — the failing shape (anti-pattern)

```json
{
  "body_weight/any_event:0/confounding_factors|value": "Patient had just returned from a 10km run"
}
```

EHRbase rejects Case C with an error along the lines of "Attribute `code_string` of class CODE_PHRASE does not match existence 1..1" — because `|value` with no `|code` gives a malformed `DV_CODED_TEXT`.

## Notes

- **`|other`** is the FLAT escape for the "Limit to list unchecked" template configuration. The spec does not document this suffix formally, but EHRbase requires it to commit free-text into a DV_CODED_TEXT element.
- **Better Platform** handles the same situation differently — consult its docs before assuming portability. A vendor-adapter layer is the usual abstraction.
- **Alternative modelling** (per Ian McNicoll on the Discourse thread) — if the template design allows, constrain the element to `DV_TEXT` only. `DV_TEXT` is the RM supertype; any `DV_CODED_TEXT` satisfies a `DV_TEXT` constraint, so coded values still work, and free-text works without the `|other` dance. The reverse does not hold.
- For round-trip safety: INPUT `|other` will typically return on OUTPUT as a `DV_TEXT` instance under the same path (no `|code`/`|terminology`), not as the `|other` suffix. This is part of the INPUT≠OUTPUT asymmetry that affects all FLAT payloads.
