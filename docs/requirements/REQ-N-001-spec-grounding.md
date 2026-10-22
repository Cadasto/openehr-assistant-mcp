---
kind: requirement
id: REQ-N-001
title: Authoritative spec grounding
status: draft
implementation: landed
---

# REQ-N-001 — Authoritative spec grounding

## Capability

All openEHR-standard content is grounded in authoritative sources (`specifications.openehr.org`, CKM, BMM) and
never invented from model memory, because correct clinical and standards content is the product's core value.

## Acceptance criteria

- [x] The spec-lookup how-to names the authoritative sources and the cheapest-first retrieval order.
- [x] The README's claims about spec lookup match the how-to, guarded by a content test.

## Out of scope

- Content about vendor products or non-openEHR standards.

## Canonical specification

- [SPEC-CONTENT §1](../specifications/content.md#grounding-req-n-001)

## Notes

- Formerly `REQ-N1`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0005](../decisions/0005-spec-aligned-content-retrieval.md), spec-aligned content retrieval.
