<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainArchetype;
use Mcp\Capability\Attribute\McpPrompt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ExplainArchetype::class)]
final class ExplainArchetypeTest extends TestCase
{
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new ExplainArchetype();
        $messages = $prompt->__invoke();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $allowedRoles = ['system','user','assistant'];
        $combined = '';
        foreach ($messages as $msg) {
            $this->assertIsArray($msg);
            $this->assertArrayHasKey('role', $msg);
            $this->assertArrayHasKey('content', $msg);
            $this->assertContains($msg['role'], $allowedRoles);
            $this->assertIsString($msg['content']);
            $this->assertNotSame('', trim($msg['content']));
            $combined .= "\n" . $msg['content'];
        }

        // Key guideline references and placeholders
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/principles', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/terminology', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/structural-constraints', $combined);
        $this->assertStringContainsString('{{adl_text}}', $combined);
        $this->assertStringContainsString('{{audience}}', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(ExplainArchetype::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('explain_archetype', $args['name']);
    }
}
