# openEHR Template Serialization Formats

**Scope:** The four serialisations a template takes — OET, OPT, Archetype Designer `.t.json`, web template — and which are hand-authorable.
**Related:** openehr://guides/templates/oet-syntax, openehr://guides/templates/opt-structure, openehr://guides/templates/web-template, openehr://guides/templates/oet-idioms-cheatsheet, openehr://guides/templates/principles, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am2-AOM2, openehr://guides/specs/its-rest-simplified_formats
**Keywords:** OET, OPT, t.json, web template, Archetype Designer, template overlay, serialization, FLAT, STRUCTURED, MD5-CAM, checksum

---

## The Four Formats

One design intent, several serialisations — **not interchangeable**, different audiences.

| Format | Purpose | Hand-authorable? | Tool checksums? |
|---|---|---|---|
| **OET** (`.oet`) | Design-time source XML; references archetypes + narrowing. The artefact you author/version. | **Yes** (canonical) | `integrity_checks/digest` (`MD5-CAM`) — regenerated on import, not a precondition |
| **OPT** (`.opt`/`.optx`/`.optj`) | **Flattened** operational template: all archetype constraints inlined, filled slots substituted, closed slots removed (unfilled open slots may remain). For runtimes/validators/generators. | **No** (compiled from OET + archetypes) | Yes (compiler output) |
| **Archetype Designer `.t.json`** | ADL2/AOM2 **differential template** as JSON: `@type: TEMPLATE`, `parentArchetypeId`, `differential: true`, `C_COMPLEX_OBJECT` `definition`, `templateOverlays`. Archetype Designer's working format. | In-tool only | Tool-managed (`build_uid`) |
| **Better/EHRbase web template** (JSON) | Runtime template driving **simplified (FLAT/STRUCTURED)** data entry + UI. Derived from the OPT. | **No** (from OPT) | n/a (`templateId`, derived node ids) |

> **`.t.json` is NOT a web template.** It is an ADL2 *differential template* (`TEMPLATE`/`TEMPLATE_OVERLAY` in AOM2; see openehr://guides/specs/am2-AOM2). A web template is *flattened runtime* JSON from the OPT for FLAT/STRUCTURED (see openehr://guides/specs/its-rest-simplified_formats) — different lifecycle stage and consumer.

**Extensions and variants.** In ADL 1.4 practice, "the OPT" is a single XML file named `.opt` (root `<template xmlns="http://schemas.openehr.org/v1">`). The `.opt`/`.optx`/`.optj` scheme above is the OPT2 (ADL2) convention: `.opt` = ADL text, `.optx` = XML, `.optj` = JSON (OPT2 spec, *Types of OPT*). OPT2 also distinguishes a **raw** OPT (all languages and terminology bindings retained) from a **profiled** OPT (languages/bindings filtered, `annotations` optionally removed, terminology substitutions applied) — formally the same artefact with less content.

**Media types.** Composition FLAT `application/openehr.wt.flat+json` and STRUCTURED `application/openehr.wt.structured+json` are fixed by the ITS-REST *Simplified Formats* spec (no `.schema` variants — treat EHRbase's `.schema`-suffixed strings as a deviation). The web template itself is served as `application/openehr.wt+json` (ITS-REST DEFINITION API / de-facto; the WT-as-resource is out of the Simplified Formats spec's scope).

---

## Pipeline

```
OET (or .t.json) + referenced archetypes  ──►  OPT (flattened)  ──►  web template (FLAT/STRUCTURED data entry)
```

Author in OET (or Archetype Designer `.t.json`) → compile to OPT (the runtime contract) → derive the web template from the OPT.

---

## MD5-CAM / `build_uid` — a Checksum, Not a Gate

An OET `<integrity_checks>` block holds one `<digest id="MD5-CAM-1.0.1">` per referenced archetype (`.t.json` carries `build_uid`). It is a **tamper-detection checksum** that CKM/tooling **computes on upload/build** — *output, not input*.

- You may author/edit a template **without a valid `MD5-CAM`**; CKM regenerates it on import. It is not an authoring step or a validity gate.
- **Never hand-fabricate a hash** — a wrong one is worse than none. Leave the existing digest (or omit it) and let tooling recompute.

```xml
<!-- Structure only — value computed by CKM/tooling; never hand-write it -->
<integrity_checks xsi:type="ArchetypeIntegrity" archetype_id="openEHR-EHR-COMPOSITION.encounter.v1">
  <digest id="MD5-CAM-1.0.1">…recomputed on build/import…</digest>
</integrity_checks>
```

---

## Choosing a Format

- Editing constraints / aggregating archetypes (hand or LLM) → **OET**.
- Deploying to a runtime / generating schemas, forms → **OPT**.
- Building FLAT/STRUCTURED payloads or UIs → **web template** (from OPT).
- Working inside Archetype Designer → **`.t.json`**.

---

## Deep-dive guides per format

This guide is the map; for the structure and particularities of each non-OET runtime format see:

- **OET** (source, hand-authored): openehr://guides/templates/oet-syntax
- **OPT** (compiled runtime contract): openehr://guides/templates/opt-structure
- **Web template** (UI / FLAT-STRUCTURED path schema): openehr://guides/templates/web-template

`.t.json` (Archetype Designer source JSON) has no separate guide — it is the AOM2/JSON analogue of OET, covered by the table above and openehr://guides/specs/am2-AOM2.
