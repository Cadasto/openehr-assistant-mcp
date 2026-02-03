# openEHR Archetype Terminology & Ontology Guide

**Purpose:** Provide guidance on terminology modelling and terminology binding in archetypes, including value sets and external terminology bindings.
**Keywords:** terminology, archetypes, value sets, external bindings, term, binding, ontology, code

---

## Core Principle: Archetypes are Terminology-Neutral

Archetypes define **clinical meaning**, not terminologies.

- Terminology bindings are **optional but recommended**
- Archetypes must remain usable even if bindings are absent
- External code systems must *not* replace clear internal definitions

---

## Internal vs External Terminology

### Internal Terminology (Archetype Terms)

Each coded node (`at-code`) must have in `term_definitions`:
- A clear **text** (short label)
- A precise **description** (full meaning)
- Stable semantic meaning across versions

**Rule:**
> Internal term definitions are authoritative; external codes are references.

### Specialisation Depth and Code Structure (AOM 1.4)

Term codes follow a structured format based on **specialisation depth**:
- Archetypes with no parent have specialisation depth 0 (codes like `at0001`)
- Specialised archetypes use dot-notation to indicate depth and lineage:
  - `at0.0.1` — new term at depth 2, not specialising any parent term
  - `at0001.0.1` — specialises `at0001` from top parent (intervening `.0` shows depth 2)
  - `at0001.1.1` — specialises `at0001.1` from immediate parent, which specialises `at0001`

This systematic coding enables software to infer term relationships across specialisation hierarchies.

**Note:** Constraint codes (ac-codes) do NOT follow these rules and exist in a flat code space.

### Constraint Definitions (ac-codes)

Each constraint code (`ac-code`) must have in `constraint_definitions`:
- A **text** describing the value set intent
- A **description** explaining what values are acceptable

Constraint definitions describe the *meaning* of a value set constraint in human-readable form, independent of any specific terminology. The actual terminology query is defined separately in `constraint_bindings`.

---

### External Terminology Bindings

#### Term Bindings (`term_bindings`)

Map at-codes to external terminology codes. Two types:
- **Global bindings:** at-code → external code, applies everywhere the at-code is used
- **Path-based bindings:** archetype path → external code, for context-specific mappings

Bindings may reference:
- openEHR terminology
- SNOMED CT
- LOINC
- ICD
- Other recognised ontologies

**Rules:**
- Bindings must match the **exact semantic intent** of the node
- Do not bind to overly generic or loosely related concepts
- Do not mix code systems within a single value set unless justified
- openEHR terminology binding should be valid against openEHR terminology (accessible via `openehr://terminology`)

#### Constraint Bindings (`constraint_bindings`)

Map ac-codes to terminology queries or value set URIs. Used to define which external codes satisfy a constraint.

Example: An ac-code for "type of hepatitis" would have constraint bindings specifying the actual terminology query (e.g., "descendants of hepatitis concept in ICD-10").

---

## Value Sets and DV_CODED_TEXT

### Use Coded Value Sets When:
- The domain concept is clinically enumerated
- Comparability or analytics is expected
- International reuse is anticipated

### Avoid Coded Value Sets When:
- The domain is free-text by nature
- Values are unpredictable or narrative

---

## Binding Granularity

- Bind **leaf nodes**, not structural containers
- Avoid binding at multiple hierarchy levels for the same concept
- Do not bind implementation artefacts (e.g., protocol metadata)

---

## Language and Localisation

- **Principle: No Language Primacy.** Archetypes are fully translatable; they can be authored in any language (though English is preferred for international CKM submission).
- **Semantic Preservation:** Translations must preserve the exact clinical meaning and intent, not necessarily literal word-order.
- **Natural Phrasing:** Use the target language's clinical register; depart from awkward source wording to produce natural phrasing.
- **Consistency:** Maintain internal consistency in terminology and grammatical forms (e.g., definite/indefinite forms).
- **Prohibitions:** Do not translate archetype class names (e.g., ACTION, OBSERVATION). Never change node identifiers (`at-codes`, `ac-codes`) or computable structure during translation.
- **Translate Metadata:** Narrative fields (Purpose, Use, Misuse, etc.).
- **Localisation:** Avoid encoding locale-specific semantics or business logic in term text; local presentation belongs to the UI/template layer.

---

## Ontological Alignment

Archetype concepts should align with:
- Real-world clinical ontology structure
- Established domain modelling patterns
- Existing CKM artefacts where applicable

> If no suitable ontology concept exists, document the gap explicitly.

---

## Common Terminology Anti-Patterns

- Binding vague nodes (e.g. “Other”, “Miscellaneous”)
- Reusing codes with different meanings
- Using local or proprietary codes without justification
- Encoding workflow states as coded clinical values

---
