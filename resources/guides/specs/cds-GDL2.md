# Guideline Definition Language 2 (GDL 2) — Digest

**Scope:** Formal, model-agnostic language for expressing executable clinical decision-support logic as production rules over archetype and template paths.
**Component:** CDS
**Document:** GDL2
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/CDS/development/GDL2.html
**Markdown URL:** https://specifications.openehr.org/releases/CDS/development/GDL2.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/lang-EL, openehr://guides/specs/am2-ADL2
**Keywords:** GDL, GDL 2, guideline, CDS, decision support, archetype path, rules

---

## Purpose

GDL 2 (Guideline Definition Language 2) defines a formal, platform- and terminology-agnostic language for encoding clinical decision-support (CDS) logic as production rules ("when-then" assertions) whose operands are bound to archetype and template paths rather than to a proprietary information model. It is the successor to GDL 1 and is designed so that guidelines authored once can execute against openEHR, ISO 13606, or HL7 FHIR data sources, emit structured result objects, and be maintained by clinical domain experts independently of any specific runtime implementation.

## Scope

- In: the abstract artefact model of a guideline (metadata, definition, terminology), data-binding to archetype/template paths with predicates, pre-conditions, rule structure (`when` assertions / `then` statements) with salience-style priority, expression syntax derived from ADL assertions, local gt-code terminology with external bindings, and template-based output object construction.
- Out: the execution engine architecture and conformance testing (delegated to implementers), concrete serialisation details beyond references to openEHR ODIN, workflow orchestration across multiple guidelines, UI rendering of results, and any clinical content of guidelines themselves — GDL 2 supplies the form, not the knowledge.

## Key Classes / Constructs

- Guideline artefact — top-level structure carrying `gdl_version`, `id`, `concept`, authored-resource `description` metadata, the `definition`, and the local terminology with gt-codes (`term_definitions`).
- `definition` section — container holding `pre_conditions`, `data_bindings`, ordered `rules`, and optional reporting `templates`.
- Rule — a `when` condition list and `then` action list with a numeric `priority` controlling firing order (higher first).
- `data_bindings` — map archetype/template paths (optionally scoped by `template_id`) to gt-code variables used throughout the definition.
- `pre_conditions` — guideline-wide boolean assertions gating whether any rules apply to the current input set.
- `predicates` — filter expressions attached to a data-binding to select matching instances (e.g. latest, coded value).
- `then` actions — assign values to variables, set output fields, or invoke a reporting template via `use_template`.
- `templates` — output shapes (keyed by `model_id` / `template_id`, with `{}` variable substitution) used to emit structured decision results such as CDS-Hooks cards.
- GDL2 Object Model — three packages, `guideline`, `expression`, and `terminology`; a GDL source is a serialised instance of this model in ODIN or JSON.

## Relations to Other Specs

- Depends on: `BASE/foundation_types`, `RM/common` and `RM/data_types` (for value semantics when bound to openEHR sources), `AM/ADL2` and `AM/AOM2` (archetype/template path model that bindings reference), the openEHR Expression Language (`LANG/EL`) and the ADL assertion syntax (which GDL 2 expressions are loosely based on), and `BASE/resource` plus openEHR ODIN for serialisation.
- Consumed by: CDS execution engines, guideline authoring tools, and platform services that expose decision-support endpoints; GDL 2 is also intended to interoperate with ISO 13606 and HL7 FHIR data sources via alternative bindings.

## Architectural Placement

Sits in the openEHR CDS component as the authoring-level formalism between clinical models (AM archetypes/templates) and runtime decision services: guidelines consume typed data via model paths, evaluate rules using expressions related to the openEHR Expression Language, and emit template-shaped results. It is deliberately orthogonal to the Reference Model so the same guideline can run on non-openEHR back-ends.

## When to Read the Full Spec

Read the full specification when authoring or implementing a GDL 2 engine (parser, evaluator, binding resolver), designing a migration path from GDL 1, encoding rules that require the full expression grammar (arithmetic, temporal, terminology-aware operators), or specifying how predicates, priorities, and pre-conditions must interact under a particular conformance profile. The digest is sufficient for orientation, roles in the architecture, and deciding whether GDL 2 is the right tool; it does not substitute for the normative syntax tables and semantic rules.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/CDS/development/GDL2.html
- Full spec (Markdown): https://specifications.openehr.org/releases/CDS/development/GDL2.md
- Related digests: specs/lang-EL, specs/am2-ADL2
