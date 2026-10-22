---
kind: requirement
id: REQ-F-010
title: Global server instructions
status: draft
implementation: landed
---

# REQ-F-010 — Global server instructions

## Capability

Publish always-on server instructions encoding the global tool-usage policy (Guide-First, Spec-Lookup-First,
Digest-First, Examples-First), so every client session starts with the same discipline.

## Acceptance criteria

- [x] A client receives the instructions during initialisation.
- [x] The full global policy appears in the instructions and in no prompt body, apart from the shared policy
  block the specification allows.
- [x] A missing instructions file degrades to a logged warning, not a failed start.

## Out of scope

- Per-client or per-user policy variants.

## Canonical specification

- [SPEC-SURFACE §5](../specifications/mcp-surface.md#server-instructions-req-f-010)

## Notes

- Formerly `REQ-F10`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0003](../decisions/0003-prompt-policy-split.md), global policy split from task prompts.
