<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewArchetype;
use Mcp\Capability\Attribute\McpPrompt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DesignOrReviewArchetype::class)]
final class DesignOrReviewArchetypeTest extends TestCase
{
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new DesignOrReviewArchetype();
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

        // Guideline references (templated) and placeholders
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/principles', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/rules', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/terminology', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/structural-constraints', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/anti-patterns', $combined);
        $this->assertStringContainsString('openehr://guidelines/archetypes/v1/checklist', $combined);

        $this->assertStringContainsString('{{task_type}}', $combined);
        $this->assertStringContainsString('{{concept}}', $combined);
        $this->assertStringContainsString('{{rm_type}}', $combined);
        $this->assertStringContainsString('{{clinical_context}}', $combined);
        $this->assertStringContainsString('{{existing_archetype}}', $combined);
        $this->assertStringContainsString('{{parent_archetype}}', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(DesignOrReviewArchetype::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_archetype', $args['name']);
    }
}
