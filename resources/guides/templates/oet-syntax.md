# openEHR OET Syntax Guide

**Scope:** Technical specification of the OET (Ocean Template) XML format
**Related:** openehr://guides/templates/oet-idioms-cheatsheet, openehr://guides/templates/serialization-formats, openehr://guides/templates/rules, openehr://guides/specs/am2-OPT2
**Source:** Ocean Template Designer Documentation, CKM analysis
**Keywords:** OET, OPT, XML, constraint, template, specification, syntax, validation, design, clinical, lint, validation, definition

---

## Overview
The OET format is an XML-based representation used primarily by the Ocean Template Designer. It describes a template by referencing archetypes and applying constraints.

- **Root Element:** `<template>`
- **Namespace:** `xmlns="openEHR/v1/Template"`

## Top-Level Structure
```xml
<template xmlns="openEHR/v1/Template" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <id>...</id> <!-- UUID or unique string -->
  <name>...</name> <!-- Human-readable template name -->
  <description> ... </description> <!-- Metadata -->
  <definition> ... </definition> <!-- The tree of constraints -->
  <annotations> ... </annotations> <!-- Key/Value pairs -->
  <integrity_checks archetype_id="...">
    <digest>...</digest>
  </integrity_checks>
</template>
```

## The `<definition>` Element
The definition tree consists of structural elements (`Content`, `Items`) and constraint directives (`Rule`).

### Structural Elements
- **`<Content>`**: Represents a top-level ENTRY or COMPOSITION.
  - Attributes: `archetype_id`, `path` (usually `/content`), `xsi:type`.
- **`<Items>`**: Represents a nested archetype (e.g., a CLUSTER inside a slot).
  - Attributes: `archetype_id`, `path` (relative to parent).

### The `<Rule>` Element
The `<Rule>` element applies constraints to a specific node located by its `path`.

**Common Attributes:**
- `path`: The openEHR path to the constrained node.
- `name`: Override the node's display name.
- `min` / `max`: Occurrence constraints (e.g., `max="0"` to exclude).
- `hide_on_form`: Boolean UI hint.
- `clone="true"`: Creates named variants of repeated structures.

## Constraint Types
Rules often contain a `<constraint>` child element of a specific `xsi:type`.

### `textConstraint`
Used for `DV_TEXT` and `DV_CODED_TEXT`.
- `<includedValues>`: List of permitted codes/strings.
- `limitToList="true"`: Forces selection from the list.

### `quantityConstraint`
Used for `DV_QUANTITY`.
- `<unitMagnitude>`: Defines permitted units and their ranges (`minMagnitude`, `maxMagnitude`).
- `<excludedUnits>`: List of units to forbid.

### `multipleConstraint`
Used for nodes that allow multiple RM types (choice).
- `<includedTypes>`: List of permitted types (e.g., `Coded_text`).

## Metadata and Annotations
- **`<description>`**: Contains `lifecycle_state`, `purpose`, `use`, `misuse`, and `other_details` (key/value items).
- **`<annotations>`**: Stores free-form metadata, often used for mappings (e.g., `fhir_mapping`).

## Implementation Note
OET is a design-time format. For runtime systems, it should be compiled into an **Operational Template (OPT)**, which flattens the structure and resolves all archetype references. For how OET relates to OPT, ADL Designer `.t.json`, and web templates, see openehr://guides/templates/serialization-formats.

---

## Attribute Reference (by element)

Grounded in real CKM OETs (CID `1013.26.1`, `1013.26.380`); do not invent attributes beyond those listed.

> **Namespace note.** Some Ocean Template Designer exports prefix the template types (`xsi:type="tem:OBSERVATION"`); others omit the prefix (`xsi:type="OBSERVATION"`). Both are valid; the `tem:` prefix maps to `xmlns:tem="openEHR/v1/Template"`.

| Element | Role | Key attributes |
|---|---|---|
| `<definition>` | Root of the constraint tree (the COMPOSITION). | `archetype_id`, `concept_name`, `name`, `xsi:type` (e.g. `COMPOSITION`) |
| `<Content>` | A top-level ENTRY/SECTION placed in `/content`. | `archetype_id`, `concept_name`, `name`, `path` (`/content`), `max`, `xsi:type`, `template_id` (when the slot is filled by a sub-template) |
| `<Item>` | A single archetype placed into a SECTION/structure node (Ocean Designer style). | `archetype_id`, `concept_name`, `name`, `path`, `min`, `max`, `xsi:type` |
| `<Items>` | An archetype filling a slot (often nested CLUSTER). | `archetype_id`, `concept_name`, `name`, `path`, `min`, `max`, `xsi:type` |
| `<Rule>` | Constrains an existing node located by `path`. | `path`, `name`, `min`, `max`, `hide_on_form`, `default`, `clone` |
| `<constraint>` | Child of `<Rule>` carrying a typed value constraint. | `xsi:type` (`textConstraint`, `quantityConstraint`, `multipleConstraint`, `countConstraint`), `limitToList` (on `textConstraint`) |
| `<Context>` | Trailing container for `EVENT_CONTEXT` constraints. | (children only: `<Items>`, `<Rule>`, `<hide_on_form>`) |

Notes:
- `archetype_id` / `template_id` and `xsi:type` appear on the *placement* elements (`Content`/`Item`/`Items`), never on `<Rule>` — a `<Rule>` only re-points at an already-placed node via `path`.
- `min`/`max` are occurrence overrides; `name` is a display-label override; `hide_on_form` and `default` are tooling hints (not normative OPT 2 — see openehr://guides/templates/principles).
- `clone="true"` produces a named variant of a repeatable node (pair it with `name`).

### Worked snippets

**Required entry (`min="1" max="1"`)** — escalate an optional node to mandatory-single:
```xml
<Rule path="/data[at0001]/items[at0002]" min="1" max="1"/>
```

**Excluded node (`max="0"`)** — remove an archetype node not needed for the use case:
```xml
<Rule max="0" path="/data[at0001]/events[at0006]/state[at0007]/items[at1052]"/>
```

**Node rename (`name="..."`)** — relabel without moving the path:
```xml
<Rule path="/activities[at0001]/description[at0002]/items[at0009]" name="dosageInstructions"/>
```

**Inline `textConstraint` + value-set narrowing (`limitToList`)** — subset a coded list; `limitToList="true"` blocks free-text leakage, `"false"` allows additional free text:
```xml
<Rule path="/data[at0001]/items[at0003]/items[at0008]/items[at0009]">
  <constraint limitToList="true" xsi:type="tem:textConstraint">
    <includedValues>Diabetes</includedValues>
    <includedValues>Heart disease</includedValues>
    <includedValues>Cancer</includedValues>
  </constraint>
</Rule>
```
(Also: `<excludedValues>local::at1002</excludedValues>` drops one at-code; `<constraint xsi:type="tem:multipleConstraint"><includedTypes>Coded_text</includedTypes></constraint>` picks one RM type from a choice.)

**Fix a context value to a code (`/context/setting`)** — `EVENT_CONTEXT.setting` is a `DV_CODED_TEXT` from the openEHR `setting` group; narrow it with a `textConstraint` inside `<Context>` (runtime value via `ctx/setting|code`):
```xml
<Context>
  <Rule path="/context/setting">
    <constraint xsi:type="tem:textConstraint">
      <includedValues>secondary medical care</includedValues>
    </constraint>
  </Rule>
</Context>
```

**The trailing `<Context/>` element** — every `<definition>` ends with a `<Context>` block holding `EVENT_CONTEXT` constraints (other-context CLUSTERs, setting, `hide_on_form`). When no context constraints are needed it is present but empty:
```xml
<Context />
```

---
