# openEHR REST API — SMART App Launch — Digest

**Scope:** SMART on openEHR launch and authorisation framework — an OAuth 2.0 / OpenID Connect profile adapted from SMART on FHIR for securing openEHR REST endpoints.
**Component:** ITS-REST
**Document:** smart_app_launch
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/ITS-REST/development/smart_app_launch.html
**Markdown URL:** https://specifications.openehr.org/releases/ITS-REST/development/smart_app_launch.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/its-rest-api, openehr://guides/specs/sm-openehr_platform
**Keywords:** SMART, OAuth2, launch, authorisation, scopes, service discovery, well-known, FHIR

---

## Purpose

Specifies how third-party "substitutable" applications authenticate users, acquire authorisation, and discover endpoints against an openEHR platform. The profile reuses the HL7 SMART App Launch Framework (OAuth 2.0 + OpenID Connect) and re-targets it at openEHR resources — compositions, templates, AQL queries, and EHR-level context — so that a single app ecosystem can span both FHIR and openEHR back-ends with consistent launch, consent, and token semantics.

## Scope

- In: service discovery via `.well-known/smart-configuration`; standalone and embedded (EHR/iFrame) launch flows; Authorization Code with PKCE, confidential-client, client-credentials, and JWT-bearer grants; openEHR-specific scope syntax (`compartment/resource.permissions`); launch-context parameters (`ehrId`, `patient`, experimental `episodeId`); capability advertisement.
- Out: the openEHR REST resource APIs themselves (defined in `ITS-REST/openehr_api`); concrete user-authentication UX; deprecated flows (implicit grant, ROPC) are explicitly prohibited; FHIR resource semantics (delegated to HL7 SMART).

## Key Classes / Constructs

- `.well-known/smart-configuration` — JSON discovery document at platform base; advertises endpoints, services, capabilities.
- `authorization_endpoint` / `token_endpoint` — OAuth 2.0 endpoints issuing authorisation codes and access/refresh/ID tokens.
- `launch` parameter — opaque handle (or experimental base64-JSON token) binding an embedded launch to its clinical context.
- `services.org.openehr.rest` — service-discovery entry exposing the openEHR REST base URL (reverse-DNS keyed, alongside `org.fhir.rest`).
- SMART scopes — `patient/*.read`, `user/template-*.crud`, `system/aql-*.s`; `<compartment>/<resource>.<crud+s>` pattern with namespace wildcards.
- Launch-context scopes — `launch`, `launch/patient`, experimental `launch/episode`; drive context selection at the authorisation server.
- `ehrId` / `episodeId` token-response claims — openEHR-specific context returned alongside `access_token` and `id_token`.
- `capabilities[]` — platform-declared features such as `launch-ehr`, `permission-patient`, `context-openehr-ehr`, `openehr-permission-v1`.

## Relations to Other Specs

- Depends on: IETF OAuth 2.0 (RFC 6749), PKCE (RFC 7636), JWT (RFC 7519), OpenID Connect Core, and the HL7 FHIR SMART App Launch framework it profiles.
- Binds to: `ITS-REST/openehr_api` (scope resource types map to REST resources: compositions, templates, AQL), `SM/openehr_platform` (authorised calls land on the platform service interfaces), `QUERY/AQL` (for `aql-*` scopes).
- Peers with: HL7 SMART on FHIR — a dual-stack platform typically advertises both `org.openehr.rest` and `org.fhir.rest` from the same discovery document and shares the authorisation server.

## Architectural Placement

Sits at the security and integration edge of an openEHR platform: above the REST resource APIs and the `SM` service interfaces, below application clients. The authorisation server, openEHR Clinical Data Repository, and (optionally) a FHIR server together form the "platform"; a launcher initiates context, the authorisation server mediates consent and issues tokens, and applications then call the `ITS-REST` endpoints as bearer clients. The profile intentionally aligns openEHR deployments with the broader SMART app ecosystem so that substitutable clinical apps can be written once and bound to either data plane.

## When to Read the Full Spec

Read end-to-end when implementing a SMART-compliant authorisation server for openEHR, when onboarding a client application and choosing a grant type (PKCE vs confidential vs client-credentials vs JWT-bearer), when defining fine-grained scopes over templates / compositions / AQL, when wiring embedded iFrame launches that must carry `ehrId` (or experimental `episodeId`) context, when composing a discovery document that advertises both openEHR and FHIR services, or when reconciling SMART capability flags (`openehr-permission-v1`, `context-openehr-ehr`, `launch-base64-json`) with platform features.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/ITS-REST/development/smart_app_launch.html
- Full spec (Markdown): https://specifications.openehr.org/releases/ITS-REST/development/smart_app_launch.md
- Related digests: specs/its-rest-api, specs/sm-openehr_platform
- Upstream: HL7 SMART App Launch Framework (https://hl7.org/fhir/smart-app-launch/)
