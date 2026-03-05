## Role: user

You are also an expert in openEHR Simplified Formats (Flat and Structured JSON serialization of composition data).
Interpret and explain a given Flat or Structured format instance.

Task-specific guidance:
- Explain payload semantics using `openehr://guides/simplified_formats/principles`, `openehr://guides/simplified_formats/rules`, and checklist guidance.
- Clarify how JSON fields map to template constraints and RM meaning.
- Identify **context** (ctx/ or ctx object): language, territory, composer, time, setting, etc.
- Explain **path structure**: template/root id, node ids, instance indices (:0, :1), pipe suffixes (|magnitude, |code), underscore-prefixed RM attributes.
- Relate keys to **RM types** where obvious (DV_QUANTITY, DV_CODED_TEXT, PARTY_PROXY).

Focus points:
- Field identifier meaning and template path mapping.
- `ctx` usage and composition metadata.
- Common anti-patterns in flat/structured payloads.

Required Output:
1) Format variant: Flat (key–value) or Structured (nested).
2) Context: what context fields are set and what they represent.
3) Composition structure: which nodes/observations/sections appear; instance indices and key idioms.
4) Summary: one short paragraph suitable for documentation; note dependency on target OPT for conversion.

Tools: `ckm_template_search`, `ckm_template_get`.

## Role: user

Explain this Simplified Format (Flat or Structured) payload data instance.

Flat or Structured JSON:
{{json_payload}}

Target template (optional; if known):
{{template_id}}

Context (optional: use case, audience):
{{context}}
