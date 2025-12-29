# OET Idioms Cheat Sheet
**URI:** openehr://guidelines/templates/v1/oet-idioms-cheatsheet  
**Version:** 1.0.0  
**Purpose:** Fast grounding for writing and reviewing openEHR templates (OET)
**Related:** openehr://guidelines/templates/v1/oet-syntax, openehr://guidelines/templates/v1/rules

---

## 0) Mental Model
- **Template = Aggregation + Narrowing**.
- **Aggregation**: Picking archetypes and placing them in slots.
- **Narrowing**: Tightening constraints (occurrences, values, types) on the underlying archetypes.
- **OET** is for designers; **OPT** is for systems.

---

## 1) The "Max=0" Exclusion Idiom
**Idiom:** If a field in an archetype is not needed for the use case, set `max="0"`.
- Do not leave unused fields visible if they aren't part of the data set.
- This is the primary way to "shrink" a maximal archetype.

---

## 2) Mandatory Escalation
**Idiom:** If a field is optional in the archetype (`0..1`) but required for your workflow, set `min="1"`.
- Never set `min` higher than the archetype's `max`.
- Never set `min` lower than the archetype's `min`.

---

## 3) Naming: Contextual Overrides
**Idiom:** Override the name of an archetype node ONLY if the generic name is confusing in the template's context.
- Example: Rename `Pulse/Heart beat` to `Heart Rate` if that's the local clinical term.
- Keep the underlying path stable; only the label changes.

---

## 4) Coded Text: The "Limit to List" Guardrail
**Idiom:** For `DV_CODED_TEXT`, define a list of `<includedValues>` and always set `limitToList="true"`.
- This prevents free-text "leakage" into coded fields.
- Use this to subset a large archetype value set to a local one.

---

## 5) Slots: Explicit Inclusion
**Idiom:** Don't leave slots wide open. Explicitly include the archetypes (`<Items>`) you expect.
- If a slot is not used, use a Rule with `max="0"` on the slot path.

---

## 6) Quantity: Unit Hardening
**Idiom:** Constrain `DV_QUANTITY` to the specific units used in your facility.
- Set `minMagnitude` and `maxMagnitude` for clinical safety (e.g., prevent entering 300°C for body temperature).

---

## 7) Cloning for Repetition
**Idiom:** Use `clone="true"` when you need multiple instances of the same archetype structure with different meanings.
- Example: Two "Generic Cluster" instances, one for "Personal History" and one for "Family History".

---

## 8) Annotations as Metadata Bridge
**Idiom:** Use `<annotations>` to store implementation-specific mapping (e.g., `fhir_mapping: Observation.code`).
- Keep clinical logic in the definition; keep technical links in annotations.

---

## 9) The "Flat" Check
**Idiom:** When designing, visualize how the template will look in a flat form.
- Use `hide_on_form="true"` for structural nodes that don't need a UI label (e.g., intermediate Containers).

---

## 10) Micro Check before OPT Generation
- Is the root COMPOSITION appropriate?
- Are all paths valid against the referenced archetypes?
- Are all `DV_CODED_TEXT` nodes either constrained or explicitly left open?
- Are units UCUM compliant?

---

| Version | Date    | Notes           |
| ------- | ------- | --------------- |
| 1.0.0   | 2025-12 | Initial release |
