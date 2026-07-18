# The CGEM Framework — Categorising Clinical Data for openEHR Modelling

**Scope:** A clinical-analysis framework for categorising a complex dataset before openEHR modelling, so that each part is placed in the right kind of composition, reused optimally, and given the correct persistence/versioning behaviour.
**Purpose:** Give archetype/template modellers a repeatable lens for turning a "flat" clinical form or dataset into a well-structured set of openEHR compositions and templates.
**Related:** openehr://guides/templates/principles, openehr://guides/templates/rules, openehr://guides/archetypes/principles, openehr://guides/specs/rm-ehr, openehr://guides/specs/am-Overview, openehr://guides/specs/rm-common
**Keywords:** CGEM, freshEHR, composition category, persistent, episodic, event, Instruction, Action, ISM, single source of truth, template reuse, dataset splitting, modelling methodology

---

## Attribution and status

CGEM is a clinical-informatics framework developed and published by **freshEHR Clinical Informatics** ([Introduction to the 'CGEM' Framework](https://freshehr.notion.site/Introduction-to-the-CGEM-Framework-115ed58514b344da825c3b42c372aff2)). It is **not a normative openEHR specification** — it is an *analysis and design methodology* that layers on top of openEHR. As shown below, its four categories map directly onto concepts that the openEHR specifications already define (the `COMPOSITION.category` temporal categories and the Instruction/Action model), so applying CGEM does not conflict with the specs — it operationalises them for day-to-day modelling. Where this guide states openEHR semantics, the openEHR EHR Information Model and openEHR Terminology remain the authoritative source.

## Why the framework exists

A single clinical form usually mixes data with very different *lifecycles*: some items are lifelong facts about the patient, some belong to one care journey, some are one-off measurements, and some kick off an order that must be tracked to completion. Modelling all of it as one composition — or one template per form — produces data that is hard to reuse and query, and versioning behaviour that does not match clinical reality.

CGEM helps the modeller move from a **tactical** view (this form, this screen) to a **strategic** view (patient-centric, reusable data), by classifying every datapoint into one of four categories. The category then points to the right composition type, the right reuse strategy, and the right persistence behaviour.

## The four categories

The acronym is **C-G-E-M**:

- **C — Contextual Situation.** Data that is the single source of truth for one **care journey, episode or condition**, updated gradually but kept as one current picture for that pathway. Examples: a cancer diagnosis-and-staging summary, a condition-specific care plan, a per-admission problem list. A patient may have *several* separate instances (one per journey/condition).
- **G — Global Background.** Data that is true across **all** clinical contexts for the patient's **whole life**, maintained as one current version that updates in place. Examples: the allergies/adverse-reaction list, a resuscitation (CPR/ReSPECT) decision, the persistent problem list, current medications.
- **E — Event Assessment.** **Discrete, repeated** recordings captured at a point in time; each submission is a new, independent record that never overwrites the previous one. Examples: vital-signs at a clinic visit, a laboratory result, an imaging report, an assessment score.
- **M — Managed Response.** Data belonging to a **formal order/fulfilment cycle** that must be tracked from request to completion. Examples: a referral, a prescription/medication order, an investigation request. These use the **Instruction** and **Action** archetypes and the Instruction State Machine (ISM) to track state.

> A useful discipline: what *looks* like a Managed Response often turns out to be a simple record ("Seen by key worker? Y/N", "Referral date"). Only model it as Managed Response when there is a genuine order lifecycle to track; otherwise it belongs in a Contextual or Event template.

## Mapping to openEHR

| CGEM category | `COMPOSITION.category` | Versioning behaviour | Typical entry types | Reuse / template implication |
|---|---|---|---|---|
| Global Background | `persistent` (431) | One current version per patient, maintained for life; updates in place (new versions of the same composition) | EVALUATION (problem, allergy), often maintained lists | Small number per patient; frequently **read** by many forms (often via AQL, read-only in a given form) |
| Contextual Situation | `episodic` (451) | One current version per journey/condition; updated in place, but bounded to the episode; a re-admission/new journey creates a new instance | EVALUATION, ADMIN_ENTRY, care-plan structures | One instance per pathway; the "source of truth" for that pathway |
| Event Assessment | `event` (433) | New composition per submission; not overwritten; many over time | OBSERVATION, EVALUATION | High volume, low versions/instance; strong candidate for a reusable event template |
| Managed Response | usually `event` (or `persistent`) | Order state tracked across ACTIONs via the ISM | INSTRUCTION + ACTION | Not a distinct category code — see the note below |

The three category codes (`431 persistent`, `451 episodic`, `433 event`) are defined in the openEHR Terminology (`openehr` code set, group "composition category") and described in the openEHR EHR IM. Persistent and episodic compositions are both "single source of truth" forms (persistent for life, episodic for an episode); event compositions record occurrents at a point in time.

## Applying CGEM in practice

1. **Inventory** the datapoints of the form/dataset.
2. **Categorise** each datapoint as C, G, E or M.
3. **Group** datapoints of the same category into candidate compositions/templates — one form may read/write several compositions across several categories.
4. **Set the composition category** for each template to match (persistent / episodic / event).
5. **Decide reuse:** Global Background compositions are usually already modelled (allergies, problems, medications) — reuse existing published templates rather than re-inventing; they are often *read* by the form. Event templates are prime reuse candidates across many forms.
6. **Confirm Managed Response** items genuinely need Instruction/Action + ISM tracking; downgrade the rest to simple records.

## Alignment notes and caveats

- **No contradiction with the specs.** CGEM's categories map 1:1 onto openEHR's own temporal composition model and the Instruction/Action design; it adds vocabulary and method, not new serialisation or validation semantics. It does not change ADL, AOM or OPT.
- **Four CGEM categories, three category codes.** `Global Background → persistent`, `Contextual Situation → episodic`, `Event Assessment → event`. **Managed Response is *not* a distinct `COMPOSITION.category`** — a Managed Response composition is typically an `event` (or sometimes `persistent`) composition, distinguished by its use of INSTRUCTION/ACTION entries and the ISM, not by its category code.
- **`episodic` is less widely implemented.** The `451 episodic` code is normative, but some CDRs and tools in practice use only `persistent` and `event`. Confirm your target platform's support before relying on episodic-specific behaviour; if unsupported, `persistent` with governance conventions is a common fallback.
- **CGEM is a design aid, not a conformance rule.** Nothing in openEHR *requires* CGEM; it is one proven way to reach clean, reusable, correctly-versioned models.

## References

- freshEHR, *Introduction to the 'CGEM' Framework* — <https://freshehr.notion.site/Introduction-to-the-CGEM-Framework-115ed58514b344da825c3b42c372aff2>
- openEHR EHR Information Model — Compositions (persistent / episodic / event) and the Instruction State Machine: <https://specifications.openehr.org/releases/RM/latest/ehr.html> (see openehr://guides/specs/rm-ehr)
- openEHR Terminology — composition category codes 431 / 451 / 433 (see openehr://guides/specs/term-SupportTerminology)
- openEHR Common IM — version lifecycle (completing/abandoning episodic compositions): openehr://guides/specs/rm-common
