# Spec Lookup — Efficient Retrieval of openEHR Specifications

**Scope:** How to locate and retrieve openEHR specification content efficiently — the `llms.txt` site index, per-page `.md` URL twins, the `/api/*.json` endpoints, and the important class-table caveat when using Markdown sources.
**Keywords:** specifications, llms.txt, markdown twin, components.json, classes.json, releases.json, retrieval, WebFetch

---

Before fetching any openEHR specification document, route through this lookup order. It avoids 30k+ word HTML pages when a cheaper source answers the question.

## 1. Site-level index: `llms.txt`

`https://specifications.openehr.org/llms.txt` lists each component with one release (mostly a pinned `Release-x.y.z`), its documents, and the JSON endpoints. Use it to:

- resolve a user's component/doc phrase to a canonical URL,
- discover sibling docs within a component,
- prefer `development` stream URLs (swap the release segment, e.g. `releases/RM/development/`) unless the user requests a fixed release tag.

## 2. Markdown twin of spec pages

**Most** specification pages have a `.md` counterpart that returns the same chapter as Markdown prose. Examples:

- `releases/RM/development/ehr.html` → `releases/RM/development/ehr.md`
- `releases/AM/development/ADL2.html` → `releases/AM/development/ADL2.md`

The same representation is also obtainable by sending `Accept: text/markdown` against the HTML URL.

**Caveat — do not miss this:** the Markdown representation contains prose, rationale, and examples, **but not the per-class tables** of attributes, functions, invariants, and inherited members. For those, use `type_specification_get` (BMM-backed) first, then the HTML page.

## 3. Structured JSON APIs

- `GET /api/components.json` — every component, short description, current release.
- `GET /api/classes.json` — flat class index (development stream): component, package, spec, anchor; some `link` values are empty.
- `GET /api/releases.json` — release calendar (id, component, date); status is in `components.json`.

Use these for class lookup, component enumeration, and release-tag resolution instead of scraping HTML.

## 4. Fall-through order

1. Is the user asking for a digest/overview? → try `guide_search(category="specs")` first; if a digest of the target doc exists, prefer it.
2. Is the user asking about a class? → `type_specification_get`; `/api/classes.json` only to locate its spec page and anchor.
3. Is the user asking about prose, rationale, or examples? → `.md` twin URL (cheapest textual source).
4. Does the answer require a class-level attribute / function / invariant table? → `type_specification_get`, then the HTML URL (Markdown omits these).

## When this guide is wrong

If `llms.txt` or `/api/*.json` returns 4xx/5xx, or the `.md` twin 404s for a given page, report the failure and fall back to HTML. Do not silently retry without telling the user.

