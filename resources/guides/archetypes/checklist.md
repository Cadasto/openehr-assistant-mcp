# openEHR Archetype Design & Review Checklist
**URI:** openehr://guides/archetypes/checklist  
**Version:** 1.0.0  
**Purpose:** Support consistent, high-quality archetype creation
**Scope:** Quality guide for openEHR archetype design, review, and publication  
**Source:** openEHR editorial guidelines, design principles, and review practices

---

## Key Principles

1. **Broad, Coherent Concept Scope**
    - Ensure the archetype represents a *single, coherent clinical concept* (e.g., Blood Pressure measurement, not isolated fragments). 
    - The scope should be *universal* - usable across diverse care settings. 

2. **Consistent, Stable Definitions**
    - Names, descriptions, and term definitions must be clear and reflect the clinical intent. 
    - Archetypes should aim for *wide reuse* where sensible, avoiding unnecessary fragmentation. 

---

## Structural Checklist

### 1. Header & Metadata

- [ ] Archetype ID follows conventions (namespace, versioning).
- [ ] Original language set to English (ISO-639-1 “en”). 
- [ ] Purpose and usage fields are concise and accurate.
- [ ] Author, contributor, and licensing metadata are complete.

### 2. Definition & Structure

- [ ] Root node corresponds to the targeted clinical concept.
- [ ] Cardinality constraints are justified and documented.
- [ ] Data types and RM types (e.g., *Observation*, *Evaluation*) are appropriate.
- [ ] Hierarchical structure (C_OBJECT/C_ATTRIBUTE) is logical and follows RM semantics.

### 3. Terminology Binding

- [ ] All coded values bind to recognized code systems (SNOMED-CT, LOINC, ICD where applicable).
- [ ] Terms match the semantics of the clinical concept.
- [ ] Term definitions avoid ambiguity.

### 4. Semantic Clarity

- [ ] Definitions reflect *clinical meaning*, not implementation detail. 
- [ ] Data semantics are independent of any one workflow/UI presentation.
- [ ] Required vs optional elements are well justified.

---

## Editorial Content Review

- [ ] The archetype name accurately reflects its content. 
- [ ] Scope is for a *single clinical concept* and not overly narrow or broad. 
- [ ] Protocol/State sections are used appropriately for measurement procedures vs semantics. 
- [ ] Existing CKM comments and editor tasks have been addressed. 

---

## Reuse and Specialization

- [ ] Existing archetypes were reviewed for possible reuse before creating a new one.
- [ ] If using slots, ensure they refer to appropriate cluster or element archetypes. 
- [ ] Specialized versions justify divergence from the parent archetype.

---

## Quality & Consistency

- [ ] Consistency with related archetypes within the domain.
- [ ] Internal consistency of term usage and cardinality definitions.
- [ ] No duplicate content that should be refactored into clusters.

---

## Documentation & Examples

- [ ] Example constraint instances or use-case sketches are provided.
- [ ] Rationale for structural or semantic choices included (reviewer guidance).
- [ ] Link to related templates/use cases where this archetype is used.

---

## Revision History

| Version | Date       | Notes |
|---------|------------|-------|
| 1.0.0   | 2025-12- | Initial public release |
