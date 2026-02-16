# openEHR Simplified Formats — Rules

**Scope:** Structural and syntactic rules for Flat and Structured formats
**Related:** openehr://guides/simplified_formats/principles, openehr://guides/simplified_formats/idioms-cheatsheet
**Keywords:** flat, structured, field identifier, path, suffix, context, validation

**Official specification:** For full structure, RM mappings, and context details, visit the openEHR Simplified Formats specification: https://specifications.openehr.org/releases/ITS-REST/development/simplified_formats — agents should consult it when looking for more information beyond this guide.

---

## Field Identifier Components

1. **Node IDs**: From archetype node names (normalised: lowercase, non-alphanumeric → underscore, uniqueness suffix if needed).
2. **Path separator**: Forward slash (`/`) between hierarchy levels.
3. **Instance index**: Colon notation for repeating elements: `node_id:0`, `node_id:1` (zero-based).
4. **Attribute suffix**: Pipe (`|`) for RM attributes: `|magnitude`, `|unit`, `|code`, `|value`, `|terminology`, `|name`, etc.
5. **RM attribute prefix**: Underscore (`_`) for optional RM attributes not in the template: `_uid`, `_end_time`, `_normal_range`.

---

## Flat Format Rules

- All keys are **fully qualified** from the composition root (template_id or root node id + path).
- **Context** keys use `ctx/` prefix (e.g. `ctx/language`, `ctx/territory`, `ctx/time`, `ctx/composer_name`).
- Instance indices are **zero-based** (`:0`, `:1`, …).
- Attribute suffixes are separated by **pipe** (`|magnitude`, `|code`).
- RM-only attributes use **leading underscore** in the path segment (`_uid`, `context/_end_time`).
- Path segments separated by **forward slash** (`/`).
- No ELEMENT wrapper: the key ends at the value’s attributes (e.g. `path/systolic|magnitude`).

---

## Structured Format Rules

- **Hierarchy** preserved as nested JSON objects; each path segment is a property.
- Instance indices **remain in property names** (e.g. `body_temperature:0`).
- Attribute suffixes become properties with **pipe prefix** (e.g. `|magnitude`, `|code`).
- **Context** grouped under a single `ctx` object.
- **Arrays** used for values (even when cardinality is 0..1 or 1..1).
- Omit empty objects where appropriate.

---

## Context (ctx)

- **Mandatory** (typical): language, territory.
- **Optional**: composer (name, id, id_scheme, id_namespace, composer_self), time, end_time, setting, location, participations, health_care_facility, workflow_id, etc.
- In Flat: `ctx/field` or `ctx/field|suffix`; in Structured: `ctx.field` or nested under `ctx`.

---

## Level Removal (Path Simplification)

Certain RM types are omitted; their content is lifted into the path:

- ITEM_TREE → items; ITEM_LIST → items; ITEM_SINGLE → item; ITEM_TABLE → rows; HISTORY → events.
- EVENT removed when max = 1 and no sibling EVENT types; otherwise retained with instance index.

---

## Validation (Checklist-Oriented)

- Field identifiers **match the Web Template** for the target OPT.
- **Mandatory context** (language, territory) present.
- **Cardinality** and data types match the template.
- **Terminology** codes and suffixes (e.g. `|code`, `|terminology`) valid where applicable.
- **Underscore-prefixed** paths only for valid optional RM attributes.

---
