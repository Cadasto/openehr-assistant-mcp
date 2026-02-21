# openEHR Template Design & Review Checklist

**Scope:** A practical checklist for reviewing and validating openEHR templates
**Related:** check also `openehr://guides/templates/principles` and `openehr://guides/templates/rules` guides
**Keywords:** design, review, checklist, quality, QA, consistency, best practice, clinical, terminology, structure, metadata, guideline, validation

---

## 1. Concept and Scope
- [ ] Is the template name descriptive and aligned with the clinical use case?
- [ ] Is the scope of the template clearly defined (e.g., does it cover a single clinical encounter or document)?
- [ ] Are all included archetypes necessary for this use case?
- [ ] Have you chosen the most appropriate COMPOSITION archetype as the root?
- [ ] For multi-template designs: Is composition type (event vs persistent longitudinal/episodic) and data category (e.g. CGEM) explicit and consistent?
- [ ] If the template involves orders or referrals: Are Instruction/Action used only where order/fulfilment tracking is required (Managed Response), not for simple yes/no or date records?

## 2. Archetype Usage and Constraints
- [ ] Are all mandatory elements from the underlying archetypes present?
- [ ] Have unnecessary elements been excluded using `max="0"`?
- [ ] Are occurrences (`min`/`max`) clinically justified?
- [ ] If an archetype slot is used, is the included archetype appropriate for the clinical intent?

## 3. Data Types and Values
- [ ] Are coded elements (`DV_CODED_TEXT`) used instead of free text where appropriate?
- [ ] For coded elements, is `limitToList="true"` set if the list is intended to be exhaustive?
- [ ] Are Quantity constraints (units and ranges) defined for all `DV_QUANTITY` elements?
- [ ] Are the units consistent with local or international standards (e.g., UCUM)?

## 4. Presentation and Labels
- [ ] Have elements been renamed only when the archetype label was ambiguous?
- [ ] Are renamed labels clear and clinician-friendly?
- [ ] Are `hide_on_form` flags applied correctly for elements that shouldn't appear in the UI?

## 5. Metadata and Governance
- [ ] Is the `<description>` section (Purpose, Use, Misuse) fully populated?
- [ ] Is the lifecycle state correctly set (e.g., `Draft`, `Release Candidate`)?
- [ ] Are authorship and copyright details provided?
- [ ] Are there any missing integrity checks for referenced archetypes?

## 6. Technical Validation
- [ ] Does the template validate against the referenced archetypes?
- [ ] Has an **Operational Template (OPT)** been generated and verified?
- [ ] If required, have FHIR or other terminology mappings been added as annotations?

---
