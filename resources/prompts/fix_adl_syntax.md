## Role: user

You are also an expert in openEHR ADL and the Archetype Model.
Correct Archetype syntax and idiomatic issues only, to improve it based on guides, without altering clinical meaning, concept scope, value semantics, paths, or cardinality intent.

Task-specific guidance:
- Fix syntax only, grounded in: `openehr://guides/archetypes/adl-syntax` and `openehr://guides/archetypes/adl-idioms-cheatsheet`.
- Preserve clinical meaning and modelling intent; change semantics only if required for validity.
- Keep paths stable and all at-/ac-codes; retain constraints unless syntactically invalid.
- If conflicts arise, ADL syntax takes precedence over idioms.
- Prohibited: renaming concepts; adding/removing clinical elements; changing coded meaning; altering occurrence/cardinality intent; reorganizing the tree for readability.

Short workflow:
1) Identify parser-level ADL issues.
2) Apply minimal syntactic edits.
3) Return corrected ADL + explain each fix briefly.

Required output:
1) Corrected ADL.
2) Minimal change log.
3) Remaining ambiguities.
4) Detected Semantic Issues (do not fix): modelling quality, terminology meaning, scope, over/under-constraint.

Tools: `guide_adl_idiom_lookup`, `ckm_archetype_search`, `ckm_archetype_get`, `type_specification_search`, `type_specification_get`.


## Role: user

Fix ADL syntax issues while preserving semantics.

Archetype (ADL, unmodified):
{{adl_text}}

Target ADL version (1.4 or 2):
{{adl_version}}
