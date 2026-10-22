---
kind: guide
---

<!-- sdd-check: allow rfc2119 -->

# Specifications — normative behaviour

The **how must it behave** layer. Topic specs carry RFC-2119 normative prose with stable section numbers;
each requirement's normative statements live in exactly **one** canonical section here.

Write and extend specs with `/sdd-specify`.

## Conventions

- **RFC-2119 keywords** mark force: **MUST/SHALL** (absolute), **SHOULD** (strong; exceptions need a reason),
  **MAY** (optional). No keyword ⇒ informative.
- **One canonical home** per requirement — the requirements index links here; prose is never duplicated.
- **Stable § numbers** — cite as `SPEC-<NAME> §N`. Never renumber a published section.
- **Mode** — `spec-first` (the spec leads) or `implementation-aligned` (code may lead, spec updated in the
  same PR); a spec whose frontmatter names neither takes the descriptor's `default_mode`.
- Specs contain **no** checkbox task lists, file paths, or PR summaries.
- The machine-readable [`traceability.yaml`](traceability.yaml) maps each `REQ` to its canonical section,
  packages, tests, and probes; the `spec-check` target validates it against the tree.

## Known gaps

These binding sentences hold in the shipped code, but no test yet fails when they break. A follow-up change
adds the tests; until then, review guards them.

- SPEC-TOOLS §1: the `ckm_*_get` tool names, the `adl` default, and `ckm_template_search`'s `total`.
- SPEC-TOOLS §2, §3: the `guide_get` and `examples_get` names, and `guide_adl_idiom_lookup` returning only the
  matching sections.
- SPEC-TOOLS §5: functions and invariants in `type_specification_get`.
- SPEC-TOOLS §6: `maxResults` bounds outside `guide_search`, and the closed `{items, total}` envelope.
- SPEC-SURFACE §1, §2: the four explorer prompt names, and the four `openehr://` URIs.
- SPEC-SURFACE §3: example-name completion.
- SPEC-SURFACE §4: transport selection and the `MCP_ALLOWED_HOSTS` default.
- SPEC-SURFACE §5: the advertised instructions and the missing-file fallback.
- SPEC-SURFACE §6: the disk cache handed to discovery, and eager loading.
- SPEC-ENGINEERING §1: no network request outside the CKM client, and mirrored tests for `CliOptions`,
  `SearchTokenizer`, `TerminologyXmlLoader` and `CompletionProviders\Examples`.

## Topic specs

The table below is generated from the specification documents by `sdd-check generate`. Never hand-edit it.

<!-- sdd:generated specifications-index -->

| Spec | Topic | Status | Mode |
|---|---|---|---|
| [`SPEC-CONTENT`](content.md) | bundled content | Draft | implementation-aligned |
| [`SPEC-ENGINEERING`](engineering.md) | engineering constraints | Draft | implementation-aligned |
| [`SPEC-TOOLS`](knowledge-tools.md) | knowledge tools | Draft | implementation-aligned |
| [`SPEC-SURFACE`](mcp-surface.md) | MCP surface and runtime | Draft | implementation-aligned |

<!-- /sdd:generated -->
