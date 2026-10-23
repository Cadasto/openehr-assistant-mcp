# Expression Language (EL) — Digest

**Scope:** Advanced object/functional expression syntax for BMM invariants, Task Planning expressions, and Decision Language (DLM) rules; GDL2 does not use it.
**Component:** LANG
**Document:** EL
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/EL.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/EL.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/lang-BEL, openehr://guides/specs/proc-decision_language, openehr://guides/specs/cds-GDL2
**Keywords:** EL, expression language, rules, task planning, guidelines, advanced, BMM, decision tables, agents, quantifiers, pattern matching, path expressions, temporal operators

---

## Purpose

The openEHR Expression Language (EL) defines a textual, strongly typed, void-safe syntax that serialises the BMM `expression` meta-model. It is the substrate for writing rules, assertions, pre/post-conditions, decision logic, and derived values wherever openEHR needs richer expressiveness than the Basic Expression Language (BEL) provides. EL merges first-order predicate logic, object-oriented structural traversal, and functional-style constructs (agents, higher-order quantifiers, decision tables), drawing stylistically from OCL, Eiffel, and contemporary object languages (Java, C#, Python, TypeScript) so that modellers and implementers share a single intelligible formalism.

## Scope

- In: terminal entities (literals, variables, feature references, function calls, container access, object matching, predicates, agents); arithmetic, relational, logical, string, and temporal operators; higher-order constructs (`there_exists`, `for_all`, if/then/else chains, case tables, multi-dimensional decision tables); type-promotion rules; path expressions over compositional trees; container literals for arrays, lists, sets, and maps; integration contract with the BMM `expression` package and Foundation Types.
- Out: the BMM `expression` meta-model itself (defined normatively in the BMM — see `LANG/bmm3`), evaluator implementation choices, concrete semantics of embedding hosts (archetype rules runtime, Task Planning and DLM executors), terminology-service behaviour behind `Terminology_code` literals, and the retired BEL syntax/meta-model (retained separately for legacy rule bases).

## Key Classes / Constructs

- `EL_FUNCTION_CALL` — qualified dot-notation invocation (`patient.age_at(Event_time)`); underlies every operator alias.
- `Agent` — delayed routine reference with `agent f(?, x)` partial application; enables closures passed to higher-order quantifiers.
- Variables (`Self` / `Result`) — read-only variables (routine parameters, implicit `Self`; `EL_READONLY_VARIABLE`) and writable ones (locals, implicit `Result` of a function; `EL_WRITABLE_VARIABLE`).
- `Object matching predicate` — container filter `list[pred]` returning matched items; used for pattern-style selection over archetyped data.
- Qualified referencing — dot-notation feature traversal (`blood_pressure.history.events[3].data.data.systolic`), with `[i]` / `[k]` container item access.
- `Quantifier (there_exists / for_all)` — predicate-logic operators with infix and functional forms for set-level assertions.
- `Decision table` — if/then/else chains and case tables with `∈` range membership and `*` wildcard catch-all for compact branching logic.
- `Temporal operator` — Date/Time arithmetic including `+`, `-`, and `++` / `--` distinguishing precise from nominal duration addition.

## Relations to Other Specs

- Depends on: the BMM `expression` meta-model that EL serialises (see `LANG/bmm3`), `BASE/foundation_types` (primitive, container, and interval types plus operator functions), and `RM/data_types` / `RM/support` (for `DV_*` coded values, `TERMINOLOGY_CODE`, and URI literals referenced from EL programs).
- Supersedes: `LANG/BEL` — EL is a major evolution on BEL syntax and does not use the BEL meta-model; BEL remains in use for AOM2 archetype rules.
- Consumed by: `PROC/task_planning` (context expressions, preconditions) and `PROC/decision_language` (DLM rules are EL functions), and BMM models using `expression` for pre/post-conditions and invariants. EL also names archetype rules and newer GDL versions as uses, but `AM/ADL2` specifies rules as a BEL subset and `CDS/GDL2` has its own expression syntax (`cds.expression`).

## Architectural Placement

EL sits in the LANG component as the default concrete syntax over the BMM `expression` package (`LANG/bmm3`), one layer above Foundation Types and beneath the formalisms that adopt it for computable logic (Task Planning, Decision Language DLMs, BMM invariants). It is intentionally host-agnostic: a conforming parser emits BMM EL meta-type instances that downstream engines can evaluate, round-trip to XML/JSON/YAML, or compile to native code.

## When to Read the Full Spec

Consult the full document when implementing an EL parser or evaluator, mapping EL expressions to a BMM-based runtime, designing multi-dimensional decision tables, handling agent partial application and closure capture, resolving operator overload and type-promotion edge cases, or aligning a Task Planning / DLM rule engine with the normative operator and quantifier semantics. The digest is insufficient for grammar-level work, precedence tables, and the full catalogue of temporal and interval operators.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/EL.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/EL.md
- Related digests: specs/lang-BEL, specs/proc-decision_language, specs/cds-GDL2
