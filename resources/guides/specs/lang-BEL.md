# Basic Expression Language (BEL) — Digest

**Scope:** Foundation syntax and object model for rules, constraints, and assertions referenced from ADL 2 archetypes and other openEHR artefacts.
**Component:** LANG
**Document:** BEL
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/LANG/development/BEL.html
**Markdown URL:** https://specifications.openehr.org/releases/LANG/development/BEL.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/lang-EL, openehr://guides/specs/am2-ADL2
**Keywords:** BEL, expression language, rules, archetype rules, constraints, assertions, BEOM, first-order logic, quantifiers, bound variables

---

## Purpose

BEL defines a compact, strongly typed formalism for authoring rules, constraints, and assertions over openEHR data structures. It supplies both a concrete surface syntax and the Basic Expression Object Model (BEOM) that serves as the normative semantic definition, so that implementations may parse any concrete syntax yet evaluate against one common tree model. BEL was originally designed to underpin the `rules` section of ADL 2 archetypes and any other openEHR context needing computable first-order predicate logic, including cross-archetype invariants, derived values, and decision-support preconditions.

## Scope

- In: primitive and container type system (Boolean, Integer, Real, Date/Time/Duration, String, Uri, Terminology_code, List, Set, Hash); lexical conventions (identifiers, ODIN-style literals, keywords); declarations, assignments, and Boolean-valued assertions; unary/binary operators with precedence; variable references (local and path-bound); path expressions into archetyped data; conditional expressions; universal (`for_all`) and existential (`there_exists`) quantifiers over containers; built-in and external function calls; the BEOM class hierarchy that underlies the grammar.
- Out: concrete integration with a specific archetype parser (covered by `AM/ADL2`); the broader Expression Language / BMM expression model that now supersedes BEL for platform-level use (`LANG/EL`); execution-engine semantics beyond the abstract evaluation contract; decision-support service APIs (`PROC`, `CDS`); terminology value-set resolution; and choice of serialised carrier format for rules.

## Key Classes / Constructs

- Literals — ODIN-conformant primitive values (Boolean, numeric, temporal, String, Terminology_code) plus `[...]`/`{...}` container literals used as leaves of expression trees.
- Operators — arithmetic (`^ * / % + -`), relational (`= != < <= > >=`), and logical (`not`, `and`, `or`, `xor`, `implies`) operators carrying fixed precedence and textual/symbolic aliases.
- Variable references — `$name`-prefixed local and bound variables; bound variables are resolved against an external data context and may raise an undefined-value exception when their backing path is absent.
- Function calls — built-ins (`current_date_time`, aggregates such as `sum`, `mean`, `min`, `max`) and externally declared functions whose BEOM leaf node treats the callee as an opaque black box with a typed signature.
- Path references — XPath-like navigation (`$event/data[id4]/items[id7]/value/magnitude`) that binds variables to locations within archetyped compositional trees and drives constraint evaluation.
- Conditional expressions — `if/then/else` and equivalent constructs that combine Boolean predicates with typed branches while respecting the type-promotion rules (Integer to Real).
- Quantified expressions — `for_all` and `there_exists` (with ∀/∃ synonyms) iterate over `List`, `Set`, and `Hash` containers to produce Boolean results, enabling collection-level invariants.
- Assertions and statements — declarations (`name: Type [:= expr]`), assignments (`:=`), and tagged Boolean assertions grouped into `STATEMENT_SET` instances that form the top-level rule blocks.

## Relations to Other Specs

- Depends on: `BASE/foundation_types` for primitive and container semantics, `RM/data_types` for values exposed via path references, and `AM/ADL2` for the host archetype `rules` section that invokes BEL expressions.
- Consumed by: `AM/ADL2` (archetype invariants and derived values) and historically by decision-support prototypes. BEL and its BEOM have been superseded for new work by the BMM expression model and Expression Language (`LANG/EL`), but remain normative for ADL 2 rule blocks and continue to be implemented in tooling such as Archie.

## Architectural Placement

BEL occupies the LANG component as the foundational expression formalism that sits between the type system (`BASE`) and the archetype/template layer (`AM`): it provides a language-neutral semantic model (BEOM) that AM constructs embed, while downstream decision-support and derivation engines consume its expression trees against live RM data.

## When to Read the Full Spec

Consult the full document when authoring non-trivial archetype `rules` sections, implementing a BEL parser or evaluator, mapping BEL trees to BEOM for interoperability, reasoning about type promotion and undefined-value propagation, or assessing the migration path from BEL to the newer `LANG/EL` expression framework.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/LANG/development/BEL.html
- Full spec (Markdown): https://specifications.openehr.org/releases/LANG/development/BEL.md
- Related digests: specs/lang-EL, specs/am2-ADL2
