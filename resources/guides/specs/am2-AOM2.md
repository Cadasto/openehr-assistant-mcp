# Archetype Object Model 2 (AOM 2) — Digest

**Scope:** Syntax-independent object model representing openEHR archetypes and templates in memory, corresponding to ADL 2.
**Component:** AM
**Document:** AOM2
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/AM/development/AOM2.html
**Markdown URL:** https://specifications.openehr.org/releases/AM/development/AOM2.md
**Last updated:** 2026-04-20
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

AOM 2 covers the full archetype artifact: identification and governance
metadata, the constraint tree over a reference model, terminology bindings,
second-order (tuple) constraints, predicate-logic rules, slot semantics, and
template/operational-template composition. It is intentionally decoupled from
the Reference Model (RM) being constrained; AOM instances refer to RM class
and attribute names but do not import RM classes themselves. Out of scope:
concrete textual/JSON syntax (see ADL 2), RM semantics (see RM specs),
persistence/versioning (see CM/SM), and CKM governance workflows.

## Key Classes / Constructs

- `ARCHETYPE`, `AUTHORED_ARCHETYPE`, `DIFFERENTIAL_ARCHETYPE`, `FLAT_ARCHETYPE` — root artifact variants; differential carries only specialization deltas, flat is the fully-expanded inherited form used for validation.
- `TEMPLATE`, `TEMPLATE_OVERLAY`, `OPERATIONAL_TEMPLATE` — slot-filling composition; OPT is the self-contained, fully flattened artifact for runtime use.
- `ARCHETYPE_ID`, `ARCHETYPE_HRID` — structured and human-readable identifiers (namespace, RM publisher, package, class, concept, version).
- `C_OBJECT`, `C_DEFINED_OBJECT`, `C_COMPLEX_OBJECT`, `C_COMPLEX_OBJECT_PROXY`, `C_ARCHETYPE_ROOT` — object-level constraint hierarchy; proxies and roots support internal references and external archetype embedding.
- `C_ATTRIBUTE`, `MULTIPLICITY_INTERVAL` — attribute constraints with existence, cardinality, and ordering.
- `C_PRIMITIVE_OBJECT` family (`C_BOOLEAN`, `C_STRING`, `C_INTEGER`, `C_REAL`, `C_DATE`, `C_TIME`, `C_DATE_TIME`, `C_DURATION`, `C_TERMINOLOGY_CODE`) — leaf constraints on primitive RM types.
- `ARCHETYPE_SLOT`, `ARCHETYPE_ID_CONSTRAINT`, `ASSERTION` — slot inclusion/exclusion rules expressed as assertion trees.
- `ARCHETYPE_TERMINOLOGY`, `ARCHETYPE_TERM_DEFINITION`, `ARCHETYPE_TERM`, `VALUE_SET`, `TERM_BINDING`, `CONSTRAINT_BINDING`, `TERMINOLOGY_CODE` — local term/ac-code definitions and external URI bindings.
- `C_ATTRIBUTE_TUPLE`, `C_PRIMITIVE_TUPLE`, `ARCHETYPE_RULE` — second-order constraints covering co-varying attributes (e.g. `DV_QUANTITY.magnitude`/`units`) and cross-path predicate rules.

## Relations to Other Specs

- **ADL 2** (`am2-ADL2`): textual serialization; every AOM 2 class maps to an ADL 2 construct. Read AOM 2 first to understand what ADL 2 is expressing.
- **OPT 2** (`am2-OPT2`): operational template is an AOM 2 `OPERATIONAL_TEMPLATE` produced by flattening archetype + overlays.
- **AOM 1.4** (`am-AOM1.4`): predecessor; differs in differential form, id-code/at-code/ac-code separation, URI-based terminology bindings, tuple constraints, and rules.
- **BASE** (`foundation_types`, `resource`, `expressions`): AOM reuses `AUTHORED_RESOURCE`, `RESOURCE_DESCRIPTION`, interval and expression types.
- **RM**: constraint targets RM class/attribute names; validation requires an RM BMM.
- **ISO 13606-2:2019**: AOM 2 is broadly aligned with the ISO 13606-2 archetype constraint model and contributes to its revision lineage, while adding differential specialization and URI terminology bindings.

## Architectural Placement

AOM 2 sits at the centre of the AM component: above the BASE foundation and
expression packages, alongside ADL 2 (its syntactic form), and below the
OPT/flattener and downstream tooling. It is the in-memory contract between
archetype editors (e.g. Archetype Designer, LinkEHR, ADL Workbench) and
runtime consumers (validators, template compilers, form/UI generators,
terminology resolvers). Concretely: parsers produce AOM 2 objects; flatteners
consume `DIFFERENTIAL_ARCHETYPE` plus parents and emit `FLAT_ARCHETYPE` or
`OPERATIONAL_TEMPLATE`; persistence/versioning layers (CKM, repositories)
treat AOM 2 as the canonical object graph; runtime data validation walks the
`C_OBJECT`/`C_ATTRIBUTE` tree against RM instances.

## When to Read the Full Spec

Read the full AOM 2 specification when implementing a parser, flattener,
validator, or editor; when resolving differential-vs-flat semantic questions;
when working with tuple constraints, slot assertions, or `ARCHETYPE_RULE`
predicates; or when debugging terminology binding (URI format, constraint
bindings, value-set resolution). This digest is orientation only — it lists
class names but omits attributes, invariants, and function signatures. For
per-class detail (attributes, types, multiplicities, inherited members,
invariants), use the BMM-backed `type_specification_get` tool, which returns
authoritative class definitions from the AOM 2 BMM without re-reading the
prose. Consult the prose spec itself for design rationale, worked examples,
and the formal flattening and conformance algorithms.

## References

- openEHR AM — AOM 2 specification: https://specifications.openehr.org/releases/AM/development/AOM2.html
- openEHR AM — ADL 2 specification: https://specifications.openehr.org/releases/AM/development/ADL2.html
- openEHR AM — Operational Template (OPT 2): https://specifications.openehr.org/releases/AM/development/OPT2.html
- openEHR AM — AOM 1.4 (legacy): https://specifications.openehr.org/releases/AM/latest/AOM1.4.html
- ISO 13606-2:2019 — Health informatics — EHR communication — Part 2: Archetype interchange specification.
