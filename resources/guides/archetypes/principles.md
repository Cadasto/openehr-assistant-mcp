# openEHR Archetype Design Principles

**Scope:** Foundational principles for openEHR archetype modelling, community modelling practices
**Keywords:** archetype, design, principles, modelling, foundation

---

## Archetype as Formal Domain Content Model

An archetype is a formal, constraint-based expression of a domain concept grounded in the openEHR Reference Model (RM). It defines how data is structured, constrained, and identified.

- Each archetype represents **one coherent clinical or domain concept**
- Modelled independent of UIs or workflows

---

## Two-Level Modelling and Separation of Concerns

openEHR separates stable **Reference Model (RM)** from expressive **archetypes**.
Archetypes expose domain semantics; the RM provides stable data structures. This enables independent evolution of content and systems.

---

## Terminology Neutrality

Archetypes are terminology-neutral; external code systems (SNOMED CT, LOINC) can be bound but are not mandatory. Bindings reflect clinical semantics, not implementation convenience.

---

## Unique Identification, Paths, and Locatability

Each archetype node has a **stable identifier** (at-code) and a **unique semantic path**, enabling AQL querying and unambiguous data access. Paths are a public API; stability across compatible versions is mandatory.

---

## Backwards-Compatible Evolution

Changes must preserve data validity where possible. Incompatible changes require major version increments.

---

## Archetype Reuse, Slots, and Composition

Reuse existing archetypes wherever possible. **Slots** enable controlled inclusion of other archetypes and should be **constrained explicitly** (avoid wildcards). Specialise only for true semantic subtypes, not convenience; maintain clear lineage to parent artefacts.

---

## Archetypes Model Data, Not Process

Archetypes describe what data means, not when or how it is collected. Workflow and UI constraints belong in templates or applications.

---

## Instruction, Action, and Observation

Match the archetype’s root to the concept: **order/request with fulfilment tracking** → use or reuse **Instruction** (and **Action**) archetypes; **one-off assessment or simple record** → use **Observation** or other entry types. Do not combine orders and observations in a single archetype.

---

## Templates Are Not Archetypes

Templates aggregate archetypes for specific use cases. If a model is scenario-specific, it belongs in a template, not an archetype.

---

## Governance and Clinical Validation

Archetypes require multidisciplinary review, clear documentation, and transparent governance to ensure interoperability.

---

## Clarity and Usability

Archetypes must have clear metadata (purpose, definitions, usage) understandable by clinicians and implementers.

---
