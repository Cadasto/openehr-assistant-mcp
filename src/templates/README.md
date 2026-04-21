# Authoring templates and conventions

Developer reference for authoring and curating content under `resources/guides/` and `resources/examples/`. Files in this directory are **not** consumed by the MCP server — they are copy-ready skeletons, style references, and curation notes for developers and maintainers.

## Guide markdown style (all categories)

Conventions for files under `resources/guides/<category>/<name>.md`.

### Header block (top of file, before the first `---`)

- **Scope:** and/or **Purpose:** — what the guide covers or intends
- **Related:** (optional) — comma-separated `openehr://guides/...` URIs for cross-referenced guides
- **Keywords:** (optional) — comma-separated terms for discovery and search
- Normative guides may optionally include **URI:** and **Version:**; the URI is otherwise derived from the file path

### Section headings

- **Lettered (A, B, C…)** — long normative guides (rules, language-standards, reference-formatting) with many numbered rules (A1, B1, …)
- **Numeric (0, 1, 2… or 1, 2, 3…)** — short reference or cheat sheets (e.g. `adl-idioms-cheatsheet`, `structural-constraints`)
- **Unnumbered (short titles)** — principles or high-level overview guides (e.g. `principles`, `terminology`)

Choose the style that fits the document; no need to force one convention everywhere.

### Rule numbering

- Normative rules docs: `A1`, `B1`, `C1` within lettered sections
- Cheat sheets / principles: bold labels like **Rule:**, **Idiom:**, or plain bullets without rule codes

### Code blocks

- Prefer ` ```text ` for prose examples and sample content
- Use other language tags when they add value (e.g. `adl`, `json`)

### Checklists

- **☑** (Unicode) — conformance checklists embedded inside a guide (e.g. "Consistency Checklist" sections)
- **`- [ ]`** (task list) — the dedicated `archetypes/checklist` guide uses task-list syntax for interactive use

---

## Authoring spec digests (`resources/guides/specs/*.md`)

Spec digests are short structured summaries (250–900 words body) of openEHR specification documents. One file per upstream spec document. They prime LLM context without fetching the 30k+ word full-HTML chapter. A copy-ready skeleton is at [`spec-digest-template.md`](./spec-digest-template.md).

### Filename convention

`<component>-<doc>.md`, using the upstream doc ID. Examples: `rm-ehr.md`, `rm-data_types.md`, `am-ADL1.4.md`, `am2-ADL2.md`, `sm-openehr_platform.md`. Preserve upstream casing for mixed-case doc IDs.

### When to use

- Priming a conversation with "what does the EHR IM define?"
- Cross-spec navigation ("how does AM relate to RM?")
- Onboarding prompts, glossaries, routing logic

### When NOT to use

- Normative implementation work — always read the full `.md` or HTML (see the `howto/spec-lookup` guide for the retrieval order)
- Class-level detail (attributes, invariants) — use `type_specification_get` (BMM-backed)

### Authoring rules

- **250–900 words body** (header block excluded), enforced by `SpecDigestsTest`
- **No invented class names** — every named class must exist in the upstream spec
- **All URLs must resolve** — verify with `curl -sI` before commit. When the upstream doc has no `.md` twin (e.g. OpenAPI-rendered ITS-REST endpoints whose source is YAML), set `**Markdown URL:** N/A` and let the Spec URL carry the HTML reference. The validator recognises the `N/A` sentinel and skips the pattern check.
- **Terse Key Classes / Constructs section** — 5–8 top constructs with half-line roles. Per-class attribute / function / invariant detail belongs in `type_specification_get`, not here. Cite that tool explicitly when recommending deeper lookups.
- **Update `**Last updated:**`** on any edit
- **Default to `**Release:** development`** with `/releases/<COMPONENT>/development/` URLs so digests track the living spec rather than a year-old snapshot. Fork to a `-release-X.Y.Z.md` sibling if you need a point-in-time pinned digest for a specific openEHR release.
- **Use the repo's `**Field:**` header convention** — do not add YAML frontmatter

---

## Curated archetype examples (`resources/examples/archetypes/*.adl`)

Gold-standard published CKM archetypes, stored as native `.adl` files and served by the MCP server at `openehr://examples/archetypes/<archetype-id>` with MIME type `text/plain`. Purpose: few-shot references for writing, editing, and reviewing archetypes. Load specific examples by RM type as needed — do not load all at once.

**Source:** Exported from CKM (2026-03). Translations stripped to English-only for context efficiency.

### Example index

| # | RM type | Archetype | File | Size | Why it is here / key patterns |
|---|---|---|---|---|---|
| 1 | **OBSERVATION** | Blood Pressure (v2) | `openEHR-EHR-OBSERVATION.blood_pressure.v2.adl` | ~1800 lines | Canonical openEHR archetype. HISTORY/EVENT (point + interval), DV_QUANTITY with units/magnitude ranges, DV_CODED_TEXT with internal value sets, DV_ORDINAL (Korotkoff sounds), protocol (method, device, cuff), state (position, exertion, tilt), ARCHETYPE_SLOT for device/exertion CLUSTERs, SNOMED CT bindings, comprehensive description. **Load when:** writing OBSERVATION archetypes, HISTORY/EVENT, quantity/coded-text patterns. |
| 2 | **EVALUATION** | Problem/Diagnosis (v1) | `openEHR-EHR-EVALUATION.problem_diagnosis.v1.adl` | ~1400 lines | Core clinical EVALUATION used in every deployment. No HISTORY, single assessment. DV_CODED_TEXT for diagnosis (external binding expected), DV_TEXT, DV_DATE_TIME (onset/resolution), internal value sets (severity/course/certainty), multiple ARCHETYPE_SLOTs (status, body site, staging), protocol with extension slot. **Load when:** writing EVALUATION archetypes, clinical-assessment patterns, slot usage. |
| 3 | **INSTRUCTION** | Medication Order (v3) | `openEHR-EHR-INSTRUCTION.medication_order.v3.adl` | ~1500 lines | Complex ordering with ACTIVITY and deep nesting. Dosage → therapeutic direction → dose amount CLUSTER chain. Multiple ARCHETYPE_SLOTs at different levels. DV_CODED_TEXT, DV_TEXT, DV_DURATION, DV_COUNT, DV_BOOLEAN, DV_PARSABLE (structured dose syntax), complex protocol. **Load when:** writing INSTRUCTION archetypes, ACTIVITY patterns, complex nested structures. |
| 4 | **ACTION** | Procedure (v1) | `openEHR-EHR-ACTION.procedure.v1.adl` | ~1700 lines | Demonstrates the **Instruction State Machine** (ISM). `ism_transition` with `careflow_step` + `current_state` (planned, postponed, cancelled, scheduled, active, suspended, aborted, completed). DV_CODED_TEXT procedure name, DV_TEXT description/comment, DV_DATE_TIME scheduling, SLOTs (multimedia, body site, detail), protocol with requestor/receiver. **Load when:** writing ACTION archetypes, ISM state-machine work, procedure/activity patterns. |
| 5 | **CLUSTER** | Anatomical Location (v1) | `openEHR-EHR-CLUSTER.anatomical_location.v1.adl` | ~800 lines | Widely reused CLUSTER — reusable data group design. Large internal value sets (body sites), DV_TEXT free-text alternative, ARCHETYPE_SLOT for relative location sub-cluster, DV_MULTIMEDIA for image markup. Compact, focused single-concept design. **Load when:** writing CLUSTER archetypes, reusable data group design, coded value sets. |
| 6 | **COMPOSITION** | Encounter (v1) | `openEHR-EHR-COMPOSITION.encounter.v1.adl` | ~460 lines | Simple event composition showing the COMPOSITION root. `category`, `context`, `content`; `EVENT_CONTEXT` with `other_context` `ITEM_TREE`; content ARCHETYPE_SLOT wide open for any SECTION/ENTRY; SECTION slot alongside direct ENTRY slots. Minimal but complete COMPOSITION pattern. **Load when:** writing COMPOSITION archetypes, root document structure, event/persistent/episodic patterns. |
| 7 | **ADMIN_ENTRY** | Translation Requirements (v1) | `openEHR-EHR-ADMIN_ENTRY.translation_requirements.v1.adl` | ~130 lines | Smallest ENTRY type. Data-only (no protocol/state/history). ITEM_TREE with simple elements, DV_CODED_TEXT, DV_TEXT, DV_BOOLEAN, ARCHETYPE_SLOT for language CLUSTER. Compact, easy end-to-end read. **Load when:** writing ADMIN_ENTRY archetypes, or when a simple end-to-end example is needed. |

### Notes

- **Progressive disclosure:** fetch only the specific `.adl` file(s) relevant to the task at hand. Each archetype is 100+ KB as text.
- **English-only:** translations stripped to keep context lean. `language` section shows `original_language = en`. Sufficient for understanding structure and patterns.
- **CKM-published:** these are real, published, community-validated archetypes — they represent best practice.
- **Immutable by policy:** treat these files as read-only reference data. Any update should be sourced from a fresh CKM export, not hand-edited, so the attribution stays accurate.

---

## Files in this directory

- [`README.md`](./README.md) — this file (authoring conventions + curated-content reference)
- [`spec-digest-template.md`](./spec-digest-template.md) — copy-ready skeleton for new spec digests
