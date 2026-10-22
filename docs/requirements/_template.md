<!-- Template: a single requirement. Authored by /sdd-specify. Fill the <...> placeholders. Delete this comment in the file you create.
     A requirement states WHAT + acceptance. It carries NO file paths and NO implementation detail. -->
---
kind: requirement
id: <REQ-AREA-NNN>
title: <short capability name>
status: draft # spec stability: draft | stable | deprecated (draft is binding now)
implementation: proposed # build status: proposed | planned | in_progress | partial | landed | shipped | deferred | retired
---

# <REQ-AREA-NNN> — <capability name>

## Capability

<What must the system deliver, and why? One or two sentences of WHAT + WHY. No file paths, no how-to.>

## Acceptance criteria

- [ ] <observable, testable condition that proves the capability is delivered>
- [ ] <the negative space: an input or state the capability must refuse or fail closed on, with the intended failure behaviour as an observable outcome — the normative *how* lives in the `SPEC §`>
- [ ] <…>

## Out of scope

- <state explicitly what this requirement does NOT cover>

## Canonical specification

<!-- The single SPEC section that owns this requirement's normative prose (once written). Link only — never copy the prose here. -->
- <SPEC-NAME §N> — `<path#anchor>`

## Notes

<!-- Optional. Don't settle open questions here — raise a STRAND. Cross-link related REQs. -->
