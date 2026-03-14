# openEHR Archetype Reference Formatting Guide

**Scope:** Rules for formatting references, citations, and bibliographic elements in archetype metadata and documentation
**Related:** openehr://guides/archetypes/terminology
**Keywords:** references, citations, formatting, bibliography, punctuation, capitalization, authors, journals, books, URLs

---

## A. General Structure

- Order: Author(s) → Title → Publication Context → Date → Location.
- Consistent formatting across all references in an archetype.
- Each element ends with appropriate punctuation (period unless continued).

---

## B. Authors & Editors

| Rule | Convention |
|------|-----------|
| Name order | `Surname, First Initial. Middle Initial.` |
| Multiple | Comma-separated; final preceded by "and": `Smith, J. A., Jones, M. K., and Williams, R. L.` |
| All listed | No et al. in formatted refs (allowed in narrative for >10 authors) |
| Surname variants | Preserve as published: Van Der Horn, de Wolf, DeWolf |
| Rank designations | Retain: `Smith Jr., J. A.` |
| Non-Roman scripts | Transliterate per standard systems (see Section P) |
| Organization author | Full name: `World Health Organization.` |
| No author | Begin with title (or editors for edited collections) |
| Editor marker | `Smith, J. A., editor.` or `ed.` |

---

## C. Titles

- Minimal capitalization: first word, proper nouns, acronyms only.
- End with period (unless `?` or `!`).
- Subtitle: `Main Title: Subtitle.`
- Non-English: apply source-language rules; English translation in brackets: `La Historia de la Medicina [History of Medicine].`
- Article type in brackets before journal title: `[Abstract]`, `[Letter]`, `[Editorial]`.

---

## D. Punctuation Conventions

| Element | Ending |
|---------|--------|
| Author/editor section | Period |
| Title | Period (or `?`/`!`) |
| Journal title | Period |
| Publication info (place, publisher) | Parentheses + semicolon: `(Place: Publisher);` |
| Date before volume/issue | Semicolon |
| Brackets | Translations `[text]`, article types `[Letter]`, access dates `[cited 2024 Jan 15]` |
| Parentheses | Edition `(2nd ed.)`, issue `(Suppl 2)` |
| Page ranges | Hyphen: `123-5`, `1001-5`, `126A-127A` |

---

## E. Capitalization

- First word of title/subtitle: always capitalize.
- Proper nouns/adjectives: capitalize (`Smith`, `American`).
- Acronyms: capitalize, omit periods (`DNA`, `USA`); be consistent.
- English journal titles: major words capitalized.
- Non-English: follow source-language rules.
- Articles/conjunctions/prepositions: lowercase unless first word.

---

## F. Dates

- Format: `Year Month Day` → `2024 Jan 15`
- Months: 3-letter English abbreviations (`Jan`–`Dec`).
- Day optional; year required.
- Unknown date: use access date in brackets `[cited 2024 Jan 15]`.
- Seasons allowed: `2024 Spring`.
- Roman numerals → Arabic: `II → 2`.

---

## G. Page Numbers

- Inclusive ranges: `123-145`.
- Omit repeated digits: `123-5` (not `123-125`).
- Journal articles: no `p.`/`pp.` prefix; direct numbers.
- Books: total pages with `p.`: `660 p.` or `viii, 660 p.`
- Discontinuous: comma-separated ranges: `23-45, 67-89`.

---

## H. Journal Articles

**Order:** Authors. Title. *Journal Title.* Date; Volume(Issue):Pages. [Optional: DOI/PMID]

Optional elements: article type `[Letter]`, author affiliations, language note, DOI/PMID.

```text
Smith, J. A., Jones, M. K., and Williams, R. L. Efficacy of novel therapeutic intervention
in acute myocardial infarction. Journal of the American Medical Association. 2024 Jan 15;148(3):123-145.
doi: 10.1001/jama.2024.123456
```

---

## I. Books

**Order:** Authors. *Title.* Edition. Place: Publisher; Year. [Pages.]

Optional: series info, ISBN, author affiliation.

```text
Smith, J. A., and Jones, M. K. The comprehensive guide to cardiovascular disease. 2nd ed.
Boston, Massachusetts: Harvard University Press; 2024. 660 p.
```

---

## J. Book Chapters

**Order:** Chapter Authors. Chapter Title. In: Book Editors, eds. *Book Title.* Edition. Place: Publisher; Year. pp. Pages.

```text
Williams, R. L. Management of acute complications. In: Smith, J. A., and Jones, M. K., eds.
The comprehensive guide to cardiovascular disease. 2nd ed. Boston, Massachusetts:
Harvard University Press; 2024. pp. 123-145.
```

---

## K. Online Resources

**Order:** Author/Org. Title. *Site Name.* URL. Updated date. [Cited date.]

```text
World Health Organization. Guidelines for cardiovascular risk assessment.
Available at: <https://www.who.int/publications/guidelines/cardiovascular>.
Updated 2024 Jan 10. [Cited 2024 Jan 15].
```

---

## L. Government Documents

**Order:** Agency. Title. (Report No.) Place: Publisher; Year. [Pages.]

```text
United States Department of Health and Human Services. Guidelines for cardiovascular
disease prevention. Washington, DC: U.S. Government Publishing Office; 2024.
Report No. HHS-2024-001. 47 p.
```

---

## M. Dissertations & Theses

**Order:** Author. Title. [Type]. Institution; Year. [Pages. Advisor.]

```text
Williams, R. L. Molecular mechanisms of myocardial regeneration. [PhD thesis].
Harvard Medical School; 2024. 250 p. Advisor: Prof. J. A. Smith.
```

---

## N. Conference Proceedings

**Order:** Authors. Paper Title. In: Conference Name; Location; Dates. Publisher; Year. Pages.

```text
Smith, J. A., and Jones, M. K. Novel therapeutics in acute myocardial infarction.
In: Proceedings of the International Conference on Cardiovascular Medicine;
held in Boston, Massachusetts, USA; June 15-18, 2024. Boston: American Heart Association; 2024.
pp. 234-245.
```

---

## O. Standards & Technical Specs

**Order:** Organization. Designation Title. Edition. Place: Publisher; Year.

```text
International Organization for Standardization. ISO 9001:2015 Quality Management Systems.
2nd ed. Geneva: ISO; 2015.
```

---

## P. Non-English & Transliteration

- Present original with English translation in brackets.
- Note language at end: `German.`, `French.`
- Preserve Greek letters, chemical formulas, mathematical notation.
- Transliteration systems: Cyrillic (ISO 9), Arabic (ISO 233), Hebrew (ISO 259), Chinese (Pinyin), Japanese (Hepburn Romaji), Korean (Revised Romanization).
- Preserve diacritical marks: `Müller`, `Montaña`, `Côté`.

---

## Q. Journal Abbreviations

- Use standard abbreviations (NLM, ISO 4) consistently.
- Common: `Journal` → `J`, `American` → `Am`, `Medical` → `Med`, `International` → `Int`, `Clinical` → `Clin`, `Research` → `Res`, `Review` → `Rev`, `Association` → `Assoc`, `Society` → `Soc`.
- If full titles used anywhere, use full titles everywhere (and vice versa).

---

## R. Optional Elements

- Author affiliations for disambiguation.
- DOI / PMID for traceability.
- Errata: `Erratum in: J Am Med Assoc. 2024 Feb 1;150(2):189.`
- Retraction: `[Retracted in: ...]`
- Epub ahead of print: `[Epub ahead of print 2024 Jan 10.]`
- ISBN/ISSN, series information, accompanying materials.

---

## Quick Checklist

- [ ] Authors: surnames first, initials only
- [ ] Title: minimal capitalization
- [ ] Journal/publication: consistent abbreviation or full form
- [ ] Date: `Year Month Day` format
- [ ] Volume/issue: `Volume(Issue):Pages`
- [ ] Page ranges: digit omission applied
- [ ] Punctuation: consistent throughout
- [ ] Non-English: transliterated or language-coded
- [ ] Identifiers (DOI, PMID): included where available
- [ ] All references use identical formatting
