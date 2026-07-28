# Plan — Product website (REQ-N10, ADR-0007)

**Implements:** REQ-N10 · [ADR-0007](../decisions/0007-product-website-mkdocs-github-pages.md)  
**Branch:** `feat/product-website`  
**Verification:** `make docs-build`; `make spec-check`; `make ci`

## Definition of Done

- [x] MkDocs site under `docs/site/` builds to `docs-build/`
- [x] `docs/install.md` reused via symlink (no duplicated install prose)
- [x] GitHub Actions workflow deploys via Pages artifact (`deploy-pages`)
- [x] REQ-N10, ADR-0007, traceability updated; `make spec-check` green

---

### Task 1: Scaffold `docs/site/` and gitignore

- [x] Add `/docs-build/` to `.gitignore`
- [x] Create `docs/site/mkdocs.yml`, placeholder pages, symlink `pages/install.md`
- [x] Add `docs-build`, `docs-serve`, `docs-clean` Makefile targets
- [x] Run `make docs-build` — expect `docs-build/index.html`

### Task 2: Cadasto theme and home layout

- [x] Add `docs/site/stylesheets/cadasto.css` (palette + fonts)
- [x] Add `docs/site/overrides/home.html`, expand `pages/index.md`
- [x] Add placeholder `docs/site/assets/logo.svg`

### Task 3: Product content pages

- [x] Write `mcp-server.md` and `plugin.md`
- [x] Add `docs/site/README.md` with Pages setup note

### Task 4: GitHub Actions Pages artifact deploy

- [x] Create `.github/workflows/docs-site.yml` (build job + deploy job)
- [x] Document one-time Pages → GitHub Actions setting in `docs/site/README.md`

### Task 5: SDD chain and README links

- [x] Add REQ-N10 to `requirements.md`; architecture section; traceability
- [x] Update root `README.md`, `docs/README.md`, `CHANGELOG.md`
- [x] Run `make spec-check` and `make ci`
