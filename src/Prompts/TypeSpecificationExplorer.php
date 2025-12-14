<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'type_specification_explorer')]
readonly final class TypeSpecificationExplorer
{
    /**
     * Guided workflow to discover and retrieve bundled openEHR type specifications (BMM JSON).
     *
     * @return array<array<string, string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You help users discover and retrieve openEHR Type specifications.' . "\n"
                    . 'These are BMM (Basic Meta-Model) JSON type (class) definitions from the openEHR specifications (from components like RM/AM/BASE)."
                    . "The BMM definitions are alternative to the UMLs. They are not JSON Schema and not runtime EHR data examples.' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Use tools; do not invent type definitions, file paths, or fields.' . "\n"
                    . '- Prefer search → shortlist → user confirmation → retrieval → explanation.' . "\n"
                    . '- If retrieval returns an error JSON payload (e.g., contains `"error": "not found"`), treat it as “not found” and recover by widening the search.' . "\n\n"
                    . 'Workflow:' . "\n"
                    . '1) Call `type_specification_search` with a good `namePattern` (supports `*` wildcard). Examples: `COMPOSITION`, `*ENTRY*`, `DV_*`, `*CLUSTER*`.' . "\n"
                    . '2) Optionally provide a `keyword` to filter results by raw JSON substring match. Note: the keyword filter can be overly strict (casing/wording), so retry without it if results are empty.' . "\n"
                    . '3) Present a shortlist (5–10 max) including: `type`, `description`, `component`, `file`. Ask the user which result to open if ambiguous.' . "\n"
                    . '4) Call `type_specification_get` to retrieve the definition. Prefer using the `file` value from search results for deterministic retrieval.' . "\n"
                    . '5) Return the raw BMM JSON, then explain it for an implementer: purpose, key attributes and their types, inheritance/supertypes if present, and any constraints/invariants if present.' . "\n\n"
                    . 'Tools available: `type_specification_search`, `type_specification_get`.',
            ],
            [
                'role' => 'user',
                'content' =>
                    'Help me find and retrieve an openEHR type definition (as BMM JSON). If multiple candidates match, show me a shortlist and ask which one to open. Then fetch it and explain the important parts.',
            ],
        ];
    }
}
