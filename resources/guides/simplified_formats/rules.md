# openEHR Simplified Formats — Rules

**Scope:** Structural and syntactic rules for Flat and Structured formats
**Related:** openehr://guides/simplified_formats/principles, openehr://guides/simplified_formats/idioms-cheatsheet, openehr://guides/specs/its-rest-simplified_formats
**Keywords:** flat, structured, field identifier, path, suffix, context, validation

**Official specification:** For full structure, RM mappings, and context details, visit the openEHR Simplified Formats specification: https://specifications.openehr.org/releases/ITS-REST/development/simplified_formats — agents should consult it when looking for more information beyond this guide.

---

## Field Identifier Components

1. **Node IDs**: From archetype node names, normalised as follows: any character that is not a Unicode letter, digit, `_`, `.` or `-` → underscore; consecutive underscores consolidated; lowercased; leading/trailing underscores trimmed; empty result → `id`; leading digit → prepend `a`; numeric suffix appended for uniqueness among siblings (e.g. `blood_pressure`, `blood_pressure_1`).
2. **Path separator**: Forward slash (`/`) between hierarchy levels.
3. **Instance index**: Colon notation for repeating elements: `node_id:0`, `node_id:1` (zero-based).
4. **Attribute suffix**: Pipe (`|`) for RM attributes: `|magnitude`, `|unit`, `|code`, `|value`, `|terminology`, `|name`, etc.
5. **RM attribute prefix**: Underscore (`_`) for optional RM attributes not in the template: `_uid`, `_end_time`, `_normal_range`.
6. **Raw canonical JSON**: `|raw` suffix embeds a pre-serialized canonical RM object as the value; it must carry `_type` and conform to the RM.

---

## Flat Format Rules

- All keys are **fully qualified** from the data-instance root (the root node id generated from the template id, e.g. `blood_pressure_demo.v0`).
- **Context** keys use `ctx/` prefix (e.g. `ctx/language`, `ctx/territory`, `ctx/time`, `ctx/composer_name`).
- Instance indices are **zero-based** (`:0`, `:1`, …).
- Attribute suffixes are separated by **pipe** (`|magnitude`, `|code`).
- RM-only attributes use **leading underscore** in the path segment (`_uid`, `context/_end_time`).
- Path segments separated by **forward slash** (`/`).
- No ELEMENT wrapper: the key ends at the value’s attributes (e.g. `path/systolic|magnitude`).

---

## Structured Format Rules

- **Hierarchy** preserved as nested JSON objects; each path segment is a property.
- Repeated instances appear as **successive elements of the array value** (the spec's examples); index-suffixed property names (e.g. `body_temperature:0`) also remain valid per the spec's syntax rules, notably when converting mechanically from Flat.
- Attribute suffixes become properties with **pipe prefix** (e.g. `|magnitude`, `|code`).
- **Context** grouped under a single `ctx` object.
- **Arrays** used for values (even when cardinality is 0..1 or 1..1).
- Omit empty objects where appropriate.

---

## Context (ctx)

- **Mandatory** (typical): language, territory.
- **Optional**: composer (`composer_name`, `composer_id`, `composer_self`), default id qualifiers (`id_scheme`, `id_namespace`), `time`, `end_time`, `history_origin`, `action_time`, `activity_timing`, `setting`, `location`, `participation_*:i` (name/function/mode/id/identifiers), `health_care_facility`, `work_flow_id`, `provider_name`/`provider_id`, `action_ism_transition_current_state`, `instruction_narrative`, `link:i`.
- In Flat: `ctx/field` or `ctx/field|suffix`; in Structured: `ctx.field` or nested under `ctx`.
- ctx values are **defaults for the RM tree**: e.g. `ctx/time` feeds `context/start_time`, `OBSERVATION.history.origin` (and thence `EVENT.time`), and `ACTION.time`.
- **Server-side defaults when omitted**: `ctx/time` → `now()`; `ctx/setting` → "other care"; ENTRY `subject` → `PARTY_SELF`; `history.origin` → time of earliest event; `ACTIVITY.action_archetype_id` → `/.*/`.

---

## Level Removal (Path Simplification)

Two distinct removals happen relative to the canonical RM path:

- **Container attribute names are always elided**: `content`, `items`, `rows`, `item`, `events`, `data`, `state`, `protocol`, `description`, `activities` never appear as path segments — the parent connects directly to the child via the child's node id.
- **Wrapper node types are always collapsed** (their archetype node-id is also dropped): the `ITEM_STRUCTURE` family (`ITEM_TREE`, `ITEM_LIST`, `ITEM_SINGLE`, `ITEM_TABLE`) and `HISTORY`.
- **EVENT is collapsed conditionally**: only when max = 1 AND no sibling EVENT nodes exist in the same HISTORY; otherwise retained with instance index.
- `ELEMENT.value` is replaced by the attribute suffix (e.g. `|magnitude`); the ELEMENT's node id names the leaf.

---

## Open Value-Sets (`|other`)

When a leaf is constrained to DV_CODED_TEXT with an **open** value-set (`listOpen: true` in the Web Template; non-limit-to-list in ADL), the free-text branch is written explicitly as `<path>|other: "<text>"`:

- `|other` is **mutually exclusive** with `|code`, `|value`, `|terminology` (and `|preferred_term`) on the same leaf; servers MUST reject combinations.
- `|other` MUST be rejected when the list is **closed** (`listOpen: false`).
- Canonically the leaf becomes a **DV_TEXT** (not a DV_CODED_TEXT with empty defining_code); on read, servers SHOULD emit such leaves via `|other` so round-trips hold.

---

## Validation (Checklist-Oriented)

- Field identifiers **match the Web Template** for the target OPT.
- **Mandatory context** (language, territory) present.
- **Cardinality** and data types match the template.
- **Terminology** codes and suffixes (e.g. `|code`, `|terminology`) valid where applicable.
- **Underscore-prefixed** paths only for valid optional RM attributes.
- **`|other`** used only on open value-set leaves, never combined with `|code`/`|value`/`|terminology`.

---
