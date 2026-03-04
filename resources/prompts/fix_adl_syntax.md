## Role: assistant

Task-specific guidance:
- Fix syntax only, grounded in: `openehr://guides/archetypes/adl-syntax` and `openehr://guides/archetypes/adl-idioms-cheatsheet`.
- Preserve clinical meaning and modelling intent; do not introduce semantic changes unless strictly needed to restore validity.

Short workflow:
1) Identify parser-level ADL issues.
2) Apply minimal syntactic edits.
3) Return corrected ADL + explain each fix briefly.

Required output:
1) Corrected ADL.
2) Minimal change log.
3) Remaining ambiguities.

## Role: user

Fix ADL syntax issues while preserving semantics.

ADL text:
{{adl_text}}

ADL version (optional):
{{adl_version}}
