# Spec Lookup — Efficient Retrieval of openEHR Specifications

**Scope:** How to locate and retrieve openEHR specification content efficiently — the `llms.txt` site index, per-page `.md` URL twins, the `/api/*.json` endpoints, and the important class-table caveat when using Markdown sources.
**Keywords:** specifications, llms.txt, markdown twin, components.json, classes.json, releases.json, retrieval, WebFetch

---

Before fetching any openEHR specification document, route through this lookup order. It avoids 30k+ word HTML pages when a cheaper source answers the question.

## 1. Site-level index: `llms.txt`

`https://specifications.openehr.org/llms.txt` enumerates every release, document, and JSON endpoint in a machine-readable list. Use it to:

- resolve a user's component/doc phrase to a canonical URL,
- discover sibling docs within a component,
- confirm the current `latest` release tag before linking.

## 2. Markdown twin of any spec page

**Any** `*.html` spec URL has a `.md` counterpart that returns the same chapter as Markdown prose. Examples:

- `releases/RM/latest/ehr.html` → `releases/RM/latest/ehr.md`
- `releases/AM/latest/ADL2.html` → `releases/AM/latest/ADL2.md`

The same representation is also obtainable by sending `Accept: text/markdown` against the HTML URL.

**Caveat — do not miss this:** the Markdown representation contains prose, rationale, and examples, **but not the per-class tables** of attributes, functions, invariants, and inherited members. For those, fall through to step 3 or the HTML page.

## 3. Structured JSON APIs

- `GET /api/components.json` — every component, short description, current release.
- `GET /api/classes.json` — every class across every release, with source doc and anchor.
- `GET /api/releases.json` — release calendar and status.

Use these for class lookup, component enumeration, and release-tag resolution instead of scraping HTML.

## 4. Fall-through order

1. Is the user asking for a digest/overview? → try `guide_search(category="specs")` first; if a digest of the target doc exists, prefer it.
2. Is the user asking about a class? → `/api/classes.json` then `type_specification_get`.
3. Is the user asking about prose, rationale, or examples? → `.md` twin URL (cheapest textual source).
4. Does the answer require a class-level attribute / function / invariant table? → HTML URL (Markdown omits these).

## When this guide is wrong

If `llms.txt` or `/api/*.json` returns 4xx/5xx, or the `.md` twin 404s for a given page, report the failure and fall back to HTML. Do not silently retry without telling the user.

