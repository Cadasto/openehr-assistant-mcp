# openEHR Simplified Formats — Design & Review Checklist

**Purpose:** Pre-flight checklist for creating or reviewing Flat/Structured format instances
**Related:** openehr://guides/simplified_formats/principles, openehr://guides/simplified_formats/rules
**Keywords:** flat, structured, validation, checklist, OPT, context, cardinality

---

## 1. Template and Scope

- [ ] **Target OPT** (and Web Template if used) is identified; field identifiers are valid for that template only.
- [ ] **Format variant** chosen: Flat (key–value) or Structured (nested) per use case.
- [ ] **MIME type** acknowledged: `application/openehr.wt.flat+json` or `application/openehr.wt.structured+json`.

---

## 2. Context (ctx)

- [ ] **Mandatory context** present: at least language and territory (per spec and template).
- [ ] **Optional context** (composer, time, setting, etc.) correctly prefixed (`ctx/` in Flat, `ctx` object in Structured).
- [ ] **Time**: `ctx/time` or equivalent set or understood to default to server time where applicable.

---

## 3. Field Identifiers (Paths)

- [ ] **Paths** built from template node IDs (normalised names); no ad-hoc keys.
- [ ] **Separators**: `/` between segments; `|` before attribute suffixes; `_` prefix for optional RM attributes only.
- [ ] **Instance indices** zero-based (`:0`, `:1`) for repeating nodes; cardinality respected.
- [ ] **Suffixes** match RM type (e.g. DV_QUANTITY: `|magnitude`, `|unit`; DV_CODED_TEXT: `|code`, `|value`, `|terminology`).

---

## 4. Flat-Specific

- [ ] All keys **fully qualified** from root (template_id or root id + path).
- [ ] No ELEMENT/value distinction; key points to value attributes directly.
- [ ] Context keys use `ctx/` prefix consistently.

---

## 5. Structured-Specific

- [ ] **Hierarchy** preserved; arrays used for values as per spec.
- [ ] **Context** under single `ctx` property.
- [ ] Pipe-prefixed properties for attribute suffixes.

---

## 6. RM and Validation

- [ ] **Optional RM attributes** (e.g. `_uid`, `_end_time`, `_normal_range`) use underscore prefix only where defined.
- [ ] **Cardinality** of repeated nodes not exceeded; mandatory nodes present where required.
- [ ] **Terminology** codes and terminology ids valid where applicable.
- [ ] **Data types** match template (number, string, ISO datetime as appropriate).

---

## 7. Conversion and Interop

- [ ] If converting **to/from canonical**: same OPT used for serialization and deserialization.
- [ ] **|raw** usage (if any): embedded JSON includes `_type` and conforms to RM.

---
