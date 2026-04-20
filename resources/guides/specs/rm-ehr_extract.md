# openEHR EHR Extract Information Model — Digest

**Scope:** Cross-system packaging of EHR content for coarse-grained transmission between health systems
**Component:** RM
**Document:** ehr_extract
**Release:** Release-1.1.0
**Spec URL:** https://specifications.openehr.org/releases/RM/Release-1.1.0/ehr_extract.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/Release-1.1.0/ehr_extract.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-common, openehr://guides/specs/rm-demographic
**Keywords:** EHR_EXTRACT, X_VERSIONED_COMPOSITION, EXTRACT_CHAPTER, EXTRACT_FOLDER, transmission, provenance, demographic extract, messaging

---

## Purpose
The `ehr_extract` package formalises the concepts of *extract request*, *extract*, *content items* (openEHR and non-openEHR), and an optional *message wrapper* used when a portion of one or more EHRs must be packaged and transferred between systems. It provides a structural "lingua franca" for coarse-grained interoperability scenarios — EHR-to-EHR communication, clinical content messaging, and inter-repository synchronisation — independent of any particular wire format. The package also defines the *serialisable* (`X_*`) forms of versioned objects so that internal reference-model graphs can be flattened for transport without losing change-control semantics.

## Scope
- In: extract request and response envelopes; per-patient chapters; folder/content organisation inside an extract; serialisable forms of versioned objects; simplified participations; message wrapper for point-to-point exchange; rewriting of external demographic references into locally-packaged copies.
- Out: concrete wire formats (XML, JSON, Protobuf) — those belong to `ITS-XML` / `ITS-JSON`; archetype or terminology payload inclusion; notarisation / digital-signature mechanisms; full cascade-retrieval policy for demographic graphs; transport protocol and security channel (delegated to platform / ITS).

## Key Classes / Constructs
- `EXTRACT_REQUEST` — request message identifying the repository content to be returned in an extract.
- `EXTRACT_SPEC` — detailed specification, embedded in the request, of what subset of a target repository is required.
- `EXTRACT_ACTION_REQUEST` — re-executes a previously persisted extract specification (standing-request pattern).
- `EXTRACT` — reply envelope carrying the retrieved content and metadata.
- `EXTRACT_ENTITY_CHAPTER` — groups all content associated with a single entity (typically one patient / EHR subject).
- `EXTRACT_CHAPTER` — container holding a folder tree of content items within an entity chapter.
- `EXTRACT_FOLDER` — organisational sub-tree mirroring `FOLDER` structures inside an extract.
- `EXTRACT_CONTENT_ITEM` — abstract parent for payload items (metadata + serialised content reference).
- `X_VERSIONED_OBJECT<T>` — serialisable form of a versioned object, flattening `VERSIONED_OBJECT<T>` and its `ORIGINAL_VERSION` history for transport.
- `X_VERSIONED_COMPOSITION` — concrete binding of `X_VERSIONED_OBJECT` to `COMPOSITION`, used when extracting clinical compositions with full version history.
- `GENERIC_CONTENT_ITEM` — wrapper for legacy / non-openEHR payloads carried alongside openEHR content.
- `EXTRACT_PARTICIPATION` — simplified, self-contained form of `PARTICIPATION` suitable for extract-time provenance without requiring a live demographic service.
- `MESSAGE` — lightweight point-to-point envelope around an `EXTRACT_REQUEST` or `EXTRACT`.

## Relations to Other Specs
- Depends on: `RM/ehr` (references `COMPOSITION`, `EHR_ACCESS`, `EHR_STATUS`, `VERSIONED_OBJECT<T>`); `RM/common` (generic `PARTICIPATION`, `PARTY_PROXY`, `PARTY_SELF`, `PARTY_IDENTIFIED`, `LOCATABLE`; change-control `ORIGINAL_VERSION`, `CONTRIBUTION`, audit types); `RM/demographic` (`PARTY` subtypes serialised into dedicated demographic chapters); `RM/support` (`OBJECT_REF`, `HIER_OBJECT_ID`, `OBJECT_ID` for rewritten external references); `RM/data_types` (`DV_PARSABLE`, `DV_TEXT`, `DV_CODED_TEXT`, `DV_EHR_URI`, `DV_LINK`, `DV_MULTIMEDIA`, `DV_URI`).
- Consumed by: `ITS-XML` and `ITS-JSON` (concrete serialisations of the extract graph); `SM/openehr_platform` (extract and messaging service interfaces); FHIR bridges and CDA gateways that translate extracts into other standards; replication / synchronisation tooling.

## Architectural Placement
The EHR Extract model sits *above* the core EHR and demographic information models as a transmission layer: it does not redefine clinical semantics but re-projects existing RM objects into shapes that are self-contained, address-rewritten, and serialisable. It is the canonical bridge between live repository APIs and any ITS-level wire representation.

## When to Read the Full Spec
Read it when you need to (a) implement or consume an extract producer/consumer and must understand `EXTRACT_SPEC` filters; (b) reason about the relationship between `VERSIONED_COMPOSITION` in an EHR and its `X_VERSIONED_COMPOSITION` serialised counterpart, including audit-trail preservation; (c) decide how far the demographic graph should be walked and how `PARTY_REF` rewriting affects downstream consumers; or (d) design synchronisation flows where `EXTRACT_ACTION_REQUEST` standing queries trigger recurring transfers.

## References
- Full spec (HTML): https://specifications.openehr.org/releases/RM/Release-1.1.0/ehr_extract.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/Release-1.1.0/ehr_extract.md
- Related digests: specs/rm-ehr, specs/rm-common, specs/rm-demographic
