## Summary
<What changed and why, in at most three sentences, with the decisions taken. Link the
REQ / SPEC § anchors the change implements or amends.>

## Spec and traceability
Lane: <full | maintenance — no normative change>
- <REQ-…> — <planned → shipped> in the traceability map and the index
- <SPEC-… §N> — <amended | promoted to stable | unchanged>
- <ADR-…, or the docs/ pages reconciled>

## Verification
- `make ci` — <what the output said>
- red before green: <the tests that failed before the change>
- can-fail proof: <the guards removed and the tests that then failed>
- per-task review: <tasks reviewed; findings fixed in-task, by severity>

## Notes for review
<!-- omit when there is nothing to say -->
- <at most three bullets: where to look hardest, what is out of scope, a known gap left on purpose>

## Checklist
- [ ] Scoped to one logical change
- [ ] Tests added or updated; no debug code or leftover comments
- [ ] `make ci` passes locally and its output was read
- [ ] CHANGELOG `[Unreleased]` line added, short and high-level
- [ ] Traceability and documents updated for what landed
- [ ] Conventional Commits, citing the identifiers the change touches
