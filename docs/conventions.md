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
  - **Schemas are a contract, and the input half is enforced by the SDK** —
    `CallToolHandler` validates arguments against the generated `inputSchema`
    *before* your method runs, so a published constraint rejects bad calls rather
    than clamping them. [REQ-N9](requirements.md) states normatively *what* must
    hold and is the single canonical home for it; the four steps below are only
    *how* to satisfy it in this codebase, guarded by
    `tests/Tools/InputSchemaGuardTest.php`:
    1. Add method-level `#[Schema(additionalProperties: false)]` so unknown
       arguments are rejected instead of silently ignored.
    2. Constrain closed sets with `#[Schema(enum: [...])]`. For an **optional**
       enum use `?string $x = null` and include `null` **in** the enum list —
       never `string $x = ''` with `''` as a "no filter" sentinel.
    3. Bound numeric parameters with `#[Schema(minimum:, maximum:)]`, and say
       "rejected, not clamped" in the description — an in-method `max(min(...))`
       is now only a defence against non-validating callers.
    4. Search tools return the `{items, total}` envelope, with top-level
       `required` + `additionalProperties: false` and the same on each item.
  - **Output schemas have no runtime enforcement.** The SDK does *not* validate
    return values against `outputSchema`, so `tests/Tools/OutputSchemaConformanceTest.php`
    is the only thing holding that contract. Add a case there for every new
    structured tool.
- **Prompts** — `src/Prompts/`; annotate classes with `#[McpPrompt(name: '...')]` and
  extend `AbstractPrompt`, which loads the **Markdown** body from `resources/prompts/`
  and renders it via
  `loadPromptMessages('<name>', $values, $requiredKeys, $vocabularies, $requiredWhen)`.
  The three-part contract, pinned by `tests/Prompts/PromptArgumentSubstitutionTest.php`:
  - one `{{lower_snake_case}}` token in the markdown per `__invoke()` parameter, and
    vice versa (a token outside that charset is rejected as malformed, not shipped);
  - `$requiredKeys` lists exactly the parameters **without** a default — that is what
    the SDK advertises as `required` in `prompts/list`;
  - parameters are typed **`mixed`, never `string`** — see the docblock on
    `AbstractPrompt::normaliseArguments()` for the two SDK behaviours that forces.
  Where the body *branches* on a value ("for review, do not rewrite the artefact"),
  declare it in `$vocabularies` and pair it with the artefact it needs via
  `$requiredWhen`; an unvalidated token means the branch never fires.
  - **Prompt policy split:** global, always-applicable policy (tool discipline, no
    guessing, workflow) lives in `resources/server-instructions.md`; keep
    `resources/prompts/*.md` focused on task-specific constraints and output structure.
    **Resilience exception:** `resources/prompts/shared/policy.md` is a thin user-role
    block prepended to every prompt that deliberately restates a small subset of the
    global policy, for clients that under-inject the MCP `instructions` field —
    `server-instructions.md` remains the sole canonical source for full global policy
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
