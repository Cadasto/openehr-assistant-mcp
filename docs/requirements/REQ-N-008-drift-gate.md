---
kind: requirement
id: REQ-N-008
title: Machine-checked traceability drift gate
status: draft
implementation: landed
---

# REQ-N-008 — Machine-checked traceability drift gate

## Capability

The requirement, specification, code, and test traceability map is machine-validated in CI; a missing
artefact, a dangling path, or an index/map disagreement fails the build, so the SDD chain cannot rot silently.

## Acceptance criteria

- [x] PR validation and `make ci` fail when the drift gate fails.
- [x] A record whose cited path is missing, or whose canonical anchor does not resolve, fails the gate.
- [x] A requirement file without a map record, or a record without an index row, fails the gate.

## Out of scope

- Checking that a test actually exercises the requirement it is mapped to; review covers that.

## Canonical specification

- [SPEC-ENGINEERING §4](../specifications/engineering.md#drift-gate-req-n-008)

## Notes

- Formerly `REQ-N8`; renumbered once, in the move to the sdd 0.15.0 layout.
- [ADR-0006](../decisions/0006-machine-checked-traceability.md) records the first, PHP implementation of this
  gate; it is deprecated now that the sdd plugin's gate covers the same checks.
