# AI Guidelines: openEHR Assistant MCP Server

These guidelines summarize the high-level architecture, coding conventions, and developer workflows for this repository, with the goal of helping an AI agent quickly discover available tooling and work within the project structure.

## Project overview

- The openEHR Assistant MCP Server is a PHP 8.4 MCP server that exposes openEHR tools, prompts, and resources for MCP clients. The codebase is PSR-compliant and structured around attribute-driven discovery. See `README.md` for the feature overview and capabilities.
- The project uses the `modelcontextprotocol/php-sdk` to register tools (`#[McpTool]`), prompts (`#[McpPrompt]`), resources (`#[McpResourceTemplate]`/`#[McpResource]`), and completion providers (`#[CompletionProvider]`).

## Repository layout (high level)

- `public/index.php`: entrypoint; registers MCP capabilities and starts the server.
- `src/Tools`: MCP tools, each method annotated with `#[McpTool(...)]`.
- `src/Prompts`: MCP prompts, each class annotated with `#[McpPrompt(...)]`.
- `src/Resources`: MCP resources and resource templates (`#[McpResourceTemplate]`, `#[McpResource]`).
- `src/CompletionProviders`: completion providers implementing `ProviderInterface`.
- `resources/`: static assets such as guides, BMM JSON, and terminology files.
- `tests/`: PHPUnit tests and configuration for tools, prompts, resources, and completion providers.

## Configuration & Environment

- **Runtime**:
  - Docker services (from docker-compose.yml and docker-compose.dev.yml):
    - `mcp`: single service used for both production-like and development runs. Dev overrides mount the source and expose port 8343.
  - PHP 8.4 provided by multi stage Dockerfile.
- **Environment variables**: Configured in `.env` (see `.env.example`). Key variables:
  - `CKM_API_BASE_URL`: Base URL for the CKM REST API (default: `https://ckm.openehr.org/ckm/rest`).
  - `LOG_LEVEL`: Monolog logging level (e.g., `debug`).
  - `HTTP_TIMEOUT`, `HTTP_SSL_VERIFY`: Guzzle client settings.
- **Server Transports**:
  - `streamable-http`: Default, exposes SSE endpoint on port `:8343`.
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
  - Keep tests unit/integration focused; **mock external HTTP calls** to CKM rather than relying on live APIs.
- **Commit messages**:
  - Follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) conventions, e.g. `fix(resources): refreshed BMM definitions in resources`, `feat(tools): added new tool for operational templates`.
- **Documentation**:
  - Use PHPDoc for public methods and classes.
  - Use Markdown for guides and other documentation.

## MCP conventions (tools, prompts, resources)

- **Tools**: in `src/Tools`, annotate public methods with `#[McpTool(name: '...')]` to expose MCP tools.
- **Prompts**: in `src/Prompts`, annotate classes with `#[McpPrompt(name: '...')]` to expose MCP prompts.
- **Resources**:
  - `Guides` provides `openehr://guides/{category}/{name}` resources and registers guide resources at startup.
  - `TypeSpecifications` provides `openehr://spec/type/{component}/{name}` resources.
  - `Terminologies` provides `openehr://terminology/{type}/{id}` resources and registers terminology resources at startup.
- **Completion providers** live in `src/CompletionProviders` and are annotated with `#[CompletionProvider]` to suggest parameter values.

## Discovering and running developer tools

Tool definitions are declared in `composer.json` under `scripts`.

### Recommended workflow (Docker dev container)

1. **Start dev containers** (uses `docker-compose.dev.yml` overrides):
   ```bash
   make up-dev
   ```

2. **Install Composer dev dependencies**:
   ```bash
   make install
   ```

3. **Run PHPUnit**:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -u 1000:1000 mcp composer test
   ```

4. **Run PHPStan**:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -u 1000:1000 mcp composer check:phpstan
   ```

5. **Run coverage (HTML)**:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -u 1000:1000 mcp composer test:coverage
   ```

### Local (non-Docker) workflow

If you already have PHP 8.4 and required extensions installed locally, you can install dev deps and run tools directly:

```bash
composer install
composer test
composer check:phpstan
composer test:coverage
```

## Additional notes

- The dev container expects your host user ID to be `1000`; adjust the `-u` flag if your UID is different.
- To run a single test class or subset, call `vendor/bin/phpunit --filter SomeTest` inside the dev container.
- Coverage requires Xdebug; the `composer test:coverage` script sets `XDEBUG_MODE` automatically.
