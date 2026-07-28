# ADR-0007 — Product website via MkDocs Material and GitHub Actions Pages

- **Status:** Accepted
- **Requirements:** REQ-N10 (public product website)
- **Related:** [docs/site/README.md](../site/README.md), [install.md](../install.md)

## Context

End users need a discoverable product presence for the openEHR Assistant ecosystem
(MCP server and user-facing plugin): what it is, how the two parts fit together,
and how to install. Contributor SDD specs (`requirements.md`, traceability, ADRs)
must stay separate from public-facing pages. Install instructions already live in
`docs/install.md`; the site has to reuse that file rather than keep a second copy
that drifts.

## Decision

1. **Source:** MkDocs Material under `docs/site/`; product Markdown in
   `docs/site/pages/`. `docs/install.md` is symlinked into `pages/` so there is
   exactly one install source, and a build hook rewrites its links to contributor
   docs — which are not published — to their GitHub URLs.
2. **Build:** the pinned image `squidfunk/mkdocs-material:9.7.6` via
   `make docs-build`, writing to gitignored `docs-build/`. The build is strict
   (warnings fail), and `make docs-check` additionally asserts that the published
   output is complete.
3. **Publish:** GitHub Actions runs `make docs-check` on pull requests; on `main`
   it uploads the result as a Pages artifact and deploys it with
   `actions/deploy-pages`, not an orphan publish branch.
4. **Branding:** the Cadasto palette and typography, defined in
   [`pages/stylesheets/cadasto.css`](../site/pages/stylesheets/cadasto.css). Web
   fonts are self-hosted at build time by the Material `privacy` plugin, so the
   published site issues no third-party requests.
5. **Scope:** landing, MCP server overview, plugin overview, install (symlink).
   Full plugin install detail stays in the plugin repository — the site may
   repeat a short quick-start block, but links out for anything longer.
6. **URL:** this is a GitHub Pages *project site*, so it serves at
   `https://<org>.github.io/<repo>/` and the path segment is the repository name.
   The project keeps the name `openehr-assistant-mcp`; a custom domain can be
   adopted later without renaming. Operator steps and the URL options live in
   [docs/site/README.md](../site/README.md).

## Consequences

- **Positive:** Markdown-first pages readable on GitHub; a single install source;
  local and CI builds agree, because both run the same pinned image through the
  same `make` target; broken output is caught on the pull request rather than
  after it is published; no long-lived `build-doc` branch to maintain.
- **Negative:** one-time manual step — Pages source must be set to GitHub Actions
  in repo settings before the first deploy succeeds. Self-hosting fonts means the
  build fetches them at build time, so it is not fully offline-capable.
- **Neutral:** the docs build uses Docker like the PHP stack, but does not
  require the PHP dev container.
