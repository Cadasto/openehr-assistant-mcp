# openEHR Operational Template (OPT) — Structure & Particularities

**Scope:** The operational template (OPT) — the flattened, compiled form of a template that a CDR validates data against. Complements the source-form guide openehr://guides/templates/oet-syntax.
**Related:** openehr://guides/templates/serialization-formats, openehr://guides/templates/oet-syntax, openehr://guides/templates/web-template, openehr://guides/templates/principles, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am2-AOM2
**Keywords:** OPT, operational template, flattening, .opt, .optx, .optj, OPT2, OPERATIONALTEMPLATE, raw, profiled, CDR validation, ADL2

---

## What an OPT is

An **Operational Template (OPT)** is the **flattened, self-contained** form of a template: every referenced archetype is inlined and specialised into a single tree, and all template-level constraints (removals, mandations, narrowing, defaults) are applied. It is the artefact a Clinical Data Repository (EHRbase, Better, …) ingests and **validates compositions against** — the runtime contract.

- You **do not hand-author** an OPT. It is *generated* by compiling a source template (OET or `.t.json`) together with its referenced archetypes.
- An OPT is authoritative: FLAT/STRUCTURED payloads and web templates are all **derived from** it (see openehr://guides/templates/web-template).

## How it is produced

```
OET (or .t.json) + referenced archetypes  ──flatten/compile──▶  OPT
```

Flattening resolves **filled** slots (the chosen archetype is inlined) and applies the template's constraints. Note: **unfilled open `ARCHETYPE_SLOT`s can still remain** in an OPT — flattening substitutes filled slots, it does not delete every empty one. Closed slots are removed.

## Serialisations

- **ADL 1.4 OPT (`.opt`)** — the dominant form in practice. XML, root `<template xmlns="http://schemas.openehr.org/v1">` (the `OPERATIONALTEMPLATE` / AOM XmlBeans model). This is what most current tooling and CDRs consume. EHRbase, for example, ingests it via `POST .../definition/template/adl1.4` with `Content-Type: application/xml` (vendor/de-facto endpoint, not the abstract spec).
- **ADL2 operational template (OPT2)** — the ADL2 successor, per the OPT2 specification. Serialised as **ADL text (`.opt`)**, **XML (`.optx`)** or **JSON (`.optj`)** — the spec defines no `.opt2` extension (OPT2 spec, *Types of OPT*). Archetype Designer can produce ADL2 but has historically exported ADL 1.4 OPT by default.

> Terminology trap: in ADL 1.4 practice "the OPT" is one XML file named `.opt`. The `.opt`/`.optx`/`.optj` triple is the **OPT2 (ADL2)** convention, where `.opt` means ADL *text* (not XML). Always confirm which generation you are handling.

## Raw vs profiled OPT (OPT2)

The OPT2 specification distinguishes two variants of the same artefact:

- **Raw OPT** — retains all languages and terminology bindings.
- **Profiled OPT** — languages and bindings filtered to those needed, `annotations` optionally removed, terminology substitutions applied.

They are formally the same template with less content; a profiled OPT is smaller and deployment-targeted.

## Particularities & gotchas

- **It is generated, not edited.** Fix problems in the source (OET/`.t.json`) and recompile; do not patch an OPT by hand.
- **Unfilled slots may persist** — do not assume an OPT is fully closed.
- **ADL 1.4 vs ADL2** OPTs are different object models (XmlBeans `OPERATIONALTEMPLATE` vs AOM2); tools are usually specific to one.
- The OPT — not a `.t.json` and not a web template — is the **normative validation contract**. A `.t.json` still has open slots (flatten it first); a web template is a simplified, lossy projection.

## References

- openEHR AM — Operational Template (OPT2) specification: openehr://guides/specs/am2-OPT2
- openEHR AM — Archetype Object Model 2 (AOM2): openehr://guides/specs/am2-AOM2
- Format map & pipeline: openehr://guides/templates/serialization-formats
