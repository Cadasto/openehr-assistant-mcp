# Plans

Implementation plans — the **only** place checkbox task lists live in this SDD
set. A plan slices work for one or more requirements and is disposable once the
work lands.

- **Filename:** `docs/plans/YYYY-MM-DD-<slug>.md`.
- **Header:** cite the `REQ-*` / ADR the plan implements.
- **Definition of Ready:** the `REQ-*` exists with acceptance criteria; affected
  design/ADR is known; out-of-scope and verification commands are named.
- **Definition of Done:** code + tests merged; requirements/traceability updated;
  `make spec-check` and `make ci` green; then move the plan to `archive/`.

Archive completed plans under [`archive/`](archive/) — leaving `done` plans in the
active list rots the index. Day-to-day work in this repo is typically tracked via
pull requests and the CHANGELOG; use a plan file for multi-step changes that
benefit from an explicit, citable task breakdown.
