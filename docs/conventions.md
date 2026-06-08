# Coding & MCP Conventions

Canonical conventions for code in this repository — for human contributors and AI
agents alike. Architecture (components, layers) is in [architecture.md](architecture.md);
the test/validation workflow is in [testing.md](testing.md); the dev environment is in
[development.md](development.md).

## Coding standard

- **PSR-12.** Use PHP CS Fixer or IDE formatting.
- Keep methods small; prefer typed signatures; add PHPDoc only when types aren't self-evident.
- Run **`make ci`** (PHPStan + tests) before pushing.
- Use Markdown for guides and other documentation; PHPDoc for public methods/classes.

## Namespaces

- Production code: `Cadasto\OpenEHR\MCP\Assistant\` → `src/`.
- Tests: `Cadasto\OpenEHR\MCP\Assistant\Tests\` → `tests/`, files `*Test.php`, mirroring `src/`.
  Mock external HTTP to CKM — never hit live APIs. See [testing.md](testing.md).

## MCP capabilities (authoring)

Capabilities are declared with PHP attributes and discovered automatically; results
are cached for fast startup ([ADR-0001](decisions/0001-attribute-driven-discovery.md)).
**After adding or renaming a capability class, clear the discovery cache** or it won't
register — see [development.md](development.md#gotcha--mcp-discovery-cache).

- **Tools** — `src/Tools/`; annotate public methods with `#[McpTool(name: '...')]`.
  Constrain closed-set parameters with `#[Schema(enum: [...])]` so the published
  `inputSchema` lists valid values.
- **Prompts** — `src/Prompts/`; annotate classes with `#[McpPrompt(name: '...')]` and
  extend `AbstractPrompt`, which loads the **Markdown** body from `resources/prompts/`
  via `loadPromptMessages('<name>')`.
  - **Prompt policy split:** global, always-applicable policy (tool discipline, no
    guessing, workflow) lives in `resources/server-instructions.md`; keep
    `resources/prompts/*.md` focused on task-specific constraints and output structure
    ([ADR-0003](decisions/0003-prompt-policy-split.md)).
- **Resources** — `src/Resources/`; `#[McpResource]` / `#[McpResourceTemplate]`. URIs:
  `openehr://guides/{category}/{name}`, `openehr://examples/{kind}/{name}`,
  `openehr://spec/type/{component}/{name}`, `openehr://terminology`.
- **Completion providers** — `src/CompletionProviders/`; `#[CompletionProvider]`,
  implementing `Mcp\Capability\Completion\ProviderInterface`, to suggest argument values.

Attribute `name:` values are part of the public MCP contract — renaming one is a
breaking change. The app version is `APP_VERSION` in `src/constants.php`.

## Content & spec alignment

When authoring guides, prompts, BMM JSON, or terminology, keep wording aligned with
the authoritative openEHR specifications — retrieve them rather than relying on
memory ([ADR-0005](decisions/0005-spec-aligned-content-retrieval.md); the
`spec-lookup` how-to under `resources/guides/howto/`). Authoring scaffolding and the
guide/spec-digest style live under `src/templates/`.
