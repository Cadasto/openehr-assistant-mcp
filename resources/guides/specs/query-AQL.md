# Archetype Query Language (AQL) — Digest

**Scope:** Declarative, path-based query language for retrieving data from archetype-governed openEHR repositories.
**Component:** QUERY
**Document:** AQL
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/QUERY/development/AQL.html
**Markdown URL:** https://specifications.openehr.org/releases/QUERY/development/AQL.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/am2-ADL2, openehr://guides/specs/sm-openehr_platform
**Keywords:** AQL, query, archetype path, containment, WHERE, SELECT, CONTAINS, stored query

---

## Purpose

Defines a declarative query language for openEHR data that is independent of any physical storage schema. AQL expresses queries in terms of Reference Model (RM) classes and archetype-constrained nodes, so the same statement is portable across any conformant back end. It supports retrieval of fine-grained clinical data items, whole compositions, folders, demographic records, and versioned history, enabling use cases ranging from point-of-care data display to cohort analytics, decision support, and data export.

## Scope

- In: query syntax (SELECT, FROM, WHERE, ORDER BY, LIMIT/OFFSET), containment via `CONTAINS`, archetype-path addressing, RM type variables, predicates (archetype, standard, node), comparison and logical operators, `EXISTS` and `MATCHES`, terminology value-set matching, aggregation functions, parameterised and stored queries, query against EHR, composition, versioned-object, folder, and demographic targets.
- Out: REST/HTTP transport and result envelopes (see `ITS-REST`), execution-plan optimisation, storage layout, server-side state management, archetype constraint semantics (see `AM`), RM class definitions (see `RM`), terminology content (see `TERM`).

## Key Classes / Constructs

- `SELECT` — projection list of paths, RM variables, literals, or aggregates; supports `DISTINCT` and column aliases.
- `FROM` / `CONTAINS` — declares the root class (typically `EHR`, `COMPOSITION`, `FOLDER`, or a demographic `PARTY` subtype) and hierarchical containment between variables.
- Archetype paths — locate nodes using `/data[at0001]/items[at0004]/value` plus archetype predicates like `[openEHR-EHR-OBSERVATION.blood_pressure.v2]`.
- RM type variables — single-letter bindings (e.g., `e`, `c`, `o`) that anchor paths and appear in SELECT/WHERE.
- Predicates — archetype (`[archetype_id=...]`), standard (`[name/value='Systolic']`), and node (`[at-code]`) filters applied inline.
- `WHERE` — boolean expression using comparison operators, `LIKE`, `MATCHES {...}` for value sets, `EXISTS`, `NOT`, `AND`/`OR`.
- Aggregation — `COUNT`, `MIN`, `MAX`, `SUM`, `AVG` over projected paths.
- `ORDER BY` / `LIMIT` / `OFFSET` — deterministic ordering and result windowing; parameters (`$var`) enable stored, reusable queries.

## Relations to Other Specs

- Depends on: `RM/ehr` and `RM/demographic` (classes navigated by queries), `RM/data_structures` and `RM/data_types` (leaf-value structure), `AM` (archetype identifiers and node codes embedded in paths/predicates), `TERM/openehr_terminology` and external terminology services (value-set resolution via `TERMINOLOGY()` and `MATCHES`).
- Consumed by: `SM/openehr_platform` (Query Service interface, stored-query registry), `ITS-REST` (query endpoints, request/response bindings), tooling such as query builders and composition finders.
- Companion: formal ANTLR4 lexer/parser grammars published alongside the specification.

## Architectural Placement

AQL is the query-layer counterpart to the ADL archetype formalism: where ADL constrains what can be committed, AQL retrieves it using the same archetype/path vocabulary. It sits above the RM and AM, below the Service Model and REST binding, and is the canonical mechanism by which applications traverse the two-level model without leaking physical schema details.

## When to Read the Full Spec

Consult the full specification for the authoritative ANTLR4 grammar, precedence and associativity of operators, exact semantics of path shortening and name-predicate disambiguation, rules for null handling and three-valued logic, the complete list of reserved words and functions, parameter binding and stored-query registration semantics, temporal-version query forms (e.g., `VERSIONED_COMPOSITION`, time-based selection), and conformance requirements for query engines.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/QUERY/development/AQL.html
- Full spec (Markdown): https://specifications.openehr.org/releases/QUERY/development/AQL.md
- Related digests: specs/rm-ehr, specs/am2-ADL2, specs/sm-openehr_platform
