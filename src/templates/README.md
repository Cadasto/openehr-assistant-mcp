# Authoring templates and conventions

Developer reference for authoring content under `resources/guides/`. Files in this directory are **not** consumed by the MCP server — they are copy-ready skeletons and style references for authors.

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

## Files in this directory

- [`README.md`](./README.md) — this file (authoring conventions)
- [`spec-digest-template.md`](./spec-digest-template.md) — copy-ready skeleton for new spec digests
