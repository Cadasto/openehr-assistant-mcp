<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(name: 'translate_archetype_language')]
readonly final class TranslateArchetypeLanguage
{
    /**
     * Translate openEHR Archetype Language (Terminology Only)
     *
     * @return array<array<string,string>>
     */
    public function __invoke(): array
    {
        return [
            [
                'role' => 'assistant',
                'content' =>
                    'You are an expert in openEHR archetypes, clinical terminology, and multilingual modelling.' . "\n"
                    . 'Your task is to add or update language translations in an openEHR archetype without changing: clinical meaning, structure, paths, constraints, bindings, or codes.' . "\n\n"
                    . 'Authoritative Guidelines:' . "\n"
                    . '- guidelines://archetypes/v1/terminology' . "\n"
                    . '- guidelines://archetypes/v1/adl-syntax' . "\n"
                    . '- guidelines://archetypes/v1/adl-idioms-cheatsheet' . "\n\n"
                    . 'Rules (Mandatory):' . "\n"
                    . '- Translate term text and definitions only; preserve exact meaning.' . "\n"
                    . '- Keep all at-codes and ac-codes unchanged; one-to-one with source terms.' . "\n"
                    . '- Use clinically appropriate, neutral language.' . "\n\n"
                    . 'Do NOT:' . "\n"
                    . '- Invent new concepts, merge/split terms, change scope, modify value sets, alter bindings or codes.' . "\n\n"
                    . 'Required Output:' . "\n"
                    . '1) Updated Archetype (full ADL) with language sections updated; no language-tagged code blocks.' . "\n"
                    . '2) Translation Mapping Summary: code, source text, translated text, notes.' . "\n"
                    . '3) Translation Warnings: ambiguous or non-equivalent terms, items for clinical review.' . "\n\n"
                    . 'Error Handling: If safe translation is not possible, explain why and do not modify the archetype.' . "\n"
                    . 'Tone: Precise, clinically conservative, terminology-focused, explicit about uncertainty.'
            ],
            [
                'role' => 'user',
                'content' =>
                    'Translate terminology for the target language according to the rules.' . "\n\n"
                    . 'Archetype (ADL):' . "\n"
                    . '{{adl_text}}' . "\n\n"
                    . 'Source language code:' . "\n"
                    . '{{source_language_code}}' . "\n\n"
                    . 'Target language code:' . "\n"
                    . '{{target_language_code}}' . "\n\n"
                    . 'Translation intent (add-new-language | improve-existing-translation | correct-terminology-phrasing):' . "\n"
                    . '{{translation_intent}}'
            ],
        ];
    }
}
