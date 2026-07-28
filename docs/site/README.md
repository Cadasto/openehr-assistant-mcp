# Product website source

Public landing pages for the openEHR Assistant ecosystem (MCP server + plugin).

## Build locally

```bash
make docs-build    # output → docs-build/
make docs-serve    # preview at http://127.0.0.1:8000
make docs-clean    # remove docs-build/
```

Uses the `squidfunk/mkdocs-material` Docker image — no host Python required.

## Content layout

| Path | Role |
|------|------|
| `pages/index.md` | Landing page (custom home template) |
| `pages/mcp-server.md` | Server product overview |
| `pages/plugin.md` | Plugin overview (links to plugin repo for install detail) |
| `pages/install.md` | Symlink → [`../install.md`](../install.md) (canonical install) |

Contributor SDD docs in `docs/` (requirements, architecture, traceability) are
**not** included in the public site.

## Publish (GitHub Pages)

CI workflow: [`.github/workflows/docs-site.yml`](../../.github/workflows/docs-site.yml)

**One-time repository setup** (required before the first deploy):

1. Open **Settings → Pages → Build and deployment**
2. Set **Source** to **GitHub Actions**

The workflow builds on push to `main` (when site paths change) and deploys via
the official Pages artifact (`upload-pages-artifact` + `deploy-pages`).

**Published URL:** [https://cadasto.github.io/openehr-assistant-mcp/](https://cadasto.github.io/openehr-assistant-mcp/)

A shorter path (e.g. `/openehr-assistant/`) is **not** available without renaming
this repository — GitHub Pages project URLs always mirror the repo name. A custom
domain is the alternative; see [ADR-0007](../decisions/0007-product-website-mkdocs-github-pages.md#published-url).

→ [ADR-0007](../decisions/0007-product-website-mkdocs-github-pages.md)
