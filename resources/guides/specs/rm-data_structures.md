# openEHR Data Structures Information Model — Digest

**Scope:** Content-level container classes — item structures and history — that organise values inside every openEHR Entry.
**Component:** RM
**Document:** data_structures
**Release:** Release-1.1.0
**Spec URL:** https://specifications.openehr.org/releases/RM/Release-1.1.0/data_structures.html
**Markdown URL:** https://specifications.openehr.org/releases/RM/Release-1.1.0/data_structures.md
**Last updated:** 2026-04-20
**Related:** openehr://guides/specs/rm-ehr, openehr://guides/specs/rm-data_types, openehr://guides/specs/rm-common
**Keywords:** ITEM_TREE, ITEM_LIST, ITEM_TABLE, ITEM_SINGLE, HISTORY, EVENT, INTERVAL_EVENT, POINT_EVENT, CLUSTER, ELEMENT, time-series, representation

---

## Purpose

Defines the generic, archetypable container classes used to shape the `data`, `state`, `protocol`, and other content slots of every `ENTRY`. The package standardises how lists, tables, trees, single values, and time-series events are represented so that archetypes can constrain them rather than invent new structures. It also supplies the hierarchical `CLUSTER`/`ELEMENT` primitives that carry leaf values and a uniform `as_hierarchy` view across all structure shapes.

## Scope

- In: item-structure shapes (tree, list, table, single); time-series history with point-in-time and interval events; generic `CLUSTER`/`ELEMENT` representation; a common ancestor (`DATA_STRUCTURE`) exposing a uniform hierarchical view; attachment of terminology-coded math functions on interval summaries.
- Out: data-type semantics (see `RM/data_types`); archetype constraint language and templates (see `AM`); domain-level clinical meaning (encoded by archetypes); change control, identifiers, and `LOCATABLE` infrastructure (see `RM/common`); serialisation, query, and wire formats.

## Key Classes / Constructs

- `DATA_STRUCTURE` — abstract ancestor of every openEHR data structure; exposes an `as_hierarchy` projection.
- `ITEM_STRUCTURE` — parent of the four generic item-structure shapes used by archetypes.
- `ITEM_SINGLE` — a structure carrying one named `ELEMENT` (e.g. a single weight).
- `ITEM_LIST` — ordered, flat list of named `ELEMENT`s (e.g. address lines, name parts).
- `ITEM_TABLE` — table of uniform rows, each a `CLUSTER` of like-named `ELEMENT`s.
- `ITEM_TREE` — arbitrary hierarchy of `CLUSTER` and `ELEMENT` nodes; the workhorse container for rich structured content.
- `CLUSTER` — inner-node grouping of `ITEM`s; supports recursive nesting.
- `ELEMENT` — leaf node carrying a `DATA_VALUE`, plus `null_flavour` / `null_reason` for absence semantics.
- `HISTORY` — time-series container anchored at `origin`, holding one or more `EVENT`s plus optional period/duration/summary attributes.
- `EVENT` — abstract base for a timed data point inside a `HISTORY`; carries `time`, `data`, and optional `state`.
- `POINT_EVENT` — event representing an instantaneous observation at `time`.
- `INTERVAL_EVENT` — event summarising an interval (`width`, `math_function` such as mean/max/change) ending at `time`.

## Relations to Other Specs

- Depends on: `RM/support` (identifiers, terminology interface), `RM/data_types` (values inside `ELEMENT`, timing types, durations, multimedia), `RM/common` (`LOCATABLE`, `PATHABLE`, archetype-node wiring), and openEHR terminology (null flavours, event math function codes).
- Consumed by: `RM/ehr` (Entries use `ITEM_STRUCTURE` for `data`/`state`/`protocol`; `OBSERVATION.data` is a `HISTORY`), `RM/demographic` (party details use item structures), `AM` (archetypes constrain these shapes via C_COMPLEX_OBJECT), `QUERY` (AQL paths traverse `ITEM_TREE`/`HISTORY` hierarchies), and `ITS-*` serialisations.

## Architectural Placement

This package sits immediately above `RM/data_types` and below the clinical-content packages of `RM/ehr` and `RM/demographic`. It is the generic "shape layer" that lets a single archetype formalism describe everything from a one-off boolean flag to a multi-channel vital-signs time series, without the reference model prescribing domain content.

## When to Read the Full Spec

Consult the full spec when selecting the correct `ITEM_STRUCTURE` shape for an archetype slot, modelling periodic versus ad-hoc observations (`HISTORY.period`, `INTERVAL_EVENT.width`), choosing interval `math_function` codes, designing summary events, or when implementing path resolution, `as_hierarchy` traversal, or AQL navigation over time-series and tree-shaped content.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/RM/Release-1.1.0/data_structures.html
- Full spec (Markdown): https://specifications.openehr.org/releases/RM/Release-1.1.0/data_structures.md
- Related digests: specs/rm-ehr, specs/rm-data_types, specs/rm-common
