---
kind: requirement
id: REQ-N-005
title: Docker-only, reproducible runtime
status: draft
implementation: landed
---

# REQ-N-005 — Docker-only, reproducible runtime

## Capability

The runtime is Docker-only and reproducible; no host PHP, Composer, or Python is assumed, so every maintainer
(WSL2 on Windows included) works in the same environment.

## Acceptance criteria

- [x] Every `make` target that builds, tests, checks, or runs the server works with only Docker on the host.
- [x] PR validation and `make ci` run the same checks.

## Out of scope

- Supporting a host-installed PHP toolchain.

## Canonical specification

- [SPEC-ENGINEERING §3](../specifications/engineering.md#docker-only-runtime-req-n-005)

## Notes

- Decision: [ADR-0004](../decisions/0004-docker-only-runtime.md), Docker-only runtime.
