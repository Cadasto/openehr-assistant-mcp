# openEHR OET Syntax Guide
**URI:** openehr://guides/templates/oet-syntax  
**Version:** 1.0.0  
**Scope:** Technical specification of the OET (Ocean Template) XML format  
**Source:** Ocean Template Designer Documentation, CKM analysis

---

## 1. Overview
The OET format is an XML-based representation used primarily by the Ocean Template Designer. It describes a template by referencing archetypes and applying constraints.

- **Root Element:** `<template>`
- **Namespace:** `xmlns="openEHR/v1/Template"`

## 2. Top-Level Structure
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

## 3. The `<definition>` Element
The definition tree consists of structural elements (`Content`, `Items`) and constraint directives (`Rule`).

### 3.1 Structural Elements
- **`<Content>`**: Represents a top-level ENTRY or COMPOSITION.
  - Attributes: `archetype_id`, `path` (usually `/content`), `xsi:type`.
- **`<Items>`**: Represents a nested archetype (e.g., a CLUSTER inside a slot).
  - Attributes: `archetype_id`, `path` (relative to parent).

### 3.2 The `<Rule>` Element
The `<Rule>` element applies constraints to a specific node located by its `path`.

**Common Attributes:**
- `path`: The openEHR path to the constrained node.
- `name`: Override the node's display name.
- `min` / `max`: Occurrence constraints (e.g., `max="0"` to exclude).
- `hide_on_form`: Boolean UI hint.
- `clone="true"`: Creates named variants of repeated structures.

## 4. Constraint Types
Rules often contain a `<constraint>` child element of a specific `xsi:type`.

### 4.1 `tem:textConstraint`
Used for `DV_TEXT` and `DV_CODED_TEXT`.
- `<includedValues>`: List of permitted codes/strings.
- `limitToList="true"`: Forces selection from the list.

### 4.2 `tem:quantityConstraint`
Used for `DV_QUANTITY`.
- `<unitMagnitude>`: Defines permitted units and their ranges (`minMagnitude`, `maxMagnitude`).
- `<excludedUnits>`: List of units to forbid.

### 4.3 `tem:multipleConstraint`
Used for nodes that allow multiple RM types (choice).
- `<includedTypes>`: List of permitted types (e.g., `Coded_text`).

## 5. Metadata and Annotations
- **`<description>`**: Contains `lifecycle_state`, `purpose`, `use`, `misuse`, and `other_details` (key/value items).
- **`<annotations>`**: Stores free-form metadata, often used for mappings (e.g., `fhir_mapping`).

## 6. Implementation Note
OET is a design-time format. For runtime systems, it should be compiled into an **Operational Template (OPT)**, which flattens the structure and resolves all archetype references.

---

## Revision History

| Version | Date | Summary |
|---------|------------|---------|
| 1.0.0    | 2025-12 | Initial syntax documentation |
