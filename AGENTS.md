# AI Guidelines: openEHR Assistant MCP Server

These guidelines summarize the high-level architecture, coding conventions, and developer workflows for this repository, with the goal of helping an AI agent quickly discover available tooling and work within the project structure.

## Documentation map

This repo follows a lightweight **Specification-Driven Development** paradigm. The
spec set under [`docs/`](docs/README.md) is the source of truth; AGENTS.md is the
concise conventions layer that links into it.

- [`docs/requirements.md`](docs/requirements.md) — `REQ-#` functional + non-functional requirements (the *what*).
- [`docs/architecture.md`](docs/architecture.md) — components mapped to requirements (the *how*).
- [`docs/decisions/`](docs/decisions/README.md) — Architecture Decision Records (the *why*).
- [`docs/traceability.md`](docs/traceability.md) — REQ ↔ code ↔ test ↔ ADR matrix.
- [`docs/development.md`](docs/development.md) · [`docs/testing.md`](docs/testing.md) — Docker dev environment and the test/validation workflow.

> **Maintainer tooling.** Authoring tools/prompts/guides/examples and managing
> releases for this repo is supported by the
> [openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin)
> (Claude Code + Cursor). The user-facing
> [openehr-assistant plugin](https://github.com/cadasto/openehr-assistant-plugin)
> wraps this server for clinical end users — do not confuse the two audiences.

## Project overview

- The openEHR Assistant MCP Server is a PHP 8.4 MCP server that exposes openEHR tools, prompts, and resources for MCP clients. The codebase is PSR-compliant and structured around attribute-driven discovery. See `README.md` for the feature overview and capabilities.
- The project uses the `modelcontextprotocol/php-sdk` to register tools (`#[McpTool]`), prompts (`#[McpPrompt]`), resources (`#[McpResourceTemplate]`/`#[McpResource]`), and completion providers (`#[CompletionProvider]`).

## Repository layout (high level)

- `public/index.php`: entrypoint; registers MCP capabilities (tools, prompts, resources) and starts the server. It uses a file-based cache for MCP discovery to improve startup performance.
- `src/Tools`: MCP tools, each method annotated with `#[McpTool(...)]`.
- `src/Prompts`: MCP prompts, each class annotated with `#[McpPrompt(...)]`.
- `src/Resources`: MCP resources and resource templates (`#[McpResourceTemplate]`, `#[McpResource]`).
- `src/CompletionProviders`: completion providers implementing `Mcp\Capability\Completion\ProviderInterface`.
- `src/Apis`: internal API clients for CKM and other services.
- `resources/`: static assets such as guides, BMM JSON, and terminology files.
- `tests/`: PHPUnit tests and configuration for tools, prompts, resources, and completion providers.

## Configuration & Environment

- **Runtime**:
 - Docker services (from `.docker/docker-compose.yml` and `.docker/docker-compose.dev.yml`):
 - `app`: application service used for both production-like and development runs. Dev overrides mount the source and expose port 8343. `ingress`: Caddy reverse proxy. `node`: dev-only service with Node and curl for MCP conformance (`make conformance`) and other npx/curl tooling.
  - PHP 8.4 provided by multi-stage `.docker/Dockerfile`.
- **Environment variables**: Configured in `.env` (see `.env.example`). Key variables:
  - `CKM_API_BASE_URL`: Base URL for the CKM REST API (default: `https://ckm.openehr.org/ckm/rest`).
  - `LOG_LEVEL`: Monolog logging level (e.g., `debug`).
  - `HTTP_TIMEOUT`, `HTTP_SSL_VERIFY`: Guzzle client settings.
  - `XDG_DATA_HOME`: directory for application data, including cache and sessions (default: `/tmp`).
  - `MCP_ALLOWED_HOSTS`: comma-separated DNS-rebinding allow-list for the `streamable-http` transport (SDK ≥ 0.6; default loopback only). Set to the reverse-proxy host / public domain when deployed behind a proxy.
- **Server Transports**:
  - `streamable-http`: Default; in development exposes SSE endpoint on port `:8343` (mapped from Caddy).
  - `stdio`: For CLI/Desktop clients. Run via `php public/index.php --transport=stdio`.
- **Versioning**: App version is defined in `src/constants.php` (`APP_VERSION`).

## Coding style and conventions

- **Coding standard**: 
  - PSR-12. Use PHP CS Fixer or IDE formatting where available.
  - Keep methods small; prefer typed signatures. Add phpdoc only when types aren’t self-evident.
  - Run full test + static analysis before pushing: composer test; composer check:phpstan.
- **Namespaces**:
  - Production code uses `Cadasto\OpenEHR\MCP\Assistant\` (mapped to `src/`).
  - Tests use `Cadasto\OpenEHR\MCP\Assistant\Tests\` (mapped to `tests/`).
- **Testing conventions**:
  - Tests live under `tests/` and follow `*Test.php` naming.
  - Run tests with `composer test` within the dev container or docker compose equivalent (see below at Recommended workflow).
  - Keep tests unit/integration focused; **mock external HTTP calls** to CKM rather than relying on live APIs.
- **Commit messages**:
  - Follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) conventions, e.g. `fix(resources): refreshed BMM definitions in resources`, `feat(tools): added new tool for operational templates`.
- **CHANGELOG.md entries**:
  - Keep `## [Unreleased]` entries **short and high-level**: one-line bullets naming the artefact class and scope. Do not enumerate individual files, classes, drift fixes, or audit details — those belong in commit messages and PR bodies, not the CHANGELOG.
- **Branching**:
  - Use feature branches and pull requests. Standard PR validation runs on every push.
- **Documentation**:
  - Use PHPDoc for public methods and classes.
  - Use Markdown for guides and other documentation.

## MCP conventions (tools, prompts, resources)

- **Tools**: in `src/Tools`, annotate public methods with `#[McpTool(name: '...')]` to expose MCP tools.
- **Prompts**: in `src/Prompts`, annotate classes with `#[McpPrompt(name: '...')]` to expose MCP prompts. Prompt classes should extend `Cadasto\OpenEHR\MCP\Assistant\Prompts\AbstractPrompt` to load their messages from YAML resources via `$this->loadPromptMessages('prompt_name')`.
  - **Prompt policy split (rule of thumb)**: put global, always-applicable policy (tool discipline, no guessing, workflow principles) in `resources/server-instructions.md`; keep `resources/prompts/*.md` focused on task-specific constraints, required output structure, and domain-specialized rules.
- **Resources**:
  - `Guides` provides `openehr://guides/{category}/{name}` resources and registers guide resources at startup. Categories: `archetypes`, `templates`, `aql` (AQL principles, syntax, idioms-cheatsheet, checklist), `simplified_formats` (Flat/Structured principles, rules, idioms-cheatsheet, checklist), `specs` (per-document openEHR spec digests, e.g. `rm-ehr`, `rm-data_types`, `am-Overview`, `am2-ADL2`, `sm-openehr_platform`), `howto` (toolchain how-to guides such as `spec-lookup`).
  - `Examples` provides `openehr://examples/{kind}/{name}` resources and registers curated example artefacts at startup. Kinds: `aql` (reference AQL queries), `flat` / `structured` (paired simplified-format JSON payloads), `archetypes` (gold-standard CKM-published ADL archetypes covering OBSERVATION, EVALUATION, INSTRUCTION, ACTION, CLUSTER, COMPOSITION, ADMIN_ENTRY). `aql` / `flat` / `structured` files are Markdown (metadata header + fenced code block); `archetypes` files are native `.adl` served as `text/plain`. Search via `examples_search`, fetch via `examples_get`.
  - `TypeSpecifications` provides `openehr://spec/type/{component}/{name}` resource template.
  - `Terminologies` provides `openehr://terminology` resource.
- **Completion providers** live in `src/CompletionProviders` and are annotated with `#[CompletionProvider]` to suggest parameter values.

## Discovering and running developer tools

Tool definitions are declared in `composer.json` under `scripts`. The runtime is
**Docker-only** — there is no host PHP/Composer; all `php`, `composer`, and
`vendor/bin/*` commands run inside the `app` dev container, or they fail
([ADR-0004](docs/decisions/0004-docker-only-runtime.md)).

```bash
make up-dev      # start dev containers
make install     # install Composer dev dependencies
make conformance # MCP conformance suite (stack must be up)
```

- Full `docker compose … exec` invocations for **tests**, **PHPStan**, and
  **coverage**, plus configuration and the discovery-cache gotcha →
  [`docs/development.md`](docs/development.md) and [`docs/testing.md`](docs/testing.md).
- Run a single test class: `vendor/bin/phpunit --filter SomeTest` (in the container).

### Looking up openEHR specification content

When authoring or editing guides, prompts, BMM JSON, terminology data, AQL grammar notes, or any artifact that must stay aligned with the upstream openEHR standards, **do not guess or rely on training memory**. Retrieve from the authoritative source on `specifications.openehr.org`, preferring the cheapest representation that answers the question:

1. **Site index** — `https://specifications.openehr.org/llms.txt` enumerates every release, document, and JSON endpoint; use it to resolve doc phrases to canonical URLs and confirm the current `latest` release tag.
2. **Markdown twin** — every `*.html` spec page has a `.md` counterpart (e.g. `releases/RM/latest/ehr.html` → `releases/RM/latest/ehr.md`). Prefer it for prose, rationale, and examples. **Caveat:** the Markdown representation omits the per-class tables of attributes, functions, invariants, and inherited members — for those, fall through to the HTML page or a structured API.
3. **Structured APIs** — `/api/components.json`, `/api/classes.json`, `/api/releases.json` for component enumeration, class lookup, and release-tag resolution.
4. **Only then** scrape the HTML page.

The full policy, fall-through order, and failure modes live in the [`spec-lookup` how-to guide](resources/guides/howto/spec-lookup.md). AI agents running against the MCP server itself should read it via `guide_get(category="howto", name="spec-lookup")` — this is also stated in `resources/server-instructions.md`.

### Guides and specification alignment

- When adding or editing guides (e.g. under `resources/guides/`) or prompts that describe a standard (e.g. AQL), keep wording aligned with the authoritative spec and any formal grammar in the repo.
- Avoid duplicate or misplaced paragraphs in guide files.
- **Archetypes/templates**: Guides under `resources/guides/archetypes/` and `resources/guides/templates/` should stay consistent with openEHR modelling docs and ADL/OET conventions referenced in the project.
- **Simplified Formats**: Guides under `resources/guides/simplified_formats/` (Flat and Structured JSON serialization; Web Template field identifiers, ctx, pipe suffixes, underscore prefix) must align with the authoritative openEHR **ITS Simplified Formats** specification — retrieve it per the spec-lookup policy below, not from memory.
- **Authoring conventions and templates** — guide markdown style, spec-digest authoring rules, and the copy-ready digest skeleton all live under [`src/templates/`](src/templates/). Start there before adding or modifying a guide.

### Clinical modelling and governance

- When adding or changing guidance on archetypes, templates, or clinical modelling, uphold: **two-level modelling** (RM vs archetype/template); **single-concept archetypes**; **no workflow/UI in archetypes** (those belong in templates or apps); **reusability and semantic correctness** over app-specific convenience; **CKM and spec alignment**.

## Learned User Preferences

- When updating guides under `resources/guides/`, prefer substantive improvements that add value; avoid trivial or small changes that do not improve the guidelines.
- Guide content is consumed by AI agents: keep it short, concise, and scannable.

## Learned Workspace Facts

- Repo tooling that can be implemented in PHP should live as classes in `src/` with CLI entrypoints (e.g. `scripts/*.php`) and `composer.json` script entries; update AGENTS.md and related docs when changing such tooling.

