# openEHR Archetype Reference Formatting Guide

**Scope:** Normative rules for formatting references, citations, and bibliographic elements in archetype metadata, descriptions, and documentation
**Related:** `openehr://guides/archetypes/terminology`
**Keywords:** references, citations, formatting, bibliography, punctuation, capitalization, authors, journals, books, URLs

---

## A. General Reference Structure

- **A1:** All references SHALL follow a consistent hierarchical structure: Author(s) → Title → Publication Context → Date → Location.
- **A2:** All references in an archetype's documentation SHALL use identical formatting conventions throughout.
- **A3:** References MAY be presented as a formatted bibliography (structured list) or inline citations with full details on first mention.
- **A4:** Each reference element SHALL end with appropriate punctuation (period, unless continued by subsequent required element).

---

## B. Author and Editor Formatting

- **B1:** Author surnames SHALL appear first, followed by first initial(s) and middle initial(s): `Surname, First Initial. Middle Initial.`
- **B2:** All authors in a reference SHALL be listed individually; do not use et al. abbreviation in formatted references (though et al. MAY be used in narrative text for references with >10 authors).
- **B3:** Multiple authors SHALL be separated by comma and space; the final author SHALL be preceded by comma and "and": `Smith, J. A., Jones, M. K., and Williams, R. L.`
- **B4:** Author names SHALL preserve surname components as published (Van Der Horn, de Wolf, DeWolf, etc.).
- **B5:** Designations of rank (Jr., Sr., III, II) SHALL be retained: `Smith Jr., J. A.`
- **B6:** Non-Roman alphabets (Cyrillic, Greek, Arabic, Hebrew, Korean, Chinese, Japanese) SHALL be transliterated according to standard transliteration systems.
- **B7:** When an organization is the author, the full organizational name SHALL be used: `World Health Organization.` or `National Institutes of Health (US).`
- **B8:** When no author exists, the reference SHALL begin with the title (for works) or editor(s) (for edited collections).
- **B9:** Editor designations SHALL be marked: `Smith, J. A., editor.` or `Smith, J. A., ed.` (end of editor section).

---

## C. Title Formatting

- **C1:** Titles SHALL use minimal capitalization: capitalize only the first word, proper nouns, proper adjectives, acronyms, and initialisms.
- **C2:** For non-English titles, original language capitalization rules SHALL be applied; English translations SHALL follow English capitalization.
- **C3:** Titles SHALL end with a period, unless concluded by a question mark or exclamation point.
- **C4:** Subtitles SHALL be separated from main titles by a colon and space: `Main Title: Subtitle.`
- **C5:** For non-English titles, an English translation MAY be provided in square brackets immediately after the original title: `La Historia de la Medicina [History of Medicine].`
- **C6:** Article type designations (when required) SHALL be enclosed in square brackets and placed before the journal title: `[Abstract]`, `[Letter]`, `[Editorial]`.

---

## D. Punctuation and Formatting Conventions

- **D1:** Author/editor sections SHALL end with a period.
- **D2:** Title sections SHALL end with a period, unless already concluded by question mark or exclamation point.
- **D3:** Journal titles SHALL end with a period (if abbreviated or abbreviated-and-italicized).
- **D4:** Publication information (place, publisher) SHALL be enclosed in parentheses and end with a semicolon: `(Place: Publisher);` or separated by colons as context requires.
- **D5:** Dates SHALL end with a semicolon when followed by volume/issue information; otherwise a period.
- **D6:** Square brackets SHALL enclose translations `[English translation]`, article types `[Letter]`, access dates `[cited 2024 Jan 15]`, and electronic publication indicators.
- **D7:** Parentheses SHALL enclose edition statements `(2nd ed.)`, issue numbers `(Suppl 2)`, and affiliations where included.
- **D8:** Hyphens in page ranges SHALL connect consecutive pages: `123-125` or when omitting repeated digits: `123-5`, `1001-5`, `126A-127A`.

---

## E. Capitalization Rules

- **E1:** Capitalize the first word of a title or subtitle unconditionally.
- **E2:** Proper nouns and proper adjectives SHALL be capitalized: `Smith`, `American`, `British`.
- **E3:** Acronyms and initialisms SHALL be capitalized and periods typically omitted (though author preference or style may preserve them): `DNA`, `USA`, or `U.S.A.` (consistency required within a reference list).
- **E4:** Journal and publication titles in English SHALL have major words capitalized: `Journal of American Medical Association`.
- **E5:** For non-English journal titles, apply the capitalization rules of the source language.
- **E6:** Do not capitalize articles, conjunctions, or prepositions in titles unless they are the first word: `A study of the effect of ...` not `A Study Of The Effect Of ...`.
- **E7:** Chemical names and biological nomenclature SHALL be capitalized and formatted as in the source publication.

---

## F. Date Formatting

- **F1:** Dates SHALL follow the order: Year Month Day, using Arabic numerals for year and day: `2024 Jan 15`.
- **F2:** Months SHALL be abbreviated to the first three letters in English: `Jan`, `Feb`, `Mar`, `Apr`, `May`, `Jun`, `Jul`, `Aug`, `Sep`, `Oct`, `Nov`, `Dec`.
- **F3:** The day MAY be omitted if not available; year is required.
- **F4:** If the publication date is unknown, the access or retrieval date MAY be used, enclosed in square brackets: `[cited 2024 Jan 15]`.
- **F5:** Seasons MAY be used if month is unavailable: `2024 Spring`, `2023 Summer`.
- **F6:** Roman numerals in dates SHALL be converted to Arabic numerals: `II → 2`, `XII → 12`.
- **F7:** Supplementary publication information (such as supplements to volumes or issues) SHALL be noted: `2024 May(Suppl):S123-S145`.

---

## G. Pagination and Page Numbers

- **G1:** Page ranges SHALL use inclusive page numbers (start and end pages): `pages 123-145`.
- **G2:** Repeated digits in page ranges MAY be omitted: `pp. 123-5` not `pp. 123-125`; but `pp. 1001-5` not `pp. 1001-1005`; and `pp. 126A-127A` preserves letter designations.
- **G3:** For books, the total page count MAY be provided: `660 p.` or `viii, 660 p.` (including preliminary pages).
- **G4:** For journal articles, the abbreviation `p.` or `pp.` SHALL NOT be used; page numbers are listed directly: `123-145` not `pp. 123-145`.
- **G5:** For discontinuous pagination (articles on non-consecutive pages), list all page ranges separated by commas: `23-45, 67-89`.
- **G6:** Roman numerals in page numbers SHALL be preserved as found in the source document: `pp. i-viii` (introductory pages).

---

## H. Journal Articles

### Required Elements

In order:

- **H1:** Authors SHALL be listed as in Section B.
- **H2:** Article title SHALL be formatted per Section C.
- **H3:** Journal title SHALL follow the article title, separated by a period, and in italic font (if typography is available): *Journal of the American Medical Association* or `Journal of the American Medical Association`.
- **H4:** Journal title abbreviations SHOULD be used when consistent with standard references (see section G-1 below); consult the NLM Journal Abbreviations database or the journal's own abbreviation standard.
- **H5:** Publication date SHALL follow journal title, formatted per Section F: `2024 Jan 15;`
- **H6:** Volume number SHALL follow the date (without the prefix "Vol."): `148:`
- **H7:** Issue number, if present, SHALL be enclosed in parentheses and precede the colon: `148(3):`
- **H8:** Page range SHALL follow the issue/volume information: `123-145.`

### Optional Elements

- **H9:** Article type designations MAY be included in square brackets before the journal title: `[Letter]`, `[Abstract]`, `[Editorial]`.
- **H10:** Author affiliations MAY be included in parentheses after the author names: `Smith, J. A. (Department of Medicine, University Hospital, Boston, Massachusetts).`
- **H11:** Language SHALL be noted if not English: `German.` at the end of the reference.
- **H12:** Digital Object Identifier (DOI) or PubMed ID MAY be included at the end: `doi: 10.1001/jama.2024.123456` or `PMID: 1234567`.

### Example Journal Article

```text
Smith, J. A., Jones, M. K., and Williams, R. L. Efficacy of novel therapeutic intervention 
in acute myocardial infarction. Journal of the American Medical Association. 2024 Jan 15;148(3):123-145. 
doi: 10.1001/jama.2024.123456
```

---

## I. Books (Entire Works)

### Required Elements

In order:

- **I1:** Author(s) or editor(s) SHALL be listed per Section B.
- **I2:** Book title SHALL be formatted per Section C and in italic font (if available).
- **I3:** Edition statement, if other than first edition, SHALL follow the title: `(2nd ed.)` or `(3rd ed., revised)`.
- **I4:** Place of publication SHALL be listed; for US and Canadian cities, include state/province abbreviation; for international cities, include country: `Boston, Massachusetts:` or `Toronto, Ontario:` or `London, England:`.
- **I5:** Publisher name SHALL follow the place of publication: `Harvard University Press;`
- **I6:** Publication year SHALL follow the publisher: `2024.`
- **I7:** Total page count MAY be included: `660 p.`

### Optional Elements

- **I8:** Series information MAY be included at the end: `(Series in Medical Ethics; vol. 23)`.
- **I9:** ISBN MAY be included: `ISBN: 978-0-674-12345-6`.
- **I10:** Author affiliation MAY be included: `Smith, J. A. (Professor of Medicine, Harvard Medical School).`

### Example Book

```text
Smith, J. A., and Jones, M. K. The comprehensive guide to cardiovascular disease. 2nd ed.
Boston, Massachusetts: Harvard University Press; 2024. 660 p.
```

---

## J. Book Chapters and Contributions

### Required Elements

In order:

- **J1:** Chapter author(s) SHALL be listed per Section B.
- **J2:** Chapter title SHALL be formatted per Section C, ending with a period.
- **J3:** The phrase "In:" SHALL introduce the book information.
- **J4:** Book author(s) or editor(s) SHALL follow "In:" (listing editors with `ed.` or `editor` designation).
- **J5:** Book title SHALL be in italic font (if available), formatted per Section C.
- **J6:** Edition statement (if other than first), place, publisher, and year SHALL follow the book title, per Section I.
- **J7:** Chapter page range SHALL be provided: `pp. 123-145.` or `pages 123-145.`

### Example Book Chapter

```text
Williams, R. L. Management of acute complications. In: Smith, J. A., and Jones, M. K., eds.
The comprehensive guide to cardiovascular disease. 2nd ed. Boston, Massachusetts:
Harvard University Press; 2024. pp. 123-145.
```

---

## K. Online and Web Resources

### Required Elements

- **K1:** Author(s) or organization SHALL be listed per Section B.
- **K2:** Document/page title SHALL be formatted per Section C, ending with a period.
- **K3:** Website or source name SHALL be included (italicized if available).
- **K4:** URL SHALL be provided in full form (including `http://` or `https://`).
- **K5:** Access date SHALL be included in square brackets: `[cited 2024 Jan 15]`.
- **K6:** Publication date or last update date SHOULD be included if available: `updated 2024 Jan 10;`

### Optional Elements

- **K7:** Content type or description MAY be included: `[PDF]`, `[HTML]`, `[interactive tool]`.

### Example Web Resource

```text
World Health Organization. Guidelines for cardiovascular risk assessment.
Available at: <https://www.who.int/publications/guidelines/cardiovascular>.
Updated 2024 Jan 10. [Cited 2024 Jan 15].
```

---

## L. Government Documents and Reports

### Required Elements

- **L1:** Government agency or issuing body SHALL be listed as author (per Section B-7): `United States Department of Health and Human Services.`
- **L2:** Document title SHALL be formatted per Section C.
- **L3:** Report number or identifier MAY be included if available: `(Report No. HHS-2024-001)`.
- **L4:** Publication place, publisher/issuing agency, and date SHALL be provided per Section I.
- **L5:** Total pages MAY be included: `47 p.`

### Example Government Report

```text
United States Department of Health and Human Services. Guidelines for cardiovascular 
disease prevention. Washington, DC: U.S. Government Publishing Office; 2024. 
Report No. HHS-2024-001. 47 p.
```

---

## M. Dissertations, Theses, and Academic Works

### Required Elements

- **M1:** Author (student name) SHALL be listed per Section B.
- **M2:** Thesis title SHALL be formatted per Section C.
- **M3:** Thesis type MUST be identified: `[PhD thesis]`, `[Master's thesis]`, `[Doctoral dissertation]`.
- **M4:** Institution name SHALL be provided: `Harvard Medical School.`
- **M5:** Year of publication/completion SHALL be provided: `2024.`

### Optional Elements

- **M6:** Total pages MAY be included: `250 p.`
- **M7:** Advisors or supervisors MAY be noted: `Advisor: Prof. J. A. Smith.`

### Example Thesis

```text
Williams, R. L. Molecular mechanisms of myocardial regeneration. [PhD thesis]. 
Harvard Medical School; 2024. 250 p. Advisor: Prof. J. A. Smith.
```

---

## N. Conference Proceedings and Presentations

### Required Elements

- **N1:** Author(s) of the paper/presentation SHALL be listed per Section B.
- **N2:** Paper or presentation title SHALL be formatted per Section C.
- **N3:** Conference name SHALL be provided: `Proceedings of the International Conference on Cardiovascular Medicine.`
- **N4:** Conference location (city, country) SHALL be included: `held in Boston, Massachusetts, USA;`
- **N5:** Conference dates SHALL be provided: `June 15-18, 2024;`
- **N6:** Publishing body, place, and date SHALL follow if formally published: `Boston: American Heart Association; 2024.`
- **N7:** Page range or article number SHALL be provided: `pp. 234-245.` or `article e12345.`

### Example Conference Paper

```text
Smith, J. A., and Jones, M. K. Novel therapeutics in acute myocardial infarction. 
In: Proceedings of the International Conference on Cardiovascular Medicine; 
held in Boston, Massachusetts, USA; June 15-18, 2024. Boston: American Heart Association; 2024. 
pp. 234-245.
```

---

## O. Standards and Technical Specifications

### Required Elements

- **O1:** Standards-issuing organization SHALL be listed as author: `International Organization for Standardization.` or `American National Standards Institute.`
- **O2:** Standard designation and title SHALL be provided: `ISO 9001:2015 Quality Management Systems.`
- **O3:** Edition and publication date SHALL follow: `2nd ed. Geneva: ISO; 2015.`
- **O4:** Status (draft, published, withdrawn) MAY be noted if not current.

### Example Standard

```text
International Organization for Standardization. ISO 9001:2015 Quality Management Systems. 
2nd ed. Geneva: ISO; 2015.
```

---

## P. Non-English References, Transliteration, and Special Characters

- **P1:** Non-English titles MAY be presented in original language with English translation in square brackets: `La Historia de la Medicina [History of Medicine].`
- **P2:** Language of publication SHALL be noted at the end of the reference if not English: `German.`, `French.`, `Chinese.`
- **P3:** Greek letters, chemical formulas, and mathematical notation SHALL be preserved exactly as published.
- **P4:** Cyrillic, Arabic, Hebrew, Korean, Chinese (Hanzi/Kanji), and Japanese scripts SHALL be transliterated using standard transliteration systems:
  - **Cyrillic:** ISO 9 or BGN/PCGN transliteration
  - **Arabic:** ISO 233 or ALA-LC transliteration
  - **Hebrew:** ISO 259 or ALA-LC transliteration
  - **Chinese (Simplified):** Pinyin (e.g., Mao → Mao, not Mao)
  - **Chinese (Traditional):** Wade-Giles or Pinyin transliteration with original characters optionally included
  - **Japanese:** Romaji transliteration (using Hepburn or Modified Hepburn system)
  - **Korean:** Romanization (McCune-Reischauer or Revised Romanization)
- **P5:** Diacritical marks (accents, umlauts, tildes) in European languages SHALL be preserved: `Müller`, `Montaña`, `Côté`.
- **P6:** When original script is provided alongside transliteration, place original in parentheses after transliteration: `Ivanov (Иванов)`.

---

## Q. Journal Title Abbreviations

- **Q1:** Journal titles in references SHOULD be abbreviated according to standard abbreviation lists (NLM Journal Abbreviations, ISO 4, or the journal's own recommended abbreviation).
- **Q2:** Significant words in journal titles SHALL be included in abbreviations; articles, conjunctions, and prepositions MAY be omitted: `Journal of the American Medical Association` → `J Am Med Assoc`
- **Q3:** When journal names have changed, use the name and abbreviation current at the time of publication: `British Medical Journal` (pre-1988) → `Br Med J`; `BMJ` (1988 forward).
- **Q4:** Consistency is critical: if full journal titles are used in any reference, use them in all references; similarly, if abbreviations are used, maintain abbreviations throughout.
- **Q5:** Common abbreviations:
  - `American` → `Am`
  - `British` → `Br`
  - `Journal` → `J`
  - `Medical` → `Med`
  - `International` → `Int`
  - `Society` → `Soc`
  - `Association` → `Assoc`
  - `Review` → `Rev`
  - `Research` → `Res`
  - `Clinical` → `Clin`
  - `Laboratory` → `Lab`
  - `Letters` → `Lett`
  - `Reports` → `Rep`

### Common Journal Abbreviations in Medical/Healthcare Literature

- JAMA → J Am Med Assoc
- The Lancet → Lancet
- The New England Journal of Medicine → N Engl J Med
- Nature Medicine → Nat Med
- Science → Science
- Circulation → Circulation
- BMJ → BMJ
- JAMA Pediatrics → JAMA Pediatr
- Archives of Internal Medicine → Arch Intern Med

---

## R. Optional Elements and Best Practices

- **R1:** Author affiliations MAY be included for clarity, especially when disambiguation is needed: `(Department of Cardiology, Johns Hopkins Hospital, Baltimore, Maryland)`.
- **R2:** Digital identifiers (DOI, PubMed ID, PMID) SHOULD be included when available for traceability: `doi: 10.1001/jama.2024.123456` or `PMID: 12345678`.
- **R3:** Errata or corrections to published materials SHOULD be noted: `Erratum in: J Am Med Assoc. 2024 Feb 1;150(2):189.`
- **R4:** Retracted publications SHOULD be marked: `[Retracted in: J Am Med Assoc. 2024 Feb 15;149(4):267.]`
- **R5:** Electronic publication ahead of print MAY be noted: `[Epub ahead of print 2024 Jan 10.]`
- **R6:** ISBN or ISSN numbers MAY be included for book and serial publications.
- **R7:** Series information MAY be noted for multi-volume works: `(In: Series Name; vol. 5)`.
- **R8:** Accompanying materials (CD-ROM, DVD, supplementary files) MAY be noted: `(accompanying CD-ROM)`.

---

## S. Reference List Organization and Consistency

- **S1:** References within an archetype's documentation SHALL be listed in a single, consistently ordered bibliography (chronological, alphabetical by author, or by citation order).
- **S2:** If multiple reference formats are necessary (e.g., inline citations with full details and a bibliography), the full formatting rules (Sections A-R) SHALL apply to both formats.
- **S3:** Consistency of capitalization, punctuation, abbreviation, and formatting SHALL be maintained across all references within a given archetype.
- **S4:** When references span different languages, transliteration standards (Section P) SHALL be applied uniformly.

---

## T. Quick Checklist for Reference Formatting

- ☑ All authors listed, surnames first, initials only
- ☑ Title uses minimal capitalization (first word, proper nouns, acronyms only)
- ☑ Journal/publication name is consistent with standard abbreviation (if abbreviated) or fully spelled out (if full form is used)
- ☑ Date follows Year Month Day format (2024 Jan 15)
- ☑ Volume and issue numbers correctly formatted (Volume(Issue):pages)
- ☑ Page ranges use inclusive numbers with digit omission rule applied (123-5, not 123-125)
- ☑ Punctuation is consistent throughout (periods, commas, colons, semicolons in correct positions)
- ☑ Non-English content transliterated or marked with language code
- ☑ Optional elements (DOI, PMID, errata) included where available
- ☑ All references in list use identical formatting conventions
