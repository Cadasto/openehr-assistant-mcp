# openEHR Template Serialization Formats

**Scope:** The four serialisations a template takes — OET, OPT, ADL Designer `.t.json`, web template — and which are hand-authorable.
**Related:** openehr://guides/templates/oet-syntax, openehr://guides/templates/oet-idioms-cheatsheet, openehr://guides/templates/principles, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am2-AOM2, openehr://guides/specs/its-rest-simplified_formats
**Keywords:** OET, OPT, t.json, web template, ADL Designer, template overlay, serialization, FLAT, STRUCTURED, MD5-CAM, checksum

---

## The Four Formats

One design intent, several serialisations — **not interchangeable**, different audiences.

| Format | Purpose | Hand-authorable? | Tool checksums? |
|---|---|---|---|
| **OET** (`.oet`) | Design-time source XML; references archetypes + narrowing. The artefact you author/version. | **Yes** (canonical) | `integrity_checks/digest` (`MD5-CAM`) — regenerated on import, not a precondition |
| **OPT** (`.opt`/`.optx`/`.optj`) | **Flattened** operational template: all archetype constraints inlined, slots resolved. For runtimes/validators/generators. | **No** (compiled from OET + archetypes) | Yes (compiler output) |
| **ADL Designer `.t.json`** | ADL2/AOM2 **differential template** as JSON: `@type: TEMPLATE`, `parentArchetypeId`, `t_<concept>` id, `C_COMPLEX_OBJECT` `definition`, `templateOverlays`. ADL Designer's working format. | In-tool only | Tool-managed (`build_uid`) |
| **Better/EHRbase web template** (JSON) | Runtime template driving **simplified (FLAT/STRUCTURED)** data entry + UI. Derived from the OPT. | **No** (from OPT) | n/a (`templateId`, derived node ids) |

> **`.t.json` is NOT a web template.** It is an ADL2 *differential template* (`TEMPLATE`/`TEMPLATE_OVERLAY` in AOM2; see openehr://guides/specs/am2-AOM2). A web template is *flattened runtime* JSON from the OPT for FLAT/STRUCTURED (see openehr://guides/specs/its-rest-simplified_formats) — different lifecycle stage and consumer.

---

## Pipeline

```
OET (or .t.json) + referenced archetypes  ──►  OPT (flattened)  ──►  web template (FLAT/STRUCTURED data entry)
```

Author in OET (or ADL Designer `.t.json`) → compile to OPT (the runtime contract) → derive the web template from the OPT.

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
- Working inside ADL Designer → **`.t.json`**.
