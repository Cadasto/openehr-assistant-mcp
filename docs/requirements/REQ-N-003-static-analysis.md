---
kind: requirement
id: REQ-N-003
title: Static analysis and code style
status: draft
implementation: landed
---

# REQ-N-003 — Static analysis and code style

## Capability

Code follows PSR-12 and passes PHPStan static analysis before merge, so it stays maintainable and reviewable.

## Acceptance criteria

- [x] Pull-request validation runs PHPStan at level 8 and fails on a finding.

## Out of scope

- An automatic formatter in CI.

## Canonical specification

- [SPEC-ENGINEERING §2](../specifications/engineering.md#static-analysis-req-n-003)
