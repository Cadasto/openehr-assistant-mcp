# openEHR Archetype Language Standards – Dutch

**Scope:** Dutch language conventions, terminology, and translation patterns for archetype authoring and localization  
**Related:** `openehr://guides/archetypes/language-standards`, `openehr://guides/archetypes/terminology`  
**Keywords:** Dutch, Nederlands, translation, terminology, metadata, clinical register, sentence case, Nictiz, SNOMED CT NL

---

## A. Introduction

This guide extends [openehr://guides/archetypes/language-standards](openehr://guides/archetypes/language-standards) with Dutch-specific conventions. Dutch (ISO-639-1: `nl`) archetypes are typically **translations** from English originals (`en`), following the principle that English as original language prevents semantic drift through translation chains.

**Sources:**

- Nictiz — Nederlandse terminologie en interoperabiliteitsstandaarden (zib's / health and care information models)
- SNOMED CT Netherlands Edition / Nationale Release (Nederlandse vertaling, beheerd door Nictiz)
- This guide's seed glossary was established on a real Dutch translation task; entries beyond the seed must be verified against the sources above before being treated as authoritative.

All principles from the base language standards guide (Sections A–F) apply to Dutch translations. This guide specifies Dutch-language implementation.

> **No fabrication.** Where this guide references Nictiz or the Dutch SNOMED CT edition, those are pointers to **where a preferred term should be confirmed** — not a claim that a specific coded preferred term has been verified here. Terms not present in the seed glossary and not yet confirmed against an authoritative source are flagged **(needs review)**.

---

## B. Dutch Case and Capitalization

- **B.1:** Concept names SHALL use sentence case: first letter uppercase, remainder lowercase, except proper nouns and acronyms. Concept names MUST NOT end with a full stop.
  - Correct: "Bevindingen lichamelijk onderzoek", "Klinische interpretatie"
  - Incorrect: "Bevindingen Lichamelijk Onderzoek", "Klinische Interpretatie"

- **B.2:** Data element names SHALL use sentence case: first letter uppercase, remainder lowercase. Data element names MUST NOT end with a full stop.
  - Correct: "Onderzocht systeem of structuur", "Lichaamslocatie"
  - Incorrect: "Onderzocht Systeem of Structuur", "Lichaamslocatie."

- **B.3:** Proper nouns (person names, geographic locations, organization names) SHALL be capitalized as in their original form.
  - Example: "Glasgow Coma Scale" (if used untranslated in Dutch text), "Nictiz"

- **B.4:** Acronyms and initialisms SHALL be capitalized; periods are typically omitted in Dutch unless part of the established abbreviation.
  - Correct: "DICOM", "WHO", "SNOMED CT", "zib"

- **B.5:** All descriptions, Purpose, Use, and Misuse statements SHALL end with a full stop (period) or equivalent terminal punctuation.

---

## B1. Active vs. Passive Voice

Active voice is the preferred form in Dutch archetype documentation. Passive constructions should be avoided unless clinically necessary for precision.

- **B1.1:** Descriptions and Purpose statements SHOULD use active voice where possible.
  - Preferred: "De zorgverlener registreert het bloeddrukmeetresultaat van de zorgvrager." (The clinician records the patient's blood pressure result.)
  - Avoid: "Het bloeddrukmeetresultaat wordt door de zorgverlener geregistreerd." (The blood pressure result is recorded by the clinician.)

- **B1.2:** Dutch forms the passive periphrastically with **worden** (present/future) and **zijn/worden** participles; there is no synthetic passive. Where a passive is unavoidable, keep it single-layered and do not omit a clinically relevant agent.
  - Acceptable: "De meting wordt geregistreerd…" (The measurement is recorded…)
  - Avoid stacking auxiliaries or dropping the agent when the clinical actor matters.

- **B1.3:** Metadata statements (Purpose/Use/Misuse) use the impersonal infinitive opening described in Section C ("Vastleggen van…", "Gebruik om… vast te leggen"), which keeps the text agent-neutral without a true passive.

- **B1.4:** Avoid the impersonal "men" and avoid colloquial agent omission; if the clinical actor is important, state it explicitly in active form.

---

## B2. Dutch Compound Words

Dutch, like the other West-Germanic languages, is a compound-rich language where nouns combine to create new closed-form terms. Archetype terminology relies heavily on compound nouns, which must follow Dutch morphological rules.

### Formation Rules

- **B2.1:** Compound nouns in archetype terminology SHOULD use established clinical terminology from Nictiz zib's and the Dutch SNOMED CT edition rather than creating new compounds.
  - Example: "lichaamslocatie" (body site) — compound of "lichaam" + "locatie".
  - Example: "lichaamshouding" (body position) — compound of "lichaam" + "houding".
  - Example: "onderzoeksbevindingen" (examination findings) — compound of "onderzoek" + "bevindingen".

- **B2.2:** When combining nouns into compounds, the first noun may receive a linking element (**tussenletter**):
  - **-s- linking:** "onderzoek" + "detail" = "onderzoeksdetails"; "onderzoek" + "bevindingen" = "onderzoeksbevindingen".
  - **-en- linking:** common where the first element has a plural in -en (verify per word; do not generalize).
  - **Null linking:** "lichaamshouding", "lichaamslocatie" use the connective -s- on "lichaam" (lichaam**s**…); confirm the established form rather than inventing one.
  - Do not invent new linking patterns; consult Nictiz/SNOMED CT NL for established precedent.

- **B2.3:** Compounds SHALL be written as a single unhyphenated closed word, except where the official spelling (Het Groene Boekje / Woordenlijst Nederlandse Taal) prescribes a hyphen, for example:
  - Vowel-collision (klinkerbotsing): a hyphen is inserted to prevent misreading, e.g. "auto-ongeluk".
  - Compounds with abbreviations, symbols, or proper nouns where clarity requires it, e.g. "SNOMED CT-code".

- **B2.4:** Archetype terminology SHOULD favour established Dutch clinical compounds over English loanwords when a standardized Dutch compound exists in Nictiz/SNOMED CT NL.
  - Where no established Dutch term exists, retaining the English term (or the modality/standard name) is acceptable — see the glossary notes on *Study* and *Modality*.

- **B2.5:** Avoid creating excessively long compounds (more than 3 base morphemes) unless already established in an authoritative Dutch source. Long technical strings should be checked against the zib's rather than coined.

---

## B3. Grammatical Gender, Articles, and Number

Dutch has a **two-article** system: **de** (common gender — historically masculine and feminine, now merged) and **het** (neuter). Number is singular/plural. These affect:

- Article choice (de / het / een)
- Attributive adjective inflection (the -e ending)
- Diminutive forms (which are always **het**)

In archetype terminology, gender and number affect data element names, descriptions, and metadata statements.

### Article (gender) system

- **de-words (common gender):** the majority of nouns, including most agent nouns and most -ing nominalizations.
  - "de meting" (the measurement), "de bevinding" (the finding), "de interpretatie" (the interpretation), "de zorgvrager" (the subject of care), "de modaliteit" (the modality).
- **het-words (neuter):** a closed but common set; includes all diminutives and many compounds whose head is neuter.
  - "het onderzoek" (the examination), "het commentaar" (the comment), "het resultaat" (the result), "het systeem" (the system).
- **Compound rule:** a compound takes the article of its **head (last) noun**.
  - "lichaamslocatie" → head "locatie" (de) → "de lichaamslocatie".
  - "onderzoeksdetail" → head "detail" (het) → "het onderzoeksdetail".

**Archetype Note:** Gender assignment is largely lexical and must be verified per word against an authoritative Dutch source. Unverified gender assignments in this guide are flagged **(needs review)**.

### Number

- **B3.1:** Regular plurals take **-en** or **-s**; the choice is lexical (and affects compound linking elements — B2.2). Diminutives always pluralize in **-s** and are always **het** in the singular.

  | Singular | Article | Plural |
  |---|---|---|
  | meting | de | metingen |
  | bevinding | de | bevindingen |
  | onderzoek | het | onderzoeken |
  | resultaat | het | resultaten |

- **B3.2:** Data element names and descriptions SHALL use consistent number forms:
  - **Singular preferred** for element labels: "Klinische interpretatie", "Lichaamslocatie".
  - **Plural used when semantically appropriate:** "Bevindingen lichamelijk onderzoek", "Verstorende factoren" (plural where multiple instances are expected).

- **B3.3:** When referencing elements in descriptions, use the correct article and agreement: "de meting van…", "het resultaat van…".

### Adjective Agreement

- **B3.4:** Attributive adjectives normally take the **-e** ending, with one exception: a **singular indefinite het-word** takes the **uninflected** adjective.

  | Context | Form |
  |---|---|
  | de-word, definite or indefinite | "de/een klinische interpretatie" |
  | het-word, definite | "het structurele onderzoek" |
  | het-word, singular indefinite | "een structureel onderzoek" (no -e) |
  | plural (any gender) | "structurele onderzoeken" |

- **B3.5:** Predicative adjectives are always uninflected: "De bevinding is relevant."

### Maintaining Consistency

- **B3.6:** When translating element names from English to Dutch, preserve gender and number consistency within the same semantic domain — use the same head noun (and therefore the same article) for the same concept throughout an archetype.

- **B3.7:** When uncertain about article or inflection, prefer the form attested in a Nictiz zib or the Dutch SNOMED CT edition, and flag any unverified choice **(needs review)**.

---

## C. Metadata Statement Patterns

### Purpose Statements (Doel)

- **C1:** Purpose statements SHALL begin with the impersonal infinitive "Vastleggen van…" followed by a clinically-oriented definition.
  - Example: "Vastleggen van de bevindingen van een lichamelijk onderzoek."
  - Note: The variant "Voor het vastleggen van…" is also idiomatic; pick one form and use it consistently. **(needs review — confirm the house form against Nictiz zib metadata conventions.)**

- **C2:** The definition SHOULD reflect how clinicians apply the concept in clinical practice, not dictionary or technical definitions.

### Use Statements (Gebruik)

- **C3:** Use statements SHALL begin with "Gebruik om… vast te leggen" followed by clinical context and application scope.
  - Example: "Gebruik om de bevindingen van een lichamelijk onderzoek vast te leggen, ongeacht het onderzochte systeem of de onderzochte structuur."

- **C4:** Use statements SHOULD align with Purpose while clarifying implementation context and boundaries.

### Misuse Statements (Onjuist gebruik)

- **C5:** Misuse statements SHALL begin with "Niet gebruiken om… vast te leggen" followed by what archetype or approach should be used instead.
  - Example: "Niet gebruiken om beeldvormend onderzoek vast te leggen; gebruik daarvoor OBSERVATION.imaging_exam_result."

- **C6:** All Purpose, Use, and Misuse statements SHALL end with a full stop.

### Concept Descriptions

- **C7:** Concept descriptions SHALL define the concept as clinicians understand and apply it, not as academic or technical definitions.
  - Example: "De bevindingen die voortkomen uit het lichamelijk onderzoek van een zorgvrager."

> The exact house phrasing of the Purpose/Use/Misuse openers above is a reasonable Dutch rendering of the English patterns in the base guide (Section B5–B7); confirm the preferred wording against published Dutch (nl) CKM archetype metadata before treating it as normative. **(needs review)**

---

## D. Data Element Naming in Dutch

Dutch data element names follow established clinical patterns. The table seeds common patterns; verify any new pattern against Nictiz zib's and published Dutch CKM translations.

| English Pattern | Dutch Pattern | Example |
|---|---|---|
| `<XYZ> examined / tested` | `<XYZ> onderzocht / getest` | "Onderzocht systeem of structuur" |
| Examination detail / findings | Onderzoeksdetails / Onderzoeksbevindingen | Grouping for detailed findings |
| Clinical interpretation | Klinische interpretatie | Clinician's interpretation of findings |
| Comment | Commentaar | Unstructured narrative or notes |
| Confounding factors | Verstorende factoren | Factors affecting measurement or assessment |
| Body site | Lichaamslocatie | Anatomical location |
| Structured body site | Gestructureerde lichaamslocatie | SLOT for structured anatomical location |
| Extension | Extensie | Extensions in PROTOCOL or other sections |

- **D1:** Element names SHALL be clinically meaningful and reflect standard Dutch medical terminology (Nictiz zib's, SNOMED CT NL).

- **D2:** For examination/testing elements, use "onderzocht" or "getest" depending on clinical context.

- **D3:** For grouping elements, use established patterns: "Commentaar", "Extensie", "Verstorende factoren", "Onderzoeksdetails".

---

## E. Dutch Translation Glossary

This glossary documents standardized Dutch translations for common archetype terms. The seed entries below were **established on a real Dutch translation task** and are the authoritative starting set. Entries added later MUST be verified against Nictiz zib's or the Dutch SNOMED CT edition; unverified terms are flagged **(needs review)**. Glossary entries should be maintained as new archetypes are published with Dutch translations.

### Reference Model and ADL Class Names (Unchanged)

These are NOT translated; they remain in English:

| Term | Usage |
|---|---|
| ACTION | archetype class |
| CLUSTER | archetype class |
| COMPOSITION | archetype class |
| EVALUATION | archetype class |
| ENTRY | abstract base type |
| INSTRUCTION | archetype class |
| OBSERVATION | archetype class |
| SECTION | archetype class |
| SLOT | composition mechanism |

### Internal RM Nodes and Structural Labels (Leave Verbatim)

These structural labels are part of the model/tooling and are kept verbatim in Dutch translations:

| Term | Usage |
|---|---|
| Event Series | RM event-series structure — keep verbatim |
| Tree | RM/tooling structural node — keep verbatim |
| `@ internal @` | internal RM node marker — keep verbatim |

### Seed Glossary (Authoritative)

Sourced from a real Dutch translation task. Treat as the authoritative base.

| English | Dutch | Note |
|---|---|---|
| Physical examination findings | Bevindingen lichamelijk onderzoek | |
| (System or) structure examined | Onderzocht systeem of structuur | |
| Examination detail / findings | Onderzoeksdetails / Onderzoeksbevindingen | |
| Body site / Structured body site | Lichaamslocatie / Gestructureerde lichaamslocatie | |
| Clinical interpretation | Klinische interpretatie | |
| Confounding factors | Verstorende factoren | |
| Comment | Commentaar | |
| Extension | Extensie | |
| Position (body position) | Lichaamshouding | label, not *Positie* |
| Imaging examination result | Resultaat beeldvormend onderzoek | |
| Modality | Modaliteit | |
| Study (DICOM) | onderzoek | label keeps *Study* in *Study Instance UID*; flag for review |
| Subject of care | Zorgvrager | |

### Common Metadata Terminology (needs review)

These are conventional renderings of common archetype metadata labels. They are **not** verified against a specific Nictiz/SNOMED CT NL preferred term; confirm before treating as normative.

| English | Dutch | Status |
|---|---|---|
| Purpose | Doel | (needs review) |
| Use | Gebruik | (needs review) |
| Misuse | Onjuist gebruik | (needs review) |
| Description | Beschrijving | (needs review) |
| Comment | Commentaar | seed-confirmed |
| Record (verb) | Vastleggen / Registreren | (needs review — confirm house form) |
| Subject of care | Zorgvrager | seed-confirmed |
| Confounding factors | Verstorende factoren | seed-confirmed |
| Extension | Extensie | seed-confirmed |

### Glossary Maintenance

- **E1:** The Dutch glossary SHALL be reviewed and updated when new archetypes with Dutch translations are published to CKM, or when a Nictiz zib establishes a preferred term for a concept used here.

- **E2:** Updates should capture:
  - New terminology patterns observed in published Dutch translations
  - Refinements to existing terminology
  - Promotion of a **(needs review)** entry to confirmed status once verified against Nictiz or SNOMED CT NL (record the source)

- **E3:** When terminology variants exist (e.g., "Vastleggen" vs "Registreren" for *record*), document both variants with usage context.

- **E4:** Do NOT fabricate Nictiz/SNOMED CT NL preferred terms. If a coded preferred term cannot be verified, reference the source as the place to confirm it and flag the entry **(needs review)** — do not present an unverified term as official.

---

## F. Formatting and Style Rules

- **F1:** All descriptions, Purpose, Use, and Misuse statements SHALL end with a full stop (period) or equivalent terminal punctuation. Concept names and data element names MUST NOT end with full stops.

- **F2:** Extra whitespace at the beginning or end of descriptions will be flagged; follow standard punctuation spacing rules.

- **F3:** Bullet points and lists SHALL use hyphen with space: `- tekst` (text should not begin with a capital letter unless it is a proper noun).

- **F4:** Sentence case SHALL be used for concept and data element names (first letter uppercase, remainder lowercase).

- **F5:** Abbreviations and acronyms SHOULD be placed immediately after the words they abbreviate, not at the end of phrases.
  - Correct: "Neurologic Assessment in Neuro-Oncology (NANO) schaal"
  - Incorrect: "Neurologic Assessment in Neuro-Oncology schaal (NANO)"

- **F6:** Follow the official Dutch spelling (Woordenlijst Nederlandse Taal / "Het Groene Boekje") for compounds, hyphenation, and the tussen-n/tussen-s linking letters.

---

## G. Cross-Language Consistency

Semantic equivalence in archetypes is maintained through internal archetype codes (at-codes) and their textual descriptions, not through external terminology bindings. External terminology bindings (SNOMED CT, LOINC) provide additional linking to equivalent concepts in other ontologies but are not the primary definition mechanism.

### Example: "Unsure" Across Languages

**English Original:**

- Term: "Unsure" (indicates personal doubt)
- SNOMED CT binding: concept code for clinical uncertainty in patient response

**Dutch Translation:**

- Term: "Onzeker"
- SNOMED CT binding: **same concept code** as English

**Key principle:** Semantic equivalence is maintained through identical at-codes and their descriptions across language versions. The Dutch SNOMED CT edition supplies the Dutch *display* term for a bound concept, but the binding (concept code) is unchanged from the English original.

- **G1:** Each language's archetype translation MUST use the same internal at-codes (e.g., "at0001", "at0002") as the English original.

- **G2:** The textual descriptions of at-codes (in the Description section) form the primary semantic definition. Translations must preserve the meaning of these descriptions even when terminology differs.

- **G3:** Document in this guide when Dutch terminology differs significantly from a literal translation (e.g., "Lichaamshouding" rather than "Positie"), and explain the clinical reasoning.

---

## H. Examples of Proper Dutch Archetype Text

The examples below illustrate the Dutch metadata patterns of Section C using seed-glossary terminology. They are **illustrative phrasing**, not verbatim quotes from a published Dutch CKM archetype; confirm house wording against published Dutch translations.

### Example 1: Physical Examination Findings (illustrative)

Purpose (Doel):

```text
Vastleggen van de bevindingen van een lichamelijk onderzoek.
```

Use (Gebruik):

```text
Gebruik om de bevindingen van een lichamelijk onderzoek vast te leggen, ongeacht het onderzochte
systeem of de onderzochte structuur. Leg de klinische interpretatie en eventuele verstorende
factoren vast als onderdeel van de onderzoeksdetails.
```

Misuse (Onjuist gebruik):

```text
Niet gebruiken om beeldvormend onderzoek vast te leggen; gebruik daarvoor
OBSERVATION.imaging_exam_result.
```

**Key Observations (Dutch Patterns):**

- Purpose uses the impersonal infinitive opener "Vastleggen van…".
- Use employs "Gebruik om… vast te leggen" (separable verb *vastleggen* splits around the object).
- Misuse uses "Niet gebruiken om… vast te leggen" and names the alternative archetype.
- Seed-glossary terms are used consistently: "bevindingen lichamelijk onderzoek", "onderzocht systeem of structuur", "klinische interpretatie", "verstorende factoren".

### Example 2: Imaging Examination Result (illustrative)

Purpose (Doel):

```text
Vastleggen van het resultaat van een beeldvormend onderzoek.
```

Use (Gebruik):

```text
Gebruik om het resultaat beeldvormend onderzoek vast te leggen, inclusief de modaliteit en de
gestructureerde lichaamslocatie. Het label Study Instance UID blijft ongewijzigd, conform DICOM.
```

**Key Observations (Dutch Patterns):**

- "Modaliteit" (de-word) and "gestructureerde lichaamslocatie" follow Dutch adjective inflection (de-word → inflected -e).
- *Study* is translated as "onderzoek" in prose, but the DICOM identifier label "Study Instance UID" is kept verbatim (glossary note: flag for review).
- Article/agreement follows the head-noun rule: "het resultaat", "de modaliteit".

### Example 3: Dutch Terminology Notes

- "Lichaamshouding" is used for body **position**, deliberately not "Positie", to match clinical register (glossary).
- "Zorgvrager" is the preferred rendering of *subject of care*; "patiënt" is narrower and context-dependent.
- Internal RM nodes — "Event Series", "Tree", `@ internal @` — are left verbatim and not translated.

---

## I. Prohibited Practices

- **I1:** Do NOT mix Dutch and Flemish (Belgian) regional spelling/terminology variants within a single archetype's Dutch text; follow the standard Netherlands Dutch register unless the project specifies otherwise.

- **I2:** Do NOT translate reference model class names (ACTION, OBSERVATION, CLUSTER) or internal RM types.

- **I3:** Do NOT translate archetype identifiers or internal node identifiers (at-codes, ac-codes), nor the verbatim structural labels (Event Series, Tree, `@ internal @`).

- **I4:** Do NOT alter archetype structure, paths, cardinalities, or constraints during translation.

- **I5:** Do NOT encode locale-specific legal requirements or Dutch-only healthcare system assumptions in archetype semantics.

- **I6:** Do NOT use colloquialisms, regional dialects, or culturally-specific language in archetype definitions intended for international reuse.

- **I7:** Do NOT fabricate Nictiz or SNOMED CT NL preferred terms; reference the source for confirmation and flag unverified terms **(needs review)**.

---

## J. Dutch Consistency Checklist

- ☑ Language set to Dutch (nl)
- ☑ Concept names use sentence case and do NOT end with full stop
- ☑ All descriptions, Purpose, Use, Misuse statements end with full stop
- ☑ Data element names follow established Dutch patterns (onderzocht, lichaamslocatie, commentaar, extensie, etc.) and do NOT end with full stop
- ☑ Purpose begins with "Vastleggen van…"
- ☑ Use begins with "Gebruik om… vast te leggen"
- ☑ Misuse begins with "Niet gebruiken om… vast te leggen"
- ☑ Articles (de/het) and adjective inflection follow head-noun gender; uncertain cases flagged (needs review)
- ☑ Terminology matches the seed glossary or a verified Nictiz/SNOMED CT NL term
- ☑ No RM class names, internal identifiers, or verbatim structural labels are translated
- ☑ Internal at-codes and their descriptions match the English archetype version (primary semantic definition)
- ☑ No regional (Belgian/Flemish) or healthcare-system assumptions in definitions
- ☑ Abbreviations placed immediately after terms, not at end
- ☑ No fabricated preferred terms; unverified terms flagged (needs review)

---

## K. References

- [Nictiz](https://www.nictiz.nl/) — Dutch national competence centre for electronic health information exchange; publisher of the zib's (zorginformatiebouwstenen / health and care information models) and of Dutch interoperability standards.
- [SNOMED CT — Netherlands](https://www.nictiz.nl/standaarden/snomed-ct/) — the Dutch SNOMED CT edition / Nationale Release; the authoritative source for Dutch preferred terms of bound concepts.
- [Het Groene Boekje / Woordenlijst Nederlandse Taal](https://woordenlijst.org/) — official Dutch spelling, compounding, and linking-letter rules.
- [Base Language Standards Guide](openehr://guides/archetypes/language-standards)
- [ISO 639-1 Language Codes](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
- [SNOMED CT Concept Browser](https://browser.ihtsdotools.org/)
