# Archetype Object Model 2 (AOM 2) — Digest

**Scope:** Syntax-independent object model representing openEHR archetypes and templates in memory, corresponding to ADL 2.
**Component:** AM
**Document:** AOM2
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/AOM2.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/AOM2.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/am2-ADL2, openehr://guides/specs/am2-OPT2, openehr://guides/specs/am-AOM1.4
**Keywords:** AOM, AOM 2, archetype object model, C_OBJECT, constraint, differential, validity

---

## Purpose

AOM 2 is the normative, format-independent object model describing the runtime
structure and semantics of openEHR archetypes and templates. It defines the
classes, associations, and invariants used by tooling (parsers, validators,
flatteners, editors, form generators) to load, manipulate, compare, and
serialize archetype artifacts in any concrete syntax (ADL 2, XML, JSON, ODIN).
AOM 2 is the semantic target for ADL 2 source and the input to operational
template flattening; it also supplies the conformance rules used by the
Archetype Definition Language 2 (ADL 2) validator (`valid_value()`,
`codes_conformant()`, specialization-depth checks).

## Scope

- In: the full archetype artifact — identification and governance metadata, the constraint tree over a reference model, terminology bindings, second-order (tuple) constraints, rules, slot semantics, template/operational-template composition, validity rules, and flattening. AOM instances name RM classes and attributes but do not import RM classes.
- Out: concrete textual/JSON syntax (ADL 2), RM semantics (RM specs), persistence and versioning, and CKM governance workflows.

## Key Classes / Constructs

- `ARCHETYPE` (abstract), `AUTHORED_ARCHETYPE` — root artifact classes; the `is_differential` flag separates the differential source form (only specialization deltas) from the flat, fully-expanded form used for validation. The BMM defines no `DIFFERENTIAL_ARCHETYPE` or `FLAT_ARCHETYPE` class, although some prose still uses those names.
- `TEMPLATE`, `TEMPLATE_OVERLAY`, `OPERATIONAL_TEMPLATE` — slot-filling composition; OPT is the self-contained, fully flattened artifact for runtime use.
- `ARCHETYPE_HRID` — structured human-readable identifier (namespace, RM publisher/package/class, concept, version), with computed `semantic_id` / `physical_id` forms; machine identification via `ARCHETYPE.uid`.
- `C_OBJECT`, `C_DEFINED_OBJECT`, `C_COMPLEX_OBJECT`, `C_COMPLEX_OBJECT_PROXY`, `C_ARCHETYPE_ROOT` — object-level constraint hierarchy; proxies and roots support internal references and external archetype embedding.
- `C_ATTRIBUTE` — attribute constraints with existence, cardinality, and sibling ordering (`sibling_order` on child `C_OBJECT`s).
- `C_PRIMITIVE_OBJECT` family (`C_BOOLEAN`, `C_STRING`, `C_INTEGER`, `C_REAL`, `C_DATE`, `C_TIME`, `C_DATE_TIME`, `C_DURATION`, `C_TERMINOLOGY_CODE`) — leaf constraints on primitive RM types.
- `ARCHETYPE_SLOT`, `ARCHETYPE_ID_CONSTRAINT`, `ASSERTION` — slot inclusion/exclusion rules expressed as assertion trees.
- `ARCHETYPE_TERMINOLOGY`, `ARCHETYPE_TERM`, `VALUE_SET` — local id/at/ac-code term definitions, explicit value sets, and external URI-based term bindings.
- `C_ATTRIBUTE_TUPLE`, `C_PRIMITIVE_TUPLE` — second-order (tuple) constraints covering co-varying attributes (e.g. `DV_QUANTITY.magnitude`/`units`).
- Rules package — archetype `rules` statements (declarations, assignments, assertions) built on the Basic Expression Language (`lang.beom` package), plus `EXPR_ARCHETYPE_REF` (archetype-path variables) and `EXPR_ARCHETYPE_ID_CONSTRAINT` (slot archetype-id matchers).

## Relations to Other Specs

- Depends on: `BASE/foundation_types` (leaf types, `Interval<T>`), `BASE/base_types` (`VALIDITY_KIND`, `VERSION_STATUS`), `BASE/resource` (`AUTHORED_RESOURCE`, `RESOURCE_DESCRIPTION`), `LANG/BEL` (rules, `lang.beom`); validation against RM class/attribute names needs an RM BMM.
- Consumed by: `AM/ADL2` (textual serialization; every AOM 2 class maps to an ADL 2 construct), `AM/OPT2` (`OPERATIONAL_TEMPLATE` produced by flattening archetype + overlays).
- Supersedes: `AM/AOM1.4` — differs in differential form, the id/at/ac-code scheme (ADL/AOM 2.4 re-introduces the ADL 1.4 at-code system as the openEHR primary one), URI-based terminology bindings, tuple constraints, and rules.
- Aligns with: ISO 13606-2:2019 — AOM 2 is broadly aligned with the ISO 13606-2 archetype constraint model and contributes to its revision lineage, while adding differential specialization and URI terminology bindings.

## Architectural Placement

AOM 2 sits at the centre of the AM component: above the BASE foundation and
expression packages, alongside ADL 2 (its syntactic form), and below the
OPT/flattener and downstream tooling. It is the in-memory contract between
archetype editors (e.g. Archetype Designer, LinkEHR, ADL Workbench) and
runtime consumers (validators, template compilers, form/UI generators,
terminology resolvers). Concretely: parsers produce AOM 2 objects; flatteners
consume differential archetypes plus their flat parents and emit flat archetypes or an
`OPERATIONAL_TEMPLATE`; persistence/versioning layers (CKM, repositories)
treat AOM 2 as the canonical object graph; runtime data validation walks the
`C_OBJECT`/`C_ATTRIBUTE` tree against RM instances.

## When to Read the Full Spec

Read the full AOM 2 specification when implementing a parser, flattener,
validator, or editor; when resolving differential-vs-flat semantic questions;
when working with tuple constraints, slot assertions, or `rules`-section
statements; or when debugging terminology binding (URI format, value-set
resolution via `value_set_expanded` / `value_set_resolved`). This digest is orientation only — it lists
class names but omits attributes, invariants, and function signatures. For
per-class detail (attributes, types, multiplicities, inherited members,
invariants), use the BMM-backed `type_specification_get` tool, which returns
authoritative class definitions from the AOM 2 BMM without re-reading the
prose. Consult the prose spec itself for design rationale, worked examples,
and the formal flattening and conformance algorithms.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/AM/development/AOM2.html
- Full spec (Markdown): https://specifications.openehr.org/releases/AM/development/AOM2.md
- Related digests: specs/am2-ADL2, specs/am2-OPT2, specs/am-AOM1.4, specs/am-Identification, specs/lang-BEL
- ISO 13606-2:2019 — Health informatics — EHR communication — Part 2: Archetype interchange specification.
