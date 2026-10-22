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

- Outbound network access MUST go only through the API client layer in `src/Apis/`, which today holds one
  client, `CkmClient`. Classes outside `src/Apis/`, and code under `public/`, MUST NOT construct an HTTP
  client or open a network stream ([ADR-0002](decisions/0002-single-ckmclient-http-boundary.md)).
- The discovery cache MUST be namespaced by `APP_VERSION`, so a release never serves capabilities cached by
  an earlier one ([ADR-0001](decisions/0001-attribute-driven-discovery.md)).

## Contracts

- Every published MCP tool MUST declare a closed input schema (`additionalProperties: false`), and an
  optional enum parameter whose default is `null` MUST list `null` among its values.
- Every tool's output MUST conform to the `outputSchema` it declares.
- The full global tool-usage policy MUST live in `resources/server-instructions.md`. Task prompts under
  `resources/prompts/` MUST NOT restate it; the shared policy block prepended to every prompt MAY restate a
  subset, for clients that drop the MCP `instructions` field
  ([ADR-0003](decisions/0003-prompt-policy-split.md)).
- Each prompt's message length MUST stay within its baseline in
  `tests/fixtures/prompt_lengths_before_shared.json`.
- `docs/install.md` MUST stay at that path, keep a section that offers the hosted endpoint and names its
  URL, and keep every relative link resolving: the public website fetches the file at a released tag
  ([ADR-0007](decisions/0007-website-in-separate-repository.md)).

## Data

- openEHR standards content (guides, specification digests, prompts, examples, terminology, type
  specifications) MUST be derived from authoritative sources: `specifications.openehr.org` on its
  `development` branch, CKM, and the published BMM schemas. It MUST NOT be written from model memory
  ([ADR-0005](decisions/0005-spec-aligned-content-retrieval.md)).
- The server holds no patient or EHR data; it serves knowledge content only.

## Known gaps

- Published tool, prompt and resource names, transport selection and the `MCP_ALLOWED_HOSTS` default are
  stable by convention; no test pins them yet, so they are not bound here.
- The test mirror (one `tests/…/*Test` per `src/` class) is a convention: `CliOptions`, `SearchTokenizer`,
  `TerminologyXmlLoader` and `CompletionProviders\Examples` have none.
- `PromptPolicySeparationTest` checks only the four explorer prompts for restated global policy.
