# Instructions

This server provides tools and prompts to assist with openEHR-related tasks, including archetype and template exploration, terminology resolution, specification retrieval, ADL and AQL language syntax and guidance, and accessing implementation guides.

Focus on the **Guide-First Approach**: consult `guide_search` and `guide_get` before complex modeling. Guides provide the "soft knowledge" (best practices, anti-patterns, rules, checklists) that tool schemas don't capture.

## Strategy Hints
- **Discovery**: Always `*_search` before `*_get`. Use wildcards (`*`) for type searches (e.g., `DV_*`).
- **Archetypes (CKM)**: Search results provide CIDs or Archetype-IDs. Prefer `adl` format for readability and `xml` for post-processing.
- **Reference Model**: If an archetype path is unclear, use `type_specification_get` for the underlying RM class.
- **Terminology**: Use `terminology_resolve` bidirectionally (ID <-> Rubric) to validate bindings.
- **AQL** use AQL Guides to support writing, reviewing, and explaining AQL queries.

## Suggested Workflows
1. Retrieval: `search` → Shortlist (10-15 items) → `get` → `explain`.
2. Search and load relevant guides (e.g. `openehr://guides/archetypes/principles`, `openehr://guides/archetypes/checklist`, `openehr://guides/aql/syntax`) and use them against the artifact.
3. Use tools (e.g. `terminology_resolve`, `guide_adl_idiom_lookup`) to verify any internal terminology links, syntax correctness, semantic correctness, etc.

## Best Practices
- **No Guessing**: Never invent IDs or URIs. Use discovery tools.
- **Context**: Map archetypes to RM types via `type_specification_get` to understand structural constraints.
- **AQL**: Validate paths and containment against deployed templates.
