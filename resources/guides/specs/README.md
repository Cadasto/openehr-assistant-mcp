# Spec Digests

Short structured digests (250–900 words) of openEHR specification documents.
One file per upstream spec document. Primes LLM context without fetching the
30k+ word full-HTML chapter.

## Filename convention

`<component>-<doc>.md`, lowercase, using the upstream doc ID.
Examples: `rm-ehr.md`, `rm-data_types.md`, `am-adl2.md`, `sm-openehr_platform.md`.

## When to use

- Priming a conversation with "what does the EHR IM define?"
- Cross-spec navigation ("how does AM relate to RM?")
- Onboarding prompts, glossaries, routing logic.

## When NOT to use

- Normative implementation work — always read the full `.md` or HTML (see the
  `howto/spec-lookup` guide for the retrieval order).
- Class-level detail (attributes, invariants) — use `type_specification_get`.

## Authoring rules

See `_template.md` for the exact structure.

- 250–900 words body (header block excluded).
- Every named class must exist in the upstream spec (no invention).
- All URLs must resolve — verify with `curl -sI` before commit. When the
  upstream doc has no `.md` twin (e.g. OpenAPI-rendered ITS-REST endpoints
  whose source is YAML rather than prose), set `**Markdown URL:** N/A` and
  let the Spec URL carry the HTML reference. The validator recognises the
  `N/A` sentinel and skips the pattern check.
- Keep the **Key Classes / Constructs** section intentionally terse — list
  5–8 top constructs with half-line roles. Per-class attribute / function /
  invariant detail belongs in `type_specification_get` (BMM-backed), not
  here. Cite that tool explicitly when recommending deeper lookups.
- Update `**Last updated:**` on any edit.
- Default to `**Release:** development` with `/releases/<COMPONENT>/development/`
  URLs so digests track the living spec rather than a year-old snapshot.
  Fork to a `-release-X.Y.Z.md` sibling if you need a point-in-time pinned
  digest for a specific openEHR release.
- Use the repo's `**Field:**` header convention; do not add YAML frontmatter.
