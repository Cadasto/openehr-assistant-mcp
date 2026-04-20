# openEHR Foundation Types — Digest

**Scope:** BASE package defining the assumed primitive, structural, interval, temporal, terminology, and root built-in types every other openEHR specification builds on.
**Component:** BASE
**Document:** foundation_types
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/BASE/development/foundation_types.html
**Markdown URL:** https://specifications.openehr.org/releases/BASE/development/foundation_types.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/base-base_types
**Keywords:** foundation types, primitive types, Any, Ordered, Interval, List, Set, Hash, Iso8601, Terminology_code, Cardinality, BASE

---

## Purpose

Fixes the names and minimal semantics of the built-in and library types every other openEHR specification — RM, AM, QUERY, TERM, SM, and the serialisation ITS documents — silently assumes to exist. It defines the root type `Any` with equality semantics, the primitive numeric and character types, container abstractions, the generic `Interval` family used throughout cardinality and constraint expressions, ISO 8601 date/time classes, and the abstract `Terminology_code` / `Terminology_term` pair that standardises how coded concepts flow through the model. The document deliberately gives only the minimum surface needed to anchor downstream semantics; concrete implementation mapping (Java, C#, Python, schema technologies) is expected to be done per deployment.

## Scope

- In: root type `Any`; ordering mix-ins `Ordered` and `Ordered_numeric`; primitive types `Octet`, `Character`, `Boolean`, `Integer`, `Integer64`, `Real`, `Double`, `String`; structural generics `Array`, `List`, `Set`, `Hash`; interval family `Interval`, `Point_interval`, `Proper_interval`, `Multiplicity_interval`, `Cardinality`; ISO 8601 temporal types `Iso8601_date`, `Iso8601_time`, `Iso8601_date_time`, `Iso8601_duration`; terminology value types `Terminology_code`, `Terminology_term`.
- Out: concrete implementation bindings for any target language, schema, or wire format; RM identifier and reference types (`OBJECT_ID`, `OBJECT_REF`, see `RM/support`); data-type value classes such as `CODE_PHRASE`, `DV_CODED_TEXT`, `DV_QUANTITY` (in `RM/data_types`); terminology-service contracts and code-set registries; archetype-specific constraint constructs such as C_OBJECT.

## Key Classes / Constructs

- `Any` — ultimate ancestor; equality, inequality, and type-identity operations every foundation type inherits.
- `Ordered` / `Ordered_numeric` — ordering mix-ins enabling comparison and arithmetic substitutability across numeric primitives.
- `Interval<T>` with `Point_interval<T>` and `Proper_interval<T>` — generic open/closed interval abstraction underpinning every range constraint in RM and AOM.
- `Multiplicity_interval` and `Cardinality` — integer-interval specialisations used for occurrences and container cardinality in AOM2.
- `List<T>` / `Set<T>` / `Array<T>` / `Hash<K,V>` — the four container generics referenced by RM and AM attribute definitions.
- `Iso8601_date`, `Iso8601_time`, `Iso8601_date_time`, `Iso8601_duration` — partial-date-aware temporal types used by `DV_DATE_TIME` and friends.
- `Terminology_code` / `Terminology_term` — abstract coded-concept pair consumed by `CODE_PHRASE` and terminology bindings.
- Primitives `Octet`, `Character`, `Boolean`, `Integer`, `Integer64`, `Real`, `Double`, `String` — the assumed scalar value space.

## Relations to Other Specs

- Depends on: nothing within openEHR — this is the floor of the type stack. External anchors are ISO 8601 (temporal), OMG IDL and W3C XML Schema primitives (scalar types), and common programming-language collection libraries (containers).
- Consumed by: `BASE/base_types` (identifier and version semantics), `RM/support` (primitive-type assumptions, terminology interfaces), `RM/data_types` (embeds interval and temporal types into `DV_INTERVAL`, `DV_DATE_TIME`, `DV_QUANTITY`), `RM/common` and all other RM packages, `AM/AOM2` (cardinality, multiplicity, interval constraints on C_OBJECT nodes), `QUERY/AQL` (literal typing), `TERM`, and every `ITS-*` serialisation that must map these types into a concrete encoding.

## Architectural Placement

Bottom of the openEHR type stack: the document that makes the rest of BASE, RM, AM, TERM, QUERY, and SM expressible at all. It stands at the boundary between openEHR and its host implementation technology, defining the vocabulary of types RM authors may use without further justification.

## When to Read the Full Spec

Consult the full `.md` or HTML when implementing interval-overlap or subsumption semantics, handling partial ISO 8601 dates/times, defining equality and hashing for a primitive binding, or mapping foundation types into a target language or schema. For per-class attribute-level detail (signatures, invariants, member functions) use the BMM-backed `type_specification_get` tool rather than mirroring that material here — this digest intentionally keeps class entries to single-line roles.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/BASE/development/foundation_types.html
- Full spec (Markdown): https://specifications.openehr.org/releases/BASE/development/foundation_types.md
- Related digests: specs/base-base_types, specs/rm-support
