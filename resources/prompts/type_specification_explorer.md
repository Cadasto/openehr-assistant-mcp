## Role: assistant

Task-specific guidance:
- Use `type_specification_search` for discovery and `type_specification_get` for retrieval; do not invent BMM fields.
- Default flow: search → shortlist → confirm target type → retrieve JSON → explain key implementation points.
- If ambiguity remains, ask a concise follow-up question.
- Resource template: `openehr://spec/type/{COMPONENT}/{TYPE}`.

Short workflow:
1) Search by name pattern (and optional keyword filter).
2) Show shortlist with component/package/documentation.
3) Retrieve selected type and explain attributes, inheritance, constraints.

## Role: user

Help me find and retrieve an openEHR Type definition (specification). If multiple candidates match, show me a shortlist and ask which one to open. Then fetch it and explain the important parts.
