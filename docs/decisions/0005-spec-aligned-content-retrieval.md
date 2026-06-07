# ADR-0005 — Authoritative, cheapest-first specification retrieval

- **Status:** Accepted
- **Requirements:** REQ-N1 (spec alignment, no guessing)
- **Related:** [AGENTS.md — spec lookup](../../AGENTS.md), `resources/guides/howto/spec-lookup.md`

## Context

The server's value is correct openEHR knowledge. Authoring guides, prompts, BMM
JSON, terminology, and AQL notes from a language model's memory risks inventing
class names, attributes, invariants, or grammar — subtle errors that erode trust
and are hard to detect downstream. The authoritative specifications are published
at `specifications.openehr.org` in several representations of differing cost and
completeness.

## Decision

When authoring or editing any artefact that must track the openEHR standards, do
not rely on training memory. Retrieve from authoritative sources, preferring the
cheapest representation that answers the question:

1. **Site index** — `https://specifications.openehr.org/llms.txt` to resolve doc
   phrases to canonical URLs and confirm the current release tag.
2. **Markdown twin** — every `*.html` spec page has a `.md` counterpart; prefer
   it for prose. *Caveat:* the `.md` omits per-class attribute/function/invariant
   tables.
3. **Class detail** — for per-class tables, use the BMM-backed
   `type_specification_get` tool, or fall through to the HTML page.
4. **Structured APIs** — `/api/components.json`, `/api/classes.json`,
   `/api/releases.json`.
5. **Track the `development` branch** (`releases/XX/development/`), not `latest`
   or a pinned tag, unless a fixed release is explicitly required.

This policy is surfaced to agents three ways: server `instructions`
(Spec-Lookup-First / Digest-First / Examples-First), the
`guide_get(category="howto", name="spec-lookup")` how-to, and
[AGENTS.md](../../AGENTS.md).

## Consequences

- **Positive:** standards content stays grounded and verifiable; cheapest-first
  ordering keeps token/latency cost down.
- **Negative:** authoring requires a network round-trip to the spec site rather
  than answering from memory — slower, but correct by construction.
- The full fall-through order and failure modes live in the `spec-lookup` how-to
  guide, which is the operational source of truth.
