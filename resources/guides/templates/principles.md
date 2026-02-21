# openEHR Template Design Principles

**Scope:** Foundational principles for openEHR templates (OET/OPT)
**Keywords:** templates, OET, OPT, design, principles, CGEM, composition, event, persistent

---

## Use Case Specificity

Templates define clinical datasets for **specific use cases** (e.g., "Discharge Summary", "Vital Signs Monitoring").

- Unlike archetypes (maximal), templates are **minimal** — only what's necessary
- Represents the data set for a specific business process

---

## Aggregation and Composition

Templates aggregate multiple archetypes into coherent documents (usually a COMPOSITION).

- Define EHR record structure by nesting archetypes
- Manage slots and inclusions defined in archetypes

---

## The Narrowing Principle

Templates can only **further constrain** archetypes — never relax or add unsupported data points.

- Mandatory archetype elements remain mandatory
- Optional elements can be made mandatory or excluded (`max=0`)
- Value sets can be reduced but not expanded

---

## Design-time vs Run-time

- **OET (Source Template):** For authoring, references archetypes, used in editors
- **OPT (Operational Template):** Flattened, self-contained XML for runtime systems

---

## UI and Presentation

Templates bridge clinical models and user interfaces.

- Rename elements for local context (e.g., "Body mass index" → "BMI")
- UI flags (`hide_on_form`) guide form generation without altering data model

---

## Template Reuse

Templates can embed other templates for modularity and consistency across documents.

---

## Splitting Datasets and Composition Semantics (CGEM)

When one form or use case touches many datapoints, split the dataset across templates so data is **strategic** (patient-centric, reusable) while forms stay **tactical** (good UX). The CGEM framework categorises data to guide this split:

- **Global Background:** True regardless of care context (e.g. allergies, CPR decision). One current version per patient → **longitudinal** persistent composition.
- **Contextual Situation:** Single source of truth for a care journey or episode (e.g. cancer staging, care plan). One current version per journey → **episodic** persistent composition.
- **Event Assessment:** Each submission is a new record (e.g. clinic visit, lab result). Many compositions over time → **event** composition.
- **Managed Response:** Formal order/fulfilment cycle (referral, prescription). Use Instruction and Action archetypes; place in dedicated or encounter template as appropriate.

One form can read/write multiple compositions; Global Background may be read-only (AQL) in a form. Distinguish true managed workflows (Instruction/Action) from simple yes/no or date records—the latter belong in Contextual or Event templates.

---
