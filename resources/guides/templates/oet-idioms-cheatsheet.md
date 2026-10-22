# OET Idioms Cheat Sheet

**Purpose:** Fast grounding for writing and reviewing openEHR templates (OET)
**Related:** openehr://guides/templates/oet-syntax, openehr://guides/templates/serialization-formats, openehr://guides/templates/opt-structure, openehr://guides/templates/web-template, openehr://guides/templates/rules
**Keywords:** OET, OPT, constraint, syntax, idioms, cheat sheet, minimal, fast, QA, CGEM, event, persistent

---

## Mental Model
- **Template = Aggregation + Narrowing**.
- **Aggregation**: Picking archetypes and placing them in slots.
- **Narrowing**: Tightening constraints (occurrences, values, types) on the underlying archetypes.
- **OET** is for designers; **OPT** is for systems.

> Per-element attribute table + worked snippets: openehr://guides/templates/oet-syntax (Attribute Reference). OET vs OPT vs `.t.json` vs web template: openehr://guides/templates/serialization-formats.

---

## The "Max=0" Exclusion Idiom
**Idiom:** If a field in an archetype is not needed for the use case, set `max="0"`.
- Do not leave unused fields visible if they aren't part of the data set.
- This is the primary way to "shrink" a maximal archetype.

---

## Mandatory Escalation
**Idiom:** If a field is optional in the archetype (`0..1`) but required for your workflow, set `min="1"`.
- Never set `min` higher than the archetype's `max`.
- Never set `min` lower than the archetype's `min`.

---

## Naming: Contextual Overrides
**Idiom:** Override the name of an archetype node ONLY if the generic name is confusing in the template's context.
- Example: Rename `Pulse/Heart beat` to `Heart Rate` if that's the local clinical term.
- Keep the underlying path stable; only the label changes.

---

## Coded Text: The "Limit to List" Guardrail
**Idiom:** Subset an archetype's local value set by dropping codes with `<excludedValues>local::atNNNN</excludedValues>`; `<includedValues>` lists free-text or external codes (e.g. `SNOMED-CT::…`). Keep `limitToList="true"` (the default).
- This prevents free-text "leakage" into coded fields.

---

## Slots: Explicit Inclusion
**Idiom:** Don't leave slots wide open. Explicitly include the archetypes (`<Items>`) you expect.
- If a slot is not used, use a Rule with `max="0"` on the slot path.

---

## Quantity: Unit Hardening
**Idiom:** Constrain `DV_QUANTITY` to the specific units used in your facility.
- Set `minMagnitude` and `maxMagnitude` for clinical safety (e.g., prevent entering 300°C for body temperature).

---

## Cloning for Repetition
**Idiom:** Use `clone="true"` when you need multiple instances of the same archetype structure with different meanings.
- Example: Two "Generic Cluster" instances, one for "Personal History" and one for "Family History".

---

## Annotations as Metadata Bridge
**Idiom:** Use `<annotations>` to store implementation-specific mapping (e.g., `fhir_mapping: Observation.code`).
- Keep clinical logic in the definition; keep technical links in annotations.

---

## The "Flat" Check
**Idiom:** When designing, visualize how the template will look in a flatten form.
- Use `hide_on_form="true"` for structural nodes that don't need a UI label (e.g., intermediate Containers).

---

## Composition Category
**Idiom:** Set each template's `COMPOSITION.category` from its data lifecycle (CGEM): see openehr://guides/templates/cgem-framework.

---

## Defaults for Fixed Context
**Idiom:** When the use case fixes or strongly implies a value, set it as a default (`default="..."` on the `<Rule>`).
- Example: `<Rule path=".../items[at0005]" default="Phadiatop"/>` pre-fills the test name in a single-assay template.
- Defaults appear in the recorded data; archetype *assumed values* do not — don't confuse the two.

---

## Micro Check before OPT Generation
- Is the root COMPOSITION appropriate?
- Are all paths valid against the referenced archetypes?
- Are all `DV_CODED_TEXT` nodes either constrained or explicitly left open?
- Are units UCUM compliant?

---
