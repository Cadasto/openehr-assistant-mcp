<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'fix_adl_syntax')]
readonly final class FixAdlSyntax extends AbstractPrompt
{
    /**
     * Fix openEHR ADL Syntax (No Semantic Changes).
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('fix_adl_syntax');
    }
}
