# openEHR Support Terminology — Digest

**Scope:** Curated internal vocabulary and external code-set references used by the RM, AM and SM for informational classification (lifecycle states, ISM, attestation, audit, composition category, properties, null flavours) and for binding standard identifiers (ISO 639, ISO 3166, IANA media types, character sets).
**Component:** TERM
**Document:** SupportTerminology
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/TERM/development/SupportTerminology.html
**Markdown URL:** https://specifications.openehr.org/releases/TERM/development/SupportTerminology.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-common, openehr://guides/specs/rm-ehr
**Keywords:** terminology, vocabulary, code set, lifecycle state, ISM, attestation, external terminology, ISO

---

## Purpose

Defines the non-clinical, informational vocabulary that openEHR models assume exists: the closed code sets and coded terms consumed by RM attributes such as `COMPOSITION.category`, `AUDIT_DETAILS.change_type`, `ENTRY.subject.relationship`, `INSTRUCTION.narrative` / `ISM_TRANSITION`, `ATTESTATION.reason`, `ELEMENT.null_flavour`, and `DV_MULTIMEDIA.media_type` / `character_set`. The specification is explicit that this is not an ontology of clinical reality — it is the minimum classifier vocabulary the models themselves need to be interoperable, with richer clinical coding delegated to SNOMED CT, LOINC, ICD and similar external terminologies.

## Scope

- In: openEHR-internal terminology groups (each with numeric concept IDs and multilingual rubrics); external code-set bindings (ISO 639-1 languages per RFC 5646, ISO 3166-1 alpha-2 countries, IANA character sets, IANA media types, compression and integrity-check algorithms, HL7 abnormal flags); the two-file XML representation (`codesets`, `terminology` with per-language `group`/`concept` entries); and the governance / translation process via the specifications-TERM repository.
- Out: Clinical terminologies (SNOMED CT, LOINC, ICD); concrete terminology-service wire protocols (defined by `TERMINOLOGY_SERVICE` in RM/support); measurement units (see UCUM binding via `MEASUREMENT_SERVICE`); archetype-internal `ac`/`at` codes (owned by the archetype, not by TERM).

## Key Classes / Constructs

- `openehr/composition_category` — persistent / episodic / event / report classification for `COMPOSITION.category`.
- `openehr/version_lifecycle_state` — complete, incomplete, inactive, abandoned states carried on `VERSION`/`VERSIONED_OBJECT`.
- `openehr/instruction_states` and `openehr/instruction_transitions` — the ISM state machine (planned, scheduled, active, suspended, completed, aborted, cancelled) and its legal transitions for `INSTRUCTION` / `ACTION`.
- `openehr/attestation_reason` and `openehr/audit_change_type` — reasons for signing/witnessing and the enumerated change kinds (creation, amendment, modification, deleted, attestation, synthesis) on `AUDIT_DETAILS`.
- `openehr/null_flavours`, `openehr/subject_relationship`, `openehr/participation_function` / `participation_mode`, `openehr/setting` — classifier vocabularies used across `ENTRY`, `PARTICIPATION` and care-context attributes.
- `openehr/property`, `openehr/event_math_function`, `openehr/term_mapping_purpose` — physical property vocabulary for `DV_QUANTITY` constraints, statistical function labels, and term-mapping provenance.
- External bindings (code sets, not translated): `languages` (ISO 639-1), `countries` (ISO 3166-1), `character_sets` and `media_types` (IANA), `compression_algorithms`, `integrity_check_algorithms`, `normal_statuses` (HL7).
- XML artefacts: a single `openehr_code_sets.xml` plus one `openehr_terminology.xml` per language under `computable/XML/<lang>/`, validated against an XSD.

For concrete code-to-rubric lookup do not paste content here — call the server's `terminology_resolve` MCP tool (groups + codes + language), and consult the XML files in the specifications-TERM repository for full content and translations.

## Relations to Other Specs

- Consumed by: `RM/common` (audit, attestation, change-control), `RM/ehr` (composition category, ISM, null flavour, subject relationship), `RM/data_types` (`DV_CODED_TEXT`, `DV_MULTIMEDIA`, `DV_QUANTITY` property binding), `RM/demographic` (participation mode/function, settings), `AM` (validation of coded constraints against openEHR terminology groups), `SM`/ITS (extract types, update triggers).
- Depends on: `RM/support` (`TERMINOLOGY_ID`, `CODE_PHRASE`, `TERMINOLOGY_SERVICE`, `CODE_SET_ACCESS`); external ISO / IANA / HL7 registries for the bound code sets.

## Architectural Placement

TERM sits alongside but outside the RM class stack: RM defines the shape of coded attributes; TERM supplies the concrete vocabulary content behind them. Operationally it is a read-only resource consulted by every RM, AM and SM implementation through the `TERMINOLOGY_SERVICE` / `CODE_SET_ACCESS` proxies, keeping concrete code values, languages and external registry bindings out of the class model.

## When to Read the Full Spec

Open the full document when implementing an ISM state machine and need the exact transition table, when wiring a terminology-service back-end that must expose openEHR groups by numeric ID, when translating or adding a language file (workflow and XML schema), when binding `DV_MULTIMEDIA` or `DV_CODED_TEXT` to IANA / ISO registries, or when resolving what counts as a valid `audit_change_type`, `null_flavour` or `composition_category` value during validation.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/TERM/development/SupportTerminology.html
- Full spec (Markdown): https://specifications.openehr.org/releases/TERM/development/SupportTerminology.md
- MCP tool: `terminology_resolve` (group + code + language)
- Related digests: specs/rm-common, specs/rm-ehr, specs/rm-support
