# Archetype Design & Review Checklist
**URI:** guidelines://archetypes/v1/checklist  
**Version:** 1.0.0  
**Scope:** Quality guidelines for openEHR archetype design, review, and publication  
**Source:** openEHR editorial guidelines, design principles, and review practices

---

## Metadata

- **Title:** openEHR Archetype Design Checklist
- **Purpose:** Support consistent, high-quality archetype creation
- **Audience:** Clinical modelers, archetype reviewers, implementers
- **Original Language:** en

---

## Key Principles

1. **Broad, Coherent Concept Scope**
    - Ensure the archetype represents a *single, coherent clinical concept* (e.g., Blood Pressure measurement, not isolated fragments). :contentReference[oaicite:1]{index=1}
    - The scope should be *universal* — usable across diverse care settings. :contentReference[oaicite:2]{index=2}

2. **Consistent, Stable Definitions**
    - Names, descriptions, and term definitions must be clear and reflect the clinical intent. :contentReference[oaicite:3]{index=3}
    - Archetypes should aim for *wide reuse* where sensible, avoiding unnecessary fragmentation. :contentReference[oaicite:4]{index=4}

---

## Structural Checklist

### 1. Header & Metadata

- [ ] Archetype ID follows conventions (namespace, versioning).
- [ ] Original language set to English (ISO-639-1 “en”). :contentReference[oaicite:5]{index=5}
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

- [ ] Definitions reflect *clinical meaning*, not implementation detail. :contentReference[oaicite:6]{index=6}
- [ ] Data semantics are independent of any one workflow/UI presentation.
- [ ] Required vs optional elements are well justified.

---

## Editorial Content Review

- [ ] The archetype name accurately reflects its content. :contentReference[oaicite:7]{index=7}
- [ ] Scope is for a *single clinical concept* and not overly narrow or broad. :contentReference[oaicite:8]{index=8}
- [ ] Protocol/State sections are used appropriately for measurement procedures vs semantics. :contentReference[oaicite:9]{index=9}
- [ ] Existing CKM comments and editor tasks have been addressed. :contentReference[oaicite:10]{index=10}

---

## Reuse and Specialization

- [ ] Existing archetypes were reviewed for possible reuse before creating a new one.
- [ ] If using slots, ensure they refer to appropriate cluster or element archetypes. :contentReference[oaicite:11]{index=11}
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

## Review Sign-off

- **Reviewer(s):** _____________________
- **Date Completed:** _____________________
- **Review Status:** [ ] Pass  [ ] Requires Revision  [ ] Defer

---

## Revision History

| Version | Date       | Notes |
|---------|------------|-------|
| 1.0.0   | 2025-12- | Initial public release |
