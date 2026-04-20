# openEHR Integration Information Model — Digest

**Scope:** Bridge for ingesting legacy and non-archetyped clinical data into openEHR compositions via a single generic, archetypable entry container.
**Component:** RM
**Document:** integration
**Release:** Release-1.1.0
**Spec URL:** https://specifications.openehr.org/releases/RM/Release-1.1.0/integration.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/Release-1.1.0/integration.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-common, openehr://guides/specs/rm-data_structures
**Keywords:** integration, GENERIC_ENTRY, legacy data, FEEDER_AUDIT, import, HL7v2, CDA

---

## Purpose
The Integration Information Model is the RM's dedicated boundary for migrating legacy, proprietary, or otherwise non-openEHR-native clinical data into the standardised, patient-centric EHR. It defines a minimal surface — a single generic entry container — that accepts externally sourced payloads in a form that can still be stored, versioned, and audited by an openEHR repository. Its premise is a two-step transformation: syntactic import first (data lands as a `GENERIC_ENTRY`), followed by semantic transformation to properly archetyped `ENTRY` subtypes once mappings mature. The package is intentionally small because the heavy lifting of clinical semantics belongs in archetype-driven entries under `rm/ehr`.

## Scope
- In: wrapping legacy/external content (HL7v2 messages, CDA documents, CSV feeds, proprietary exports) as archetypable entries; carrying source-system provenance through `FEEDER_AUDIT`; enabling ingestion pipelines to produce valid `COMPOSITION` instances.
- Out: designed clinical archetypes; strongly-typed observation/evaluation/instruction/action semantics (those live in `rm/ehr`); terminology binding logic; transformation rules themselves (an implementation concern); long-term storage strategy for legacy-shaped data.

## Key Classes / Constructs
- `GENERIC_ENTRY` — the sole class defined by the package; an archetypable entry whose only hard-wired attribute is `data` (an `ITEM_TREE` or comparable generic structure), intended to hold externally sourced content while its shape is governed by integration-specific archetypes.

Note: this package is deliberately minimal. Release-1.1.0 specifies exactly one class; all additional behaviour is inherited from `ENTRY`, `CONTENT_ITEM`, and `LOCATABLE` in other RM packages.

## Relations to Other Specs
- Depends on: `RM/ehr` — `GENERIC_ENTRY` is a sibling of `SECTION` and the archetyped `ENTRY` subtypes, and is a legal value for `COMPOSITION.content`.
- Depends on: `RM/common` — `FEEDER_AUDIT` (inherited via `LOCATABLE`) carries source-system identifiers, original content references, and originating-system-item IDs, preserving provenance across the import boundary.
- Depends on: `RM/data_structures` — the `data` payload is typically an `ITEM_TREE` (or other generic structure), letting integration archetypes model arbitrary externally sourced shapes without inventing new RM primitives.
- Consumed by: ingestion gateways, HL7v2/CDA/FHIR-to-openEHR mapping pipelines, EHR Extract import flows, and any system staging legacy clinical data prior to semantic harmonisation.

## Architectural Placement
This package is the explicit integration boundary of the Reference Model: everything entering an openEHR repository from a non-openEHR source is expected to pass through `GENERIC_ENTRY` unless it can already be produced as a properly archetyped `ENTRY` subtype. It sits between external feeders and the clinical RM proper, preserving provenance via `FEEDER_AUDIT` while keeping legacy data structurally valid but semantically opaque.

## When to Read the Full Spec
Read the full specification when designing an HL7v2, CDA, FHIR, or CSV-to-openEHR ingestion pipeline; when authoring integration archetypes that shape the `data` payload of a `GENERIC_ENTRY`; when deciding which `FEEDER_AUDIT` fields (originating system IDs, original content, feeder-system item IDs) must be populated for traceability; or when planning a staged migration where legacy content lives as `GENERIC_ENTRY` today and is progressively rewritten into archetyped `OBSERVATION`, `EVALUATION`, `INSTRUCTION`, or `ACTION` entries. A standing caveat from the spec is worth internalising: a repository composed solely of `GENERIC_ENTRY` instances is not a reliable or interoperable record — it supports neither robust clinical computation nor AQL-driven querying at the level that designed archetypes enable. The long-term trajectory is therefore always toward properly archetyped entries; `GENERIC_ENTRY` is an intentional bridge, not a destination.

## References
- Full spec (HTML): https://specifications.openehr.org/releases/RM/Release-1.1.0/integration.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/Release-1.1.0/integration.md
- Related digests: specs/rm-ehr, specs/rm-common, specs/rm-data_structures
