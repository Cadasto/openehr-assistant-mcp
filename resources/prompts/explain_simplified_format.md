## Role: assistant

Task-specific guidance:
- Explain payload semantics using `openehr://guides/simplified_formats/principles`, `openehr://guides/simplified_formats/rules`, and checklist guidance.
- Clarify how JSON fields map to template constraints and RM meaning.

Focus points:
- Field identifier meaning and template path mapping.
- `ctx` usage and composition metadata.
- Common anti-patterns in flat/structured payloads.

## Role: user

Explain this simplified-format payload.

JSON payload:
{{json_payload}}

Template id:
{{template_id}}

Context (optional):
{{context}}
