# openEHR Archetype Design Rules

**Scope:** Concrete guidance and rules for modelling openEHR archetypes
**Related:** check also `openehr://guides/archetypes/structural-constraints` and `openehr://guides/archetypes/terminology`
**Keywords:** archetype, design, rules, modelling, guidance, ADL, structure

---

## A. Concept and Scope

- **Rule A1:** An archetype SHALL represent exactly one coherent clinical or domain concept.
- **Rule A2:** An archetype SHALL NOT combine unrelated or weakly related concepts (e.g., observations and orders).
- **Rule A3:** The scope SHALL be broad enough for international reuse but no broader than the core semantic concept.
- **Rule A4:** If a concept is use-case, document, or workflow specific, it SHALL be modelled as a template, not an archetype.

---

## B. Metadata Conventions

- **Rule B1:** Use standard naming conventions for archetype IDs:  
  `openEHR-<DOMAIN>-<TYPE>.<name>.v<N>`
- **Rule B2:** Provide a clear, clinician-friendly *purpose* description.

---

## C. Structural Modelling Rules

- **Rule C1:** RM structures SHALL be used as intended; archetypes MUST NOT compensate for missing application features.
- **Rule C2:** Cardinalities (min/max) SHALL be justified by clinical reality, not UI convenience.
- **Rule C3:** Optionality SHOULD be maximised in archetypes; restriction belongs in templates.
- **Rule C4:** Leaf nodes SHALL represent atomic data values using appropriate RM data types (e.g., DV_QUANTITY, DV_CODED_TEXT).
- **Rule C5:** Repeating structures SHALL be modelled using RM repetition mechanisms, not duplicated nodes.
- **Rule C6:** Clusters SHALL only be used for logically inseparable sub-concepts.

---

## D. Reuse, Slots, and Specialisation

- **Rule D1:** Existing published archetypes SHALL be reused wherever semantically appropriate.
- **Rule D2:** Slots SHALL be constrained explicitly to avoid uncontrolled archetype inclusion.
- **Rule D3:** Specialisation SHALL only be used for true semantic subtypes of the parent concept.
- **Rule D4:** A specialised archetype SHALL preserve the meaning and intent of its parent.
- **Rule D5:** Creating a new archetype for minor structural preference is prohibited.
- **Rule D6:** Only single inheritance is allowed; an archetype cannot have multiple specialisation parents.
- **Rule D7:** Specialised node identifiers use dot-extension notation based on specialisation depth (e.g., `at0001.1` at depth 1, `at0001.0.1` at depth 2 specialising `at0001`).
- **Rule D8:** Internal references (`use_node` / ARCHETYPE_INTERNAL_REF) SHALL be used to reuse identical constraint structures within the same archetype rather than duplicating them.

---

## E. Terminology and Language

- **Rule E1:** Archetypes SHALL be terminology-neutral; terminology bindings are optional but recommended.
- **Rule E2:** When bindings are provided, they SHALL reference authoritative, internationally recognised code systems.
- **Rule E3:** Bind coded elements to internationally recognised code systems (SNOMED CT, LOINC) whenever possible. 
- **Rule E4:** Bindings SHALL reflect semantic equivalence, not approximate or convenience mappings.
- **Rule E5:** Textual descriptions and translations SHALL NOT alter computable semantics, node identifiers, or structure.
- **Rule E6:** Translations SHOULD use authoritative clinical language in the target locale.

---

## F. Paths, Identifiers, and Queryability

- **Rule F1:** All nodes SHALL have stable identifiers (at-codes) that MUST NOT change across compatible versions.
- **Rule F2:** Archetype paths SHALL remain stable to ensure AQL query compatibility.
- **Rule F3:** Path design SHOULD support intuitive semantic querying and downstream analytics.

---

## G. Versioning and Evolution

- **Rule G1:** Changes that alter semantic meaning or invalidate existing data SHALL trigger a major version increment.
- **Rule G2:** Additive, backward-compatible changes MAY increment minor or patch versions.
- **Rule G3:** Deprecated elements SHOULD be retained and clearly marked rather than removed.
- **Rule G4:** Versioning decisions SHALL be based on semantic impact, not modelling effort.

---

## H. Governance and Review

- **Rule H1:** Archetypes SHALL undergo multidisciplinary peer review before publication.
- **Rule H2:** All reviewer comments and resolutions SHALL be documented in revision metadata.
- **Rule H3:** Published archetypes SHALL align with existing governance and CKM editorial standards.
- **Rule H4:** Clinical safety and semantic clarity SHALL take precedence over local optimisation.

---

## I. Interoperability and Sustainability

- **Rule I1:** Archetypes SHALL be designed to support semantic interoperability across systems and jurisdictions.
- **Rule I2:** Local business rules, workflow logic, and validation specific to an application SHALL NOT be encoded in archetypes.
- **Rule I3:** Modelling decisions SHOULD favour long-term stability over short-term implementation convenience.

---

## J. ADL 1.4 Validity (Formal)

The following validity rules are defined in the ADL 1.4 specification and SHOULD be enforced by tooling:

- **VARID:** Archetype identifier validity — the archetype MUST have a valid `archetype_id` conforming to openEHR identifier specification.
- **VARCN:** Archetype concept validity — the `concept` section MUST reference a term that exists in the ontology.
- **VARDF:** Archetype definition validity — the archetype MUST have a valid `definition` section in cADL.
- **VARON:** Archetype ontology validity — the archetype MUST have a valid `ontology` section.
- **VARDT:** Archetype definition typename validity — the root RM type in `definition` MUST match the type in the archetype ID.
- **VATDF:** Archetype term validity — every at-code used in `definition` MUST be defined in `term_definitions`.
- **VACDF:** Constraint code validity — every ac-code used in `definition` MUST be defined in `constraint_definitions`.
- **VDFAI:** Archetype identifier validity in definition — archetype IDs in slots MUST conform to openEHR identifier specification.
- **VDFPT:** Path validity in definition — all paths in `definition` MUST be syntactically valid and structurally correct.

---

## K. AOM 1.4 Structural Invariants

The following invariants from the AOM 1.4 specification apply to constraint objects:
- C_ATTRIBUTE: `Rm_attribute_name_valid`, `Existence_set`, `Children_validity`
- C_MULTIPLE_ATTRIBUTE: `Cardinality_valid`, `Members_valid`
- ARCHETYPE_SLOT: `Includes_valid`: `Excludes_valid`: `Validity`
- ARCHETYPE_INTERNAL_REF: `target_path` 

---
