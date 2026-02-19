# openEHR Archetype Language Standards Guide

**Scope:** Language, spelling, terminology, and translation conventions for archetype authoring and localization  
**Related:** `openehr://guides/archetypes/terminology`, `openehr://guides/archetypes/reference-formatting`  
**Keywords:** language, English, international, translation, cultural neutrality, terminology, metadata, sentence case

---

## A. Original Language Requirement

- **A1:** All archetypes for international CKM federation SHALL use English (ISO-639-1 code: `en`) as the original language.
- **A2:** English as the original language prevents message degradation through repeated translations — semantic drift from translation chains.
- **A3:** The `original_language` attribute in archetype metadata SHALL be set to `en` (or equivalent ISO-639-1 code).
- **A4:** All term definitions, descriptions, purpose, use, and misuse statements SHALL be authored in English before translation.
- **A5:** Internal terminology (at-codes, ac-codes) identifiers and structure SHALL NOT change across translations; translations apply only to human-readable text.

---

## B. English Language Conventions

### Metadata Case and Punctuation

- **B1:** Concept names and data element names SHALL use sentence case: first letter uppercase, subsequent letters lowercase, except proper nouns and acronyms.
  - Correct: "Neurologic assessment in neuro-oncology (NANO) scale"
  - Incorrect: "Neurologic Assessment In Neuro-Oncology Scale (NANO)"

- **B2:** Abbreviations SHALL be placed immediately after the words they abbreviate, not at the end.
  - Correct: "Neurologic Assessment in Neuro-Oncology (NANO) scale"
  - Incorrect: "Neurologic Assessment in Neuro-Oncology scale (NANO)"

- **B3:** All descriptions, purpose statements, and data element names SHALL end with a full stop (period) or equivalent terminal punctuation.

- **B4:** Use hyphens with spaces for bullet points and lists: `- item text`

### Metadata Statement Patterns

- **B5:** Purpose statements SHALL begin with "To record..." followed by a clinically-oriented definition.
  - Example: "To record the local measurement of arterial blood pressure which is a surrogate for arterial pressure in the systemic circulation."

- **B6:** Use statements SHALL begin with "Use to record..." followed by clinical context and application boundaries.
  - Example: "Use to record all representations of systemic arterial blood pressure measurement, no matter which method or body location is used to record it."

- **B7:** Misuse statements SHALL begin with "Not to be used..." followed by what archetype or approach should be used instead.
  - Example: "Not to be used to record 24-hour ambulatory blood pressure monitoring; use the OBSERVATION.ambulatory_blood_pressure archetype instead."

- **B8:** All Purpose, Use, and Misuse statements SHALL end with a full stop.

### Data Element Naming Patterns

- **B9:** Data element names SHALL use established clinical naming conventions:
  - `<XYZ> tested` or `<XYZ> examined` — identify the object examined
  - `<XYZ> name` — the name or code assigned to the concept
  - `<XYZ> commenced` — start date or time
  - `<XYZ> ceased` — stop date or time
  - `Category` — broader, less specific grouping
  - `Type` — narrower, more specific classification

- **B10:** Example: `Category` for an overarching grouping for an intervention identified in 'Intervention name'; `Type` for a more specific method or process for delivering an intervention identified in ‘Intervention name’.

---

## C. International English and Cultural Neutrality

- **C1:** Archetypes SHALL avoid locale-specific semantics, assumptions about healthcare systems, or regional medical references.

- **C2:** Clinical terminology choices SHALL be internationally recognized and, where possible, aligned with authoritative standards.

- **C3:** When equivalent medical terms exist across English variants (American, British, Australian), use terminology that is clinically unambiguous and internationally accepted.
  - Example: For screening and consent contexts, use "Unsure" (indicates personal doubt) rather than "Uncertain" (implies unpredictability) or "Indeterminate" (implies clinical assessment).
  - Rationale: This aligns with established psychological and clinical terminology in international consensus.

- **C4:** Do NOT encode regional measurements, healthcare system assumptions, or jurisdiction-specific legal requirements at the archetype level.

- **C5:** Avoid idiomatic expressions, colloquialisms, or culturally-specific references in term definitions and descriptions.

- **C6:** Use neutral, clinically-standard phrasing for all descriptions; prefer objective clinical language over subjective or narrative phrasing.

---

## D. Terminology Consistency

- **D1:** The same clinical concept SHALL be represented by the same internal term (at-code) and description throughout the archetype.

- **D2:** If a concept has multiple valid names or synonyms, consolidate them into a single canonical term with synonyms noted in the description or Comment section.

- **D3:** Consistency is enforced through:
  - Stable internal terminology (at-codes) linked to fixed definitions
  - External terminology bindings (SNOMED CT, LOINC, etc.) providing semantic equivalence
  - Translation mechanisms preserving clinical intent across languages

- **D4:** Spelling variants (e.g., British "haemoglobin" vs. American "hemoglobin", or "foetus" vs. "fetus") are handled by external terminology bindings, not by creating multiple archetype terms.
  - Rationale: A single SNOMED CT concept code represents the same clinical entity regardless of spelling variant; external bindings provide disambiguation.

- **D5:** Do not create separate at-codes for spelling or terminology variants of the same clinical concept.

---

## E. Translation and Per-Language Framework

### Translation Philosophy

- **E1:** Translations are NOT literal word-for-word conversions; they SHALL preserve clinical intent while using natural phrasing in the target language.

- **E2:** Each translated archetype SHALL use the target language's established clinical register, terminology, and grammatical conventions.

- **E3:** Translated term definitions (term_definitions in target language) SHALL be clinically appropriate and idiomatic in the target language, even if phrasing differs from the English source.

- **E4:** All metadata elements (Purpose, Use, Misuse, descriptions) SHALL be translated to reflect clinical practice and terminology in the target language/locale.

- **E5:** Translations SHALL NOT alter:
  - Internal identifiers (at-codes, ac-codes)
  - Archetype structure (path hierarchy)
  - Computable constraints (cardinalities, occurrences)
  - External terminology bindings (SNOMED CT, LOINC codes remain identical)

- **E6:** Consistency within a translated archetype SHALL be maintained: the same translated term SHOULD be used for the same at-code throughout the translation.

### Per-Language Guidance Structure

- **E7:** Language-specific guidance documents MAY be created following the pattern: `language-standards-<iso-639-1-code>.md`
  - Examples: `language-standards-nb.md` (Norwegian Bokmål), `language-standards-de.md` (German), `language-standards-fr.md` (French)

- **E8:** Per-language guides SHALL:
  - Establish clinical terminology standards for the target language
  - Document established translations of common archetype terms
  - Provide examples of Purpose/Use/Misuse statements in the target language
  - Reference authoritative clinical terminology sources for the language/region
  - Maintain consistency with this base English language standards guide

- **E9:** Per-language guides SHOULD extend this guide by:
  - Specifying which translation approach (formal, natural, hybrid) is appropriate for the language
  - Documenting established terminology in target-language clinical registers
  - Providing example archetypes with proper translations

---

## F. Terminology Binding and Spelling/Terminology Variants

- **F1:** External terminology bindings (SNOMED CT, LOINC, ICD) provide semantic clarity independent of spelling variants or terminology choices in the archetype text.

- **F2:** When multiple spelling variants exist for the same concept (e.g., "fetus" and "foetus", "haemoglobin" and "hemoglobin"), a single SNOMED CT concept code represents both variants.
  - Example: SNOMED CT code 83418008 represents the concept "Human fetus" regardless of English spelling variant used in an archetype's term text.

- **F3:** Spelling and terminology variants across languages are handled by:
  - Binding the archetype at-code to the correct SNOMED CT concept
  - Using the target language's standard terminology for the translation
  - Allowing the external terminology system to manage semantic equivalence

- **F4:** This approach enables:
  - Consistent English source text without spelling variations
  - Flexible, natural translations in each target language
  - Unambiguous semantic reference through external codes
  - Independent evolution of archetype and terminology systems

---

## G. Examples of Proper Archetype Language

### Example 1: Concept Definition

**English (Original):**

```text
Concept: Blood Pressure
Purpose: To record the measurement of systemic arterial blood pressure at a specified time.
Description: The force exerted by blood on the walls of arteries during and after each heartbeat,
expressed as systolic/diastolic in mmHg.
```

**Norwegian (Hypothetical Translation):**

```text
Concept: Blodtrykk
Purpose: Å registrere målingen av systemisk arterielt blodtrykk på et spesifisert tidspunkt.
Description: Kraften som blod utøver på arterieveggen under og etter hvert hjerteslag,
uttrykt som systolisk/diastolisk i mmHg.
```

**Key Observations:**

- Phrasing is natural in each language (not literal)
- Clinical meaning is preserved
- Structure, identifiers, and bindings remain unchanged

### Example 2: Data Element Names

**English (Original):**

```text
- Systolic pressure (mmHg)
- Diastolic pressure (mmHg)
- Location tested
- Method used
- Position when examined
```

**German (Hypothetical Translation):**

```text
- Systolischer Blutdruck (mmHg)
- Diastolischer Blutdruck (mmHg)
- Messort
- Messmethode
- Position bei Messung
```

**Key Observations:**

- Terminology is clinically standard in the target language
- Sentence case is maintained in English; German capitalization rules apply in translation
- Element semantics are preserved

---

## H. Prohibited Practices

- **H1:** Do NOT use spelling variants within a single archetype's English source (e.g., mixing "foetus" and "fetus").

- **H2:** Do NOT create multiple at-codes for spelling or terminology variants of the same clinical concept.

- **H3:** Do NOT translate class names (ACTION, OBSERVATION, INSTRUCTION) or internal RM type names.

- **H4:** Do NOT alter archetype structure, identifiers, paths, or constraints during translation.

- **H5:** Do NOT encode locale-specific legal requirements, healthcare system models, or regional medical assumptions in archetype semantics.

- **H6:** Do NOT use colloquialisms, idioms, or culturally-specific language in original English archetype text intended for international reuse.

---

## I. Consistency Checklist

- ☑ Original language is English (en)
- ☑ Concept names use sentence case
- ☑ All descriptions, Purpose, Use, Misuse statements end with full stop
- ☑ Abbreviations placed immediately after terms
- ☑ Data element names follow established patterns (tested, examined, commenced, ceased, Category, Type)
- ☑ Same clinical concept uses same at-code throughout
- ☑ Terminology is clinically neutral and internationally recognized
- ☑ No regional/locale-specific semantics in archetype definitions
- ☑ External bindings (SNOMED CT, LOINC) reference correct concept codes
- ☑ Translations preserve intent while using target language clinical register
- ☑ Translations do NOT alter structure, identifiers, or bindings
- ☑ Per-language extensions (if created) reference this base guide

---

## J. References

- [openEHR CKM Editorial Style Guide](https://openehr.atlassian.net/wiki/spaces/healthmod/pages/304742407/Archetype+Editorial+style+guide)
- [ISO 639-1 Language Codes](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
- [SNOMED CT Concept Browser](https://browser.ihtsdotools.org/)
- [LOINC Database](https://loinc.org/)
