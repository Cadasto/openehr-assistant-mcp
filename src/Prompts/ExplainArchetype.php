<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'explain_archetype')]
readonly final class ExplainArchetype extends AbstractPrompt
{
    /**
     * Explain and interpret the semantic meaning of an openEHR Archetype, grounded in the bundled guides.
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('explain_archetype');
    }
}
