# Decision Language (DL) — Digest

**Scope:** High-level language and model for writing Decision Logic Modules (DLMs) — the rule-sets, conditions, and subject-variable declarations consumed by Task Plans, guidelines, and decision-support systems.
**Component:** PROC
**Document:** decision_language
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/PROC/development/decision_language.html
**Markdown URL:** https://specifications.openehr.org/releases/PROC/development/decision_language.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/proc-overview, openehr://guides/specs/proc-task_planning, openehr://guides/specs/lang-EL
**Keywords:** decision language, DL, DLM, decision logic module, rules, conditions, subject variables, tracked variables, decision table, GDL3

---

## Purpose

Specifies the openEHR Decision Language (DL), in abstract-syntax form plus a related model, defining the semantics of a first-order-predicate-style logic for writing Decision Logic Modules (DLMs). A DLM is the primary encapsulation of decision logic: rule-sets that may be used standalone or invoked by process/plan-oriented systems such as openEHR Task Planning, and that also serve the needs of GDL3-generation guidelines (rules plus declared input and output variables). All symbolic elements — constants, variables, rules — are treatable as codes with a translation terminology, so a DLM can be translated and presented in any natural language, in the manner of ADL2 archetypes. Parts of the document are marked TBD; the specification is still maturing.

## Scope

- In: the DLM textual syntax and its sections — identification (`dlm` header), `language` and `description` (authored-resource metadata), `use_model` (externally defined information models supplying types), `use` (other DLMs), `preconditions`, `reference` (domain constants / reference data), `input` (subject variables and tracked variables, including quantitative tracked variables with update semantics), `rules` (conditions and value-returning rules), `output`, and `terminology` (multi-lingual term definitions for symbolic elements); variable naming and unavailable-value handling; the DLM object model (Decision Logic Module package and concrete model) reusing BMM meta-classes.
- Out: plan/workflow structure (`PROC/task_planning`); the conceptual architecture and Subject Proxy concept (`PROC/overview`); the expression grammar itself (openEHR Expression Language, `LANG/EL`); the GDL2 formalism (`CDS/GDL2`); Subject Proxy Service interfaces (`SM/openehr_platform`); execution-engine design.

## Key Classes / Constructs

- `dlm` module — the top-level artefact; a multi-section text module analogous in layout to an ADL2 archetype (identification, language, description, definition sections, terminology).
- Reference data (`reference` section) — domain constants available to all rules in the module.
- Input variables (`input` section) — declarations of subject variables (patient-related external data items) with naming conventions and explicit handling for unavailable values; *tracked* variables add temporal update semantics, with quantitative tracked variables supporting thresholds for meaningful change.
- Conditions — Boolean-returning, domain-specified criteria based directly on subject variables.
- Rules and rule-sets — condition/action structures returning values of any type; groupable into rule-sets, representable as decision tables.
- Outputs (`output` section) — computed results of rule invocation, including logic-trace information usable as justification for a caller.
- `terminology` section — code-keyed term definitions enabling multi-lingual presentation of constants, variables, and rules.
- Model classes — `DECISION_LOGIC_MODULE` and `DLM_RULE`, with variable meta-types such as `STATE_VARIABLE<T>`, `TRACKED_VARIABLE<T>`, `TRACKED_STATE_VARIABLE<T>`, and `TRACKED_QUANTITATIVE_VARIABLE<T>`; the concrete model reuses BMM meta-classes (`BMM_CLASS`, `BMM_FUNCTION`, `BMM_CONSTANT`) and `AUTHORED_RESOURCE` metadata.

## Relations to Other Specs

- Depends on: `PROC/overview` (conceptual framework: DLMs, Subject Proxy, plans), `LANG/EL` and the BMM expression meta-model (expression syntax and typing), `LANG/bmm` (meta-classes reused by the DLM model), `BASE/resource` (`AUTHORED_RESOURCE` description/translation metadata), external information models declared via `use_model` for variable types.
- Consumed by: `PROC/task_planning` (plans delegate all decision-point logic to DLMs), GDL3-generation guideline work (DLMs subsume the guideline rule role of `CDS/GDL2`), decision-support services, and active-form applications.
- Data access: subject variables are populated at runtime through the Subject Proxy Service (`SM/openehr_platform`).

## Architectural Placement

Decision Language occupies the knowledge-artefact layer between plans and data: Task Plans reference DLM conditions and rules at decision points; DLMs declare the subject variables they need; and the Subject Proxy Service binds those variables to concrete data sources. This keeps decision logic a first-class, independently authored and change-managed artefact rather than ad-hoc expressions buried inside plan structures or application code.

## When to Read the Full Spec

Read the full specification when authoring a DLM (section-by-section syntax, identifier rules, terminology layout), implementing a DL parser or DLM execution component, designing tracked-variable update semantics for near-real-time data, mapping DLM input variables onto Subject Proxy bindings, or evaluating DL as the successor formalism to GDL2 rules. Note the DEVELOPMENT status and TBD sections — expect evolution, and cross-check against `PROC/process_examples` for worked DLM instances.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/PROC/development/decision_language.html
- Full spec (Markdown): https://specifications.openehr.org/releases/PROC/development/decision_language.md
- Related digests: specs/proc-overview, specs/proc-task_planning, specs/lang-EL, specs/cds-GDL2
