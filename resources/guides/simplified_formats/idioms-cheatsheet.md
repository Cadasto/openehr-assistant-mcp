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
- Optional: `|precision`, `|magnitude_status`, `|accuracy`, `|accuracy_is_percent`, `|normal_status`; ranges via `/_normal_range/lower|magnitude` etc.

---

## DV_CODED_TEXT

- `path/element|code`: terminology code (required)
- `path/element|value`: display term
- `path/element|terminology`: terminology id

`|value` and `|terminology` are required only for external terminologies; for local at-codes they can be derived from the template.

- `path/element|other`: free-text branch of an **open** value-set (`listOpen: true`); mutually exclusive with `|code`/`|value`/`|terminology`; persists as DV_TEXT.

---

## DV_ORDINAL

- `path/element|code`: symbol code (at-code)
- `path/element|value`: symbol text
- `path/element|ordinal`: number (RM `value`)

`|value` and `|ordinal` may be left out when the symbol is defined in the template.

---

## DV_PROPORTION

- `path/element|numerator`, `path/element|denominator`: numbers
- `path/element|type`: integer, RM PROPORTION_KIND (0 ratio, 1 unitary, 2 percent, 3 fraction, 4 integer fraction)

Magnitude is calculated on output (bare `path/element`); do not write it.

---

## Single Event vs Multiple Events

- Event segment collapsed only when max = 1 AND no sibling EVENT nodes in the same HISTORY.
- Otherwise retained with index: `any_event:0`, `any_event:1`, … (zero-based).

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
- Repeats: successive elements of the array value; index-suffixed keys (`"any_event:1"`) also occur (e.g. from Flat conversion).

---

## PARTY_PROXY / Composer

- `path/composer|name`, `path/composer|id`, `path/composer|id_scheme`, `path/composer|id_namespace`.
- In context: `ctx/composer_name`, `ctx/composer_id`, `ctx/id_scheme`, `ctx/id_namespace`; `ctx/composer_self`: true for PARTY_SELF.
- Demographic identifiers: `path/composer/_identifier:0|id`, `...|issuer`, `...|assigner`, `...|type`.

---

## Participations

- Via context (defaults for `EVENT_CONTEXT.participations` and `ENTRY.other_participations`): `ctx/participation_name:0`, `ctx/participation_function:0`, `ctx/participation_mode:0`, `ctx/participation_id:0` (repeat with `:1`, …).
- Per EVENT_CONTEXT: `path/context/_participation:0|function`, `|mode`, `|name`, `|id`, `|id_scheme`, `|id_namespace`.
- Per ENTRY: `path/entry/_other_participation:0|function`, `|mode`, `|name`, `|id`, `|id_scheme`, `|id_namespace`.

---

## ACTIVITY Timing (INSTRUCTION)

- `path/instruction/activity/timing`: parsable value (e.g. `"R4/2022-01-31T10:00:00+01:00/P3M"`)
- `path/instruction/activity/timing|formalism`: formalism string
- Defaults via `ctx/activity_timing`; `action_archetype_id` defaults to `/.*/` if unset.

---

## Micro Check

- OPT/template id known and consistent?
- All keys valid per Web Template?
- ctx/language and ctx/territory set (if required)?
- Pipe suffixes correct for type (magnitude/unit, code/value/terminology, ordinal, numerator/denominator/type)?
- Instance indices zero-based and within cardinality?
- `|other` only on open (`listOpen`) coded leaves, never with `|code`/`|value`/`|terminology`?

---
