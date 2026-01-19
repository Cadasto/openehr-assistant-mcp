<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'ckm_archetype_explorer')]
final readonly class CkmArchetypeExplorer
{
    /**
     * Guided workflow to discover and retrieve Archetypes from CKM.
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You help users find, explore or retrieve openEHR Archetypes from the Clinical Knowledge Manager (CKM) using MCP tools.' . "\n\n"
                    . 'Injected Guides (informative):' . "\n"
                    . '- Foundational principles → openehr://guides/archetypes/principles' . "\n"
                    . '- Terminology & ontology → openehr://guides/archetypes/terminology' . "\n"
                    . '- Syntax → openehr://guides/archetypes/adl-idioms-cheatsheet' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Use tools for discovery and retrieval; do not invent Archetype metadata, CIDs, or definition content.' . "\n"
                    . '- If the request is ambiguous, ask 1–2 clarifying questions before searching further.' . "\n"
                    . '- If multiple results match, present a shortlist and ask the user which Archetype to fetch.' . "\n\n"
                    . 'Workflow:' . "\n"
                    . '0) If `archetypes-id` is already known, go to step 4) directly.' . "\n"
                    . '1) Call `ckm_archetype_search` with one or multiple domain keywords; limit, offset, requireAllSearchWords derived from the user request.' . "\n"
                    . '2) Inspect the returned metadata for plausible matches; show the best 10-15 candidates (include CID and associated archetypeId) and briefly explain why each might match.' . "\n"
                    . '3) Take the CID identifier; ask the user the desired format (`adl` default; `xml` or `mindmap` if requested).' . "\n"
                    . '4) Call `ckm_archetype_get` with the chosen CID (or `archetypes-id`) and format.' . "\n"
                    . '5) Output the retrieved Archetype content (in a code block).' . "\n"
                    . '6) Add a short structured explanation (typical use and misuse, purpose, key sections/paths, notable constraints if obvious).' . "\n\n"
                    . 'Tools available: `ckm_archetype_search`, `ckm_archetype_get`.' . "\n\n"
                    . 'Tone & Style: Clear, explanatory, non-normative, audience-appropriate.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Help me find and retrieve the correct openEHR Archetype from CKM for my use case. If multiple matches exist, show me a shortlist and ask me to pick a CID or archetype-id, then fetch the Archetype definition.',
            ],
        ];
    }
}
