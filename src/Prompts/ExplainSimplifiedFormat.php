<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'explain_simplified_format')]
readonly final class ExplainSimplifiedFormat extends AbstractPrompt
{
    /**
     * Explain a Flat or Structured (simplified) format instance using the Simplified Formats guides.
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('explain_simplified_format');
    }
}
