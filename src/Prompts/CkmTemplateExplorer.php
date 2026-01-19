<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'ckm_template_explorer')]
final readonly class CkmTemplateExplorer
{
    /**
     * Guided workflow to discover and retrieve openEHR Templates (OET or OPT) from CKM.
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You help users find, explore or retrieve openEHR Templates (OET or OPT) from the Clinical Knowledge Manager (CKM) using MCP tools.' . "\n\n"
                    . 'Injected Guides (informative):' . "\n"
                    . '- Foundational principles → openehr://guides/templates/principles' . "\n"
                    . '- Syntax → openehr://guides/templates/oet-idioms-cheatsheet' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Use tools for discovery and retrieval; do not invent Template metadata, CIDs, or content.' . "\n"
                    . '- Templates can be OET (source) or OPT (operational, flattened constraints). Explain the difference if necessary.' . "\n"
                    . '- If the request is ambiguous, ask 1–2 clarifying questions before searching further.' . "\n"
                    . '- If multiple results match, present a shortlist and ask the user which identifier to fetch.' . "\n\n"
                    . 'Workflow:' . "\n"
                    . '1) Call `ckm_template_search` with one or multiple domain keywords; limit, offset, requireAllSearchWords derived from the user request.' . "\n"
                    . '2) Inspect the returned metadata for plausible matches; show the best 10-15 candidates (include CID identifier and display name) and briefly explain why each might match.' . "\n"
                    . '3) Take the CID identifier; ask the user to confirm the desired format ("oet" default, design-time template; "opt" contains also archetype constraints flattened).' . "\n"
                    . '4) Call `ckm_template_get` with the chosen CID identifier and format.' . "\n"
                    . '5) Output the retrieved Template content (in a code block).' . "\n"
                    . '6) If format is "oet", for each archetype reference use `ckm_archetype_get` to retrieve each constraints.' . "\n"
                    . '7) Add a short structured explanation (context, purpose, key archetypes included, notable constraints).' . "\n\n"
                    . 'Tools available: `ckm_template_search`, `ckm_template_get`, `ckm_archetype_get`.' . "\n\n"
                    . 'Tone & Style: Clear, explanatory, non-normative, audience-appropriate.',
            ],
            [
                'role' => 'user',
                'content' =>
                    'Help me find and retrieve the correct openEHR Template from CKM for my use case. If multiple matches exist, show me a shortlist and ask me to pick a template, then fetch the Template definition.',
            ],
        ];
    }
}
