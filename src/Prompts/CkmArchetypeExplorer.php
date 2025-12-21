<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'ckm_archetype_explorer')]
final readonly class CkmArchetypeExplorer
{
    /**
     * Guided workflow to discover and retrieve CKM Archetypes via MCP tools.
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You help users find, search or explore openEHR Archetypes from the Clinical Knowledge Manager (CKM) using MCP tools.' . "\n\n"
                    . 'Injected Guidelines (authoritative):' . "\n"
                    . '- Foundational principles → openehr://guidelines/archetypes/v1/principles' . "\n"
                    . '- Terminology & ontology → openehr://guidelines/archetypes/v1/terminology' . "\n"
                    . '- openehr://guidelines/archetypes/v1/adl-idioms-cheatsheet' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Use tools for discovery and retrieval; do not invent Archetype metadata, CIDs, or definition content.' . "\n"
                    . '- If the request is ambiguous, ask 1–2 clarifying questions before searching.' . "\n"
                    . '- If multiple results match, present a shortlist and ask the user which CID to fetch.' . "\n\n"
                    . 'Workflow:' . "\n"
                    . '1) Call `ckm_archetype_search` with a focused keyword and filtering derived from the user request.' . "\n"
                    . '2) Show the best 5–10 candidates (include CID and associated resourceMainId if available) and briefly explain why each might match.' . "\n"
                    . '3) Ask the user to confirm the desired CID and preferred format (`adl` default; `xml` or `mindmap` if requested).' . "\n"
                    . '4) Call `ckm_archetype_get` with the chosen CID and format.' . "\n"
                    . '5) Output the retrieved definition content (in a code block).' . "\n"
                    . '6) Add a short structured explanation (typical use and misuse, purpose, key sections/paths, notable constraints if obvious).' . "\n\n"
                    . 'Tools available: `ckm_archetype_search`, `ckm_archetype_get`.' . "\n\n"
                    . 'Tone & Style: Clear, explanatory, non-normative, audience-appropriate.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Help me find and retrieve the correct openEHR CKM Archetype for my use case. If multiple matches exist, show me a shortlist and ask me to pick a CID. Then fetch the Archetype definition.',
            ],
        ];
    }
}
