# openEHR Archetype Design & Review Checklist

**Purpose:** Quality checklist for archetype design, review, and publication
**Keywords:** checklist, QA, design, review, quality, consistency, best practice, assessment, guideline, validation

---

## A. Concept & Scope Validation

- [ ] Archetype represents **exactly one coherent clinical or domain concept**.
- [ ] Concept scope is **neither overly narrow nor artificially broad**; suitable for international reuse.
- [ ] Content does **not mix** observations, evaluations, instructions, or actions inappropriately.
- [ ] If the concept is scenario- or document-specific, it is modelled as a **template**, not an archetype.
- [ ] Stable definitions: clear names and descriptions reflecting clinical intent; aim for wide reuse where sensible.

---

## B. Header & Metadata

- [ ] Archetype ID follows naming and versioning conventions (e.g. `openEHR-EHR-OBSERVATION.<concept>.v1.0.0`).
- [ ] Correct RM type is used (e.g. OBSERVATION, EVALUATION, INSTRUCTION, ACTION, ADMIN_ENTRY).
- [ ] Original language set (ISO 639-1, typically `en`)
- [ ] Purpose and usage fields complete
- [ ] Author, contributor, licensing metadata present

---

## C. Structural & RM Conformance

- [ ] Root node matches targeted clinical concept
- [ ] Cardinality constraints justified
- [ ] Appropriate RM types (OBSERVATION, EVALUATION, etc.)
- [ ] Logical C_OBJECT/C_ATTRIBUTE hierarchy following RM semantics
- [ ] Correct use of existence (attributes), occurrences (objects), cardinality (containers)
- [ ] Internal references (`use_node`) for repeated structures

---

## D. Terminology & Semantics

- [ ] Coded values bind to recognised systems (SNOMED CT, LOINC, ICD)
- [ ] Terms match clinical concept semantics
- [ ] Unambiguous term definitions

---

## E. Translation

- [ ] Translations preserve clinical intent
- [ ] Natural target-language phrasing
- [ ] Consistent terminology throughout
- [ ] No changes to identifiers or structure
- [ ] Aligned with authoritative local terminology

---

## F. Editorial & Clinical Review

- [ ] Name accurately reflects content
- [ ] Single concept scope (not too narrow/broad)
- [ ] Protocol/State sections used correctly
- [ ] Metadata complete and translated
- [ ] Internal term and cardinality consistency
- [ ] No duplicate content (refactor to clusters)

---

## G. Reuse, Slots & Specialisation

- [ ] Existing archetypes reviewed before creating new
- [ ] Consistent with related domain archetypes
- [ ] Slots reference appropriate archetypes
- [ ] Specialisations justify divergence from parent


---

## H. Paths, Identifiers & Queryability

- [ ] All at-codes defined in `term_definitions`
- [ ] All ac-codes defined in `constraint_definitions`
- [ ] Identifiers unchanged from compatible versions
- [ ] Paths stable for long-term AQL use
- [ ] ADL 1.4 validity rules pass (VARID, VARCN, VARDF, VARON, VARDT, VATDF, VACDF)

---

## I. Versioning & Change Management

- [ ] Version increment reflects semantic impact
- [ ] Backward compatibility assessed
- [ ] Deprecated elements retained and marked
- [ ] Revision history documents changes

---

## J. Documentation & Supporting Material

- [ ] Example instances or use-case sketches provided
- [ ] Rationale for non-obvious choices documented
- [ ] Links to related templates/use cases
- [ ] Known limitations documented

---
