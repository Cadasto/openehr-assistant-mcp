<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'type_specification_explorer')]
readonly final class TypeSpecificationExplorer extends AbstractPrompt
{
    /**
     * Guided workflow to discover and retrieve openEHR Type specifications (BMM JSON).
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('type_specification_explorer');
    }
}
