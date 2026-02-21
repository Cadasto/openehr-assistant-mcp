# Guide Markdown Style

Short conventions for guides under `resources/guides/` so new and edited guides stay consistent.

## Header block (top of file, before `---`)

- Use **Scope:** and/or **Purpose:** to describe what the guide covers or its intent.
- **Related:** (optional) — list canonical `openehr://guides/...` URIs for cross-referenced guides.
- **Keywords:** (optional) — comma-separated terms for discovery and search.
- Normative guides may optionally include **URI:** and **Version:**; the URI is otherwise derived from the file path.

## Section headings

- **Lettered (A, B, C...):** Long normative guides (rules, language-standards, reference-formatting). Use for documents with many numbered rules (A1, B1, ...).
- **Numeric (0, 1, 2... or 1, 2, 3...):** Short reference or cheat sheets (e.g. adl-idioms-cheatsheet, structural-constraints).
- **Unnumbered (short titles):** Principles or high-level overview guides (e.g. principles, terminology).

Choose the style that fits the document; no need to force one convention everywhere.

## Rule numbering

- Normative rules docs: use **A1**, **B1**, **C1** within lettered sections.
- Cheat sheets / principles: bold labels like **Rule:**, **Idiom:**, or bullet points without rule codes.

## Code blocks

- Prefer ` ```text ` for prose examples and sample content.
- Use other language tags when they add value (e.g. `adl`, `json`).

## Checklists

- **☑** (unicode): Conformance checklists embedded inside a guide (e.g. “Consistency Checklist” sections).
- **`- [ ]`** (task list): The dedicated checklist guide (`archetypes/checklist`) uses task-list syntax for interactive use.
