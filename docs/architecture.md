---
kind: constitution
---

# Architecture

What every change in this repository must respect. Each sentence below with MUST or MUST NOT binds;
everything else in `docs/` describes the code as it is and does not bind. The code leads: a change that
alters behaviour brings the documents that describe it up to date in the same change. Amend this file only
through `/sdd-specify`, with the reason in the commit body or an ADR. Where each component lives, and what
it serves, is in [components.md](components.md).

## Boundaries

- Outbound network access MUST go only through `src/Apis/CkmClient`
  ([ADR-0002](decisions/0002-single-ckmclient-http-boundary.md)). Every other class under `src/`, and code
  under `public/`, MUST NOT construct an HTTP transport (a Guzzle or PSR-18 client, Symfony HttpClient,
  cURL) or open a network stream; they reach CKM by calling `CkmClient`.
- The discovery cache MUST be namespaced by `APP_VERSION`, so a release never serves capabilities cached by
  an earlier one ([ADR-0001](decisions/0001-attribute-driven-discovery.md)).

## Contracts

- Every published MCP tool MUST declare a closed input schema (`additionalProperties: false`). A published
  input `enum` MUST NOT hold `null`, and an optional enum parameter MUST be published with `type: string`
  and no `default`, so a client omits it for "no filter" and an explicit `null` is rejected.
- Every tool's output MUST conform to the `outputSchema` it declares.
- The full global tool-usage policy MUST live in `resources/server-instructions.md`. Task prompts under
  `resources/prompts/` MUST NOT restate it; the shared policy block prepended to every prompt MAY restate a
  subset, for clients that drop the MCP `instructions` field
  ([ADR-0003](decisions/0003-prompt-policy-split.md)).
- Each prompt template under `resources/prompts/` MUST stay within its character and word budget in
  `tests/fixtures/prompt_lengths_before_shared.json`.
- `docs/install.md` MUST stay at that path, keep a section that offers the hosted endpoint and names its
  URL, and keep every relative link resolving: the public website fetches the file at a released tag
  ([ADR-0007](decisions/0007-website-in-separate-repository.md)).

## Data

- The spec-lookup how-to (`resources/guides/howto/spec-lookup.md`) MUST direct specification lookups to
  the `development` branch of `specifications.openehr.org`
  ([ADR-0005](decisions/0005-spec-aligned-content-retrieval.md)).
- The server holds no patient or EHR data; it serves knowledge content only.

## Known gaps

- Published tool, prompt and resource names, transport selection and the `MCP_ALLOWED_HOSTS` default are
  stable by convention; no test pins them yet, so they are not bound here.
- The test mirror (one `tests/…/*Test` per `src/` class) is a convention: `CliOptions`, `SearchTokenizer`,
  `TerminologyXmlLoader` and `CompletionProviders\Examples` have none.
- Deriving openEHR standards content (guides, digests, prompts, examples, terminology, type specifications)
  from authoritative sources (the specifications' `development` branch, CKM, the published BMM schemas)
  rather than model memory is the rule of [ADR-0005](decisions/0005-spec-aligned-content-retrieval.md);
  review enforces it, since no test can, so it is not bound here.
