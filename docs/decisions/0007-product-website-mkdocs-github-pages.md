# ADR-0007 — Product website via MkDocs Material and GitHub Actions Pages

- **Status:** Accepted
- **Requirements:** REQ-N10
- **Related:** [docs/site/README.md](../site/README.md), [install.md](../install.md)

## Context

End users need a discoverable product presence for the openEHR Assistant ecosystem
(MCP server and user-facing plugin): what it is, how the two parts fit together,
and how to install. Contributor SDD specs (`requirements.md`, traceability, ADRs)
must stay separate from public-facing pages. Install instructions already live in
`docs/install.md` and must not be duplicated.

## Decision

1. **Source:** MkDocs Material under `docs/site/`; product Markdown in
   `docs/site/pages/`. Reuse `docs/install.md` via symlink into `pages/`.
2. **Build:** Docker image `squidfunk/mkdocs-material:latest` — `make docs-build`
   writes to gitignored `docs-build/`; `make docs-serve` for local preview.
3. **Publish:** GitHub Actions uploads the build as a Pages artifact and deploys
   with `actions/deploy-pages` (not an orphan publish branch). Repository Pages
   source must be set to **GitHub Actions** once in repo settings.
4. **Branding:** Cadasto palette (navy `#0A1A66`, accent blue `#5CB2FF`, teal
   `#24E363`); Fira Sans Medium titles, Roboto Regular body.
5. **Scope:** Landing, MCP server overview, plugin overview, install (symlink).
   Plugin install detail remains in the plugin repository (linked, not copied).

## Consequences

- **Positive:** Markdown-first pages readable on GitHub; single install source;
  local and CI builds are identical; no long-lived `build-doc` branch to maintain.
- **Negative:** One-time manual step — enable Pages → GitHub Actions in repo
  settings before the first deploy succeeds.
- **Neutral:** Docs build uses Docker like the PHP stack but does not require the
  PHP dev container.

## GitHub Pages setup (one-time)

In the repository **Settings → Pages → Build and deployment**:

- **Source:** GitHub Actions

After the first successful workflow run, the site is available at
`https://cadasto.github.io/openehr-assistant-mcp/`.

### Published URL

GitHub Pages **project sites** serve at `https://<org>.github.io/<repo>/` — the
path segment is the **repository name** and cannot be shortened independently.

| Desired URL | What it requires |
|-------------|------------------|
| `…/openehr-assistant-mcp/` | Current repo name — **no change** |
| `…/openehr-assistant/` | Rename repository to `openehr-assistant` |
| `assistant.example.com` (or similar) | **Custom domain** — DNS CNAME/A record + **Settings → Pages → Custom domain**; update `site_url` in `docs/site/mkdocs.yml` to match |

This project keeps the repository name **`openehr-assistant-mcp`**. A custom
domain can be added later without renaming the repo.
