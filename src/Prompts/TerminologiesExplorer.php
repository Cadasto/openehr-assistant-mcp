<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'terminology_explorer')]
readonly final class TerminologiesExplorer extends AbstractPrompt
{
    /**
     * Guided workflow to discover and retrieve openEHR Terminology codes, groups, and codesets.
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('terminology_explorer');
    }
}
