<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'translate_archetype_language')]
readonly final class TranslateArchetypeLanguage extends AbstractPrompt
{
    /**
     * Translate openEHR Archetype (Terminology Only) to a new language.
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('translate_archetype_language');
    }
}
