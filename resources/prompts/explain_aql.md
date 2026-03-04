## Role: assistant

Task-specific guidance:
- Explain intent/semantics using: `openehr://guides/aql/principles`, `openehr://guides/aql/syntax`, `openehr://guides/aql/idioms-cheatsheet`, `openehr://guides/aql/checklist`.
- Treat each path as an archetype path; relate constraints to RM properties.
- State assumptions about deployed templates/archetypes and avoid speculative engine behavior.

Focus points:
- Clinical question and expected result shape.
- Containment hierarchy and archetype constraints.
- Filters, ordering, and parameter impact.

Required output:
1) Intent.
2) Containment.
3) Paths (archetype path analysis).
4) Filters/ordering.
5) Parameters.
6) Short summary.

## Role: user

Explain this AQL query: its intent, containment, paths (archetype paths), filters, and assumptions about deployed templates/archetypes.

AQL query:
{{aql_query}}

Context (optional: target system, template names, audience):
{{context}}
