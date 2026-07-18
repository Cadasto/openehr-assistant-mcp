# openEHR Template Design Principles

**Scope:** Foundational principles for openEHR templates (OET/OPT)
**Related:** openehr://guides/templates/rules, openehr://guides/templates/cgem-framework, openehr://guides/templates/oet-idioms-cheatsheet, openehr://guides/templates/checklist, openehr://guides/specs/am-Overview, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am2-AOM2, openehr://guides/specs/am-Identification
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
- Constraints may also be tightened on RM attributes not yet constrained by the referenced archetypes (still "narrowing")

Per the Archetype Technology Overview, a template's job is: **composition** (fill slots with archetypes), **element choice** (removal / mandation / leave optional), **narrowing** of remaining constraints, and **setting defaults**.

---

## Defaults vs Assumed Values

Templates may set **default values** where the use case fixes or strongly implies a value (e.g. patient position "lying" in a hospital bed template).

- Default values are a *local* (template-level) concern and **appear in the data**
- Archetype-level *assumed values* are semantic fallbacks for omitted optional items and **do not appear in the data**

---

## Design-time vs Run-time

- **OET (Source Template):** For authoring, references archetypes, used in editors
- **OPT (Operational Template):** Flattened, self-contained artefact for runtime systems (XML in ADL 1.4 practice; OPT2 allows ADL, XML, JSON, YAML serialisations)

---

## UI and Presentation

Templates bridge clinical models and user interfaces.

- Rename elements for local context (e.g., "Body mass index" → "BMI")
- Tooling-level annotations (e.g. `hide_on_form` in OET / Better / EHRbase conventions — not part of normative OPT 2) can guide form generation without altering the data model

---

## Template Reuse

Templates can embed other templates for modularity and consistency across documents.

---

## Splitting Datasets and Composition Semantics (CGEM)

When one form or use case touches many datapoints, split the dataset across templates so data is **strategic** (patient-centric, reusable) while forms stay **tactical** (good UX). The CGEM framework (freshEHR) categorises data to guide this split — see the full guide at **openehr://guides/templates/cgem-framework** for definitions, the openEHR mapping table, and caveats. In summary:

- **Global Background:** True regardless of care context (e.g. allergies, CPR decision). One current version per patient → composition `category = persistent`.
- **Contextual Situation:** Single source of truth for a care journey or episode (e.g. cancer staging, care plan). One current version per journey → composition `category = episodic` (a standalone RM category with persistent-like semantics but bounded to an episode; not a subtype of `persistent`).
- **Event Assessment:** Each submission is a new record (e.g. clinic visit, lab result). Many compositions over time → composition `category = event`.
- **Managed Response:** Formal order/fulfilment cycle (referral, prescription). Use Instruction and Action archetypes; place in dedicated or encounter template as appropriate.

One form can read/write multiple compositions; Global Background may be read-only (AQL) in a form. Distinguish true managed workflows (Instruction/Action) from simple yes/no or date records—the latter belong in Contextual or Event templates.

---
