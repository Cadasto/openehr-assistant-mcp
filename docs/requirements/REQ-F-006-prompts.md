---
kind: requirement
id: REQ-F-006
title: Guided MCP prompts
status: draft
implementation: landed
---

# REQ-F-006 — Guided MCP prompts

## Capability

Provide guided MCP prompts for explaining, designing or reviewing, and exploring openEHR artefacts (archetypes,
templates, AQL, simplified formats, terminology, type specifications), plus ADL syntax fixing and archetype
translation, so a client can start a well-scoped openEHR task in one step.

## Acceptance criteria

- [x] A client lists and invokes each of the fourteen prompts the specification names.
- [x] Each prompt substitutes its arguments into its message bodies.
- [x] Prompt bodies carry task-specific guidance; global policy stays in the
  [server instructions](REQ-F-010-server-instructions.md), apart from the shared policy block the
  specification allows.

## Out of scope

- Running the task the prompt sets up; the client's model does that.

## Canonical specification

- [SPEC-SURFACE §1](../specifications/mcp-surface.md#prompts-req-f-006)

## Notes

- Formerly `REQ-F6`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0003](../decisions/0003-prompt-policy-split.md), global policy split from task prompts.
