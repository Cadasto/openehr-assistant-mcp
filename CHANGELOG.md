# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

- Keep a Changelog: https://keepachangelog.com/en/1.1.0/
- Semantic Versioning: https://semver.org/spec/v2.0.0.html

## [Unreleased]


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

