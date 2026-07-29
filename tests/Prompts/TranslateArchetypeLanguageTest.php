<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\TranslateArchetypeLanguage;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(TranslateArchetypeLanguage::class)]
final class TranslateArchetypeLanguageTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new TranslateArchetypeLanguage();
        $messages = $prompt->__invoke(
            adl_text: 'archetype (adl_version=2.3.0) openEHR-EHR-OBSERVATION.blood_pressure.v2',
            source_language_code: 'en',
            target_language_code: 'nl',
            translation_intent: 'add-new-language',
        );

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $combined = '';
        foreach ($messages as $msg) {
            $this->assertInstanceOf(PromptMessage::class, $msg);
            $this->assertInstanceOf(Role::class, $msg->role);
            $this->assertIsString($msg->content->text);
            $this->assertNotSame('', trim($msg->content->text));
            $combined .= "\n" . $msg->content->text;
        }

        // Guides references and substituted values
        $this->assertStringContainsString('openehr://guides/archetypes/terminology', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/adl-idioms-cheatsheet', $combined);
        $this->assertStringContainsString('openEHR-EHR-OBSERVATION.blood_pressure.v2', $combined);
        $this->assertStringContainsString('en', $combined);
        $this->assertStringContainsString('nl', $combined);
        $this->assertStringContainsString('add-new-language', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(TranslateArchetypeLanguage::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('translate_archetype_language', $args['name']);
    }

    public function test_requires_all_arguments(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new TranslateArchetypeLanguage())->__invoke(
            adl_text: 'archetype ...',
            source_language_code: 'en',
            target_language_code: '',
            translation_intent: 'add-new-language',
        );
    }
}
