# openEHR Archetype Terminology & Ontology Guide

**Purpose:** Terminology modelling and binding guidance for archetypes  
**Keywords:** terminology, value sets, bindings, ontology, term, at-code, ac-code, code

---

## Core Principle

Archetypes define **clinical meaning**, not terminologies.

- Bindings are optional but recommended
- Archetypes must work without bindings
- External codes do not replace internal definitions

---

## Internal Terminology (at-codes)

Each `at-code` in `term_definitions` requires:

- **text:** short label
- **description:** full meaning
- Stable semantics across versions

> Internal definitions are authoritative; external codes are references.

### Specialisation Depth (AOM 1.4)

Term codes use dot-notation based on specialisation depth:

- Depth 0: `at0001`
- Depth 2 examples:
  - `at0.0.1` — new term, not specialising parent
  - `at0001.0.1` — specialises `at0001` from top parent
  - `at0001.1.1` — specialises `at0001.1` from immediate parent

**Note:** ac-codes exist in a flat code space (no dot-notation).

---

## Constraint Definitions (ac-codes)

Each `ac-code` in `constraint_definitions` requires:

- **text:** value set intent
- **description:** acceptable values

Describes value set meaning independent of terminology; actual queries defined in `constraint_bindings`.

---

## External Bindings

### Term Bindings (`term_bindings`)

Map at-codes to external codes:

- **Global:** at-code → external code (applies everywhere)
- **Path-based:** archetype path → external code (context-specific)

Supported systems: openEHR terminology, SNOMED CT, LOINC, ICD, etc.

**Rules:**

- Match exact semantic intent
- Avoid generic or loosely related bindings
- Don't mix code systems in one value set without justification

### Constraint Bindings (`constraint_bindings`)

Map ac-codes to terminology queries or value set URIs defining which external codes satisfy a constraint.

---

## Value Sets (DV_CODED_TEXT)

**Use when:** clinically enumerated, analytics expected, international reuse anticipated

**Avoid when:** free-text by nature, unpredictable/narrative values

---

## Binding Granularity

- Bind leaf nodes, not structural containers
- Avoid binding multiple hierarchy levels for same concept
- Don't bind implementation artefacts

---

## Language and Localisation

See `openehr://guides/archetypes/language-standards` for comprehensive guidance on original language requirements, English conventions, translation philosophy, and per-language frameworks.

**Key principles:**

- **Original language:** English (en) mandatory for international CKM; prevents semantic drift from translation chains
- **No language primacy:** translated archetypes use target language's natural clinical register
- **Preserve meaning:** translations preserve clinical intent, not literal wording
- **Consistency:** internal terminology (at-codes) stable; external bindings (SNOMED CT, LOINC) handle spelling/terminology variants
- **Prohibitions:** don't translate class names (ACTION, OBSERVATION); never change identifiers, paths, or structure
- **Translate metadata:** Purpose, Use, Misuse fields translated to reflect target-language clinical practice
- **Localisation:** avoid locale-specific semantics, healthcare system assumptions, and regional references in archetype definitions

---

## Ontological Alignment

Align with real-world clinical ontology, established patterns, and existing CKM artefacts. Document gaps explicitly.

---

## Anti-Patterns

- Binding vague nodes ("Other", "Miscellaneous")
- Reusing codes with different meanings
- Local/proprietary codes without justification
- Workflow states as coded clinical values

---
