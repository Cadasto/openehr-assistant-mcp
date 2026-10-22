---
kind: requirement
id: REQ-F-007
title: Resource exposure via openehr:// URIs
status: draft
implementation: landed
---

# REQ-F-007 — Resource exposure via openehr:// URIs

## Capability

Expose retrievable resources for guides, examples, type specifications, and terminology via stable
`openehr://` URIs, so a client can attach openEHR content to a conversation without calling a tool.

## Acceptance criteria

- [x] A client reads a guide, an example, a type specification, and the terminology through their `openehr://`
  URIs.
- [x] A URI that clients already use keeps working across releases.

## Out of scope

- Resource subscriptions and change notifications.

## Canonical specification

- [SPEC-SURFACE §2](../specifications/mcp-surface.md#resources-req-f-007)
