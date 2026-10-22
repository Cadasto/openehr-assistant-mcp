---
kind: requirement
id: REQ-N-009
title: Published MCP tool schemas validated in CI
status: draft
implementation: landed
---

# REQ-N-009 — Published MCP tool schemas validated in CI

## Capability

Every published MCP tool schema is validated in CI, inputs and outputs alike. Since `mcp/sdk` 0.9 the SDK also
validates tool output against `outputSchema` at runtime, but it does so by answering the client with a failed
(`isError`) tool call, so drift that reaches production breaks the tool for every caller. CI catches it before
it ships.

## Acceptance criteria

- [x] A tool whose input schema is open, or whose numeric parameter is unbounded, fails the test suite.
- [x] A search tool whose result envelope is not closed with both members required fails the test suite.
- [x] A tool whose output does not match its declared output schema fails the test suite, including through the
  SDK's own validator.

## Out of scope

- Validating schemas of capabilities other than tools.

## Canonical specification

- [SPEC-TOOLS §6](../specifications/knowledge-tools.md#tool-schemas-req-n-009)

## Notes

- Authoring recipe: [conventions.md](../conventions.md#mcp-capabilities-authoring).
