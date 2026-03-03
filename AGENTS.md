# AI Guidelines: openEHR Assistant MCP Server

These guidelines summarize the high-level architecture, coding conventions, and developer workflows for this repository, with the goal of helping an AI agent quickly discover available tooling and work within the project structure.

## Project overview

- The openEHR Assistant MCP Server is a **TypeScript/Node.js 22** MCP server that exposes openEHR tools, prompts, and resources for MCP clients.
- It uses the `@modelcontextprotocol/sdk` (latest) to register tools, prompts, resources, and completion providers via explicit registration in `src/server.ts`.

## Repository layout (high level)

- `src/index.ts`: entrypoint; parses CLI options, creates the MCP server via `buildServer()`, and starts the selected transport (stdio or streamable-http via Express).
- `src/server.ts`: MCP server builder; instantiates all services and registers all tools, prompts, resources, and completion providers.
- `src/tools/`: MCP tool service classes (`CkmService`, `GuideService`, `TerminologyService`, `TypeSpecificationService`).
- `src/prompts/`: MCP prompt classes extending `AbstractPrompt`; each loads a markdown file from `resources/prompts/`.
- `src/resources/`: Resource reader functions (`readGuide`, `readTerminologies`, `readTypeSpecification`, `listGuideResources`).
- `src/completionProviders/`: Completion provider classes for guide names and BMM component names.
- `src/apis/`: Internal API clients (`CkmClient` wrapping Axios).
- `src/helpers/`: Utilities (`Map` for format mapping, `parseTransportOption` for CLI parsing).
- `src/constants.ts`: All app constants loaded from environment variables.
- `src/logger.ts`: Winston logger factory.
- `resources/`: Static assets — guides (markdown), BMM JSON specs, terminology XML, prompt markdown templates. **Do not change resource filenames or structure without updating the corresponding service code.**
- `tests/`: Vitest tests mirroring the `src/` structure.

## Configuration & Environment

- **Runtime**: Node.js 22 via Docker. Services: `app` (Node.js MCP server on port 3000), `ingress` (Caddy reverse proxy).
- **Dev overrides** (`.docker/docker-compose.dev.yml`): mounts source with live reload via `tsx`, exposes port 8343 via Caddy.
- **Transports**:
  - `streamable-http` (default): Express HTTP server on port 3000; Caddy proxies to it.
  - `stdio`: For CLI/Desktop clients. Run `node dist/index.js --transport=stdio` or `npm run start:stdio`.
- **Environment variables** (configure in `.env`):
  - `CKM_API_BASE_URL`: Base URL for the CKM REST API (default: `https://ckm.openehr.org/ckm/rest`).
  - `LOG_LEVEL`: Winston logging level (e.g., `debug`, `info`). Default: `info`.
  - `HTTP_TIMEOUT`: HTTP client timeout in seconds. Default: `10`.
  - `HTTP_SSL_VERIFY`: Set to `false` to disable SSL verification. Default: `true`.
  - `XDG_DATA_HOME`: Directory for application data. Default: `/tmp`.
  - `APP_ENV`: `development` / `testing` / `production`. Default: `production`.
  - `PORT`: HTTP port for the MCP server. Default: `3000`.
- **Versioning**: App version is defined in `src/constants.ts` (`APP_VERSION`).

## Coding style and conventions

- **Language**: TypeScript 5.8+ with strict mode. Target: ESM (`"type": "module"`), `NodeNext` module resolution.
- **Style**: Use functional-style utilities; prefer `readonly` and immutable data. Keep methods small.
- **Imports**: Use `.js` extension in all relative imports (required for Node ESM).
- **Error handling**: Throw plain `Error` for tool/resource errors; do not swallow exceptions.
- **Testing**: Vitest with `globals: true`. Tests live under `tests/` and mirror `src/` structure. Mock HTTP calls with `vi.fn()` / `vi.spyOn()`.
- **Commit messages**: Follow [Conventional Commits](https://www.conventionalcommits.org/) (e.g., `feat(tools): add new CKM tool`, `fix(resources): correct guide path`).
- **Branching**: Use feature branches and pull requests.

## MCP conventions (tools, prompts, resources)

- **Tools**: registered in `src/server.ts` via `server.tool(name, description, zodSchema, handler)`.
- **Prompts**: registered in `src/server.ts` via `server.prompt(name, description, handler)`; prompt classes in `src/prompts/` extend `AbstractPrompt` and load markdown from `resources/prompts/{name}.md`.
- **Resources**:
  - Static guide files: registered individually with `server.resource(name, uri, metadata, handler)`.
  - Guide template: `openehr://guides/{category}/{name}` via `ResourceTemplate` with completion callbacks.
  - Type spec template: `openehr://spec/type/{component}/{name}` via `ResourceTemplate`.
  - Terminology: `openehr://terminology` static resource.
- **Completion providers**: inline in `ResourceTemplate` `complete` callbacks; implementation in `src/completionProviders/`.

## Discovering and running developer tools

Tool definitions are in `package.json` under `scripts`:

- `npm run build` — compile TypeScript to `dist/`
- `npm start` — run compiled server (HTTP mode)
- `npm run start:stdio` — run compiled server (stdio mode)
- `npm run dev` — run with live reload via `tsx` (HTTP mode)
- `npm run dev:stdio` — run with live reload via `tsx` (stdio mode)
- `npm test` — run tests with Vitest
- `npm run test:coverage` — run tests with V8 coverage
- `npm run typecheck` — TypeScript type check without emit
- `npm run lint` — ESLint

### Recommended workflow (Docker dev container)

1. **Start dev containers**:
   ```bash
   make up-dev
   ```

2. **Install Node.js dependencies**:
   ```bash
   make install
   # or: docker compose -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml exec -u 1000:1000 app npm install
   ```

3. **Run tests (Vitest)**:
   ```bash
   make test
   # or: docker compose -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml exec -u 1000:1000 app npm test
   ```

4. **Type check**:
   ```bash
   make typecheck
   ```

5. **Lint**:
   ```bash
   make lint
   ```

### Local (non-Docker) workflow

If you have Node.js 22+ installed locally:

```bash
npm install
npm test
npm run typecheck
npm run lint
npm run dev        # development HTTP server
npm run dev:stdio  # development stdio
```

## Additional notes

- Dev container uses UID 1000 by default; adjust `-u` flag if your UID differs.
- To run a single test file: `npx vitest run tests/tools/ckmService.test.ts`.
- Coverage requires `@vitest/coverage-v8`; the `test:coverage` script sets this up automatically.

### Guides and specification alignment

- **Guide markdown style:** See `resources/guides/README.md` for header block (Scope/Purpose, Related, Keywords), section heading style, rule numbering, code blocks, and checklist style.
- When adding or editing guides (e.g. under `resources/guides/`) or prompts, keep wording aligned with the authoritative spec.
- Avoid duplicate or misplaced paragraphs in guide files.
- **Archetypes/templates**: Guides under `resources/guides/archetypes/` and `resources/guides/templates/` should stay consistent with openEHR modelling docs and ADL/OET conventions.
- **Simplified Formats**: Guides under `resources/guides/simplified_formats/` should align with the Flat and Structured JSON serialization spec.

### Clinical modelling and governance

- When adding or changing guidance on archetypes, templates, or clinical modelling, uphold: **two-level modelling**; **single-concept archetypes**; **no workflow/UI in archetypes**; **reusability and semantic correctness**; **CKM and spec alignment**.

## Learned User Preferences

- When updating guides under `resources/guides/`, prefer substantive improvements that add value; avoid trivial changes.
- Guide content is consumed by AI agents: keep it short, concise, and scannable.

## Learned Workspace Facts

- Repo tooling that can be implemented in TypeScript should live as classes/functions in `src/` with CLI entrypoints or `package.json` script entries; update AGENTS.md and related docs when changing such tooling.
- The `resources/` directory (guides, BMM, terminology, prompts) is **language-agnostic** — unchanged from the original PHP version.
