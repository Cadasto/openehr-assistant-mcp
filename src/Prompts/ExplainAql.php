<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'explain_aql')]
readonly final class ExplainAql extends AbstractPrompt
{
    /**
     * Explain the intent, structure, and semantics of an AQL query using AQL guides.
     *
     * @param mixed $aql_query AQL query to explain
     * @param mixed $context Target system, template names, audience (optional)
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $aql_query,
        mixed $context = '',
    ): array {
        return $this->loadPromptMessages('explain_aql', [
            'aql_query' => $aql_query,
            'context' => $context,
        ], ['aql_query']);
    }
}
