# openEHR REST API — Simplified Formats — Digest

**Scope:** FLAT and STRUCTURED JSON serialisations of openEHR data for use with the openEHR REST API, as developer-friendly alternatives to canonical JSON/XML.
**Component:** ITS-REST
**Document:** simplified_formats
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/ITS-REST/development/simplified_formats.html
**Markdown URL:** https://specifications.openehr.org/releases/ITS-REST/development/simplified_formats.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/its-rest-api, openehr://guides/simplified_formats/principles, openehr://guides/simplified_formats/rules
**Keywords:** simplified format, FLAT, STRUCTURED, JSON, web template, ctx, pipe suffix, serialisation

---

## Purpose

Defines two template-driven JSON serialisations — FLAT and STRUCTURED — that encode `COMPOSITION` and related RM instances using human-readable identifiers derived from an operational template rather than full archetype paths. The formats were originally developed by Better d.o.o. and later adopted and extended by the EHRbase community, and are offered by the openEHR REST API as pragmatic alternatives to canonical JSON/XML for implementers who prefer compact, template-shaped payloads.

## Scope

- In: normative rules for FLAT path notation (slash-separated web-template node IDs, `:n` instance indices, `|suffix` attribute accessors), normative rules for STRUCTURED nested-object/array form, the `ctx` object for composition-level metadata, underscore-prefixed RM attribute keys (`_uid`, `_feeder_audit`, `_link:n`), the `|raw` escape hatch, and the associated MIME types `application/openehr.wt.flat+json` and `application/openehr.wt.structured+json`.
- Out: the HTTP endpoint catalogue (covered by `ITS-REST/openapi_ehr` and siblings), the canonical RM JSON/XML bindings (`ITS-JSON`, `ITS-XML`), template and archetype formalisms (`AM`), AQL result-set shape (`QUERY`), and terminology content (`TERM`).

## Key Classes / Constructs

- Web Template path — slash-joined, lowercased, snake-cased node IDs rooted at the template id; identifies one leaf or subtree.
- FLAT path notation — single-level `key: value` JSON map using full paths with `:n` for list indices and `|suffix` for typed attributes.
- STRUCTURED tree form — nested JSON objects and arrays that mirror the template shape; every node is an array even when cardinality is one.
- `ctx` object — composition-level defaults and metadata (`ctx/language`, `ctx/territory`, `ctx/composer_name`, `ctx/time`, `ctx/setting|code`, participations).
- Pipe suffixes — typed attribute accessors such as `|magnitude`, `|unit`, `|code`, `|value`, `|terminology`, `|ordinal`, plus `|raw` for embedding canonical JSON.
- Underscore-prefix metadata keys — access optional RM attributes outside the template skeleton (e.g. `_uid`, `_link:0`, `_feeder_audit`, `_health_care_facility`).
- RM mapping rules — concrete serialisation recipes for `COMPOSITION`, entry classes, `ELEMENT`, `CLUSTER`, `DV_QUANTITY`, `DV_CODED_TEXT`, `CODE_PHRASE`, `PARTY_IDENTIFIED`, `LINK`, `FEEDER_AUDIT`.

## Relations to Other Specs

- Depends on: `RM/ehr`, `RM/data_types`, `RM/data_structures`, `RM/common`, `AM` operational templates (OPT drives web-template identifier generation), `BASE/foundation_types`.
- Consumed by: `ITS-REST` EHR and COMPOSITION endpoints (content-negotiated via MIME type), implementer SDKs and tooling, AQL result post-processing pipelines that want template-shaped output.
- Sibling bindings: `ITS-JSON` and `ITS-XML` (canonical forms); `simplified_formats/*` author-facing guides in this repository.

## Architectural Placement

Sits inside ITS-REST as a wire-format specification: it binds template-shaped identifiers to RM instance data, turning an OPT into a predictable JSON schema. At runtime the REST layer selects FLAT, STRUCTURED, or canonical serialisation via HTTP `Content-Type` / `Accept` negotiation, so simplified formats are an interoperable peer of canonical JSON/XML rather than a replacement.

## When to Read the Full Spec

Read the upstream document when implementing or validating a FLAT/STRUCTURED serialiser or parser, resolving ambiguity around escape rules for DV_* types, handling multi-valued elements and `:n` indexing, round-tripping `ctx` defaults into `COMPOSITION` attributes, supporting `|raw` embedded canonical fragments, or reconciling differences between vendor dialects (Better, EHRbase) against the normative text. The repository's `simplified_formats/` author-facing guides handle day-to-day modelling questions; defer to this spec for binding-level edge cases.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/ITS-REST/development/simplified_formats.html
- Full spec (Markdown): https://specifications.openehr.org/releases/ITS-REST/development/simplified_formats.md
- Related digests: specs/rm-ehr, specs/rm-data_types, specs/sm-openehr_platform
