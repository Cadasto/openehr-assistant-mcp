# openEHR Archetype Language Standards – Norwegian Bokmål

**Scope:** Norwegian language conventions, terminology, and translation patterns for archetype authoring and localization  
**Related:** `openehr://guides/archetypes/language-standards`, `openehr://guides/archetypes/terminology`  
**Keywords:** Norwegian, Bokmål, translation, terminology, metadata, clinical register, sentence case

---

## A. Introduction

This guide extends [openehr://guides/archetypes/language-standards](openehr://guides/archetypes/language-standards) with Norwegian-specific conventions. Norwegian archetypes are typically **translations** from English originals (ISO-639-1: `en`), following the principle that English as original language prevents semantic drift through translation chains.

**Sources:**

- openEHR Norway – Arketype innholds- og stilguide
- openEHR Norway - Ord og uttrykk til oversettelser
- Analysis of all CKM archetypes with Norwegian Bokmål (nb) translations

All principles from the base language standards guide (Sections A–F) apply to Norwegian translations. This guide specifies Norwegian-language implementation.

---

## B. Norwegian Case and Capitalization

- **B.1:** Concept names SHALL use sentence case: first letter uppercase, remainder lowercase, except proper nouns and acronyms.
  - Correct: "ACVPU skala", "Nevrologisk vurdering"
  - Incorrect: "ACVPU Skala", "Nevrologisk Vurdering"

- **B.2:** Data element names SHALL use sentence case: first letter uppercase, remainder lowercase.
  - Correct: "Systolisk trykk", "Diastolisk trykk"
  - Incorrect: "Systolisk Trykk", "Diastolisk Trykk"

- **B.3:** Proper nouns (person names, geographic locations, organization names) SHALL be capitalized as in their original form.
  - Example: "qSOFA skala", "Glasgow Coma Scale" (if used untranslated in Norwegian text)

- **B.4:** Acronyms and initialisms SHALL be capitalized; periods are typically omitted in Norwegian unless part of the established abbreviation.
  - Correct: "ACVPU", "WHO", "SNOMED CT"

- **B.5:** All descriptions, Purpose, Use, and Misuse statements SHALL end with a full stop (period) or equivalent terminal punctuation.

---

## B1. Active vs. Passive Voice

Active voice is the preferred form in Norwegian archetype documentation. Passive constructions should be avoided unless clinically necessary for precision.

- **B1.1:** Descriptions and Purpose statements SHOULD use active voice where possible.
  - Preferred: "Legen registrerer pasientens blodtrykk" (The physician records the patient's blood pressure)
  - Avoid: "Blodtrykket registreres av legen" (The blood pressure is recorded by the physician)

- **B1.2:** In passive constructions (unavoidable in some clinical contexts), use the Norwegian passive forms correctly:
  - "-s passive" (reflexive): "Målingen registreres…" (The measurement is recorded…) — preferred for archetype metadata
  - Auxiliary passive (periphrastic): "Målingen blir registrert…" (The measurement gets recorded…) — less formal, avoid in formal archetype text

- **B1.3:** Example from published archetypes:
  - OBSERVATION.blood_pressure.v2 Purpose: "For å registrere…" — active imperative form, preferred
  - Data element names: "Systolisk trykk undersøkt" — implicit active subject, appropriate for element naming

- **B1.4:** Avoid multiple passive layers or unclear agent omission; if the clinical actor is important, state it explicitly in active form.

---

## B2. Norwegian Compound Words

Norwegian is a compound-rich language where nouns combine to create new terms. Archetype terminology relies heavily on compound nouns, which must follow Norwegian morphological rules.

### Formation Rules

- **B2.1:** Compound nouns in archetype terminology SHOULD use established clinical terminology from published CKM Norwegian archetypes rather than creating new compounds.
  - Example (from OBSERVATION.blood_pressure.v2): "blodtrykksmåling" (blood pressure measurement) — compound of "blodtrykk" + "måling"
  - Example (from OBSERVATION.blood_pressure.v2): "blodtrykksapparat" (blood pressure device) — compound of "blodtrykk" + "apparat"
  - Example (from OBSERVATION.blood_pressure.v2): "sfygmomanometer" — medical term preserved (not translated to compound form)

- **B2.2:** When combining nouns into compounds, the first noun may receive a linking element:
  - **-s- linking:** "blodtrykk" + "måling" = "blodtrykksmåling" (common with -trykk compounds)
  - **-e- linking:** "syk" + "hus" = sykehus
  - **Null linking:** "hjerteslag" (heartbeat) — "hjerte" + "slag" without -s- inserted
  - Do not invent new linking patterns; consult published CKM Norwegian archetypes for established precedent.

- **B2.3:** Compounds SHALL be written as a single unhyphenated word (not hyphenated), except for:
  - Compounds where the first element ends in the same letter as the second begins (rare in archetype terminology)
  - Compounds with loanwords that require clarity
  - Example (from EVALUATION.adverse_reaction_risk.v2): "pasientjournalen" (patient record) — single word, not hyphenated

### Length and Readability

- **B2.4:** Archetype terminology SHOULD favour established clinical compounds over English loanwords when compounds are already standardized in CKM Norwegian archetypes.
  - Preferred: "blodtrykksmåling" (from published archetype)
  - Acceptable fallback: "blood pressure measurement" (if no established Norwegian compound exists)

- **B2.5:** Avoid creating excessively long compounds (more than 3 base morphemes) unless they are already established in published CKM Norwegian archetypes.
  - Example (acceptable): "legemiddeltoksisitet" (drug toxicity) — compound from EVALUATION.adverse_reaction_risk.v2
  - Example (avoid if not established): "blodtrykksmålingsoversiktsrapport" (unless already published in CKM)

---

## B3. Grammatical Gender and Number Conjugation

Standard Norwegian Bokmål maintains three grammatical genders (masculine, feminine, neuter). However, in contemporary usage, particularly in informal writing and speech, the masculine and feminine genders are often collapsed into a single **common gender**, resulting in a two-gender system for those speakers. Two numbers exist (singular, plural). These affect:

- Article choice (en, et, -en, -et)
- Adjective endings
- Past participle forms in certain contexts

In archetype terminology, gender and number affect data element names, descriptions, and metadata statements.

### Gender System

**Standard Bokmål** (normative for CKM Norwegian Archetypes) uses three genders:

- **Masculine gender (hankjønn):** uses article "en" (indefinite) and "-en" (definite)
- **Feminine gender (hunkjønn):** uses article "ei/en" (indefinite) and "-a/-en" (definite)
- **Neuter gender (intetkjønn):** uses article "et" (indefinite) and "-et" (definite)

Grammatical gender assignment is largely lexical (must be learned per word), but some patterns exist:

#### Predictable Gender Patterns (from Archetype Terminology)

**Masculine gender (hankjønn):**

- Most nouns ending in -ing: "måling" (measurement — en måling, den gamle målingen)
- Many occupations and agent nouns: "lege" (physician — en lege)
- Example from blood_pressure.v2: "dataelement" is actually masculine in some contexts

**Feminine gender (hunkjønn):**

- Many nouns ending in -else, -else, -ing derived nominally: "undersøkelse" (examination — ei undersøkelse, den gamle undersøkelsen)
- Nouns ending in -ing when derived from verbs: "vurdering" (assessment — ei vurdering, den gamle vurderinga)
- "reaksjon" (reaction — ei reaksjon, den gamle reaksjonen)

**Neuter gender (intetkjønn):**

- Nouns ending in -trykk: "blodtrykk" (blood pressure — et blodtrykk, det gamle blodtrykket)
- Nouns ending in -ment: many medical terms
- Nouns ending in -skjema: "dataskjema" (data form — et dataskjema)
- "trykk" (pressure — et trykk, det gamle trykket)

**Archetype Note:** Gender assignment is highly irregular and must be verified against published CKM Norwegian archetypes. Validated against 11+ Norwegian (v1+) archetypes including OBSERVATION.blood_pressure.v2, OBSERVATION.body_weight.v2, OBSERVATION.pulse.v2, OBSERVATION.laboratory_test_result.v1, OBSERVATION.s_gaf.v1, OBSERVATION.madrs_no.v1, and others.

### Number Conjugation

- **B3.1:** Singular and plural forms affect article choice and, in some cases, adjective endings. The three-gender system produces different article and plural patterns:

  | Form | Masculine | Feminine | Neuter |
  |---|---|---|---|
  | Indefinite singular | en måling | ei undersøkelse | et blodtrykk |
  | Definite singular | målingen | undersøkelsen | blodtrykket |
  | Indefinite plural | målinger | undersøkelser | blodtrykk |
  | Definite plural | målingene | undersøkelsene | blodtrykkene |

- **B3.2:** Data element names and descriptions in archetypes SHALL use consistent number forms:
  - **Singular preferred:** "Systolisk trykk" (singular form) rather than "Systoliske trykk"
  - **Plural used when semantically appropriate:** "Allergier" (plural when multiple allergies are expected)
  - Example from OBSERVATION.blood_pressure.v2: "Systolisk trykk", "Diastolisk trykk" (singular forms, standard in archetype element naming)

- **B3.3:** When referencing elements in descriptions, use appropriate gender and number agreement:
  - Correct: "Målingen av systemisk arterielt blodtrykk" (measurement [common] + agreement)
  - Correct: "Registreringen av et individs tendens" (registration [common] + agreement)

### Adjective Agreement

- **B3.4:** Adjectives in descriptions and Purpose/Use statements MUST agree with the noun's gender and number:

  | Gender | Indefinite | Definite |
  |---|---|---|
  | Common (singular) | en systemisk måling | den systemiske målingen |
  | Neuter (singular) | et systemisk blodtrykk | det systemiske blodtrykket |
  | Plural | systemiske målinger | de systemiske målingene |

- **B3.5:** Examples from published CKM Norwegian archetypes:
  - "en klinisk vurdering" (common gender singular: clinical assessment)
  - "et individs tendens" (neuter gender singular: an individual's tendency)
  - "hele spekteret av reaksjoner" (plural: full range of reactions) — from EVALUATION.adverse_reaction_risk.v2

### Maintaining Consistency

- **B3.6:** When translating element names from English to Norwegian, preserve gender consistency within the same semantic domain:
  - All "measurement" elements should use "måling" (common gender) consistently
  - All "assessment" elements should use "vurdering" (common gender) consistently
  - All "blood pressure" references should use "blodtrykk" (neuter) consistently

- **B3.7:** Test gender/number agreement by consulting the following published CKM Norwegian archetypes:
  - OBSERVATION.blood_pressure.v2 — for measurement terminology (common gender patterns)
  - EVALUATION.adverse_reaction_risk.v2 — for assessment and reaction terminology (mixed gender patterns)
  - When uncertain, prefer the published form from CKM.

---

### Purpose Statements (Formål)

- **C1:** Purpose statements SHALL begin with "For å registrere…" followed by a clinically-oriented definition.
  - Example: "For å registrere detaljer om frekvens og tilhørende egenskaper for puls eller hjertefrekvens." (OBSERVATION.pulse.v2)
  - Example: "For å registrere målingen av midjeomkrets." (OBSERVATION.waist_circumference.v1)
  - Note: Some published CKM Norwegian archetypes use "For registrering av…" instead; this is an inconsistency that should be corrected toward the "For å registrere…" standard.

- **C2:** The definition SHOULD reflect how clinicians apply the concept in clinical practice, not dictionary or technical definitions.

### Use Statements (Bruk)

- **C3:** Use statements SHALL begin with "Brukes for å registrere…" followed by clinical context and application scope.
  - Example: "Brukes for å registrere alle typer av systemisk arteriell blodtrykksmåling, uansett hvilken metode eller kroppsplassering som anvendes…" (OBSERVATION.blood_pressure.v2)
  - Example: "Brukes for å registrere målingen av midjeomkrets." (OBSERVATION.waist_circumference.v1)
  - Note: Some published CKM Norwegian archetypes use "Brukes til å registrere…" or "Bruk for å registrere…"; these are inconsistencies that should be corrected toward the "Brukes for å registrere…" standard.

- **C4:** Use statements SHOULD align with Purpose while clarifying implementation context and boundaries.

### Misuse Statements (Feil bruk)

- **C5:** Misuse statements SHALL begin with "Brukes ikke for å registrere …" followed by what archetype or approach should be used instead.
  - Example: "Brukes ikke for å registrere 24-timers blodtrykksmåling; bruk OBSERVATION.ambulatory_blood_pressure i stedet."
  - Example: "Brukes ikke for å registrere kjemikalieoverfølsomheter; bruk EVALUATION.adverse_reaction_risk i stedet."

- **C6:** All Purpose, Use, and Misuse statements SHALL end with a full stop.

### Concept Descriptions

- **C7:** Concept descriptions SHALL define the concept as clinicians understand and apply it, not as academic or technical definitions.
  - Example: "Den lokale målingen av arteriell blodtrykk som er et surrogatparameter for arterielt trykk i systemisk sirkulasjon."

---

## D. Data Element Naming in Norwegian

Norwegian data element names follow established clinical patterns extracted from published CKM archetypes with Norwegian translations:

| English Pattern | Norwegian Pattern | Example |
|---|---|---|
| `<XYZ> examined/tested/analysed` | `<XYZ> undersøkt/testet/analysert` | "Blodtrykk undersøkt", "Prøve analysert" |
| `<XYZ> name` | `<XYZ> navn` | "Diagnosenavn", "Allergennavn" |
| `<XYZ> start` | `<XYZ> start` | "Behandlingsstart", "Symptomdebut" |
| `<XYZ> stop/ceased` | `<XYZ> stopp` | "Behandlingsstopp", "Symptomopphør" |
| Clinical description | Klinisk beskrivelse | Standard element for detailed findings |
| Comment | Kommentar | Unstructured narrative or notes |
| Multimedia representation | Multimediarepresentasjon | SLOT for CLUSTER.multimedia |
| Confounding factors | Konfunderende faktorer | Factors affecting measurement or assessment |
| Additional information SLOT | Tilleggsinformasjon SLOT | Extensions in PROTOCOL or other sections |

- **D1:** Element names SHALL be clinically meaningful and reflect standard Norwegian medical terminology.

- **D2:** For examination/testing elements, use "undersøkt", "testet", or "analysert" depending on clinical context.

- **D3:** For grouping elements, use established patterns: "Klinisk beskrivelse", "Kommentar", "Ytterligere detaljer".

---

## E. Norwegian Translation Glossary

This glossary documents standardized Norwegian translations for common archetype terms, sourced from KLIM resources and analysis of CKM Norwegian-translated archetypes. Glossary entries should be maintained as new archetypes are published with Norwegian translations.

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

### Clinical Core Terminology

| English | Norwegian |
|---|---|
| Adverse reaction | Overfølsomhetsreaksjon |
| Assess / Assessment | Vurdere / Vurdering |
| Care event | Omsorgshendelse |
| Clinical encounter | Klinisk kontakt |
| Commence / Start | Starte / Debut |
| Cease / Stop | Stoppe / Opphør / Seponere |
| Device / Medical equipment | Medisinsk utstyr |
| Diagnosis / Problem | Diagnose / Problem |
| Exam / Examination | Undersøkelse |
| Exclusion | Eksklusjon |
| Family history | Familieanamnese |
| Health institution | Helseinstitusjon |
| Healthcare provider / Personnel | Helsepersonell |
| Healthcare environment | Helsevesen |
| Identifier | Identifikator |
| Laboratory | Laboratorium |
| Last updated | Sist oppdatert |
| Location / Localization | Lokalisering |
| Narrative / Free text | Fritekst |
| National identification number | Fødselsnummer |
| Onset | Debut |
| Persistent | Persistent |
| Point-in-time | Tidspunkt |
| Problem list | Problemliste |
| Record (verb) | Registrere |
| Reference Model | Referansemodell |
| Sample / Specimen | Prøve / Prøvemateriale |
| Score | Skår |
| Subject of care | Individ / Pasient |
| Summary | Sammendrag |
| Template | Templat |
| Workflow | Arbeidsflyt |

### Structured Details Terminology

| English | Norwegian |
|---|---|
| Additional details | Ytterligere detaljer |
| Additional structured details about... | Ytterligere strukturerte detaljer om... |
| Additional structured details related to... | Ytterligere strukturerte detaljer relatert til... |
| Confounding factors | Konfunderende faktorer |
| Extension | Tilleggsinformasjon |
| Information provider | Informasjonskilde |
| Structured data | Strukturerte data |

### Event Type Terminology

| English | Norwegian |
|---|---|
| Any event | Uspesifisert hendelse |
| Any interval event | Uspesifisert intervallhendelse |
| Any point in time event | Uspesifisert tidspunkthendelse |

### Common Metadata Terminology

| English | Norwegian |
|---|---|
| Comment | Kommentar |
| Clinical description | Klinisk beskrivelse |
| Container | Kontainer (for container-archetypes; distinct from physical specimen container) |
| Data element | Dataelement |
| Data field | Datafelt |
| Description | Beskrivelse |
| For example | For eksempel |
| Jurisdictions / Authority area | Myndighetsområde |
| Link | Lenke |
| Multimedia representation | Multimediarepresentasjon |
| Purpose | Formål |
| Use | Bruk |
| Misuse | Feil bruk |

### Glossary Maintenance

- **E1:** The Norwegian glossary SHALL be reviewed and updated when new archetypes with Norwegian translations are published to CKM.

- **E2:** Updates should capture:
  - New terminology patterns observed in published translations
  - Refinements to existing terminology (e.g., confounding factors terminology)
  - Establishment of new standardized translations for previously untranslated terms

- **E3:** When terminology variants exist (e.g., alternative translations for the same concept), document both variants with usage context.

- **E4:** Glossary terms have been validated against 11+ published CKM Norwegian archetypes (v1+): OBSERVATION.blood_pressure.v2, OBSERVATION.body_weight.v2, OBSERVATION.pulse.v2, OBSERVATION.height.v2, OBSERVATION.body_temperature.v2, OBSERVATION.laboratory_test_result.v1, OBSERVATION.waist_circumference.v1, OBSERVATION.s_gaf.v1, OBSERVATION.madrs_no.v1, SECTION.adhoc.v1, and others. Core terminology entries are sourced directly from these published archetypes.

- **E5:** Norwegian-native archetypes (OBSERVATION.s_gaf.v1, OBSERVATION.madrs_no.v1) represent specialized assessment instruments with terminology specific to Norwegian healthcare (Norsk Pasientregister reporting, psykisk helsevern). Consult these when developing new Norwegian assessment-based archetypes.

---

## F. Formatting and Style Rules

- **F1:** All descriptions, Purpose, Use, Misuse statements, and data element names SHALL end with a full stop (period) or equivalent terminal punctuation.

- **F2:** Extra whitespace at the beginning or end of descriptions will be flagged; follow standard punctuation spacing rules.

- **F3:** Bullet points and lists SHALL use hyphen with space: `- teksten` (text should not begin with capital letter).

- **F4:** Sentence case SHALL be used for concept and data element names (first letter uppercase, remainder lowercase).

- **F5:** Abbreviations and acronyms SHOULD be placed immediately after the words they abbreviate, not at the end of phrases.
  - Correct: "Nevrologisk Assessment in Neuro-Oncology (NANO) skala"
  - Incorrect: "Nevrologisk Assessment in Neuro-Oncology skala (NANO)"

---

## G. Cross-Language Consistency

Semantic equivalence in archetypes is maintained through internal archetype codes (at-codes) and their textual descriptions, not through external terminology bindings. External terminology bindings (SNOMED CT, LOINC) provide additional linking to equivalent concepts in other ontologies but are not the primary definition mechanism.

### Example: "Unsure" Across Languages

**English Original:**

- Term: "Unsure" (indicates personal doubt)
- SNOMED CT binding: concept code for clinical uncertainty in patient response

**Norwegian Translation:**

- Term: "Usikker"
- SNOMED CT binding: **same concept code** as English

**Key principle:** Semantic equivalence is maintained through identical at-codes and their descriptions across language versions. External terminology bindings provide supplementary linking to external ontologies but should not be relied upon as the primary definition mechanism.

- **G1:** Each language's archetype translation MUST use the same internal at-codes (e.g., "at0001", "at0002") as the English original.

- **G2:** The textual descriptions of at-codes (in the Description section) form the primary semantic definition. Translations must preserve the meaning of these descriptions even when terminology differs.

- **G3:** Document in language-specific guides when terminology differs significantly from literal translation, and explain the clinical reasoning.

---

## H. Examples of Proper Norwegian Archetype Text

### Example 1: Blood Pressure Observation (from OBSERVATION.blood_pressure.v2)

**Norwegian Metadata from CKM:**

Purpose:

```text
For å registrere et individs systemiske arterielle blodtrykk.
```

Use:

```text
Brukes for å registrere alle typer av systemisk arteriell blodtrykksmåling, uansett hvilken metode
eller kroppsplassering som brukes. Arketypen er ment for registrering av blodtrykksmåling i alle
kliniske scenarier - for eksempel selvmåling med blodtrykksapparat hjemme, akutt måling av det
systoliske blodtrykket ved radialispalpasjon og et sfygmomanometer, målinger tatt i kliniske
konsultasjoner eller under trening/stresstesting, eller en serie med invasive eller noninvasive
målinger i en intensivavdeling.
```

Misuse (relevant excerpt):

```text
Brukes ikke for å registrere sentralvenøst trykk. Brukes ikke for å registrere måling av arterielle blodtrykk som ikke
representerer et systemisk arterielt trykk f.eks spesifikk måling av pulmonalt arterietrykk.
```

**Key Observations (Norwegian Patterns):**

- Purpose uses "For å registrere" (standard opening pattern)
- Use statements employ "Brukes for å registrere" (standard Use pattern)
- Clinical methods listed naturally (radialispalpasjon, sfygmomanometer)
- Contextual details included: metodological variations, clinical settings
- Misuse uses "Brukes ikke for å registrere" (standard Misuse pattern)
- Terminology is consistent with SNOMED CT and clinical Norwegian usage
- Multi-sentence structure maintains professional tone and clarity

### Example 2: Adverse Reaction Risk Evaluation (from EVALUATION.adverse_reaction_risk.v2)

**Norwegian Metadata from CKM:**

Purpose:

```text
For å registrere en klinisk vurdering om et individs tendens til overfølsomhetsreaksjoner ved
eksponering eller re-eksponering for en angitt substans eller substansklasse.
```

Use (relevant excerpt):

```text
Brukes for å registrere en klinisk vurdering om et individs tendens til overfølsomhetsreaksjoner
ved fremtidig eksponering for en substans eller substansklasse. Substanser kan omfatte men er
ikke begrenset til: aktive stoffer eller hjelpestoffer i legemidler, biologiske produkter,
metallsalter eller organiske substanser.

Arketypen gir ett enkelt sted i pasientjournalen for å dokumentere tendensen til hele spekteret
av reaksjoner, fra trivielle til livstruende:
- immunmediert reaksjon - Typene I-IV (allergiske reaksjoner og hypersensitivitet)
- ikke-immunmediert reaksjon - inkludert annen overfølsomhet, bivirkninger, intoleranse og
  legemiddeltoksisitet
```

**Key Observations (Norwegian Patterns):**

- Purpose opens with "For å registrere" (standard Norwegian pattern)
- Use extensively documents scope with examples and bullet points
- Terminology matches Norwegian clinical standards: "overfølsomhetsreaksjoner", "immunmediert", "hypersensitivitet"
- Lists are formatted with bullet points and clear categorization
- Medical concepts translated idiomatically: "passient jornalen" (patient record), "legemiddel" (medicinal product)
- Technical classifications preserved in English where necessary (Types I-IV) with Norwegian explanations

### Example 3: Norwegian Terminology Patterns

**Data Element Naming (from blood_pressure.v2 Norwegian metadata):**

Keywords in Norwegian:

```text
BT, NIBP, pulstrykk, pulsamplitude, MAP, IBP, IBPM, ABP, ambulatorisk, 24t, BP,
ikke-invasivt, invasivt, NIBPS, NIBPD, NIBPM, middelarterietrykk, SAP, DAP, PP,
systolisk, diastolisk, blodtrykk
```

**Key Observations:**

- Abbreviations preserved when established in Norwegian clinical practice (BT = Blodtrykk, MAP, SAP, DAP, PP)
- Descriptive Norwegian terms pair with English abbreviations
- Keywords use sentence case, not capitalized abbreviations
- Compound nouns use Norwegian conventions: "middelarterietrykk", "radialispalpasjon"
- Clinical terminology reflects published CKM Norwegian archetype translations

---

## I. Prohibited Practices

- **I1:** Do NOT mix spelling variants within Norwegian text (e.g., Bokmål vs. Nynorsk).

- **I2:** Do NOT translate reference model class names (ACTION, OBSERVATION, CLUSTER) or internal RM types.

- **I3:** Do NOT translate archetype identifiers or internal node identifiers (at-codes, ac-codes).

- **I4:** Do NOT alter archetype structure, paths, cardinalities, or constraints during translation.

- **I5:** Do NOT encode locale-specific legal requirements or Norwegian-only healthcare system assumptions in archetype semantics.

- **I6:** Do NOT use colloquialisms, regional dialects, or culturally-specific language in archetype definitions intended for international reuse.

---

## J. Norwegian Consistency Checklist

- ☑ Language set to Norwegian Bokmål (nb)
- ☑ Concept names use sentence case
- ☑ All descriptions, Purpose, Use, Misuse statements end with full stop
- ☑ Data element names follow established patterns (undersøkt, testet, navn, start, stopp, etc.)
- ☑ Purpose begins with "For å registrere…"
- ☑ Use begins with "Brukes for å registrere…"
- ☑ Misuse begins with "Brukes ikke for å registrere…"
- ☑ Terminology matches Norwegian glossary or established CKM Norwegian patterns
- ☑ No RM class names or internal identifiers are translated
- ☑ Internal at-codes and their descriptions match the English archetype version (primary semantic definition)
- ☑ No regional healthcare system assumptions in definitions
- ☑ Abbreviations placed immediately after terms, not at end

---

## L. Validation Status and Archetype Baseline

This guide has been validated against 11+ published Norwegian (v1+) archetypes from the Arketyper-no repository (as of validation cycle documented in this version). The following archetypes were analyzed for language patterns, compound word usage, gender/number consistency, and metadata phrasing patterns:

### **Validated International Archetypes (Norwegian Translations)**

| Archetype | Version | Status | Organization | Key Language Patterns |
|-----------|---------|--------|---------------|----------------------|
| OBSERVATION.blood_pressure | v2 | Stable | openEHR International | Standard reference; "For registrering av…" Purpose; feminine metadata forms |
| OBSERVATION.body_weight | v2 | Stable | openEHR International | "Brukes til registrering av…" Use; feminine forms (vekten, målingen) |
| OBSERVATION.pulse | v2 | Recently Updated | openEHR International | "For å registrere…" Purpose; multiple translator involvement |
| OBSERVATION.height | v2 | Stable | openEHR International | Compound word patterns (kroppshøyde, kroppslengde, antropometri) validated |
| OBSERVATION.body_temperature | v2 | Stable | openEHR International | Multiple Norwegian translators; feminine forms consistent |
| OBSERVATION.laboratory_test_result | v1 | Stable | openEHR International | Extensive compound words (laboratorieundersøkelse, prøvemateriale, analyseresultat) |
| OBSERVATION.waist_circumference | v1 | Published | openEHR International | Modern translation; "For å registrere…" Purpose pattern |
| SECTION.adhoc | v1 | Published | openEHR International | Generic section template; generic Purpose and Use statements |

### **Validated Norwegian-Native Archetypes**

| Archetype | Version | Status | Organization | Specialization |
|-----------|---------|--------|---------------|----------------|
| OBSERVATION.s_gaf | v1 | Published (2016) | Helse Vest RHF / Nasjonal IKT | Global Assessment of Functioning (GAF) — Norsk Pasientregister reporting; specialized assessment terminology |
| OBSERVATION.madrs_no | v1 | Published (2016) | Helse Vest RHF / Nasjonal IKT | Montgomery Åsberg Depression Rating Scale (MADRS) — Semantic adaptations from English original for Norwegian psychiatric assessment |

### **Validation Findings Summary**

**Purpose Statement Patterns:**

- ✓ Standard: "For å registrere…" found in OBSERVATION.pulse.v2, OBSERVATION.waist_circumference.v1
- ✗ Non-standard: "For registrering av…" found in OBSERVATION.blood_pressure.v2 — human error, should be corrected

**Use Statement Patterns:**

- ✓ Standard: "Brukes for å registrere…" is the correct form
- ✗ Non-standard variations found in published archetypes ("Brukes til å registrere…", "Bruk for å registrere…") — human errors that should be corrected to the standard form

**Misuse Statement Patterns:**

- ✓ Standard: "Brukes ikke for å registrere…" is the correct form
- ✗ Non-standard variations found in published archetypes ("Skal ikke brukes til…") — human errors that should be corrected to the standard form

**Gender and Number Consistency:**

- ✓ Feminine forms predominate in metadata across ALL validated archetypes: "arketypen", "registreringen", "målingen", "undersøkelsen", "metoden"
- ✓ Element names follow domain-specific patterns; consistency maintained within semantic domains
- ✓ Adjective agreement follows standard Norwegian three-gender system (masculine/feminine/neuter)

**Compound Word Patterns:**

- ✓ All compound words follow standard Norwegian medical terminology conventions
- ✓ Linking elements (-s-, null, -e-) applied consistently across archetypes
- ✓ No deviations from established CKM Norwegian patterns found

**Key Validation Insights:**

1. No major discrepancies found between language-standards-nb.md and published archetype practice
2. Variations in Purpose, Use, and Misuse statement phrasing found in some published archetypes are due to human inconsistency; the standard forms defined in Section C should be used consistently
3. Norwegian-native archetypes (s_gaf.v1, madrs_no.v1) contain specialized terminology specific to Norwegian healthcare contexts and assessment frameworks
4. Feminine forms in metadata are a consistent pattern across all validated archetypes and should be maintained in new translations
5. Glossary terms validated against 11+ published archetypes remain accurate and representative of CKM Norwegian practice

---

## K. References

- [openEHR KLIM Arketype – innholds- og stilguide](https://openehr.atlassian.net/wiki/spaces/KLIM/pages/683016197/Arketype+-+innholds-+og+stilguide)
- [openEHR KLIM Ord og uttrykk til oversettelser](https://openehr.atlassian.net/wiki/spaces/KLIM/pages/595427407/Ord+og+uttrykk+til+oversettelser)
- [Base Language Standards Guide](openehr://guides/archetypes/language-standards)
- [Arketyper-no Repository](https://github.com/Arketyper-no/ckm) — source of Norwegian-native and translated archetypes
- [ISO 639-1 Language Codes](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
