# Plan — Product website (REQ-N10, ADR-0007)

**Implements:** REQ-N10 · [ADR-0007](../decisions/0007-product-website-mkdocs-github-pages.md)  
**Branch:** `feat/product-website`  
**Verification:** `make docs-check`; `make spec-check`; `make ci`  
**Out of scope:** plugin install detail (stays in the plugin repository), a
custom domain, and publishing any contributor SDD doc.

## Definition of Done

- [x] MkDocs site under `docs/site/` builds to `docs-build/`
- [x] `docs/install.md` reused via symlink (no duplicated install prose)
- [x] GitHub Actions workflow deploys via Pages artifact (`deploy-pages`)
- [x] REQ-N10, ADR-0007, traceability updated; `make spec-check` green
- [ ] Merged to `main`
- [ ] **Settings → Pages → Source: GitHub Actions** enabled (one-time)
- [ ] First deploy verified at <https://cadasto.github.io/openehr-assistant-mcp/>
- [ ] Plan archived under `plans/archive/`, updating the `plans:` path in
      [`traceability.yaml`](../traceability.yaml) in the same commit

---

### Task 1: Scaffold `docs/site/` and gitignore

- [x] Add `/docs-build/` and the plugin cache to `.gitignore`
- [x] Create `docs/site/mkdocs.yml`, placeholder pages, symlink `pages/install.md`
- [x] Add `docs-build`, `docs-check`, `docs-serve`, `docs-clean` Makefile targets
- [x] Run `make docs-check`

### Task 2: Cadasto theme and home layout

- [x] Add `docs/site/pages/stylesheets/cadasto.css` (palette + fonts)
- [x] Add `docs/site/overrides/home.html`, expand `pages/index.md`
- [x] Add placeholder mark `docs/site/pages/assets/logo.svg` (logo + favicon)

### Task 3: Product content pages

- [x] Write `mcp-server.md` and `plugin.md`
- [x] Add `docs/site/README.md` with Pages setup and URL options

### Task 4: GitHub Actions Pages artifact deploy

- [x] Create `.github/workflows/docs-site.yml` (build job + deploy job)
- [x] Verify the site on pull requests; deploy only from `main`
- [x] Document the one-time Pages → GitHub Actions setting in `docs/site/README.md`

### Task 5: SDD chain and README links

- [x] Add REQ-N10 to `requirements.md`; architecture section; traceability
- [x] Update root `README.md`, `docs/README.md`, `CHANGELOG.md`
- [x] Run `make spec-check` and `make ci`

### Task 6: Review remediation

- [x] Move `stylesheets/` and `assets/` inside `docs_dir` — they were never
      published, so the brand CSS and logo 404'd
- [x] Scope the `--md-*` overrides to `[data-md-color-scheme="slate"]`; on
      `:root` they lose to the theme's own declarations on `<body>`
- [x] `strict: true` + `validation:` in `mkdocs.yml`; `make docs-check` asserts
      the output, since neither strict mode nor `--strict` can see a missing asset
- [x] Rewrite `hooks/link_fix.py` to rewrite links generically and raise on
      anything unresolvable, instead of a silent exact-string allowlist
- [x] Pin the MkDocs image; run it as the invoking user; per-job workflow
      permissions and a `main`-only deploy guard
- [x] Self-host web fonts via the Material `privacy` plugin
