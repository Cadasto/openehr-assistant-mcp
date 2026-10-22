---
kind: requirement
id: REQ-N-006
title: MCP conformance over HTTP
status: draft
implementation: landed
---

# REQ-N-006 — MCP conformance over HTTP

## Capability

The server passes the official MCP conformance suite over HTTP, so it interoperates with arbitrary MCP clients.

## Acceptance criteria

- [x] `make conformance` passes against the running dev stack.
- [x] Every expected failure is listed in the baseline with its reason, and a scenario that starts passing
  leaves the baseline.

## Out of scope

- Conformance over `stdio`.

## Canonical specification

- [SPEC-SURFACE §7](../specifications/mcp-surface.md#conformance-req-n-006)
