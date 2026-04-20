# Expression Language (EL) — Digest

**Scope:** Advanced object/functional expression syntax for rules, assertions, and decision logic across openEHR archetypes, GDL2, and Task Planning.
**Component:** LANG
**Document:** EL
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/EL.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/EL.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/lang-BEL, openehr://guides/specs/cds-GDL2
**Keywords:** EL, expression language, rules, task planning, guidelines, advanced, BMM, decision tables, agents, quantifiers, pattern matching, path expressions, temporal operators

---

## Purpose

The openEHR Expression Language (EL) defines a textual, strongly typed, void-safe syntax that serialises the BMM `expression` meta-model. It is the substrate for writing rules, assertions, pre/post-conditions, decision logic, and derived values wherever openEHR needs richer expressiveness than the Basic Expression Language (BEL) provides. EL merges first-order predicate logic, object-oriented structural traversal, and functional-style constructs (agents, higher-order quantifiers, decision tables), drawing stylistically from OCL, Eiffel, and contemporary object languages (Java, C#, Python, TypeScript) so that modellers and implementers share a single intelligible formalism.

## Scope

- In: terminal entities (literals, variables, feature references, function calls, container access, object matching, predicates, agents); arithmetic, relational, logical, string, and temporal operators; higher-order constructs (`there_exists`, `for_all`, if/then/else chains, case tables, multi-dimensional decision tables); type-promotion rules; path expressions over compositional trees; container literals for arrays, lists, sets, and maps; integration contract with the BMM `expression` package and Foundation Types.
- Out: the BMM meta-model itself (defined normatively in `BASE/bmm`), evaluator implementation choices, concrete semantics of embedding hosts (archetype rules runtime, GDL2 engine, Task Planning DLM executor), terminology-service behaviour behind `Terminology_code` literals, and the retired BEL syntax/meta-model (retained separately for legacy rule bases).

## Key Classes / Constructs

- `EL_FUNCTION_CALL` — qualified dot-notation invocation (`patient.age_at(Event_time)`); underlies every operator alias.
- `Agent` — delayed routine reference with `agent f(?, x)` partial application; enables closures passed to higher-order quantifiers.
- `Let-binding / Result / Self` — local read-only bindings plus the implicit `Self` target and writable `Result` for routine-bodied expressions.
- `Object matching predicate` — container filter `list[pred]` returning matched items; used for pattern-style selection over archetyped data.
- `Path expression` — hierarchical feature traversal (`bp.history.events[3].data`) aligned to RM `PATHABLE` semantics.
- `Quantifier (there_exists / for_all)` — predicate-logic operators with infix and functional forms for set-level assertions.
- `Decision table` — if/then/else chains and case tables with `∈` range membership and `*` wildcard catch-all for compact branching logic.
- `Temporal operator` — Date/Time arithmetic including `+`, `-`, and `++` / `--` distinguishing precise from nominal duration addition.

## Relations to Other Specs

- Depends on: `BASE/bmm` (normative `expression` meta-model that EL serialises), `BASE/foundation_types` (primitive, container, and interval types plus operator functions), and `RM/data_types` / `RM/support` (for `DV_*` coded values, `TERMINOLOGY_CODE`, and URI literals referenced from EL programs).
- Consumed by: `LANG/BEL` (as the successor formalism), `AM/ADL2` (archetype `rules` section assertions), `CDS/GDL2` (guideline rule bodies), `PROC/task_planning` (Decision Logic Modules and plan guards), and any BMM-hosted model importing `expression` for derived attributes or invariants.

## Architectural Placement

EL sits in the LANG component as the default concrete syntax over the BMM `expression` package, one layer above Foundation Types and beneath every domain formalism (AM rules, GDL2, Task Planning DLMs) that needs computable logic. It is intentionally host-agnostic: a conforming parser emits BMM EL meta-type instances that downstream engines can evaluate, round-trip to XML/JSON/YAML, or compile to native code.

## When to Read the Full Spec

Consult the full document when implementing an EL parser or evaluator, mapping EL expressions to a BMM-based runtime, designing multi-dimensional decision tables, handling agent partial application and closure capture, resolving operator overload and type-promotion edge cases, or aligning a GDL2 / Task Planning rule engine with the normative operator and quantifier semantics. The digest is insufficient for grammar-level work, precedence tables, and the full catalogue of temporal and interval operators.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/EL.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/EL.md
- Related digests: specs/lang-BEL, specs/cds-GDL2
