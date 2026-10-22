---
kind: specification
spec: SPEC-SURFACE
status: draft
mode: implementation-aligned
---

# SPEC-SURFACE — MCP surface and runtime

> **Normative.** Uses RFC-2119 keywords: **MUST/SHALL** (absolute), **SHOULD** (strong, exceptions need a reason),
> **MAY** (optional). Statements without a keyword are *informative*. *Implementation-aligned:* each section states
> what the shipped server does, and a change to that behaviour updates its section in the same pull request.

## §1 — Guided prompts

<a id="prompts-req-f-006"></a>

The server MUST publish these prompts: the explorers `ckm_explorer`, `guide_explorer`, `terminology_explorer`
and `type_specification_explorer`; `explain_archetype`, `explain_template`, `explain_aql` and
`explain_simplified_format`; `design_or_review_archetype`, `design_or_review_template`, `design_or_review_aql`
and `design_or_review_simplified_format`; and `fix_adl_syntax` and `translate_archetype_language`.

Each prompt MUST take its message bodies from the markdown prompt files bundled with the server.

**Implements:** REQ-F-006

## §2 — Resources

<a id="resources-req-f-007"></a>

The server MUST expose the resource templates `openehr://guides/{category}/{name}`,
`openehr://examples/{kind}/{name}` and `openehr://spec/type/{component}/{name}`, and the resource
`openehr://terminology`.

A published `openehr://` URI MUST NOT change its shape; a new URI MAY be added beside it.

**Implements:** REQ-F-007

## §3 — Argument completion

<a id="completion-req-f-008"></a>

The server MUST offer argument completion for guide names, example names and specification components.

**Implements:** REQ-F-008

## §4 — Transports

<a id="transports-req-f-009"></a>

The server MUST serve MCP over `streamable-http` by default, and over `stdio` when it is started with
`--transport=stdio` or `--transport stdio`.

Over `streamable-http` it MUST answer only requests whose host is listed in `MCP_ALLOWED_HOSTS`, which defaults
to the loopback names `localhost`, `127.0.0.1` and `[::1]`.

**Implements:** REQ-F-009

## §5 — Server instructions

<a id="server-instructions-req-f-010"></a>

The server MUST advertise always-on instructions in the MCP `instructions` field, carrying the global
tool-usage policy: Guide-First, Spec-Lookup-First, Digest-First and Examples-First.

The full global policy MUST live only in those instructions, and prompt message bodies MUST NOT restate it. The
one exception is a shared policy block prepended to every prompt, which MAY restate a subset for clients that do
not pass `instructions` on to the model.

When the instructions file is missing or unreadable, the server MUST log a warning and start without
instructions rather than fail.

**Implements:** REQ-F-010

## §6 — Discovery cache

<a id="discovery-cache-req-n-004"></a>

Capability discovery MUST be cached on disk between boots, so attribute scanning does not repeat on every start.

The cache MUST be namespaced by the application version, so a version bump invalidates the entries of the
previous version.

Capabilities MUST load eagerly, so a broken capability fails when the server is built rather than on first use.

**Implements:** REQ-N-004

## §7 — MCP conformance

<a id="conformance-req-n-006"></a>

The server MUST pass the official MCP conformance suite over `streamable-http`, apart from the scenarios listed
in the expected-failures baseline.

A baseline entry whose scenario now passes MUST be removed in the change that makes it pass.

**Implements:** REQ-N-006
