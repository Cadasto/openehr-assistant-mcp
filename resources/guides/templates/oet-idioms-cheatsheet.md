# OET Idioms Cheat Sheet

**Purpose:** Fast grounding for writing and reviewing openEHR templates (OET)
**Related:** check also `openehr://guides/templates/oet-syntax` and `openehr://guides/templates/rules` guides
**Keywords:** OET, OPT, constraint, syntax, idioms, cheat sheet, minimal, fast, QA, CGEM, event, persistent

---

## Mental Model
- **Template = Aggregation + Narrowing**.
- **Aggregation**: Picking archetypes and placing them in slots.
- **Narrowing**: Tightening constraints (occurrences, values, types) on the underlying archetypes.
- **OET** is for designers; **OPT** is for systems.

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
**Idiom:** For `DV_CODED_TEXT`, define a list of `<includedValues>` and always set `limitToList="true"`.
- This prevents free-text "leakage" into coded fields.
- Use this to subset a large archetype value set to a local one.

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

## Composition Type: Event vs Persistent
**Idiom:** Choose composition semantics when designing templates. 
- **Event:** many submissions over time, each a new composition (e.g. lab results, clinic visits). 
- **Persistent:** single “current version”; longitudinal = lifetime (e.g. allergies), episodic = per journey (e.g. cancer care plan). Align with CGEM (Global Background → longitudinal; Contextual Situation → episodic; Event Assessment → event).

---

## Micro Check before OPT Generation
- Is the root COMPOSITION appropriate?
- Are all paths valid against the referenced archetypes?
- Are all `DV_CODED_TEXT` nodes either constrained or explicitly left open?
- Are units UCUM compliant?

---
