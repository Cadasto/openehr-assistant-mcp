<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'ckm_archetype_explorer')]
final readonly class CkmArchetypeExplorer extends AbstractPrompt
{
    /**
     * Guided workflow to discover and retrieve Archetypes from CKM.
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('ckm_archetype_explorer');
    }
}
