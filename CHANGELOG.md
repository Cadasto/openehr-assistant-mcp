# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

- Keep a Changelog: https://keepachangelog.com/en/1.1.0/
- Semantic Versioning: https://semver.org/spec/v2.0.0.html

## [Unreleased]

### Added
- Docs: Added `AGENTS.md` file.

### Changed
- MCP Server: implemented file-based cache for discovery using Symfony Cache.
- MCP Tools: improve JSON handling with exceptions; improve search results scores and ordering.
- MCP Prompts: convert inline prompt classes to YAML prompt files
- Infra: update PHP-FPM and Caddy config for improved logging, health checks, and file handling

## [0.9.0] - 2026-01-20

### Changed
- Dependencies: Updated `mcp/sdk` to v0.3.0 and other developer tools (PHPUnit, PHPStan).
- MCP Tools: Added `outputSchema` to all tools for better AI client integration and structured outputs.
- CKM Service: Enhanced `ckm_archetype_search` and `ckm_template_search` with improved scoring logic and structured metadata.
- Terminology & Type Spec: Refactored `terminology_resolve` and type specification tools to return structured objects instead of flat arrays.

## [0.8.0] - 2026-01-20

### Added
- MCP Resource (Terminology): `openehr://terminology/all` to expose the entire openEHR terminology in JSON format.
- BMM Specifications: Added AM2 (Archetype Model 2.0) components to bundled resources and updated existing ones for better compliance.
- CI/CD: Added GitHub Actions for PR validation and enhanced Docker release process.

### Changed
- CKM Service: Refactored `ckm_archetype_search` and `ckm_template_search` tools to simplify result mapping and introduce result scoring for better relevance in AI workflows.
- MCP Prompts:
    - Updated `ckm_archetype_explorer` and `ckm_template_explorer` to leverage improved CKM search results.
    - Enhanced `translate_archetype_language` with detailed clinical terminology guidelines and better structure.
- Terminology: Improved `terminology_resolve` tool and `terminology_explorer` prompt for better clarity and coverage.
- Specification: Updated and reorganized BMM files, moving AM to AM2 for better alignment with latest openEHR specifications.
- Documentation: Updated `README.md` acknowledgments, documentation examples, and guides (checklist, rules, terminology).

## [0.7.0] - 2026-01-07

### Changed

- Refined and improved prompt descriptions and system instructions for better AI alignment.
- Enhanced resource discovery and registration in the server entry point.

## [0.6.0] - 2026-01-06

### Added

- MCP server published at https://openehr-assistant-mcp.apps.cadasto.com/

### Changed

- Decoupled Docker architecture: separated the MCP service into two distinct containers for PHP-FPM (`mcp`) and Caddy (`caddy`), improving security and maintainability.

## [0.5.0] - 2026-01-03

### Changed

- Renamed Guidelines as Guides, remove the version segment from resource URI: `openehr://guides/{category}/{name}`.
- Refined docstrings for some of the tools and prompts to improve clarity and consistency.
- Streamlined wording across guided workflows for a better user experience.
- Updated `README.md` with expanded usage instructions, feature lists, and development setup details.

### Fixed

- Removed redundant format parameters from internal `TextContent::code` calls in CKM archetype and template retrieval.


## [0.4.0] - 2025-12-29

### Added

- MCP Resources (Terminologies): `openehr://terminology/{type}/{id}` for openEHR terminology groups and codesets.
- MCP Tool (Terminology Service): `terminology_resolve` to resolve openEHR concept IDs and rubrics across groups.
- MCP Prompt (Terminology Explorer): `terminology_explorer` to guide users through discovering openEHR terminologies.
- Added tests for Terminologies resource, explorer prompt and terminology service tool.
- Added CKM template tools: `ckm_template_search` and `ckm_template_get` for OET and OPT formats.
- Added MCP Prompt (CKM Template Explorer): `ckm_template_explorer` to guide users through discovering CKM templates.
- Added `design_or_review_template` prompt to assist with openEHR Template (OET) design and review.
- Added comprehensive guides for openEHR templates (principles, rules, syntax, idioms, checklist) used by the new prompt.

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

