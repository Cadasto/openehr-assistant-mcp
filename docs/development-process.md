---
kind: guide
---

# Development process — the informative profile

How work flows in this repository. It runs the sdd plugin's **informative** profile: the **code leads**,
`docs/` is a knowledge base that describes the code as it is, and only the
[constitution](architecture.md) binds. A change that contradicts a constitution sentence is refused, or it
amends the sentence deliberately.

## Document kinds

Every document has one job and one altitude; don't mix them. Every document under `docs/` declares its job
in a `kind:` frontmatter key from the descriptor's `doc_kinds` in [`.sdd.yaml`](.sdd.yaml).

| Kind | Answers | Binds? | Where |
|---|---|---|---|
| **Constitution** | What must every change respect? | **Yes**: its MUST sentences, and only those | [architecture.md](architecture.md) |
| **ADR** (`ADR-*`) | Which irreversible fork did we take? (`status`: `proposed` · `accepted` · `superseded` · `deprecated`) | No; a rule it sets binds once the constitution states it | [decisions/](decisions/README.md) |
| **Reference** | A declared projection of the code, such as the capability catalogue | No | [requirements.md](requirements.md) |
| **Guide** | How is it built, and how do I work here safely? | No | the other pages under `docs/` |
| **Plan** | A working file for one change | No; no gate reads it, and no durable page links to it | `docs/plans/` |

**Authority.** Where a describing page and the code disagree, the page is corrected in the same change.
Where the code and a constitution sentence disagree, the code is fixed, or the sentence is amended through
`/sdd-specify` with the reason in the commit body or an ADR. RFC-2119 keywords appear only in the
constitution.

## Identifiers

Capability ids (`REQ-F#`, `REQ-N#`) and ADR numbers are stable and citable, in commits and test docblocks;
**never renumbered or reused once published**. New ids are assigned only where the catalogue already uses
them.

## The flow

```
Task (clear, with a known verification command)
 └─ CODE + TESTS   [gate: every constitution MUST still has a test that fails without it]
     └─ DOCS: the pages that describe what changed, reconciled in the same PR
         └─ review (code reviewers; the doc reviewer for consistency; the conformance
            reviewer only when the change touches what a constitution sentence binds)
```

There is one lane. A new or changed constitution sentence owes a test that fails when its guard is removed.

## The gate

The drift gate is one tool, `sdd-check`, vendored at the descriptor's `check.script`
(`scripts/sdd-check.py`) and run by `make spec-check` (`composer check:spec` in the dev container). On this
profile it checks the documents: the descriptor, document kinds, links and anchors, changelog bullets and
generated blocks. The ADR index is written by `sdd-check generate`, never by hand.

## The PR body

The pull request body is read by the maintainer and by the next agent. Copy this block; it is also
[`.github/PULL_REQUEST_TEMPLATE.md`](../.github/PULL_REQUEST_TEMPLATE.md):

```markdown
## Summary
<What changed and why, in at most three sentences, with the decisions taken. Link the
constitution sections the change touches, if any.>

## Docs
- <the docs/ pages reconciled with the change, or "none: no described behaviour changed">
- <constitution: unchanged | amended (which sentence, and why)>
- <ADR-…, if one was recorded>

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
- [ ] The docs that describe what changed are updated
- [ ] Conventional Commits, citing the identifiers the change touches
```

The body describes the branch, never its review: no session claim, no commit list, no task ticks, no
finding ids, counts or passes; those are in the findings file and the review threads (`ai-workflow.md`
§ Review). Update it when a later commit changes what the branch does.

## Artefact prose — one home per fact

The commit body, PR body, changelog, and review threads each carry only what lives nowhere else; cite
identifiers (`REQ-#`, `ADR-#`, a SHA) instead of restating: the constitution owns the rules, the code owns
the behaviour, the commit the *why*, the PR body the summary and verification, the changelog the
user-facing line.

## Open questions

Never settle an open question silently in a PR: draft an ADR, or ask. Never add a binding rule that lives
only in code; state it in the constitution, with its test.
