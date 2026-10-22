# openEHR Archetype Design Rules

**Scope:** Normative rules for modelling openEHR archetypes
**Related:** `openehr://guides/archetypes/structural-constraints`, `openehr://guides/archetypes/terminology`, `openehr://guides/specs/am-Overview`, `openehr://guides/specs/am2-AOM2`, `openehr://guides/specs/am2-ADL2`, `openehr://guides/specs/am-Identification`
**Keywords:** ADL, AOM, rules, modeling, lint, checks, validation

---

## A. Concept and Scope

- **A1:** An archetype SHALL represent exactly one coherent clinical or domain concept.
- **A2:** An archetype SHALL NOT combine unrelated concepts (e.g., observations and orders).
- **A3:** Scope SHALL be broad enough for international reuse but no broader than the core concept.
- **A4:** Use-case or workflow-specific models SHALL be templates, not archetypes.

---

## B. Metadata

- **B1:** Archetype IDs SHALL follow the standard pattern: `<rm_publisher>-<rm_closure>-<rm_class>.<concept>.v<version>`, e.g. `openEHR-EHR-OBSERVATION.blood_pressure.v2`. In ADL 1.4 source the identifier carries the **major version only** (`.v1`); the full 3-part semver form (`.v1.0.0`) is the *physical* HRID defined in the AM Identification spec, used in ADL 2 and in CKM revision metadata.
- **B2:** Provide a clear, clinician-friendly purpose description.

---

## C. Structural Modelling

- **C1:** RM structures SHALL be used as intended; do not compensate for missing application features.
- **C2:** Cardinalities SHALL be justified by clinical reality, not UI convenience. ADL 1.4 has **no default cardinality** (an attribute without one is read as single-valued), so state it on container attributes; prefer `1..*`, since an empty container has no meaning. **Exception:** `ITEM_TREE.items {0..*}` is idiomatic when a contained ELEMENT is mandatory (e.g. `ecg_result.v1`) — flag only genuinely empty/all-optional containers.
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
- **D9 — Prose↔slot consistency:** flag `use`/`misuse`/`comment` text naming archetype ids that no slot `include` regex admits (WARNING if a slot actually excludes it).

---

## E. Terminology and Language

- **E1:** Archetypes SHALL be terminology-neutral; bindings are optional but recommended.
- **E2:** Bindings SHALL reference authoritative, internationally recognised code systems.
- **E3:** Bind coded elements to SNOMED CT, LOINC, etc. where possible.
- **E4:** Bindings SHALL reflect semantic equivalence, not approximate mappings.
- **E5:** Translations SHALL NOT alter computable semantics, identifiers, or structure.
- **E6:** Translations SHOULD use authoritative clinical language in the target locale.
- **E7 — Translation accuracy:** flag `*(en)` placeholder stubs in non-English `term_definitions` and copy/paste-wrong labels (a rubric defect even when E5 semantics hold).

---

## F. Paths and Identifiers

- **F1:** All LOCATABLE nodes (not DV_* data-type nodes) SHALL have stable identifiers (at-codes) unchanged across compatible versions.
- **F2:** Archetype paths SHALL remain stable for AQL query compatibility.
- **F3:** Path design SHOULD support intuitive semantic querying.

---

## G. Versioning

- **G1:** Semantic or data-invalidating changes SHALL trigger a major version increment.
- **G2:** Additive, backward-compatible changes to the formal definition (new nodes, widened constraints, new bindings) SHALL increment at least the minor version; changes to informal parts only (metadata, translations, term wording) SHALL increment at least the patch version.
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

## J. Archetype Validity Codes

Normative **ADL 1.4 validity rules** (ADL 1.4 "Validity Rules" section; `VCOC`
under occurrences, `VUNT` under internal references); the spec recommends that
tools report errors by these codes. AOM2 keeps `VARID`, `VARCN`, `VARDT`,
`VATDF`, `VACDF`, `VDFAI`, `VUNT`; `VARDF`, `VARON`, `VDFPT`, `VCOC` are
ADL 1.4 only (AOM2's analogue of `VCOC` is `VACMCU`).

- **VARID:** Valid `archetype_id` per openEHR specification
- **VARCN:** `concept` references a term in ontology
- **VARDF:** Valid `definition` section in cADL
- **VARON:** Valid `ontology` section
- **VARDT:** Root RM type matches archetype ID type
- **VATDF:** Every at-code in `definition` defined in `term_definitions`
- **VACDF:** Every ac-code in `definition` defined in `constraint_definitions`
- **VDFAI:** Slot archetype IDs conform to identifier specification
- **VDFPT:** All paths syntactically valid and structurally correct
- **VCOC:** Sum of sibling occurrences ranges fits inside container cardinality
- **VUNT:** `use_node` RM type is same as, or supertype of, the target node's type

---

## K. AOM 1.4 Structural Invariants

- C_ATTRIBUTE: `Rm_attribute_name_valid`, `Existence_set`, `Children_validity`
- C_SINGLE_ATTRIBUTE: `Members_valid` (each alternative's occurrences upper ≤ 1)
- C_MULTIPLE_ATTRIBUTE: none (`cardinality` is a mandatory attribute)
- ARCHETYPE_SLOT: `Includes_valid`, `Excludes_valid`, `Validity`
- ARCHETYPE_INTERNAL_REF: `Consistency`, `Target_path_valid`

---
