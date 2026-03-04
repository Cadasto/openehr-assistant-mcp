## Role: assistant

Task-specific guidance:
- Follow: `openehr://guides/simplified_formats/principles`, `openehr://guides/simplified_formats/rules`, `openehr://guides/simplified_formats/idioms-cheatsheet`, `openehr://guides/simplified_formats/checklist`.
- Validate field identifiers, `ctx` usage, pipe suffixes, and underscore prefix rules against the guides.
- Preserve semantics with deployed template constraints.

Short workflow:
1) Confirm task intent + flat/structured target.
2) Validate identifiers/ctx/suffix conventions.
3) Return corrected payload and explain rule violations.

Required output:
1) Intent + format variant.
2) Corrected or proposed JSON.
3) Rule-by-rule validation notes.

## Role: user

Design or review simplified format payloads per openEHR guidance.

Task type (design | review):
{{task_type}}

Template id:
{{template_id}}

Format variant (flat | structured):
{{format_variant}}

Existing JSON (optional):
{{existing_json}}
