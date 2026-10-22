---
kind: guide
---

# Coding and MCP authoring conventions

This page holds the canonical conventions for code in this repository, for human
contributors and AI agents alike: the coding standard, namespaces, and how to author
MCP tools, prompts, resources, and completion providers. Architecture (components, layers) is in
[components.md](components.md), the test and validation workflow in
[testing.md](testing.md), and the dev environment in [development.md](development.md).

## Coding standard

- **PSR-12.** Use PHP CS Fixer or IDE formatting.
- Keep methods small; prefer typed signatures; add PHPDoc only when types aren't self-evident.
- Run **`make ci`** (spec-check, PHPStan, and tests) before pushing.
- Use Markdown for guides and other documentation; PHPDoc for public methods/classes.

## Namespaces

- Production code: `Cadasto\OpenEHR\MCP\Assistant\` → `src/`.
- Tests: `Cadasto\OpenEHR\MCP\Assistant\Tests\` → `tests/`, files `*Test.php`, mirroring `src/`.
  Mock external HTTP to CKM; never hit live APIs. See [testing.md](testing.md).

## MCP capabilities (authoring)

Capabilities are declared with PHP attributes and discovered automatically; results
are cached for fast startup ([ADR-0001](decisions/0001-attribute-driven-discovery.md)).
**After adding or renaming a capability class, clear the discovery cache** or it won't
register; see [development.md](development.md#gotcha-mcp-discovery-cache).

- **Tools**: `src/Tools/`; annotate public methods with `#[McpTool(name: '...')]`.
  - **Schemas are a contract, and the SDK enforces the input half.**
    `CallToolHandler` validates arguments against the generated `inputSchema`
    *before* your method runs, so a published constraint rejects bad calls rather
    than clamping them. The [constitution](architecture.md#contracts) states the
    binding part (closed schemas, strings-only optional enums, conforming output),
    and [REQ-N9](requirements.md) describes the whole capability; the four steps below are only
    *how* to satisfy it in this codebase, guarded by
    `tests/Tools/InputSchemaGuardTest.php`:
    1. Add method-level `#[Schema(additionalProperties: false)]` so unknown
       arguments are rejected instead of silently ignored.
    2. Constrain closed sets with `#[Schema(enum: [...])]`. For an **optional**
       enum use `?string $x = null` and list only the string values: never put
       `null` in the enum, and never use `string $x = ''` with `''` as a "no filter"
       sentinel. The server's `Helpers\OptionalEnumSchemaGenerator` publishes such a
       parameter as a plain string enum with no default, so clients omit it for
       "no filter" and an explicit `null` is rejected.
    3. Bound numeric parameters with `#[Schema(minimum:, maximum:)]`, and say
       "rejected, not clamped" in the description; an in-method `max(min(...))`
       is now only a defence against non-validating callers.
    4. Search tools return the `{items, total}` envelope, with top-level
       `required` + `additionalProperties: false` and the same on each item.
  - **Output schemas are enforced at runtime.** Since `mcp/sdk` 0.9 a return value
    that fails its `outputSchema` reaches the client as a failed (`isError`) tool call,
    so `tests/Tools/OutputSchemaConformanceTest.php` must catch it first. Add a case
    there for every new structured tool. The check runs on the value as sent: an empty
    PHP array goes out as `[]`, so return `new \stdClass()` where the schema declares
    an object.
- **Prompts**: `src/Prompts/`; annotate classes with `#[McpPrompt(name: '...')]` and
  extend `AbstractPrompt`, which loads the **Markdown** body from `resources/prompts/`
  and renders it via
  `loadPromptMessages('<name>', $values, $requiredKeys, $vocabularies, $requiredWhen)`.
  The three-part contract, pinned by `tests/Prompts/PromptArgumentSubstitutionTest.php`:
  - one `{{lower_snake_case}}` token in the markdown per `__invoke()` parameter, and
    vice versa (a token outside that charset is rejected as malformed, not shipped);
  - `$requiredKeys` lists exactly the parameters **without** a default, which is what
    the SDK advertises as `required` in `prompts/list`;
  - parameters are typed **`mixed`, never `string`**; see the docblock on
    `AbstractPrompt::normaliseArguments()` for the two SDK behaviours that forces.
  Where the body *branches* on a value ("for review, do not rewrite the artefact"),
  declare it in `$vocabularies` and pair it with the artefact it needs via
  `$requiredWhen`; an unvalidated token means the branch never fires.
  - **Prompt policy split:** global, always-applicable policy (tool discipline, no
    guessing, workflow) lives in `resources/server-instructions.md`; keep
    `resources/prompts/*.md` focused on task-specific constraints and output structure.
    **Resilience exception:** `resources/prompts/shared/policy.md` is a thin user-role
    block prepended to every prompt that deliberately restates a small subset of the
    global policy, for clients that under-inject the MCP `instructions` field;
    `server-instructions.md` remains the sole canonical source for full global policy
    ([ADR-0003](decisions/0003-prompt-policy-split.md)).
- **Resources**: `src/Resources/`; `#[McpResource]` / `#[McpResourceTemplate]`. URIs:
  `openehr://guides/{category}/{name}`, `openehr://examples/{kind}/{name}`,
  `openehr://spec/type/{component}/{name}`, `openehr://terminology`.
- **Completion providers**: `src/CompletionProviders/`; `#[CompletionProvider]`,
  implementing `Mcp\Capability\Completion\ProviderInterface`, to suggest argument values.

Attribute `name:` values are part of the public MCP contract: renaming one is a
breaking change. The app version is `APP_VERSION` in `src/constants.php`.

## Content & spec alignment

When authoring guides, prompts, BMM JSON, or terminology, keep wording aligned with
the authoritative openEHR specifications; retrieve them rather than relying on
memory ([ADR-0005](decisions/0005-spec-aligned-content-retrieval.md); the
`spec-lookup` how-to under `resources/guides/howto/`). Authoring scaffolding and the
guide/spec-digest style live under `src/templates/`.
