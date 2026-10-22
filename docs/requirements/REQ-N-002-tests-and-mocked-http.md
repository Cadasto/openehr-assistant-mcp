---
kind: requirement
id: REQ-N-002
title: Mirrored tests and mocked external HTTP
status: draft
implementation: landed
---

# REQ-N-002 — Mirrored tests and mocked external HTTP

## Capability

Application classes have mirrored tests, and external HTTP (CKM) is mocked, never called live in tests, so the
suite is deterministic and runs offline.

## Acceptance criteria

- [x] The whole suite passes with no network access.
- [x] Every test that touches CKM mocks the one CKM client.
- [x] A new application class arrives with its mirrored test.

## Out of scope

- Live smoke tests against CKM; the [conformance suite](REQ-N-006-conformance.md) covers the running server.

## Canonical specification

- [SPEC-ENGINEERING §1](../specifications/engineering.md#tests-and-http-boundary-req-n-002)

## Notes

- Decision: [ADR-0002](../decisions/0002-single-ckmclient-http-boundary.md), one CKM HTTP boundary.
