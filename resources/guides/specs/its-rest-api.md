# openEHR REST API — Digest

**Scope:** HTTP/REST binding of the openEHR Platform Service Model — resource URIs, HTTP methods, headers, media types, status codes, and the six functional API areas (System, EHR, Query, Definition, Demographic, Admin).
**Component:** ITS-REST
**Document:** overview
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/ITS-REST/development/overview.html
**Markdown URL:** N/A
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/sm-openehr_platform, openehr://guides/specs/rm-ehr, openehr://guides/specs/its-rest-simplified_formats, openehr://guides/specs/its-rest-smart_app_launch
**Keywords:** REST, HTTP, API, OpenAPI, EHR API, Query API, Definition API, Demographic API, Admin API, JSON

---

## Purpose

Defines the normative RESTful binding of the openEHR Platform Service Model. The overview document fixes cross-cutting rules — URL conventions, HTTP methods, headers (standard and `openehr-*` custom), content negotiation, media types, status codes, error payloads, authentication expectations, and `Prefer` semantics — that every ITS-REST endpoint document inherits. The per-area endpoint specs are published as OpenAPI (OAS 3.0) YAML rendered to HTML; together they form the concrete HTTP contract that clients and openEHR CDRs implement.

## Scope

- In: HTTP verbs (GET/HEAD/POST/PUT/DELETE/OPTIONS), canonical JSON/XML and Simplified (flat/structured/web-template) media types, resource identification (`ehr_id`, `versioned_object_uid`, `version_uid`), versioning headers (`openehr-version`, `openehr-audit-details`, `If-Match`, `ETag`), `Prefer` response-shape negotiation, error model, and the six functional API groups.
- Out: abstract interface semantics (see `SM/openehr_platform`), payload class definitions (`RM/*`), AQL grammar (`QUERY`), archetype formalism (`AM`), terminology content (`TERM`), conformance assertions (`CNF`), and OAuth/SMART flows (covered in `smart_app_launch`).

## Key Classes / Constructs

The REST surface is partitioned into six functional areas; each per-endpoint document is OpenAPI-rendered HTML (no `.md` twin):

- `System API` — service capabilities, version, and infrastructure discovery → https://specifications.openehr.org/releases/ITS-REST/development/system.html
- `EHR API` — EHRs, `COMPOSITION`, `FOLDER`/directory, `EHR_STATUS`, `CONTRIBUTION`, versioned object access → https://specifications.openehr.org/releases/ITS-REST/development/ehr.html
- `Query API` — ad-hoc AQL execution and stored-query invocation; `RESULT_SET` shape → https://specifications.openehr.org/releases/ITS-REST/development/query.html
- `Definition API` — ADL2/ADL1.4 archetypes, OPTs/templates, example generation, stored queries → https://specifications.openehr.org/releases/ITS-REST/development/definition.html
- `Demographic API` — parties, relationships, identities (status: development) → https://specifications.openehr.org/releases/ITS-REST/development/demographic.html
- `Admin API` — EHR physical delete, administrative lifecycle operations (status: development) → https://specifications.openehr.org/releases/ITS-REST/development/admin.html

Cross-cutting constructs defined in `overview.html`: custom headers `openehr-version`, `openehr-audit-details`, `openehr-template-id`, `openehr-uri`, `openehr-item-tag`; `Prefer: return={minimal|identifier|representation}`; simplified-format MIME types `application/openehr.wt.flat+json`, `application/openehr.wt.structured+json`, `application/openehr.wt+json`; ISO 8601 datetime rules; structured error body with `message`, `code`, coded-text array.

## Relations to Other Specs

- Binds: `SM/openehr_platform` abstract services to HTTP; operations map 1:1 to `I_EHR_SERVICE`, `I_EHR_COMPOSITION`, `I_QUERY_SERVICE`, `I_DEFINITION_ADL2`, `I_DEMOGRAPHIC_SERVICE`, `I_ADMIN_SERVICE`.
- Serialises: `RM/ehr`, `RM/common` (`VERSIONED_OBJECT`, `ORIGINAL_VERSION`, `CONTRIBUTION`), `RM/data_types` via `ITS-JSON`/`ITS-XML` canonical schemas, plus `its-rest-simplified_formats` for Flat/Structured/Web-Template payloads.
- Executes: `QUERY` (AQL) via the Query API; returns AQL `RESULT_SET`.
- Composes with: `its-rest-smart_app_launch` for SMART-on-openEHR OAuth2/OIDC launch and scope framework.
- Consumed by: CDR implementations, EHRbase/Better/Ocean/DIPS servers, and all openEHR-aware client SDKs.

## Architectural Placement

ITS-REST is the dominant wire-level expression of the openEHR platform. Sitting below `SM` (service semantics) and above `RM`/`AM`/`QUERY` (content models), it is the contract that both conformance testing and interoperability assume. Because openEHR-conformant CDRs universally expose this API, ITS-REST is effectively the industry-standard integration surface — every archetype-aware client, connector, or analytic tool negotiates through these six endpoint groups.

## When to Read the Full Spec

Read `overview.html` before implementing any client or server to internalise header/versioning/`Prefer`/error conventions. Read the specific per-area endpoint HTML (and its source OpenAPI YAML in the `specifications-ITS-REST` GitHub repo) for authoritative request/response shapes, path parameters, and status-code matrices — the OpenAPI files are the source of truth and drive code generation via the `-codegen` variants; the `-validation` variants (flattened `oneOf`) support server-side request validation. Consult `simplified_formats` when accepting Flat/Structured payloads, and `smart_app_launch` when integrating SMART-style authorisation.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/ITS-REST/development/overview.html
- Component landing: https://specifications.openehr.org/releases/ITS-REST/development/
- OpenAPI sources: https://github.com/openEHR/specifications-ITS-REST/tree/master/computable/OAS
- Related digests: specs/sm-openehr_platform, specs/rm-ehr, specs/its-rest-simplified_formats, specs/its-rest-smart_app_launch
