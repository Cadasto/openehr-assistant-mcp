# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

- Keep a Changelog: https://keepachangelog.com/en/1.1.0/
- Semantic Versioning: https://semver.org/spec/v2.0.0.html

## [Unreleased]

### Changed

- Renamed Guidelines as Guides, remove the version segment from resource URI: `openehr://guides/{category}/{name}`.


## [0.4.0] - 2025-12-29

### Added

- MCP Resources (Terminologies): `openehr://terminology/{type}/{id}` for openEHR terminology groups and codesets.
- MCP Tool (Terminology Service): `terminology_resolve` to resolve openEHR concept IDs and rubrics across groups.
- MCP Prompt (Terminology Explorer): `terminology_explorer` to guide users through discovering openEHR terminologies.
- Added tests for Terminologies resource, explorer prompt and terminology service tool.
- Added CKM template tools: `ckm_template_search` and `ckm_template_get` for OET and OPT formats.
- Added MCP Prompt (CKM Template Explorer): `ckm_template_explorer` to guide users through discovering CKM templates.

### Changed

- Using mcp/php-sdk to v0.2.2.

## [0.3.0] - 2025-12-22

### Added

- Documentation: Describe MCP Resource templates and Completion Providers now present in the codebase.
  - MCP Resources (Guidelines): `openehr://guidelines/{category}/{version}/{name}`
  - MCP Resources (Type Specifications): `openehr://spec/type/{component}/{name}`
  - Completion Providers: `ArchetypeGuidelines` and `SpecificationComponents`
- Added tests for MCP Resources and Completion Providers.

### Changed

- README and CONTRIBUTING updated to reflect current MCP Resources and Completion Providers.
- Changed the resource URI scheme from `guidelines` to `openehr`.
- improved openEHR type specification tool response and associated resources.

## [0.2.0] - 2025-12-16

### Added

- CKM tools improvements
- MCP Prompts: `explain_archetype_semantics`, `translate_archetype_language`, `fix_adl_syntax`, `design_or_review_archetype`
- MCP Resources: developer guidelines exposed via `guidelines://{category}/{version}/{name}` URIs (e.g., `guidelines://archetypes/v1/checklist`).
- CI: publish production Docker image to GitHub Container Registry (GHCR) on pushes to main.

## [0.1.0] - 2025-12-14

Initial public release.

### Added
- PHP-based MCP server builder on top of https://github.com/modelcontextprotocol/php-sdk.
- Configuration via environment variables (APP_ENV, LOG_LEVEL, HTTP_SSL_VERIFY, HTTP_TIMEOUT).
- Two transport protocols: stdio and streamable-http.
- Core tools and prompts 
- Logging via Monolog.
- HTTP client via Guzzle.
- PHPUnit tests and PHPStan configuration.
- Makefile, Dockerfile and docker-compose setup for local development.
- Documentation and contribution guidelines.

