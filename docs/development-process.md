---
kind: guide
---

# Development process — the SDD constitution

How work flows in this repository. The **specification is the source of truth**; when code and spec
disagree the spec wins — except a section marked *implementation-aligned*, or on the informative profile,
where code leads and only the constitution binds.

## Document kinds

Every document has one job and one altitude. Don't mix them. Every document under `docs/` declares its
job in a `kind:` frontmatter key from the descriptor's `doc_kinds` in [`.sdd.yaml`](.sdd.yaml); the zone
decides how it is read. On the informative profile one more kind, `constitution`, is the only document
that binds code.

| Zone | Kind | Answers | Status | Location |
|---|---|---|---|---|
| Normative | **Requirement** (`REQ-*`) | What do we deliver, and how do we accept it? | `status` and `implementation` | `docs/requirements/` |
| Normative | **Specification** (`SPEC-*`) | How does the system behave? (RFC-2119) | `status` | `docs/specifications/` |
| Normative | **ADR** (`ADR-*`) | Which irreversible fork did we take? | `status` | `docs/decisions/` |
| Informative | **Guide** | How do I work here safely? | none | `docs/` |
| Informative | **Analysis** | What did we measure or compare? | none | `docs/analysis/` |
| Informative | **Operations** | How do operators run the system? | none | `docs/operations/` |
| Informative | **Reference** | A declared projection, binding nothing | none | beside the specs, or `docs/reference/` |
| Upstream | **Upstream** | What another repository owes this one | `state` | `docs/<name>-gap-drafts/` |

**Authority.** Where an informative document and a normative one disagree, the normative one wins and the
informative document is corrected; it never holds the only copy of a product-contract rule.

**Status per kind.** A requirement tracks two axes separately: `status` (how settled the wording is) and
`implementation` (how much is built). An informative kind carries no status.

| Kind | Key | Values |
|---|---|---|
| **Specification** | `status` | `draft` · `stable` · `deprecated` |
| **Requirement** | `status` | `draft` · `stable` · `deprecated` |
| **Requirement** | `implementation` | `proposed` · `planned` · `in_progress` · `partial` · `landed` · `shipped` · `deferred` · `retired` |
| **ADR** | `status` | `proposed` · `accepted` · `superseded` · `deprecated` |
| **Upstream** | `state` | `proposed` · `submitted` · `landed-upstream` · `landed` · `rejected` |
| Guide, analysis, operations, reference | — | no status |

Requirements hold capability, acceptance and out-of-scope, no implementation detail; specifications hold
RFC-2119 prose only; an ADR holds one decision.

## Identifiers

Stable and citable, in commits and test names; **never renumbered or reused once published**. Normative
prose has a **single canonical home**.

## The flow

```
REQ (capability + acceptance)            [gate: worth doing]
 └─ SPEC § (RFC-2119, Status: Draft)      [gate: single home, no duplicate prose]
     └─ ADR (only if an irreversible fork) [gate: Accepted before code]
         └─ TASKS (a working list, never committed)  [gate: dispatch preconditions]
             └─ CODE + TESTS (tests cite ids)  [gate: tests green + drift gate green]
                 └─ update traceability; promote the SPEC § only if confirmed  [gate: same PR]
                     └─ update REQ status; write the PR body  [/sdd-close]
```

The close-out lands in the **same PR** as the slice. No follow-up PR.

## The gate

The drift gate is one tool, `sdd-check`, vendored at the descriptor's `check.script`
(`scripts/sdd-check.py`) and run by `make spec-check` (`composer check:spec` in the dev container). It checks the map against the tree both ways, the index, the
document kinds, links, the RFC-2119 and one-home rules, changelog bullets and generated blocks; each
family's severity is set under `check.families`. The derived indexes are written by `sdd-check generate`,
never by hand.

## Two source-of-truth modes

- **Spec-first** (new behaviour): the spec leads, code follows.
- **Implementation-aligned** (hardening / perf / bug-fix on shipped code): code may lead, **but the spec
  is updated in the same PR**.

## Dispatch preconditions

Five things are confirmed **before the first task is dispatched**: a `REQ` with acceptance criteria; the
affected `SPEC §` (or a new § called out); any needed ADR `Accepted`; the **negative space** cited from the
acceptance criteria and the `SPEC §` that owns the failure behaviour; the verification commands. An unmet
precondition stops the dispatch. On the informative profile: a clear task and a known verification
command.

## The two lanes

Formal profile only. The lane test is one question, answered on the PR body's `Lane:` line: **does this
change alter any normative statement** — acceptance criteria, a `SPEC §` behaviour, an API shape, an
error contract?

- **Full lane** — new capability or any such change, spec amendments included. Owes the `REQ`/spec edits,
  the traceability update and the SDD reviewers.
- **Maintenance lane** — refactors, moves, performance, dependency bumps, tooling, documentation polish,
  and a bug-fix that makes the code match an **existing** spec statement. Owes green tests and a green
  drift gate; the PR body may be short. A bug-fix that reveals the **spec** was wrong is full lane.

The drift gate runs in **both** lanes. Any newly added or materially changed requirement owes observable
acceptance criteria and its own canonical `SPEC §`, whatever lane the change claims.

## The PR body

The pull request body is read by the maintainer and by the next agent. Copy this block; the scaffold also
writes it to `.github/PULL_REQUEST_TEMPLATE.md`:

```markdown
## Summary
<What changed and why, in at most three sentences, with the decisions taken. Link the
REQ / SPEC § anchors (formal) or the architecture sections (informative) the change implements or amends.>

## Spec and traceability            <!-- informative profile: "## Docs" -->
Lane: <full | maintenance — no normative change>     <!-- formal profile only -->
- <REQ-…> — <planned → shipped> in the traceability map and the index
- <SPEC-… §N> — <amended | promoted to stable | unchanged>
- <PROBE-…, ADR-…, or the docs/ pages reconciled>

## Verification
- `make ci` — <what the output said>
- red before green: <the tests that failed before the change>
- can-fail proof: <the guards removed and the tests that then failed>
- per-task review: <tasks reviewed; findings fixed in-task, by severity>

## Notes for review                  <!-- omit when there is nothing to say -->
- <at most three bullets: where to look hardest, what is out of scope, a known gap left on purpose>

## Checklist
- [ ] Scoped to one logical change
- [ ] Tests added or updated; no debug code or leftover comments
- [ ] `make ci` passes locally and its output was read
- [ ] CHANGELOG `[Unreleased]` line added, short and high-level
- [ ] Traceability and documents updated for what landed
- [ ] Conventional Commits, citing the identifiers the change touches
```

The body describes the branch, never its review: no session claim, no commit list, no task ticks, no
finding ids, counts or passes; those are in the findings file and the review threads (`ai-workflow.md`
§ Review). Update it when a later commit changes what the branch does.

## Artefact prose — one home per fact

The commit body, PR body, changelog, and review threads each carry only what lives nowhere else — cite
identifiers (`REQ`/`SPEC §`/SHA) instead of restating: the spec owns behaviour, the commit the *why*, the
PR body the summary and verification, the changelog the user-facing line.

## Open questions

Never settle an open question silently in a PR: raise a `STRAND` (if enabled), draft an ADR, or ask.
Never add a rule that exists only in code.
