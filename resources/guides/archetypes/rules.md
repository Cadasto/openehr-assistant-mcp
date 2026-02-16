# openEHR Archetype Design Rules

**Scope:** Normative rules for modelling openEHR archetypes
**Related:** `openehr://guides/archetypes/structural-constraints`, `openehr://guides/archetypes/terminology`
**Keywords:** ADL, AOM, rules, modeling, lint, checks, validation

---

## A. Concept and Scope

- **A1:** An archetype SHALL represent exactly one coherent clinical or domain concept.
- **A2:** An archetype SHALL NOT combine unrelated concepts (e.g., observations and orders).
- **A3:** Scope SHALL be broad enough for international reuse but no broader than the core concept.
- **A4:** Use-case or workflow-specific models SHALL be templates, not archetypes.

---

## B. Metadata

- **B1:** Archetype IDs SHALL follow the standard pattern: `openEHR-<DOMAIN>-<RM_TYPE>.<concept>.v<MAJOR>.<MINOR>.<PATCH>`.
- **B2:** Provide a clear, clinician-friendly purpose description.

---

## C. Structural Modelling

- **C1:** RM structures SHALL be used as intended; do not compensate for missing application features.
- **C2:** Cardinalities SHALL be justified by clinical reality, not UI convenience.
- **C3:** Maximise optionality in archetypes; restriction belongs in templates.
- **C4:** Leaf nodes SHALL use appropriate RM data types (DV_QUANTITY, DV_CODED_TEXT, etc.).
- **C5:** Repeating structures SHALL use RM repetition, not duplicated nodes.
- **C6:** Clusters SHALL only group logically inseparable sub-concepts.

---

## D. Reuse, Slots, and Specialisation

- **D1:** Reuse existing published archetypes wherever semantically appropriate.
- **D2:** Slots SHALL be explicitly constrained.
- **D3:** Specialisation SHALL only be used for true semantic subtypes.
- **D4:** Specialised archetypes SHALL preserve parent meaning and intent.
- **D5:** Do not create new archetypes for minor structural preference.
- **D6:** Single inheritance only; no multiple specialisation parents.
- **D7:** Specialised node identifiers use dot-extension notation (e.g., `at0001.1` at depth 1, `at0001.0.1` at depth 2).
- **D8:** Use internal references (`use_node`) to reuse identical structures rather than duplicating.

---

## E. Terminology and Language

- **E1:** Archetypes SHALL be terminology-neutral; bindings are optional but recommended.
- **E2:** Bindings SHALL reference authoritative, internationally recognised code systems.
- **E3:** Bind coded elements to SNOMED CT, LOINC, etc. where possible.
- **E4:** Bindings SHALL reflect semantic equivalence, not approximate mappings.
- **E5:** Translations SHALL NOT alter computable semantics, identifiers, or structure.
- **E6:** Translations SHOULD use authoritative clinical language in the target locale.

---

## F. Paths and Identifiers

- **F1:** All nodes SHALL have stable identifiers (at-codes) unchanged across compatible versions.
- **F2:** Archetype paths SHALL remain stable for AQL query compatibility.
- **F3:** Path design SHOULD support intuitive semantic querying.

---

## G. Versioning

- **G1:** Semantic or data-invalidating changes SHALL trigger a major version increment.
- **G2:** Additive, backward-compatible changes MAY increment minor or patch versions.
- **G3:** Deprecated elements SHOULD be retained and marked, not removed.
- **G4:** Versioning decisions SHALL be based on semantic impact.

---

## H. Governance

- **H1:** Archetypes SHALL undergo multidisciplinary peer review before publication.
- **H2:** Reviewer comments and resolutions SHALL be documented.
- **H3:** Published archetypes SHALL align with CKM editorial standards.
- **H4:** Clinical safety and semantic clarity take precedence over local optimisation.

---

## I. Interoperability and Demographic Modelling

- **I1:** Archetypes SHALL support semantic interoperability across systems and jurisdictions.
- **I2:** Local business rules, workflow logic, and application-specific validation SHALL NOT be encoded.
- **I3:** Favour long-term stability over short-term implementation convenience.
- **I4:** PERSON archetypes SHALL NOT encode role-specific semantics; use ROLE archetypes for role-related data.

---

## J. ADL 1.4 Validity

Validity rules enforced by tooling:

- **VARID:** Valid `archetype_id` per openEHR specification
- **VARCN:** `concept` references a term in ontology
- **VARDF:** Valid `definition` section in cADL
- **VARON:** Valid `ontology` section
- **VARDT:** Root RM type matches archetype ID type
- **VATDF:** Every at-code in `definition` defined in `term_definitions`
- **VACDF:** Every ac-code in `definition` defined in `constraint_definitions`
- **VDFAI:** Slot archetype IDs conform to identifier specification
- **VDFPT:** All paths syntactically valid and structurally correct

---

## K. AOM 1.4 Structural Invariants

- C_ATTRIBUTE: `Rm_attribute_name_valid`, `Existence_set`, `Children_validity`
- C_MULTIPLE_ATTRIBUTE: `Cardinality_valid`, `Members_valid`
- ARCHETYPE_SLOT: `Includes_valid`, `Excludes_valid`, `Validity`
- ARCHETYPE_INTERNAL_REF: `target_path` 

---
