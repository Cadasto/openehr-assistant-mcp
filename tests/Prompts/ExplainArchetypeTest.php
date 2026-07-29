<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainArchetype;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ExplainArchetype::class)]
final class ExplainArchetypeTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new ExplainArchetype();
        $messages = $prompt->__invoke(
            adl_text: 'archetype (adl_version=2.3.0) openEHR-EHR-OBSERVATION.blood_pressure.v2',
            audience: 'clinician',
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
        $this->assertStringContainsString('openehr://guides/archetypes/principles', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/terminology', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/structural-constraints', $combined);
        $this->assertStringContainsString('openEHR-EHR-OBSERVATION.blood_pressure.v2', $combined);
        $this->assertStringContainsString('clinician', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(ExplainArchetype::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('explain_archetype', $args['name']);
    }

    public function test_requires_adl_text(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new ExplainArchetype())->__invoke(adl_text: '');
    }
}
