# openEHR Archetype ADL & Syntax Guide

**Scope:** Correct and idiomatic use of ADL and the Archetype Object Model (AOM)
**Related:** openehr://guides/archetypes/adl-idioms-cheatsheet, openehr://guides/archetypes/structural-constraints, openehr://guides/archetypes/rules, openehr://guides/specs/am-Overview, openehr://guides/specs/am2-ADL2, openehr://guides/specs/am-ADL1.4, openehr://guides/specs/am2-AOM2
**Keywords:** ADL, archetype, syntax, guide, reference, formal, constraint, AM, AOM, terms, structure, path, definition, lint

---

## Purpose of ADL

ADL is a **formal constraint language** expressing:
- constraints on the openEHR Reference Model (RM)
- clinical semantics via archetype terms
- computable structure with stable paths

ADL is not a programming language or data serialization format.

---

## Archetype Model Fundamentals

An archetype constrains RM classes, attributes, occurrences/cardinalities, and data types (DV_*).

> Every constraint must be valid against the AOM and underlying RM.

### AOM Constraint Types (AOM 1.4)

- **C_OBJECT** — abstract; has `rm_type_name`, `occurrences`, `node_id`
- **C_COMPLEX_OBJECT** — complex RM type with attributes
- **C_PRIMITIVE_OBJECT** — primitive types (String, Integer, Date, etc.)
- **C_ATTRIBUTE** — attribute constraint; has `rm_attribute_name`, `existence`
    - **C_SINGLE_ATTRIBUTE** — single-valued (one child)
    - **C_MULTIPLE_ATTRIBUTE** — container with `cardinality`
- **ARCHETYPE_SLOT** — placeholder via `include`/`exclude` assertions
- **ARCHETYPE_INTERNAL_REF** — reuse constraint from elsewhere in same archetype via `target_path` (ADL: `use_node`)
- **CONSTRAINT_REF** — reference to ac-code for external terminology

---

## Archetype Sections

An ADL 1.4 archetype has these top-level sections in this order:

### `archetype` (header)
- `adl_version` declaration and archetype identifier
- ID must follow naming conventions; version reflects semantic compatibility
- May carry a `controlled` / `uncontrolled` flag after the version; `controlled` archetypes should include a `revision_history` section

### `specialise` (optional)
- Identifier of the single specialisation parent (no multiple inheritance; both spellings `specialise`/`specialize` are valid)
- Legacy ADL 1.4 practice derives the child ID by appending a hyphenated segment to the parent concept (e.g. `openEHR-EHR-OBSERVATION.haematology-cbc.v1`); in the current Identification spec the hyphen no longer carries specialisation semantics

### `concept`
- Single at-code pointing at the root concept term in `ontology` / `term_definitions`

### `language`
- `original_language` (code_phrase) and optional `translations` block

### `description`
- Archetype metadata: purpose, use, misuse, keywords, authorship, other details, lifecycle state

### `definition` (cADL)
- Formal constraint tree
- Root node matches declared RM type
- All constraints follow RM attribute semantics

### `invariant` (optional)
- Top-level archetype section between `definition` and `ontology`, containing first-order predicate logic assertions (cross-node relationships, formulae, conditional constraints):

```adl
invariant
    validity: /speed[at0002]/kilometres/magnitude = /speed[at0004]/miles/magnitude * 1.6
```

> In ADL 1.4, invariants can only be defined in this top-level section (in AOM terms they attach to a `C_COMPLEX_OBJECT`, serialized only at archetype level). ADL 2 replaces `invariant` with the `rules` section.

### `ontology` / terminology
- `term_definitions`: at-codes with text and description (per language)
- `constraint_definitions`: ac-codes explaining value set meaning
- `term_bindings`: at-codes → external terminology codes
- `constraint_bindings`: ac-codes → terminology queries

### `revision_history` (optional)
- Monotonically-growing audit of revisions, placed at the end of the archetype
- Expected when the header carries the `controlled` flag; may be omitted for `uncontrolled` archetypes

---

## Constraint Syntax

### RM Attributes
- Use RM attribute names exactly as defined
- Do not invent or alias attributes

**Incorrect:**
```adl
value matches { DV_TEXT }
```
**Correct:**
```adl
value matches { DV_TEXT matches {*} }
```

### Existence vs Occurrences vs Cardinality

- **existence** — attributes; allowed values `{0}`/`{0..0}` (prohibited), `{0..1}` (optional), `{1}`/`{1..1}` (mandatory); default when unstated is `{1..1}`
- **occurrences** — object nodes; how many times object may appear; default when unstated is `{1..1}`
- **cardinality** — container attributes; how many children allowed

> Never confuse occurrences with cardinality. They must be mutually consistent: the sum of sibling occurrences ranges must fit inside the container cardinality (validity rule VCOC).

### Internal References (use_node)

Reuse constraints from elsewhere in same archetype:
```adl
use_node CLUSTER[at0010] /items[at0005]
```

- The stated RM type must be the same as, or a supertype of, the target node's type (VUNT)
- An `occurrences` constraint on the reference overrides the target node's occurrences; if absent, the target's occurrences apply

### Leaf Nodes
Constrain RM type and optionally internal attributes (units, magnitude, code). Avoid unconstrained leaves.

---

## Paths and Identifiers

- Paths derived from constraint tree.
- Paths are a public API: must be stable across versions. Path stability takes precedence over aesthetic structure; path-breaking changes require a major version bump.

---

## Slots

Slots (allow_archetype, include, exclude) must:
- Clearly state intent
- Constrain whenever possible
- Reference valid archetype identifiers
- Avoid unconstrained wildcards

---

## ADL Style

- Human-readable, consistently indented
- Group related constraints
- Avoid deep nesting without semantic justification

---

## Versioning

- Syntax-only changes → patch
- Constraint changes → minor/major
- Structural refactoring → major

---

## Anti-Patterns

- Invalid RM attribute names
- Missing term definitions
- Unconstrained DV_* everywhere
- `matches {*}` as default
- Template logic in archetypes

---

## Validation

All archetypes must:
- Parse successfully
- Validate against RM

> Syntax correctness is prerequisite for modelling quality.

---
