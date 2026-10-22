---
kind: requirement
id: REQ-N-010
title: Install docs canonical and externally consumable
status: draft
implementation: landed
---

# REQ-N-010 — Install docs canonical and externally consumable

## Capability

`docs/install.md` is the single source for installing this server, and external documentation consumes the
file rather than holding a copy. The public website publishes it by fetching this file at a released tag, so a
rename, a dropped hosted-endpoint section, or a dangling link ships a broken page.

## Acceptance criteria

- [x] Moving or renaming the install guide fails the test suite.
- [x] Removing the hosted-endpoint section, or its URL, fails the test suite.
- [x] A relative link in the install guide that does not resolve fails the test suite.

## Out of scope

- The website's own copy and layout.

## Canonical specification

- [SPEC-CONTENT §3](../specifications/content.md#install-guide-req-n-010)

## Notes

- Decision: [ADR-0007](../decisions/0007-website-in-separate-repository.md), website in its own repository.
