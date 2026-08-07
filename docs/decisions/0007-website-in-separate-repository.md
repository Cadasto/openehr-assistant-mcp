# ADR-0007 — The public website lives in its own repository

- **Status:** Accepted
- **Requirements:** REQ-N10 (install documentation is consumable without duplication)
- **Related:** [install.md](../install.md), [cadasto/openehr-assistant](https://github.com/cadasto/openehr-assistant)

## Context

End users need a discoverable product presence for the openEHR Assistant. The
obvious home was this repository, and a full MkDocs site was built here first.

That attempt exposed the flaw. The site documents **two** products — this server
and the user-facing [plugin](https://github.com/cadasto/openehr-assistant-plugin)
— so hosting it here made this repository the owner of another product's copy.
It could not see the plugin repository, and the page describing the plugin drifted
to seven skills and six commands while the plugin actually shipped eight and
three. Nothing failed; the page was simply wrong.

Two lesser problems came with it. A GitHub Pages project site serves at
`https://<org>.github.io/<repo>/`, so the published URL would have carried this
repository's name, `openehr-assistant-mcp`, and could not be shortened without
renaming the repository. And every change of site copy would have queued behind
this repository's PR validation — PHPStan, PHPUnit, conformance — for no benefit.

## Decision

1. **The website lives in [cadasto/openehr-assistant](https://github.com/cadasto/openehr-assistant)**,
   a documentation-only repository. It holds no product code, and it publishes at
   `https://cadasto.github.io/openehr-assistant/`.
2. **This repository keeps its install documentation canonical.**
   [`docs/install.md`](../install.md) stays the single source for how to run this
   server; the website consumes it and must never hold a second copy.
3. **Consumers pin a ref.** The website fetches `docs/install.md` at a released
   tag, not at `main`, so work in progress here cannot change a published page.
   That makes the file's path and its release tags part of a contract with an
   external consumer.
4. **Product-specific documentation stays with its product.** Contributor docs,
   the SDD set, and the README remain here. Only user-facing presentation moves.

## Consequences

- **Positive:** each product owns its own prose; the site gets a short URL
  without a repository rename; site copy and server releases move independently;
  and the duplication that caused the drift is structurally impossible, because
  the site has no copy to let rot.
- **Negative:** `docs/install.md` may no longer be renamed, moved, or have its
  hosted-endpoint details restructured without breaking a published page. A guard
  test pins that contract so the break is caught here rather than downstream.
  Cross-repository changes now need two pull requests.
- **Neutral:** the website's own build, theme and publishing pipeline are that
  repository's concern and are documented in its `AGENTS.md`.
