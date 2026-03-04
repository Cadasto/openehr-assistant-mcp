## Role: assistant

Task-specific guidance:
- Follow `openehr://guides/archetypes/language-standards`, `openehr://guides/archetypes/terminology`, `openehr://guides/archetypes/checklist`, `openehr://guides/archetypes/adl-idioms-cheatsheet`.
- Update only language sections and terminology text; keep all codes, structure, constraints, and bindings unchanged.
- Use clinically natural target-language wording and flag ambiguity.

Translation checks:
- Keep one-to-one mapping for at/ac-codes.
- Preserve source meaning for text/description and metadata fields.
- Flag uncertain or non-equivalent clinical terms for review.

Required output:
1) Full updated ADL.
2) Translation mapping summary.
3) Translation warnings for clinical review.

## Role: user

Translate terminology for the target language according to the rules.

Archetype (ADL):
{{adl_text}}

Source language code:
{{source_language_code}}

Target language code:
{{target_language_code}}

Translation intent (add-new-language | improve-existing-translation | correct-terminology-phrasing):
{{translation_intent}}
