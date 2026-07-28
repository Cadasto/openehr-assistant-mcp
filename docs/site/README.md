# Product website source

Public landing pages for the openEHR Assistant ecosystem (MCP server + plugin).

## Build locally

```bash
make docs-build    # output → docs-build/
make docs-check    # build, then assert the output is complete (what CI runs)
make docs-serve    # preview at http://127.0.0.1:8000
make docs-clean    # remove docs-build/ and the plugin cache
```

Uses a pinned `squidfunk/mkdocs-material` Docker image — no host Python required.
The build is strict: any MkDocs warning (broken link, dead nav entry, unknown
config key) fails it.

## Content layout

| Path | Role |
|------|------|
| `pages/index.md` | Landing page (custom home template) |
| `pages/mcp-server.md` | Server product overview |
| `pages/plugin.md` | Plugin overview (links to plugin repo for install detail) |
| `pages/install.md` | Symlink (`../../install.md`) → [`docs/install.md`](../install.md), the canonical install doc |
| `pages/stylesheets/`, `pages/assets/` | Brand CSS and logo — **must** stay inside `pages/`, MkDocs only publishes files under `docs_dir` |
| `hooks/link_fix.py` | Rewrites the symlinked page's links to unpublished contributor docs |

Contributor SDD docs in `docs/` (requirements, architecture, traceability) are
**not** part of the public site at all — `docs_dir` is `pages/`, so they are
neither copied nor indexed by search.

## Publish (GitHub Pages)

CI workflow: [`.github/workflows/docs-site.yml`](../../.github/workflows/docs-site.yml)

**One-time repository setup** (required before the first deploy):

1. Open **Settings → Pages → Build and deployment**
2. Set **Source** to **GitHub Actions**

Pull requests build and verify the site; pushes to `main` additionally deploy it
via the official Pages artifact (`upload-pages-artifact` + `deploy-pages`).

**Published URL (live after the first deploy):**
[https://cadasto.github.io/openehr-assistant-mcp/](https://cadasto.github.io/openehr-assistant-mcp/)

### URL options

GitHub Pages **project sites** serve at `https://<org>.github.io/<repo>/` — the
path segment is the repository name and cannot be shortened on its own.

| Desired URL | What it requires |
|-------------|------------------|
| `…/openehr-assistant-mcp/` | Current repo name — **no change** |
| `…/openehr-assistant/` | Rename the repository to `openehr-assistant` |
| `assistant.example.com` | **Custom domain** — DNS CNAME/A record + **Settings → Pages → Custom domain**; update `site_url` in [`mkdocs.yml`](mkdocs.yml) to match |

The project keeps the name `openehr-assistant-mcp`; a custom domain can be
adopted later without renaming.

→ [ADR-0007](../decisions/0007-product-website-mkdocs-github-pages.md)
