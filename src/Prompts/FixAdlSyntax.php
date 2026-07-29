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
     * @param mixed $adl_text Archetype (ADL, unmodified)
     * @param mixed $adl_version Target ADL version: 1.4 or 2
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $adl_text,
        mixed $adl_version,
    ): array {
        return $this->loadPromptMessages('fix_adl_syntax', [
            'adl_text' => $adl_text,
            'adl_version' => $adl_version,
        ], ['adl_text', 'adl_version']);
    }
}
