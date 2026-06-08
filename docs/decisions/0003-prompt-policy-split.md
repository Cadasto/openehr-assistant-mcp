# ADR-0003 — Split global policy from task-specific prompt content

- **Status:** Accepted
- **Requirements:** REQ-F6 (prompts), REQ-F10 (server instructions), REQ-N7 (context economy)
- **Related:** [architecture.md](../architecture.md)

## Context

Every prompt needs the same global guidance (use the right tools, never guess,
follow Guide-First / Spec-Lookup-First / Digest-First / Examples-First). Repeating
that policy inside each of the 15 prompt bodies would duplicate text, drift out of
sync, and waste the agent's context budget every time a prompt is invoked.

## Decision

Keep **global, always-applicable policy** in `resources/server-instructions.md`
(delivered once via the MCP `instructions` field). Keep `resources/prompts/*.md`
focused on **task-specific** constraints: required output structure and
domain-specialised rules for that one prompt. Prompt classes load their body via
`AbstractPrompt::loadPromptMessages()`.

## Consequences

- **Positive:** policy is stated once; prompt bodies stay short and scannable,
  honouring the AI context-economy goal (REQ-N7).
- **Positive:** changing global policy is a one-file edit with project-wide effect.
- **Enforcement:** `PromptPolicySeparationTest` guards the split (global policy
  must not reappear in individual prompt files); `PromptCompositionTest` guards
  prompt size against the baselines in
  `tests/fixtures/prompt_lengths_before_shared.json`.
- **Negative:** contributors must know *where* a given instruction belongs;
  the rule of thumb (global → server-instructions, task-specific → prompt file)
  is documented in [AGENTS.md](../../AGENTS.md) and the authoring guidance.
