---
kind: requirement
id: REQ-N-007
title: Concise, AI-facing content
status: draft
implementation: landed
---

# REQ-N-007 — Concise, AI-facing content

## Capability

Guide and prompt content is concise and scannable, optimised for AI context economy, because agents consume it
under token budgets.

## Acceptance criteria

- [x] A prompt that grows past its recorded size baseline fails the test suite until the baseline is updated on
  purpose.

## Out of scope

- Compressing or summarising content at request time.

## Canonical specification

- [SPEC-CONTENT §2](../specifications/content.md#content-economy-req-n-007)

## Notes

- Formerly `REQ-N7`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0003](../decisions/0003-prompt-policy-split.md), global policy split from task prompts.
