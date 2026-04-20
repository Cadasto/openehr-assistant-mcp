# Archetype Object Model 1.4 (AOM 1.4) — Digest

**Scope:** Syntax-independent object model of archetypes corresponding to ADL 1.4; the definitive semantics of 1.4-era archetypes.
**Component:** AM
**Document:** AOM1.4
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/AOM1.4.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/AOM1.4.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/am-ADL1.4, openehr://guides/specs/am2-AOM2
**Keywords:** AOM, AOM 1.4, archetype object model, C_OBJECT, constraint, legacy

---

## Purpose

Defines the normative, syntax-independent object model for archetypes as authored in ADL 1.4. AOM 1.4 provides both the semantic specification of archetype constraints and the in-memory API consumed by parsers, validators, differencing tools, constraint checkers, and serialisers. It is kept 100% synchronised with the ADL 1.4 grammar: every ADL 1.4 construct maps to an AOM 1.4 class, so tooling that processes archetypes does so against this object model rather than against raw ADL text.

## Scope

- In: the archetype root (`ARCHETYPE`), the constraint-tree classes (`C_OBJECT` hierarchy, `C_ATTRIBUTE` hierarchy), primitive and domain-typed constraint leaves (`C_PRIMITIVE`, `C_DOMAIN_TYPE`), slot and reference constraints (`ARCHETYPE_SLOT`, `ARCHETYPE_INTERNAL_REF`, `CONSTRAINT_REF`), cardinality/occurrences qualifiers, assertion/expression model (`ASSERTION`, `EXPRESSION`, `BINARY_OPERATOR`, etc.), the ADL 1.4 ontology section (`ARCHETYPE_ONTOLOGY`, `ARCHETYPE_TERM`, bindings), path semantics, specialisation depth, validity/subset predicates, and default-value generation.
- Out: ADL 1.4 lexical/grammar rules (see `AM/ADL1.4`); ADL 2 / AOM 2 structures such as differential archetypes, flat-vs-differential semantics, operational templates, and the revised terminology/tuple model (see `AM/AOM2`); Reference Model type definitions (constrained here but defined in `RM/*`); archetype profile rules; the Template Object Model (TOM); and per-class attribute-level detail, which is supplied at runtime by the BMM-backed type lookup.

## Key Classes / Constructs

- `ARCHETYPE` — root container; holds identifier, parent archetype id, concept, ontology, and the constraint `definition` tree.
- `C_OBJECT` — abstract ancestor of every constrained node; carries `rm_type_name`, `node_id`, `occurrences`, and path semantics.
- `C_COMPLEX_OBJECT` — constraint on a structured RM object; aggregates `C_ATTRIBUTE` children, enabling the alternating object/attribute tree.
- `C_ATTRIBUTE` — abstract constraint on an RM attribute (single- or multi-valued), with `existence` and specialisation of child `C_OBJECT` alternatives.
- `C_PRIMITIVE_OBJECT` — leaf constraint wrapping a `C_PRIMITIVE` (string/integer/real/boolean/date/time/date-time/duration) value set.
- `ARCHETYPE_SLOT` — reference constraint allowing other archetypes matching include/exclude assertions to fill a slot at runtime.
- `ARCHETYPE_ONTOLOGY` — terminology section: `at`/`ac` term definitions, translations, term/constraint bindings to external terminologies.

Domain and assertion classes (`C_DOMAIN_TYPE`, `C_ORDINAL`, `C_CODED_TEXT`, `C_QUANTITY`, `ASSERTION`, `EXPRESSION`, `ARCHETYPE_INTERNAL_REF`, `CONSTRAINT_REF`, `CARDINALITY`, `OCCURRENCES`) are defined in the spec. Per-class attribute, invariant, and function detail is best retrieved via `type_specification_get` (BMM-backed) rather than duplicated here.

## Relations to Other Specs

- Depends on: `AM/ADL1.4` (1:1 grammar correspondence), `RM/support` (identifiers, intervals, terminology access), `RM/data_types` (types constrained by `C_DOMAIN_TYPE` such as `DV_ORDINAL`, `DV_CODED_TEXT`, `DV_QUANTITY`), and `RM/common` `AUTHORED_RESOURCE` for the ADL 1.4 resource descriptor metadata.
- Consumed by: ADL 1.4 parsers/serialisers, archetype validators, legacy CKM content, and anything producing Operational Templates from 1.4-era archetypes.
- Superseded by: `AM/AOM2` (aligned with ADL 2; adds differential archetypes, tuples, revised terminology, and BMM-grounded typing).

## Architectural Placement

AOM 1.4 is the legacy constraint-model layer of the AM component: it sits above the RM (whose types it constrains) and below any ADL 1.4 tool or template engine. It is the semantic target of the ADL 1.4 parser and the source model for flattening, validation, and operational-template generation in 1.4-based toolchains. New work should target AOM 2; AOM 1.4 remains normative only for existing ADL 1.4 artefacts.

## When to Read the Full Spec

Consult the full document when implementing or auditing an ADL 1.4 parser, validator, or flattener; when computing specialisation-aware path resolution, `is_subset_of`, `valid_value`, or `default_value`; or when reconciling differences between AOM 1.4 and AOM 2 during migration. For the attribute list, invariants, and function signatures of any individual AOM 1.4 class, call `type_specification_get` against the BMM rather than paraphrasing this digest.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/AOM1.4.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/AOM1.4.md
- Related digests: specs/am-ADL1.4, specs/am2-AOM2
