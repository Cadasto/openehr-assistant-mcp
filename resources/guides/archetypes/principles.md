# openEHR Archetype Design Principles
**URI:** openehr://guides/archetypes/principles  
**Version:** 1.0.0  
**Scope:** Foundational principles guiding high-quality openEHR archetype modelling  
**Source:** openEHR Archetype Definitions and Principles, community modelling practices

---

## 1. Archetype as Formal Domain Content Model

**Definition:** An archetype is a formal, constraint-based expression of a domain concept, grounded in the openEHR Reference Model (RM). It defines *how* data for that concept is structured, constrained, and identified in a computable manner. 

**Implications:**
- Each archetype represents **one coherent clinical or domain concept**.
- It must be modelled independent of specific UIs or workflows.

---

## 2. Two-Level Modelling and Separation of Concerns

openEHR uses a multi-level modelling approach: stable **Reference Model (RM)** vs. expressive **archetypes**.  
Archetypes expose domain semantics while the RM provides stable underlying data structures. This separation enables flexibility and long-term maintainability. 

---

## 3. Terminology Neutrality

Archetypes remain neutral with respect to terminologies; **external code systems** such as SNOMED CT or LOINC can be *bound* but are not mandatory. Terminology bindings should reflect clinical semantics, not implementation convenience. 

---

## 4. Unique Identification and Semantic Paths

Each element in an archetype must have a unique path, enabling unambiguous data reference and semantic querying. Tools and query languages (AQL) rely on these paths to interpret archetyped data. 

---

## 5. Backwards-Compatible Evolution

Changes to an archetype must preserve data validity wherever possible; versioning must follow semantic versioning rules, and incompatible changes should trigger major version increments. 

---

## 6. Reuse and Specialisation

Archetypes should maximize reuse of existing models where possible. Specialisation should be used when localised extensions are *necessary*, and not for convenience. Specialised archetypes should maintain clear lineage to parent artefacts.

---

## 7. Clarity and Model Usability

Archetypes should have clear metadata (purpose, natural language definitions, usage context) and be easily understood by clinicians and implementers alike.

---

## Revision History

| Version | Date | Summary |
|--------|------|---------|
| 1.0.0 | 2025-12 | Initial release |
