# Instructions

This server provides tools and prompts to assist with openEHR-related tasks, including archetype and template exploration, terminology resolution, specification retrieval, ADL and AQL language syntax and guidance, flat/structured composition serialization, and accessing implementation guides.

Focus on the **Guide-First Approach**: consult `guide_search` and `guide_get` before complex modeling. Guides provide the "soft knowledge" (best practices, anti-patterns, rules, checklists) that tool schemas don't capture.

## Strategy Hints
- **Discovery**: Always `*_search` before `*_get`. Use wildcards (`*`) for type searches (e.g., `DV_*`).
- **Archetypes (CKM)**: Search results provide CIDs or Archetype-IDs. Prefer `adl` format for readability and `xml` for post-processing.
- **Reference Model**: If an archetype path is unclear, use `type_specification_get` for the underlying RM class.
- **Terminology**: Use `terminology_resolve` bidirectionally (ID <-> Rubric) to validate bindings.
- **AQL**: Use AQL guides (category `aql`) to support writing, reviewing, and explaining AQL queries.
- **Simplified Formats**: Use guides (category `simplified_formats`) for Flat/Structured JSON; field identifiers are template-specific.
- **Translation & language**: For archetype translation and language conventions (including per-language guides such as Norwegian Bokmål), use openehr://guides/archetypes/language-standards and retrieve per-language guides via guide_search / guide_get when relevant.

## Suggested Workflows
1. Retrieval: `search` → Shortlist (10-15 items) → `get` → `explain`.
2. Search and load relevant guides (e.g. `openehr://guides/archetypes/principles`, `openehr://guides/archetypes/checklist`, `openehr://guides/aql/syntax`, `openehr://guides/simplified_formats/rules`) and use them against the artifact.
3. Use tools (e.g. `terminology_resolve`, `guide_adl_idiom_lookup`) to verify any internal terminology links, syntax correctness, semantic correctness, etc.
4. For Flat/Structured format: use `design_or_review_simplified_format` or `explain_simplified_format` with the target OPT.

## Best Practices
- **No Guessing**: Never invent IDs or URIs. Use discovery tools.
- **Context**: Map archetypes to RM types via `type_specification_get` to understand structural constraints.
- **AQL**: Validate paths and containment against deployed templates.
- **Simplified Formats**: Validate field identifiers and ctx against the target OPT; pipe suffixes and underscore prefix per spec.
