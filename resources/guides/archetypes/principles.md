# openEHR Archetype Design Principles

**Scope:** Foundational principles guiding high-quality openEHR archetype modelling  
**Source:** openEHR Archetype Definitions and Principles, community modelling practices
**Keywords:** archetype, design, principles, modelling, foundation, foundational, fundament

---

## Archetype as Formal Domain Content Model

**Definition:** An archetype is a formal, constraint-based expression of a domain concept, grounded in the openEHR Reference Model (RM). It defines *how* data for that concept is structured, constrained, and identified in a computable manner. 

**Implications:**
- Each archetype represents **one coherent clinical or domain concept**.
- It must be modelled independent of specific UIs or workflows.

---

## Two-Level Modelling and Separation of Concerns

openEHR uses a multi-level modelling approach: stable **Reference Model (RM)** vs. expressive **archetypes**.  
Archetypes expose domain semantics while the RM provides stable underlying data structures. This separation enables flexibility and long-term maintainability. 

---

## Terminology Neutrality

Archetypes remain neutral with respect to terminologies; **external code systems** such as SNOMED CT or LOINC can be *bound* but are not mandatory. Terminology bindings should reflect clinical semantics, not implementation convenience. 

---

## Unique Identification and Semantic Paths

Each element in an archetype must have a unique path, enabling unambiguous data reference and semantic querying. Tools and query languages (AQL) rely on these paths to interpret archetyped data. 

---

## Backwards-Compatible Evolution

Changes to an archetype must preserve data validity wherever possible; versioning must follow semantic versioning rules, and incompatible changes should trigger major version increments. 

---

## Reuse and Specialisation

Archetypes should maximize reuse of existing models where possible. Specialisation should be used when localised extensions are *necessary*, and not for convenience. Specialised archetypes should maintain clear lineage to parent artefacts.

---

## Archetypes Model Data, Not Process or Workflow

Archetypes describe what data means, not when or how it is collected. Workflow, UI, and use-case-specific constraints belong in templates or application logic.

---

## Templates Are Not Archetypes

Templates aggregate archetypes for specific use cases. If a model is scenario-specific, it belongs in a template, not an archetype.

---

## Governance, Review, and Clinical Validation

Archetypes require multidisciplinary review, clear documentation, and transparent governance to ensure interoperability.

---

## Clarity and Model Usability

Archetypes should have clear metadata (purpose, natural language definitions, usage context) and be easily understood by clinicians and implementers alike.

---
