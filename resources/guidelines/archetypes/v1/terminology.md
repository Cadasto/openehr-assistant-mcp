# openEHR Archetype Terminology & Ontology Guidelines
**URI:** guidelines://archetypes/v1/terminology  
**Version:** 1.0.0  
**Scope:** Terminology modelling, value sets, and external terminology bindings in archetypes  
**Applies to:** Archetypes (ADL), not templates

---

## 1. Core Principle: Archetypes are Terminology-Neutral

Archetypes define **clinical meaning**, not terminologies.

- Terminology bindings are **optional but recommended**
- Archetypes must remain usable even if bindings are absent
- External code systems must *not* replace clear internal definitions

---

## 2. Internal vs External Terminology

### 2.1 Internal Terminology (Archetype Terms)

Each coded node must have:
- A clear **text**
- A precise **definition**
- Stable semantic meaning across versions

**Rule:**
> Internal term definitions are authoritative; external codes are references.

---

### 2.2 External Terminology Bindings

Bindings may reference:
- SNOMED CT
- LOINC
- ICD
- Other recognised ontologies

**Rules:**
- Bindings must match the **exact semantic intent** of the node
- Do not bind to overly generic or loosely related concepts
- Do not mix code systems within a single value set unless justified

---

## 3. Value Sets and DV_CODED_TEXT

### 3.1 Use Coded Value Sets When:
- The domain concept is clinically enumerated
- Comparability or analytics is expected
- International reuse is anticipated

### 3.2 Avoid Coded Value Sets When:
- The domain is free-text by nature
- Values are unpredictable or narrative

---

## 4. Binding Granularity

- Bind **leaf nodes**, not structural containers
- Avoid binding at multiple hierarchy levels for the same concept
- Do not bind implementation artefacts (e.g., protocol metadata)

---

## 5. Language and Localisation

- Original language should be English
- Translations must preserve semantic intent, not literal wording
- Avoid encoding locale-specific semantics in term text

---

## 6. Ontological Alignment

Archetype concepts should align with:
- Real-world clinical ontology structure
- Established domain modelling patterns
- Existing CKM artefacts where applicable

> If no suitable ontology concept exists, document the gap explicitly.

---

## 7. Common Terminology Anti-Patterns

- Binding vague nodes (e.g. “Other”, “Miscellaneous”)
- Reusing codes with different meanings
- Using local or proprietary codes without justification
- Encoding workflow states as coded clinical values

---

## Revision History

| Version | Date | Notes |
|--------|------|------|
| 1.0.0 | 2025-12 | Initial release |
