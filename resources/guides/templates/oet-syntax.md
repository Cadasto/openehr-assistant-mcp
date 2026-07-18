# openEHR OET Syntax Guide

**Scope:** Technical specification of the OET (Ocean Template) XML format
**Related:** openehr://guides/templates/oet-idioms-cheatsheet, openehr://guides/templates/serialization-formats, openehr://guides/templates/opt-structure, openehr://guides/templates/web-template, openehr://guides/templates/rules, openehr://guides/specs/am2-OPT2
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
  <annotations path="..."> ... </annotations> <!-- Repeatable; key/value items per node path -->
  <definition> ... </definition> <!-- The tree of constraints -->
  <integrity_checks xsi:type="ArchetypeIntegrity" archetype_id="..."> <!-- One per referenced archetype -->
    <digest id="MD5-CAM-1.0.1">...</digest>
  </integrity_checks>
</template>
```

## The `<definition>` Element
The definition tree consists of structural elements (`Content`, `Items`) and constraint directives (`Rule`).

### Structural Elements
- **`<Content>`**: Represents an ENTRY or SECTION archetype placed under the root COMPOSITION's `/content` (the COMPOSITION itself is the `<definition>` element).
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
- `<unitMagnitude>`: Defines a permitted unit and its range. Children: `<unit>` (UCUM string), `<minMagnitude>`, `<maxMagnitude>`, `<includesMinimum>`, `<includesMaximum>` (booleans for bound inclusivity). Repeatable, one per unit.
- `<includedUnits>` / `<excludedUnits>`: Lists of units to permit / forbid.

### `countConstraint`
Used for `DV_COUNT`. Magnitude bounds via `<minMagnitude>`/`<maxMagnitude>` with `<includesMinimum>`/`<includesMaximum>` flags (same bound elements as `unitMagnitude`).

### `multipleConstraint`
Used for nodes that allow multiple RM types (choice).
- `<includedTypes>`: List of permitted types. Values seen in real CKM OETs: `Text`, `Coded_text`, `Boolean`, `Count`, `Quantity`, `Interval`, `Date_Time`, `Duration`, `Identifier`, `URI`.

### `<eventConstraint>` (not a `<constraint>` xsi:type)
A direct child of `<Rule>` (alongside or instead of `<constraint>`) restricting the event type of an EVENT node:
```xml
<Rule path="/data[at0001]/events[at0002]">
  <eventConstraint>
    <allowedType>PointInTime</allowedType>
  </eventConstraint>
</Rule>
```

## Metadata and Annotations
- **`<description>`**: Contains `lifecycle_state`, `purpose`, `use`, `misuse`, and `other_details` (key/value items).
- **`<annotations>`**: Stores free-form node-level metadata, often used for mappings (e.g., `fhir_mapping`, `source`). Each `<annotations>` element is a top-level sibling of `<definition>` (CKM exports place them between `<description>` and `<definition>`), carries a `path` XML attribute holding the fully-qualified template path (starting `[archetype_id]/...`, with `'name'` qualifiers for cloned/renamed nodes), and contains `<items><item><key>...</key><value>...</value></item></items>`.

## Implementation Note
OET is a design-time format. For runtime systems, it should be compiled into an **Operational Template (OPT)**, which flattens the structure and resolves all archetype references. For how OET relates to OPT, Archetype Designer `.t.json`, and web templates, see openehr://guides/templates/serialization-formats.

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
| `<eventConstraint>` | Child of `<Rule>` restricting an EVENT node's type. | (child only: `<allowedType>`, e.g. `PointInTime`) |
| `<Context>` | Optional trailing container for `EVENT_CONTEXT` constraints. | (children only: `<Items>`, `<Rule>`, `<hide_on_form>`) |

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

**The trailing `<Context/>` element** — `<Context>` is an *optional* last child of `<definition>`, holding `EVENT_CONTEXT` constraints: `<Items>` filling other-context CLUSTER slots, `<Rule>`s (e.g. on `/context/other_context[at0001]/...` paths or `/context/setting`), and an optional `<hide_on_form>true</hide_on_form>` child element. In real CKM OETs roughly half the templates omit it entirely; several tools emit it empty when no context constraints are needed:
```xml
<Context />
```

---
