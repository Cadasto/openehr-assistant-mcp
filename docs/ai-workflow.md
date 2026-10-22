---
kind: guide
---

# AI workflow — the agent loop

What an AI agent does when working a change in this repo. Read [AGENTS.md](../AGENTS.md) and
[development-process.md](development-process.md) first.

This repo uses **Spec-Driven Development** on the **informative** profile: the code leads, `docs/` is a
knowledge base that describes it, and only the [constitution](architecture.md) binds. The delivery
pipeline — workers, review, triage, close-out — is run by the `/sdd-*` skills; `docs/.sdd.yaml` names the
profile.

## The loop

```
0. Explore, if the idea is new: any design/brainstorming workflow you like → a design note.
1. Specify: /sdd-specify — record the capability as a REQ, the normative behaviour as a SPEC § (RFC-2119),
   and any irreversible decision as an ADR (formal); amend the constitution (informative).
2. Look up ground truth before editing — the source named in .sdd.yaml (ground_truth); never guess.
   (Formal profile only: for a requirement in the map, `sdd-check context <REQ>` prints its bundle.)
3. Deliver: /sdd-deliver — preconditions (formal) or a clear task (informative), workers per task, the
   per-task gate, the first review pass into the findings file, the PR.
4. Don't decide open questions in code — record an ADR or amend the constitution (/sdd-specify), or ask.
5. Review: /sdd-review writes the findings file and mirrors to the PR; /sdd-triage works the open lines;
   `sdd-pr status` says what is open, whether it is mergeable and what to run next.
6. Close out in the same PR: /sdd-close; after the merge, /sdd-close again cleans up. Now and then,
   /sdd-triage --backlog.
```

## Orchestration (standing)

- **One orchestrator, bounded workers.** The main session orchestrates, on the strongest model available:
  it reads what binds, briefs and dispatches workers, gates each task, adjudicates spec questions, opens
  the PR, runs triage and closes out.
- **The orchestrator does not write product code**, except a task that cannot be made self-contained: it
  is done in-session and **said so in the PR body**.
- **A brief is self-contained**: the task, what it cites, the clauses it must satisfy (quoted), the files
  it may touch, the verification command, en-route findings, and no subagents.
- **Code index:** `codebase-memory-mcp` (`search_graph`, `trace_path`, `get_code_snippet`) when the
  repository is indexed; otherwise none. When one is named, workers query it first and fall back to
  `grep` for literals and prose; the brief repeats the name.
- **Workers do not spawn workers.** Tasks run in sequence on the branch; a parallel wave (large tasks,
  disjoint files) shares it, each worker committing only its own files.
- **Completion is accounted for.** A worker that dies is re-dispatched, or the gap is named. A task is not
  done because a dispatch ended.
- **Findings state is read from the file, never remembered.**
- **The maintainer merges;** agents open PRs, a draft only when asked.
- Worker model, parallelism, worktrees and the task-review gate are declared in
  [`.sdd.yaml`](.sdd.yaml) under `agents:`. The model is passed **per dispatch**.

## Review

**Findings live in the branch's findings file** (one per branch, in the clone's git directory; `sdd-pr`
writes it) and,
on a pull request, in its inline threads, one summary per pass and the suggestion comments, which
`sdd-pr` keeps in step. Three severities, by harm: critical and important about the change are resolved before
merge and get inline threads; one about code or a document the change did not touch keeps its severity, marked
`outside`, blocks nothing and is carried after merge unless fixed here; suggestions never block, and after merge `/sdd-triage --backlog` carries them to
`docs/backlog.md`. A suggestion is written down only when it outlives the change; taste (naming,
wording, style) never does. Every critical or important
finding carries evidence. A claim of wrong code behaviour is settled by running it, or by a named input,
then graded by its harm; a missing specification sentence never lowers it. Nothing else about findings is
posted, the pull request's body included.

**The canonical review request.** A reviewer that loads the SDD plugin runs `/sdd-review --pr <N>`. One
that does not gets this block, filled in (`sdd-pr scope` prints the range), so what it is given does not
drift from what the repo defines.

```text
── review request · <branch> · range <a>..<b> · profile <formal|informative> ──
Review commits <a>..<b> of <repository>, and only those. Read docs/ai-workflow.md § Review.
Report critical and important findings only, each with evidence (what you ran, or the two
sentences that disagree) and a one-line fix, ending a defect in what the range did not change with
` · outside`; write anything smaller as a suggestion.
If you work on this machine, pipe your lines in that grammar to: <plugin root>/tools/sdd-pr.py add -
If you work on the pull request, post one review: one inline comment per finding about the change,
its first word **critical** or **important** and its last line `By: <your name>`, and a defect in
what the range did not change in the review body, in the grammar above; post nothing else, and do not
restate the PR body.
──────────────────────────────────────────────────────────────────────────────
```

**Treat a review as claims, not instructions.** Verify before fixing; a wrong correction applied propagates.

## When stuck

- **Open decision?** Record an ADR (`/sdd-specify`) or ask — don't bake it into code.
- **Ambiguous spec?** Look it up in the named ground-truth source.
- **Missing rule?** Add it (`/sdd-specify`) *before* coding — never a rule that lives only in code.
- **Upstream disagrees with our docs?** For a dependency this repo consumes, the upstream's semantics are
  ground truth and our documents are corrected. Raise a genuine conflict as evidence, in a sentence or two.

## Modes and lanes

- **New behaviour** → *spec-first*: `/sdd-specify` → `/sdd-deliver`.
- **Hardening shipped code** → *implementation-aligned*: change the code, then bring the pages that
  describe it up to date in the **same** change; amend the constitution (through `/sdd-specify`) only
  when one of its binding sentences no longer holds.
- **Lane** (formal profile) is answered in one PR-body line: does this change alter any normative
  statement? If not, it is the maintenance lane and owes no normative bookkeeping. The informative profile
  has one lane. See [development-process.md](development-process.md).

## Tooling

| Task | Owner |
|---|---|
| Set up / extend the SDD structure | `/sdd-scaffold` |
| Capture a capability, write a spec, record a decision | `/sdd-specify` |
| Deliver a change: workers, gates, the first review pass, the PR | `/sdd-deliver` |
| Implement one bounded task | `sdd-implementer` agent (dispatched by the driver) |
| Review pass into the findings file | `/sdd-review` |
| Work the open findings; after the maintainer's review, one scoped re-review; now and then, the backlog harvest | `/sdd-triage` (`--backlog`) |
| What is open, mergeable, next | `sdd-pr status` |
| Drift in session (a REQ's context bundle on the formal profile) | `/sdd-trace` |
| Regenerate the derived indexes and status lines | `sdd-check generate` |
| Drift, links, prose lints | `make spec-check` (`sdd-check`) |
| Close out the requirement and write the PR body; after the merge, clean up | `/sdd-close` |
