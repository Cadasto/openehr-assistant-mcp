---
kind: specification
spec: SPEC-CONTENT
status: draft
mode: implementation-aligned
---

# SPEC-CONTENT — bundled content

> **Normative.** Uses RFC-2119 keywords: **MUST/SHALL** (absolute), **SHOULD** (strong, exceptions need a reason),
> **MAY** (optional). Statements without a keyword are *informative*. *Implementation-aligned:* each section states
> what the shipped content and its guard tests enforce, and a change to either updates its section in the same
> pull request.

## §1 — Grounding in authoritative sources

<a id="grounding-req-n-001"></a>

The openEHR standards content the server ships (guides, specification digests, prompts, examples, terminology
and type specifications) MUST be derived from authoritative sources: `specifications.openehr.org` on its
`development` branch, CKM, and the published BMM schemas. It MUST NOT be written from model memory.

Retrieval SHOULD take the cheapest representation first: the `llms.txt` index, then the Markdown twin, then the
structured `/api/*.json`, then the HTML page.

**Implements:** REQ-N-001

## §2 — Content economy

<a id="content-economy-req-n-007"></a>

Guide and prompt text SHOULD be short and scannable, written for agents that read it under a token budget.

Each prompt's message length MUST stay within its recorded baseline. A prompt that outgrows its baseline MUST
update the baseline in the same change, so the growth is a reviewed decision.

**Implements:** REQ-N-007

## §3 — Install guide contract

<a id="install-guide-req-n-010"></a>

The install guide MUST stay at `docs/install.md`. It MUST keep a section that offers the hosted endpoint and
names its URL, and every relative link in it MUST resolve inside the repository.

Other documentation, the public website included, SHOULD consume this file at a released tag rather than keep a
copy of it.

**Implements:** REQ-N-010
