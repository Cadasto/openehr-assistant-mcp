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
     * @param mixed $adl_text Archetype (ADL)
     * @param mixed $source_language_code Source language code
     * @param mixed $target_language_code Target language code
     * @param mixed $translation_intent add-new-language | improve-existing-translation | correct-terminology-phrasing
     * @return PromptMessage[]
     */
    public function __invoke(
        mixed $adl_text,
        mixed $source_language_code,
        mixed $target_language_code,
        mixed $translation_intent,
    ): array {
        return $this->loadPromptMessages('translate_archetype_language', [
            'adl_text' => $adl_text,
            'source_language_code' => $source_language_code,
            'target_language_code' => $target_language_code,
            'translation_intent' => $translation_intent,
        ], ['adl_text', 'source_language_code', 'target_language_code', 'translation_intent']);
    }
}
