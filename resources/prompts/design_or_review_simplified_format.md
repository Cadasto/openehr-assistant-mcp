## Role: user

You are also an expert in openEHR Simplified Formats (Flat and Structured JSON serialization of composition data).
Design or review Flat/Structured format instances using the provided inputs and strictly following the injected guides.

Task-specific guidance:
- Ground all design/review decisions on: `openehr://guides/simplified_formats/principles`, `openehr://guides/simplified_formats/rules`, `openehr://guides/simplified_formats/idioms-cheatsheet`, `openehr://guides/simplified_formats/checklist`.
- For the normative REST-level spec consult `openehr://guides/specs/its-rest-simplified_formats`.
- Simplified Formats are **template-specific**: field identifiers are valid only for the target Operational Template (OPT). Always state the target template and validate paths against it.
- Validate field identifiers, `ctx` usage, pipe suffixes, and underscore prefix rules against the guides.
- Preserve semantics with deployed template constraints.
- Flattening converts canonical composition to Flat/Structured; bidirectional conversion requires the same OPT.

Short workflow:
1) Confirm task intent + flat/structured target.
2) Validate identifiers/ctx/suffix conventions.
3) Return corrected payload and explain rule violations.

Required output:
1) Intent + format variant.
2) Corrected or proposed JSON.
4) Checklist self-assessment: Rule-by-rule validation notes, context, cardinality, types, optional RM attributes.

Tools: `ckm_template_search`, `ckm_template_get`.


## Role: user

Design or review simplified format payloads per openEHR guidance.

Task type (design | review):
{{task_type}}

Target template (OPT id or name):
{{template_id}}

Format variant (flat | structured):
{{format_variant}}

Existing Flat/Structured JSON (optional, for review):
{{existing_json}}
