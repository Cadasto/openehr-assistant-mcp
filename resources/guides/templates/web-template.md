# openEHR Web Template — Structure & Particularities

**Scope:** The **Web Template** — the vendor JSON projection of an OPT used to drive UI/form generation and to define the path schema behind the FLAT/STRUCTURED (Simplified Formats) composition serialisations.
**Related:** openehr://guides/templates/serialization-formats, openehr://guides/templates/opt-structure, openehr://guides/simplified_formats/principles, openehr://guides/specs/its-rest-simplified_formats, openehr://guides/templates/principles
**Keywords:** web template, WT, application/openehr.wt+json, FLAT, STRUCTURED, simSDT, aqlPath, web id, inputs, suffix, Better, EHRbase, runtime template

---

## What a Web Template is

A **Web Template (WT)** is a JSON format that **flattens and simplifies an OPT** into a UI- and data-entry-friendly tree. It is:

- **Derived from the OPT** (not authored) — a deliberately simplified, flattened, *lossy* projection with camelCase ids, per-leaf `inputs`, and `aqlPath` on every node.
- The **path/schema source** for the FLAT (`simSDT`) and STRUCTURED (`structSDT`) composition serialisations — the segment ids used in FLAT paths are Web-Template node ids.

> A Web Template is **not** a template you write, and **not** a JSON re-serialisation of the OPT/AOM. Better's own framing: the OPT "can be hard to parse," and the Web Template is "a much simpler JSON format based on the operational template."

## Status & provenance

- **Vendor de-facto**, not a normative openEHR artefact. Origin: **Better (Marand) "Web Templates"**; later adopted and extended by **EHRbase**. The two reference implementations (Better = Kotlin, EHRbase SDK = Java) *are* the schema; they can drift — diff `WebTemplateNode` if exactness matters.
- The **FLAT/STRUCTURED composition serialisation** the WT feeds **is** standardised (ITS-REST *Simplified Formats*, STABLE). The **Web Template object itself is out of scope** of that spec — it comes from the broader ITS-REST DEFINITION API and de-facto usage.

## How it is produced

Built **at runtime by flattening a parsed OPT**, not stored as a first-class file:

- EHRbase SDK: `OPTParser.parse(OPERATIONALTEMPLATE) → WebTemplate` (`version` pinned to `"2.3"`).
- Better: `WebTemplateBuilder.build(template)` → `WebTemplate.asJson()`.

EHRbase serves it from `GET .../definition/template/adl1.4/{template_id}` with `Accept: application/openehr.wt+json` (the dedicated `/webtemplate` sub-path is **deprecated** in favour of content negotiation).

## JSON shape (de-facto schema)

**Root** (`WebTemplate`): `templateId`, `version` (EHRbase `"2.3"`), `defaultLanguage`, `languages[]`, `tree` (single root node), `semVer?` (Better), `otherDetails?`.

**Node** (`WebTemplateNode`, recursive): `id` (sanitised camelCase "web id", disambiguated among siblings) · `name` · `localizedName(s)` / `localizedDescriptions` · `rmType` (e.g. `OBSERVATION`, `DV_QUANTITY`) · `nodeId` (archetype id or `atNNNN`) · `min`/`max` (`max=-1` = unbounded) · `aqlPath` (canonical AQL/AOM path with `atNNNN`) · `cardinalities` · `dependsOn` · `inContext` · `termBindings` · `annotations` · `proportionTypes` · `inputs[]` · `children[]`.

**Leaf input** (`WebTemplateInput`): `suffix` (the FLAT attribute suffix — `magnitude`, `unit`, `code`, `value`, `numerator`, `denominator`, …; empty = implicit value) · `type` (`TEXT`, `CODED_TEXT`, `DECIMAL`, `INTEGER`, `BOOLEAN`, `DATE`, `DATETIME`, `TIME`, `QUANTITY`, `COUNT`, `PROPORTION`, `DURATION`) · `list[]` (coded/ordinal: `value`, `label`, `localizedLabels`, `ordinal`, `termBindings`) · `listOpen` · `validation` (`pattern`, `range`, `precision`) · `terminology` · `defaultValue`.

## The key distinction: `id` vs `aqlPath`

Every node carries **both**:

- **`id`** — the sanitised **web id**; this is what appears as a **FLAT/STRUCTURED path segment**.
- **`aqlPath`** — the **canonical AOM/AQL path** (with `atNNNN` node ids) used for AQL and canonical data.

FLAT paths are built from `id`s (plus `:index` and `|suffix`); AQL uses `aqlPath`. Confusing the two is the most common Web-Template mistake.

## Particularities & gotchas

- **Not the validation contract.** A Web Template is fine for form/field-level validation, but authoritative composition validation is against the **OPT** (openehr://guides/templates/opt-structure).
- **Vendor drift.** Better and EHRbase schemas are near-identical but not guaranteed equal; EHRbase pins `version "2.3"`.
- **Media-type caution.** The FLAT/STRUCTURED composition media types are fixed by the spec: `application/openehr.wt.flat+json` and `application/openehr.wt.structured+json`. EHRbase's `.schema`-suffixed strings (`…wt.flat.schema+json`) are a **non-conformant deviation** — emit the canonical strings; be liberal on input if you must interoperate with current EHRbase. Legacy `simSDT`/`structSDT` names survive only as historical aliases.
- **Not hand-written.** Regenerate it from the OPT rather than editing it.

## References

- openEHR ITS-REST — Simplified Formats (FLAT/STRUCTURED serialisation the WT feeds): openehr://guides/specs/its-rest-simplified_formats
- Simplified Formats authoring guides: openehr://guides/simplified_formats/principles
- Format map & pipeline: openehr://guides/templates/serialization-formats
- OPT (the WT's source of truth): openehr://guides/templates/opt-structure
