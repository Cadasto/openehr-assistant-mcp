# openEHR Template Design Rules
**URI:** openehr://guidelines/templates/v1/rules  
**Version:** 1.0.0  
**Scope:** Concrete modelling rules and best practices for openEHR templates
**Related:** openehr://guidelines/templates/v1/principles, openehr://guidelines/templates/v1/oet-syntax

---

## A. Composition and Structure

- **Rule A1:** Every template must have a clear clinical scope or use case.
- **Rule A2:** Choose a COMPOSITION archetype that best fits the document type (e.g., `report`, `encounter`, `discharge_summary`).
- **Rule A3:** Minimize the number of archetypes; do not include archetypes that are not required for the specific use case.
- **Rule A4:** Use **slots** in archetypes to include relevant CLUSTER or ENTRY archetypes, ensuring they align with the clinical intent.

## B. Constraint Rules (Narrowing)

- **Rule B1:** Use `max="0"` to exclude elements from the archetype that are not applicable to the use case.
- **Rule B2:** Make optional elements mandatory (`min="1"`) only when they are clinically required for the specific workflow.
- **Rule B3:** When an archetype allows a choice of data types (e.g., DV_TEXT or DV_CODED_TEXT), use the template to select the most appropriate one.
- **Rule B4:** Use **Quantity Constraints** to limit units to those used in the local context and to set clinically sensible min/max ranges.

## C. Naming and Labels

- **Rule C1:** Rename archetype elements in the template only if the original name is ambiguous or clinically inappropriate for the specific context.
- **Rule C2:** Template names (the `<name>` element) should be descriptive and follow local naming conventions (e.g., "ED Nursing Assessment").

## D. Terminology and Annotations

- **Rule D1:** Prefer `DV_CODED_TEXT` over free text whenever possible.
- **Rule D2:** Use the `limitToList="true"` constraint to ensure data quality by forcing users to pick from the provided list.
- **Rule D3:** Add **Annotations** to elements to provide implementation hints, such as FHIR mappings or specific UI instructions.

## E. Metadata and Documentation

- **Rule E1:** Fill in the `<description>` block including purpose, use, and misuse.
- **Rule E2:** Document the lifecycle state (e.g., `Initial`, `Release Candidate`, `Published`).
- **Rule E3:** Include authorship and copyright information as per governance requirements.

## F. Operational Deployment

- **Rule F1:** Always validate the template against the referenced archetypes.
- **Rule F2:** For implementation, prefer the **OPT (Operational Template)** format as it is self-contained and stable for runtime use.
- **Rule F3:** Use **Web Templates (JSON)** for UI development to simplify integration with modern web frameworks.

---

## Revision History

| Version | Date | Summary |
|---------|------------|---------|
| 1.0.0    | 2025-12 | Initial rules set |
