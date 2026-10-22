---
kind: requirement
id: REQ-F-005
title: Type specification lookup (BMM)
status: draft
implementation: landed
---

# REQ-F-005 — Type specification lookup (BMM)

## Capability

Look up Reference Model, Archetype Model, and BASE type specifications, including per-class attribute,
function, and invariant detail, so an agent checks a class against the published model rather than memory.

## Acceptance criteria

- [x] An agent searches the type specifications by name pattern and keyword.
- [x] An agent fetches one class with its attributes, functions, and invariants.
- [x] A search reports every match, and its total equals the number of matches returned.

## Out of scope

- Validating archetypes or instances against the model.

## Canonical specification

- [SPEC-TOOLS §5](../specifications/knowledge-tools.md#type-specifications-req-f-005)

## Notes

- Formerly `REQ-F5`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0005](../decisions/0005-spec-aligned-content-retrieval.md), spec-aligned content retrieval.
