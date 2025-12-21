# openEHR Archetype Design Rules
**URI:** openehr://guidelines/archetypes/v1/rules  
**Version:** 1.0.0  
**Scope:** Concrete guidance and rules for modelling openEHR archetypes
**Related:** see also openehr://guidelines/archetypes/v1/structural-constraints and openehr://guidelines/archetypes/v1/terminology

---

## A. Concept and Scope

- **Rule A1:** Define the *single* clinical concept the archetype represents.
- **Rule A2:** An archetype must not combine unrelated clinical concepts (e.g., “blood pressure” and “medication orders”).

---

## B. Metadata Conventions

- **Rule B1:** Use standard naming conventions for archetype IDs:  
  `openEHR-<DOMAIN>-<TYPE>.<name>.v<N>`
- **Rule B2:** Provide a clear, clinician-friendly *purpose* description.

---

## C. Structural Rules

- **Rule C1:** Restrict the use of slots; use clusters only for logically grouped sub-concepts.
- **Rule C2:** Ensure minimum and maximum cardinality is justified by clinical reasoning.
- **Rule C3:** Leaf nodes should represent *atomic data points* (e.g., DV_QUANTITY, DV_CODED_TEXT).

---

## D. Terminology and Bindings

- **Rule D1:** Bind coded elements to internationally recognised code systems (SNOMED CT, LOINC) whenever possible. Bindings must match exact intent.
- **Rule D2:** All bindings should reference *actual codes* and not approximate text strings.

---

## E. Versioning

- **Rule E1:** When structural changes affect interpretation or data layout, bump major version.
- **Rule E2:** Minor edits that do not affect semantics should increment minor version.

---

## F. Review Eligibility

- **Rule F1:** Archetype must pass peer review via governance workflow (e.g., CKM draft/review process) before publishing.
- **Rule F2:** All resolved reviewer issues should be summarized in revision metadata.

---

## G. Interoperability

- **Rule G1:** Ensure archetype elements support **semantic interoperability** through consistent paths and bindings.
- **Rule G2:** Avoid localised business logic encoded in archetype constraints.

---

## Revision History

| Version | Date | Summary |
|---------|------------|---------|
| 1.0.0    | 2025-12 | Initial rules set |
