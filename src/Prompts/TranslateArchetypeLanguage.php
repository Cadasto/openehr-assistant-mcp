<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;

#[McpPrompt(name: 'translate_archetype_language')]
readonly final class TranslateArchetypeLanguage extends AbstractPrompt
{
    /**
     * Translate an openEHR archetype to a new language or add a language.
     *
     * Use when the user says: "translate this archetype to X", "add X language", "localize archetype", "add Hungarian".
     * Terminology only; calls guide_get/guide_search for language-standards and terminology before translating.
     *
     * @return PromptMessage[]
     */
    public function __invoke(): array
    {
        return $this->loadPromptMessages('translate_archetype_language');
    }
}
