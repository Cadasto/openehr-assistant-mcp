# openEHR Template Design Principles
**URI:** openehr://guides/templates/principles  
**Version:** 1.0.0  
**Scope:** Foundational principles for designing high-quality openEHR templates (OET/OPT)  
**Source:** openEHR Template Specifications, CKM modelling practices

---

## 1. Use Case Specificity

**Definition:** A template is a clinical dataset definition designed for a **particular use case, scenario, or workflow** (e.g., "Discharge Summary", "Vital Signs Monitoring"). 

**Implications:**
- Unlike archetypes (which are maximal), templates are **minimal**—they should include only what is necessary for the specific context.
- A template represents the "data set" for a specific business process.

---

## 2. Aggregation and Composition

**Definition:** Templates serve as the assembly layer, aggregating multiple archetypes into a coherent document or data structure (usually a COMPOSITION).

**Implications:**
- Templates define the structure of the EHR record by nesting archetypes.
- They manage the "slots" and "inclusions" defined in archetypes.

---

## 3. The "Narrowing" Principle

**Definition:** Templates can only **further constrain** (narrow) the rules defined in the underlying archetypes. They cannot relax constraints or add data points that aren't supported by the archetype's structure.

**Implications:**
- Mandatory elements in an archetype must remain mandatory.
- Optional elements can be made mandatory or excluded (max=0).
- Value sets (terminologies) can be reduced but not expanded beyond the archetype's definition.

---

## 4. Separation of Design-time and Run-time

**Definition:** openEHR distinguishes between the **Source Template (OET)** used for authoring and the **Operational Template (OPT)** used for technical implementation.

**Implications:**
- **OET:** Focuses on clinical modelling, references archetypes, and is used in editors (e.g., Ocean Template Designer).
- **OPT:** A flattened, self-contained XML version containing all constraints and archetype definitions, optimized for software systems.

---

## 5. UI and Presentation Awareness

**Definition:** Templates often bridge the gap between clinical models and user interfaces, providing hints for how data should be displayed or captured.

**Implications:**
- Templates can rename elements to use local or context-specific labels (e.g., renaming "Body mass index" to "BMI").
- UI-specific flags (like `hide_on_form`) help guide form generation without altering the underlying data model.

---

## 6. Template Reuse and Embedding

**Definition:** Templates can be designed to be modular and reusable by embedding them within other templates.

**Implications:**
- Encourages consistency across different clinical documents (e.g., reusing a "Patient Header" template).

---

## Revision History

| Version | Date | Summary |
|---------|------------|---------|
| 1.0.0    | 2025-12 | Initial release |
