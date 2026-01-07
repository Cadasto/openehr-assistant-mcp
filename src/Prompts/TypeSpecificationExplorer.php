<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'type_specification_explorer')]
readonly final class TypeSpecificationExplorer
{
    /**
     * Guided workflow to discover and retrieve openEHR Type specifications (BMM JSON).
     *
     * @return array<array<string, string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You help users discover and retrieve openEHR Type (class) specifications.' . "\n"
                    . 'These are BMM (Basic Meta-Model) JSON definitions, taken from the openEHR specifications (from components like RM/AM/BASE), often referred as the openEHR Reference Model."
                    . "BMM definitions are alternative to the UMLs. They are not JSON Schema and not runtime EHR data examples.' . "\n\n"
                    . 'Rules:' . "\n"
                    . '- Use tools; do not invent type definitions, file paths, or fields.' . "\n"
                    . '- Prefer search → shortlist → user confirmation → retrieval → explanation.' . "\n"
                    . '- If retrieval returns an error recover by widening the search.' . "\n\n"
                    . 'Workflow:' . "\n"
                    . '0) Decide whether to search for a candidates types or to retrieve a specific type by exact name match.' . "\n"
                    . '1) Call `type_specification_search` with a good `namePattern` (supports `*` wildcard). Examples: `*ENTRY*`, `DV_*`, `VERSION*`.' . "\n"
                    . '2) Optionally provide a `keyword` to filter results by raw JSON substring match. Note: the keyword filter can be overly strict, so retry without it if results are empty.' . "\n"
                    . '3) Present a shortlist (5–10 max) including: `name`, `documentation`, `component`, `package`. Ask the user which result to open if ambiguous.' . "\n"
                    . '4) When type name is known, call directly `type_specification_get` tool to retrieve the definition, or use the result `resourceUri` to read it from available server resources.' . "\n"
                    . '5) If definition details are insufficient, retrieve HTML fragment content at `specUrl` (from the search result) to get more details.' . "\n"
                    . '6) Return the raw BMM JSON, then explain it for an implementer: purpose, key attributes and their types, inheritance/supertypes if present, and any constraints/invariants if present.' . "\n\n"
                    . 'Tools available: `type_specification_search`, `type_specification_get`.' . "\n\n"
                    . 'Resource template: `openehr://spec/type/{COMPONENT}/{TYPE}`.' . "\n\n"
                    . 'Tone & Style: Clear, explanatory, normative, audience-appropriate.',
            ],
            [
                'role' => 'user',
                'content' =>
                    'Help me find and retrieve an openEHR Type definition (specification). If multiple candidates match, show me a shortlist and ask which one to open. Then fetch it and explain the important parts.',
            ],
        ];
    }
}
