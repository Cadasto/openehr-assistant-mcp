# Task Planning (TP) — Digest

**Scope:** Formal model of executable clinical work plans — structured, distributed, team-based task workflows with decision structures, events, callbacks, and execution history.
**Component:** PROC
**Document:** task_planning
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/PROC/development/task_planning.html
**Markdown URL:** https://specifications.openehr.org/releases/PROC/development/task_planning.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/proc-overview, openehr://guides/specs/proc-decision_language, openehr://guides/specs/rm-ehr
**Keywords:** task planning, work plan, task plan, TASK_GROUP, DISPATCHABLE_TASK, PERFORMABLE_TASK, workflow, callbacks, decision structures, TP-VML, materialised plan

---

## Purpose

Specifies the openEHR Task Planning facility for clinical process automation where granular planning of clinical work is required. The central concept is a *plan* (or set of plans) designed to achieve a *goal* for an *active subject* — an intentional, biologically reactive agent, unlike the passive objects of logistic workflow languages such as BPMN. Task Planning addresses what the standard openEHR Entry model cannot: representing future planned work at fine granularity, coordinating teamwork across performers and locations, tracking orders from plans, and recording exactly what happened during execution. It defines a declarative plan-graph formalism plus execution semantics, illustrated throughout with the companion Task Planning Visual Modelling Language (TP-VML).

## Scope

- In: the `proc.task_planning` package with its `definition`, `materialised`, and `history` sub-packages; Work Plan / Task Plan / Task Group / Task structure with sequential and parallel execution; wait states, timers, reminders, and repetition; plan data context (tracked variables, constants, expressions); system calls (`API_CALL`, `QUERY_CALL`); decision structures; task lifecycle and aggregate lifecycle-state computation; hand-offs, external and system requests; callbacks for blocking and non-blocking dispatched work; order tracking (linking plans to `INSTRUCTION`/`ACTION` Entries); cost tracking; materialisation, activation, and termination phases; execution history; and a transactional service model (definition and execution-time interfaces).
- Out: TODO-list applications, appointment booking/resource scheduling, and clinical decision support logic itself (`PROC/decision_language`, `CDS`); the conceptual framing of plans/guidelines/DLMs (`PROC/overview`); TP-VML notation details (separate spec); EHR Entry semantics (`RM/ehr`).

## Key Classes / Constructs

- `WORK_PLAN` — top-level container for a set of related `TASK_PLAN`s designed to achieve a goal, with a shared plan calendar and data context.
- `TASK_PLAN` — one performer-oriented plan of work; a graph of `PLAN_ITEM`s rooted in a `TASK_GROUP`.
- `TASK_GROUP` — grouping construct with sequential or parallel execution basis, optionality, and hierarchical nesting; supports concurrency and training-level annotation.
- `TASK` and subtypes — `PERFORMABLE_TASK` (work done by a human performer, with display/capture data-sets and `capture_dataset` for progressive data capture) and `DISPATCHABLE_TASK<T>` (work dispatched elsewhere, with `wait` flag for context switch vs fork, and `callback` wait state).
- Task actions — `TASK_ACTION` descendants: `DEFINED_ACTION` (inline-defined clinical work, optionally with prototype Entry), `SUB_PLAN` (nested plan invocation), `HAND_OFF` (transfer to another performer), `EXTERNAL_REQUEST`, `SYSTEM_REQUEST`.
- Decision structures (`choice` package) — `CONDITION_GROUP`/`CONDITION_BRANCH` (if/elseif/else), `DECISION_GROUP`/`DECISION_BRANCH` (case/switch over a `value_constraint`), `EVENT_GROUP`/`EVENT_BRANCH` (when/then over received events).
- Plan data context — `PLAN_DATA_CONTEXT` with `CONTEXT_VARIABLE<T>`, `CONTEXT_CONSTANT<T>`, `CONTEXT_EXPRESSION<T>`; external vs local variables (`EXTERNAL_VARIABLE`, `LOCAL_VARIABLE`, `CONTINUOUS_EVENT_VARIABLE`) populated via queries or API calls; expression syntax from the openEHR Expression Language.
- Events and waits — `TASK_WAIT`, `EVENT_WAIT<T>`, `TIMER_EVENT`, `TIMER_WAIT`, `TIMELINE_MOMENT`, `REMINDER`; callback machinery (`CALLBACK_NOTIFICATION`, `RESUME_ACTION`, `MANUAL_NOTIFICATION`) with time-outs and lifecycle-transition overrides.
- Order tracking — `ORDER_REF` links plan tasks to openEHR orders, supporting both tracking an existing order and creating-then-tracking (e.g. `PERFORMABLE_TASK<DEFINED_ACTION>` committing an Instruction, then `DISPATCHABLE_TASK<SYSTEM_REQUEST>` dispatching it).
- Lifecycle — `TASK_LIFECYCLE` enumeration (definition package); aggregate process state computed over sequential and parallel groups.
- `EXECUTION_HISTORY` / `TASK_EVENT_RECORD` (history package) — audit record of everything that happened during plan execution.

## Relations to Other Specs

- Depends on: `PROC/overview` (conceptual architecture: plans + decision logic + subject proxy), `RM/ehr` (Entry types; `ACTION`/`INSTRUCTION` created or tracked by tasks), `RM/common` (`LOCATABLE` identification via `uid`), `BASE/base_types` (`UID_BASED_ID`, `LOCATABLE_REF`), the openEHR Expression Language (`LANG/EL`) for context expressions.
- Companions: `PROC/tp_vml` (visual notation), `PROC/decision_language` (Decision Logic Modules used by plans), `PROC/process_examples` (worked examples).
- Consumed by: workflow/care-pathway engines, task-list and team-coordination applications, and EHR platforms persisting plan definitions, materialised state, and execution histories.

## Architectural Placement

Task Planning is the process layer of openEHR: it sits above the EHR Information Model (which records what *was* done) and adds first-class representation of what *should be* done, by whom, and when. Its three-level expression — definition model (designed workflow), materialised model (run-time state structure, non-normative outline), and execution history — mirrors the openEHR separation of definition and execution, and its plan-graph semantics are deliberately declarative rather than prescriptive.

## When to Read the Full Spec

Read the full specification when implementing a task-planning engine (materialisation, activation, worker allocation, callback processing for blocking vs non-blocking tasks), designing plan structures with nested groups and decision branches, wiring plan variables to EHR queries or APIs, computing aggregate lifecycle state across sequential/parallel groups, or mapping plan-generated clinical events onto `ACTION`/`INSTRUCTION` Entries and Compositions for EHR committal.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/PROC/development/task_planning.html
- Full spec (Markdown): https://specifications.openehr.org/releases/PROC/development/task_planning.md
- Related digests: specs/proc-overview, specs/proc-decision_language, specs/rm-ehr
