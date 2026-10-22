---
kind: requirement
id: REQ-F-009
title: Dual transport (streamable-http and stdio)
status: draft
implementation: landed
---

# REQ-F-009 — Dual transport (streamable-http and stdio)

## Capability

Serve over two transports, `streamable-http` (the default) and `stdio` (for CLI and desktop clients), so the
same server runs hosted or locally.

## Acceptance criteria

- [x] The server answers MCP over `streamable-http` when started without options.
- [x] The server answers MCP over `stdio` when started with the stdio transport option.
- [x] Over HTTP, a request for a host outside the allowed list is refused.
- [x] The HTTP transport passes the [conformance suite](REQ-N-006-conformance.md).

## Out of scope

- The deprecated HTTP+SSE transport.

## Canonical specification

- [SPEC-SURFACE §4](../specifications/mcp-surface.md#transports-req-f-009)

## Notes

- Formerly `REQ-F9`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0001](../decisions/0001-attribute-driven-discovery.md), attribute-driven discovery.
