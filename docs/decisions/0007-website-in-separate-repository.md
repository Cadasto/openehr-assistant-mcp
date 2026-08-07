# ADR-0007 — The public website lives in its own repository

- **Status:** Accepted
- **Requirements:** REQ-N10 (install documentation is consumable without duplication)
- **Related:** [install.md](../install.md), [cadasto/openehr-assistant](https://github.com/cadasto/openehr-assistant)
  — the consuming site; which document it takes from here, and at which ref, is
  declared in its [`sources.json`](https://github.com/cadasto/openehr-assistant/blob/main/sources.json)

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

**The website lives in [cadasto/openehr-assistant](https://github.com/cadasto/openehr-assistant)**,
a documentation-only repository holding no product code, published at
`https://cadasto.github.io/openehr-assistant/`.

That is the irreversible part. Two things follow from it:

1. **Documentation stays with the product it describes.** Contributor docs, the
   SDD set and the README remain here; only user-facing presentation moves. The
   site keeps no copy of this repository's install instructions — REQ-N10 states
   the contract that makes reuse possible instead of duplication.
2. **Consumers pin a released tag, not `main`.** Work in progress here therefore
   cannot change a published page, and a breaking edit is visible as a deliberate
   version bump on the consuming side rather than an accident.

## Consequences

- **Positive:** each product owns its own prose; the site gets a short URL
  without a repository rename; site copy and server releases move independently;
  and the duplication that caused the drift is structurally impossible, because
  the site has no copy to let rot.
- **Negative:** `docs/install.md` may no longer be renamed, moved, or stripped of
  its hosted-endpoint section without breaking a published page. The consuming
  site cannot be relied on to notice — it frames the fetched document with prose
  of its own, so its checks can pass while the fetched content is impoverished.
  `tests/Content/InstallDocContractTest.php` therefore enforces REQ-N10 *here*,
  where the edit happens. Cross-repository changes now need two pull requests.
- **Neutral:** the website's own build, theme and publishing pipeline are that
  repository's concern and are documented in its `AGENTS.md`.
