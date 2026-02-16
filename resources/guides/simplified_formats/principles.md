# openEHR Simplified Formats — Principles

**Scope:** Foundational principles for Flat and Structured (simplified) serialization of openEHR data
**Keywords:** simplified format, flat format, structured format, flattening, Web Template, WT, JSON, serialization

---

## What Simplified Formats Are

Simplified Formats are **JSON serializations** of openEHR data that use **human-readable field identifiers** derived from the Operational Template (OPT) instead of canonical archetype paths and full RM structure. They are not a query or modelling language—they are a **serialization format** for composition data.

- **Flat format**: key–value pairs at a single level; keys are full paths (template_id + node path + suffixes).
- **Structured format**: nested JSON preserving hierarchy; same semantics, different structure.
- **MIME types**: Flat `application/openehr.wt.flat+json`; Structured `application/openehr.wt.structured+json`.

---

## Relationship to Canonical and OPT

- **Canonical** (JSON/XML): Full RM structure, all mandatory fields, archetype paths; self-standing and verbose.
- **Flattening** is the process of converting canonical (or building directly) into Flat or Structured format.
- **Template-specific**: Field identifiers are generated from and **valid only for a specific OPT**. The same OPT is required to interpret or convert data.

Bidirectional conversion between simplified and canonical is **machine-generated** and requires the underlying OPT; semantics are preserved.

---

## Design Goals

- **Developer-friendly**: Human-readable paths (e.g. `temperature|magnitude`) instead of long archetype paths.
- **Context separation**: Composition-level metadata under `ctx/` (or `ctx` in Structured) so clinical data is clearly separated.
- **Element–value simplification**: In Flat format there is no distinction between an ELEMENT and its value; the element *is* the value.
- **Reduced boilerplate**: No `_type` on every object; intermediate RM types (e.g. ITEM_TREE, HISTORY) are omitted or folded into paths.

---

## Key Principles

1. **OPT as schema**: Field identifiers and structure come from the Web Template (WT) / OPT. Always validate and generate against the target template.
2. **Paths, not columns**: Keys are paths (template_id/node_id/... with instance indices and attribute suffixes), not arbitrary names.
3. **Context first**: Mandatory context (e.g. language, territory) and optional context (composer, time, setting) are separated; use `ctx/` prefix in Flat.
4. **RM attributes**: Optional RM attributes use underscore prefix (`_uid`, `_end_time`, `_normal_range`); pipe suffix (`|magnitude`, `|code`) for DV and party attributes.
5. **Instance indices**: Repeating nodes use zero-based colon notation (`any_event:0`, `any_event:1`).

---

## When to Use

- Form-based data entry and simple integrations.
- Systems that commit composition data against a known template.
- When readability and minimal payload matter more than self-standing canonical documents.

Canonical remains the interoperable, self-standing representation; simplified formats are a practical interface layer.

---
