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
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('explain_aql');
    }
}
