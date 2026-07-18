# CDS, Guidelines and Planning Overview — Digest

**Scope:** Conceptual entry point to the openEHR PROC component: how plans (Task Planning), Decision Logic Modules (Decision Language), and the Subject Proxy Service together formalise computable guidelines, care pathways, and clinical process automation.
**Component:** PROC
**Document:** overview
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/PROC/development/overview.html
**Markdown URL:** https://specifications.openehr.org/releases/PROC/development/overview.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/proc-task_planning, openehr://guides/specs/proc-decision_language, openehr://guides/specs/cds-GDL2
**Keywords:** PROC, process, planning, CDS, guideline, care pathway, CIG, DLM, subject proxy, work plan, co-pilot

---

## Purpose

Provides the overview of the openEHR PROC component specifications — the Decision Logic Module (DLM) language, Task Planning, and the Subject Proxy service — and the conceptual architecture that connects them. It motivates the work from clinical needs (implementing best practices, computable guideline and pathway authoring, simulation and training, long-running processes, team coordination) and articulates the "co-pilot paradigm": plan engines act as cognitive support for busy clinical workers rather than replacing their judgement. It also situates the openEHR approach against prior art (Arden Syntax, ProForma, HL7 CQL, BPMN and other workflow formalisms) — the Subject Proxy is presented as a solution to Arden's "curly braces problem" of binding rule logic to real-world variables.

## Scope

- In: the care-process artefact taxonomy — clinical practice guidelines (CPGs), score-based decision-support guidelines, care pathways, order sets, and care plans — and their formalisation as computer-interpretable guidelines (CIGs); the separation-of-concerns architecture splitting a CIG into a *plan definition* (Task Planning / TP-VML), *decision logic* (DLMs written in Decision Language), and *subject variables* served by a Subject Proxy Service with Data Access Bindings (DABs); the deployment paradigm ("separation of worlds", distributed plans); activation of plans, guidelines, and decision support; integration with the patient health record; and architectural use-cases for plan-oriented systems and guideline/decision-support systems.
- Out: normative plan-model semantics (`PROC/task_planning`), the DLM syntax and model (`PROC/decision_language`), the visual notation (`PROC/tp_vml`), the Subject Proxy Service interface definition (`SM/openehr_platform`), GDL2 rule syntax (`CDS/GDL2`), worked examples (`PROC/process_examples`).

## Key Classes / Constructs

This is a conceptual overview, so the entries below are concepts rather than classes:

- **Computer-interpretable guideline (CIG)** — the computable artefact formed of a plan plus decision logic plus subject-variable declarations.
- **Work Plan / Task Plan** — a complete plan with a goal and indications, composed of per-performer Task Plans built from task groups, dispatchable and performable tasks, and event wait states; executed by a *plan engine* acting as co-pilot for each worker.
- **Decision Logic Module (DLM)** — first-class knowledge artefact holding input (subject) variables, Boolean *conditions*, value-returning *rules*, *rule-sets* (representable as decision tables), and output variables; keeps decision logic out of plan structures so it can be authored and change-managed independently.
- **Subject Proxy** — a collection of tracked, real-world ("ontic") subject variables (e.g. date of birth, systolic BP) used by plans and DLMs, decoupled from how the data is stored.
- **Subject Proxy Service (SPS)** — the runtime bridge that populates subject proxies from back-end sources via installed Data Access Bindings (queries, API calls); handles semantic reframing from epistemic record data to use-specific ontic variables, manually reported and missing data, type conversion, and value *currency* over time.
- **Separation of worlds** — design and deployment separation between reusable guideline knowledge and legacy HIS environments, enabling distributed plan execution.

## Relations to Other Specs

- Introduces: `PROC/task_planning` (plan definition and execution model), `PROC/decision_language` (DLM syntax and model), `PROC/tp_vml` (visual modelling language), `PROC/process_examples` (worked artefacts).
- Coordinates with: `CDS/GDL2` (existing guideline formalism whose deployments informed DLM semantics), `SM/openehr_platform` (Subject Proxy Service interface), `RM/ehr` (plans read from and commit to the EHR), `LANG/EL` (expression syntax used by plans and DLMs).
- Prerequisite: `BASE/architecture_overview` for overall openEHR orientation.

## Architectural Placement

The PROC overview is the tier-zero document of the process/planning stack, mirroring the role of the Architecture Overview for the whole specification program. It fixes the three-part separation of concerns — plan structure, decision logic, subject data access — that every downstream PROC/CDS specification implements, and positions plan engines alongside (not inside) EHR platforms: plans consume subject data through the Subject Proxy Service and record outcomes back into the EHR as standard Entries.

## When to Read the Full Spec

Read the full document when you need the rationale and vocabulary for computable guidelines and pathways before diving into Task Planning or Decision Language; when comparing openEHR's approach to Arden, ProForma, CQL, GDL2, or BPMN-family workflow languages; when explaining to stakeholders why decision logic is separated from plans and from data-access bindings; or when designing the deployment topology of a plan engine, DLM library, and Subject Proxy Service around an existing clinical system landscape.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/PROC/development/overview.html
- Full spec (Markdown): https://specifications.openehr.org/releases/PROC/development/overview.md
- Related digests: specs/proc-task_planning, specs/proc-decision_language, specs/cds-GDL2, specs/sm-openehr_platform
