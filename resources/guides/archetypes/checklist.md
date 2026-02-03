# openEHR Archetype Design & Review Checklist

**Purpose:** Support consistent, high-quality archetype creation
**Scope:** Quality guide for openEHR archetype design, review, and publication  
**Source:** openEHR editorial guidelines, design principles, and review practices
**Keywords:** design, review, checklist, quality, QA, consistency, best practice, clinical, terminology, structure, metadata, guideline

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
- [ ] Existence, occurrences, and cardinality are used correctly per AOM 1.4 (existence on attributes, occurrences on objects, cardinality on containers).
- [ ] Internal references (`use_node`) are used where identical structures repeat.

### 3. Terminology Binding

- [ ] All coded values bind to recognized code systems (SNOMED-CT, LOINC, ICD where applicable).
- [ ] Terms match the semantics of the clinical concept.
- [ ] Term definitions avoid ambiguity.

### 4. Semantic Clarity

- [ ] Definitions reflect *clinical meaning*, not implementation detail. 
- [ ] Data semantics are independent of any one workflow/UI presentation.
- [ ] Required vs optional elements are well justified.

### 5. Translation & Localisation

- [ ] Translations preserve clinical intent and meaning.
- [ ] Language is natural for the target clinical register (not awkward literal translation).
- [ ] Terminology usage is consistent throughout the archetype.
- [ ] No changes to node identifiers (`at-codes`, `ac-codes`) or RM structure.
- [ ] Alignment with authoritative target-language clinical terminology (e.g., SNOMED CT).

---

## Editorial Content Review

- [ ] The archetype name accurately reflects its content. 
- [ ] Scope is for a *single clinical concept* and not overly narrow or broad. 
- [ ] Protocol/State sections are used appropriately for measurement procedures vs semantics. 
- [ ] Metadata (Purpose, Use, Misuse, etc.) is complete and translated where required.
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

## Paths, Identifiers & Queryability

- [ ] All nodes have stable identifiers (`at-codes`) defined in `term_definitions`.
- [ ] All constraint codes (`ac-codes`) are defined in `constraint_definitions`.
- [ ] No identifiers have been changed from previous compatible versions.
- [ ] Archetype paths are stable and suitable for long-term AQL use.
- [ ] Path structure supports intuitive semantic querying and analytics.
- [ ] ADL 1.4 validity rules pass (VARID, VARCN, VARDF, VARON, VARDT, VATDF, VACDF).

---

## Versioning & Change Management

- [ ] Version increment reflects the **semantic impact** of changes.
- [ ] Backward compatibility has been assessed.
- [ ] Deprecated elements are retained and clearly marked, not removed.
- [ ] Revision history accurately summarises changes and rationale.

---

## Documentation & Examples

- [ ] Example constraint instances or use-case sketches are provided.
- [ ] Rationale for structural or semantic choices included (reviewer guidance).
- [ ] Link to related templates/use cases where this archetype is used.
- [ ] Known limitations or exclusions are documented.

---
