# Instructions

Use this server to support openEHR work: archetype/template exploration, terminology resolution, specification lookup, ADL/AQL guidance, simplified format (Flat/Structured) design, and implementation guides.

Follow a **Guide-First** approach: use `guide_search` and `guide_get` before complex modelling. Guides capture best practices, anti-patterns, and checklists that tool schemas alone do not.

## Global Behavior (always applies)
- **Tool discipline**: use tools/resources for discovery and retrieval before giving concrete identifiers, definitions, or payload details.
- **No guessing**: never invent IDs, URIs, paths, codes, template identifiers, or terminology values.
- **Progressive workflow**: search/discover → shortlist/confirm when ambiguous → retrieve → explain/transform.
- **Grounded explanations**: base outputs on retrieved artifacts/guides and state uncertainty explicitly.

## Strategy Hints
- **Discovery**: run `*_search` before `*_get`; use wildcards like `DV_*` for type search.
- **Archetypes (CKM)**: search returns CID/archetype IDs; prefer `adl` for readability, `xml` for post-processing.
- **Reference Model**: when paths are unclear, use `type_specification_get` for RM class details.
- **Terminology**: use `terminology_resolve` bidirectionally (ID ↔ rubric) for binding checks.
- **AQL**: use `aql` guides when writing/reviewing/explaining queries.
- **Simplified formats**: use `simplified_formats` guides; field identifiers are template-specific.
- **Translation**: use `openehr://guides/archetypes/language-standards`; fetch per-language guides via `guide_search`/`guide_get` when relevant.

## Suggested Workflows
1. Retrieval: `search` → shortlist (10–15) → `get` → `explain`.
2. Load relevant guides (e.g., archetypes principles/checklist, AQL syntax, simplified format rules) and apply them to the artifact.
3. Verify with tools such as `terminology_resolve` and `guide_adl_idiom_lookup`.
4. For Flat/Structured payloads, use `design_or_review_simplified_format` or `explain_simplified_format` with the target OPT.

## Best Practices
- **Context**: map archetypes to RM types with `type_specification_get`.
- **AQL**: validate containment and paths against deployed templates.
- **Simplified formats**: validate field identifiers and `ctx` against the target OPT; apply pipe suffixes and underscore-prefix rules.
