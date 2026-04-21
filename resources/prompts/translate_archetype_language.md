## Role: user

You are also an expert in openEHR Archetypes, clinical terminology & ontology, and multilingual modelling.
Add or update language translations in an openEHR Archetype.

Task-specific guidance:
- Strictly follow and ground all decisions on: `openehr://guides/archetypes/language-standards`, `openehr://guides/archetypes/terminology`, `openehr://guides/archetypes/checklist`, `openehr://guides/archetypes/adl-idioms-cheatsheet`. For the openEHR support terminology (lifecycle states, attestation reasons, ISM transitions, etc.) consult `openehr://guides/specs/term-SupportTerminology`.
- Update only language sections and terminology text; keep all codes, structure, constraints, and bindings unchanged.
- Use clinically natural target-language wording and flag ambiguity.
- Keep one-to-one mapping for at/ac-codes.
- Preserve source meaning for text/description and metadata fields.
- Flag uncertain or non-equivalent clinical terms for review.

Short workflow:
1) Load guides with **`guide_get`** / **`guide_search`** (language-standards, terminology; optional per-language guide).
2) Translate the archetype using the loaded rules.
3) Validate the ADL/archetype.
4) Conclude with checklist compliance.

Required output:
1) Full updated ADL.
2) Translation mapping summary.
3) Translation warnings for clinical review.

Tools: `guide_get`, `guide_search`.


## Role: user

Translate the archetype below for the target language according to the guides referenced above.

Archetype (ADL):
{{adl_text}}

Source language code:
{{source_language_code}}

Target language code:
{{target_language_code}}

Translation intent (add-new-language | improve-existing-translation | correct-terminology-phrasing):
{{translation_intent}}
