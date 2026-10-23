# openEHR Data Types Information Model — Digest

**Scope:** Leaf-level typed data values carried by every archetyped `ELEMENT` across the openEHR Reference Model.
**Component:** RM
**Document:** data_types
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/RM/development/data_types.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/development/data_types.md
**Last updated:** 2026-10-22
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-data_structures, openehr://guides/specs/rm-support
**Keywords:** data types, DV_QUANTITY, DV_CODED_TEXT, DV_INTERVAL, DV_DATE_TIME, DV_MULTIMEDIA, CODE_PHRASE, ordinal, proportion, terminology, ISO 8601

---

## Purpose
The `data_types` package defines the clinical and scientific data value classes used as the leaf payload of every archetyped openEHR structure. It harmonises representation of text, coded terms, measured quantities, date/time values, intervals, encapsulated media, and URIs across EHR, demographic, and terminology models. The package balances clarity of clinical expression, implementation pragmatism, and interoperability with external standards (ISO 8601, RFC 3986, HL7 v3 and v2 where feasible). All `DATA_VALUE` descendants are strictly typed, immutable value objects — the carriers that make archetype constraints and AQL paths semantically meaningful at runtime.

## Scope
- In: typed value classes bound to `ELEMENT.value`; abstract ordering hierarchy (`DV_ORDERED`, `DV_QUANTIFIED`, `DV_AMOUNT`, `DV_ABSOLUTE_QUANTITY`); text with terminology bindings and mappings; measured and countable quantities with units, precision, accuracy; Gregorian date/time with partial-value and duration semantics; generic intervals; encapsulated media and parsable payloads; URI and EHR-scoped URI references.
- Out: postal addresses, person/organisation names, telecommunication identifiers (modelled as archetyped structures, not primitive types); non-Gregorian calendars; generic negation modifiers (expressed via archetype structure); pre-coordination logic outside the terminology service; direct terminology access (delegated to an abstract terminology service interface).

## Key Classes / Constructs
- `DATA_VALUE` — abstract root of all value types consumable as `ELEMENT.value`.
- `DV_TEXT` / `DV_CODED_TEXT` — plain narrative text (optionally formatted or hyperlinked); its subtype adds a terminology-bound `CODE_PHRASE`.
- `CODE_PHRASE` — `(terminology_id, code_string)` tuple underpinning every coded reference.
- `DV_ORDINAL` / `DV_SCALE` — ordered symbolic value (e.g. pain 0/+/++/+++) with a coded symbol and an Integer (ordinal) or Real (scale) value.
- `DV_QUANTITY` — measured dimensioned value with `magnitude`, `units` (UCUM), `precision`, and optional accuracy.
- `DV_COUNT` — integral countable quantity with no physical units.
- `DV_PROPORTION` — numerator/denominator pair typed by `PROPORTION_KIND` (ratio, unitary, percent, fraction, integer_fraction).
- `DV_DATE`, `DV_TIME`, `DV_DATE_TIME`, `DV_DURATION` — ISO 8601 absolute temporal values (partial precision, timezones) and signed relative durations.
- `DV_INTERVAL<T>` — generic closed/open range over any `DV_ORDERED`, enabling reference ranges and temporal windows.
- `DV_MULTIMEDIA` / `DV_PARSABLE` — encapsulated content: media with MIME type, size, compression and integrity metadata; inline syntactic payloads (XML, JSON, GLIF) tagged with a formalism.
- `DV_URI` / `DV_EHR_URI` — RFC 3986 URI references; the latter restricted to the `ehr:` scheme for intra-EHR addressing.
- `DV_IDENTIFIER` / `DV_BOOLEAN` — external real-world identifier and bistate primitive.

## Relations to Other Specs
- Depends on: `BASE/foundation_types` (`Iso8601_date`, `Iso8601_time`, `Iso8601_date_time`, `Iso8601_duration`, `Interval`, ordered primitives), `BASE/base_types` (identifiers such as `TERMINOLOGY_ID`), `RM/support` (terminology and measurement service access).
- Consumed by: `RM/ehr` (typing of `ELEMENT.value`, `EVENT.time`, audit timestamps), `RM/data_structures` (history/interval events, clusters), `RM/demographic` (party attributes), `RM/common` (audit details, attestations), `AM/AOM2` (tuple, `C_TERMINOLOGY_CODE`, `C_DATE_TIME` constraints; the ADL 1.4 `C_DV_QUANTITY` / `C_DV_ORDINAL` types are replaced by tuples), `QUERY/AQL` (typed path predicates, range queries), `ITS-REST` (canonical JSON/XML serialisations).

## Architectural Placement
`data_types` sits directly above `support` and `base` and below every archetyped content package. It supplies the concrete leaf types that archetype constraints (AOM2 primitive and tuple constraints; `C_DV_*` in ADL 1.4) restrict and that AQL expressions navigate to; no compositional structure is defined here, only typed values.

## When to Read the Full Spec
Consult the full specification when reasoning about `DV_QUANTITY` magnitude/precision invariants or UCUM unit semantics, designing `DV_INTERVAL<T>` generic parameterisation and open/closed boundary semantics, distinguishing `DV_CODED_TEXT` from `DV_TEXT` with language and `TERM_MAPPING` across terminologies, handling partial-precision `DV_DATE_TIME` values, or selecting between `DV_MULTIMEDIA`, `DV_PARSABLE`, and archetyped `CLUSTER` structures for complex payloads. For per-class attribute, function, and invariant detail use the BMM-backed `type_specification_get` tool rather than the rendered HTML.

## References
- Full spec (HTML): https://specifications.openehr.org/releases/RM/development/data_types.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/development/data_types.md
- Related digests: specs/rm-ehr, specs/rm-data_structures, specs/rm-support
