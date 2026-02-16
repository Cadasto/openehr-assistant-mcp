# Simplified Formats Idioms Cheat Sheet

**Purpose:** Fast reference for Flat and Structured format authoring and review
**Related:** openehr://guides/simplified_formats/principles, openehr://guides/simplified_formats/rules
**Keywords:** flat, structured, ctx, suffix, instance index, idiom

---

## Flat: Root Path + Context

```json
{
  "ctx/language": "en",
  "ctx/territory": "US",
  "ctx/time": "2024-01-15T10:30:00Z",
  "ctx/composer_name": "Dr. Smith",
  "template_id/observation_id:0/any_event:0/element|magnitude": 37.5
}
```

Context first; then composition paths under template/root node id.

---

## DV_QUANTITY

- `path/element|magnitude`: number
- `path/element|unit`: string (e.g. `"°C"`, `"mm[Hg]"`)

---

## DV_CODED_TEXT

- `path/element|code`: terminology code
- `path/element|value`: display term
- `path/element|terminology`: terminology id

---

## Single Event vs Multiple Events

- One event: path may omit event segment or use single index per OPT rules.
- Multiple events: use `any_event:0`, `any_event:1`, … (zero-based).

---

## Optional RM Attributes (Underscore)

- `path/_uid`: composition or node uid
- `path/context/_end_time`: end time
- `path/element/_normal_range/lower|magnitude`, `.../upper|magnitude`: normal range

---

## Raw Canonical JSON

Use `|raw` to embed a full RM object (e.g. DV_QUANTITY) as JSON; value must include `_type` and conform to RM. Use when decomposition into suffixes is impractical.

---

## Structured: Same Semantics, Nested

- Context under `ctx`: `"ctx": { "language": "en", "territory": "US" }`.
- Values as arrays: `"temperature": [ { "|magnitude": 37.5, "|unit": "°C" } ]`.
- Instance indices in keys: `"body_temperature:0"`, `"any_event:1"`.

---

## PARTY_PROXY / Composer

- `path/composer|name`, `path/composer|id`, `path/composer|id_scheme`, `path/composer|id_namespace`.
- In context: `ctx/composer_name`, `ctx/composer_id`, `ctx/id_scheme`, `ctx/id_namespace`; `ctx/composer_self`: true for PARTY_SELF.

---

## Micro Check

- OPT/template id known and consistent?
- All keys valid per Web Template?
- ctx/language and ctx/territory set (if required)?
- Pipe suffixes correct for type (magnitude/unit, code/value/terminology)?
- Instance indices zero-based and within cardinality?

---
