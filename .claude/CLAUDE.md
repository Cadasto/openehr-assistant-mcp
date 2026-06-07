# Claude Code Instructions

@../AGENTS.md is the canonical guide for this repository — architecture, coding
standards, MCP conventions, the Specification-Driven `docs/` set, and developer
workflows. Follow it.

## Claude-specific notes

- **Docker-only runtime (WSL2 on Windows):** never run `php`/`composer` on the
  host — they will fail. Use `make up-dev`, `make install`, then the
  `docker compose … exec app composer …` commands in [docs/testing.md](../docs/testing.md).
- Commit with [Conventional Commits](https://www.conventionalcommits.org/) and a scope (`feat(tools):`, `fix(resources):`, `docs:`).
