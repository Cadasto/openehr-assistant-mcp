<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewArchetype;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DesignOrReviewArchetype::class)]
final class DesignOrReviewArchetypeTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new DesignOrReviewArchetype();
        $messages = $prompt->__invoke(
            task_type: 'design',
            concept: 'Blood Pressure',
            rm_type: 'OBSERVATION',
            clinical_context: 'Primary care vitals',
            existing_archetype: 'openEHR-EHR-OBSERVATION.blood_pressure.v2',
            parent_archetype: '',
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

        // Guides references (templated) and substituted values
        $this->assertStringContainsString('openehr://guides/archetypes/principles', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/rules', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/terminology', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/structural-constraints', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/anti-patterns', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/checklist', $combined);

        $this->assertStringContainsString('design', $combined);
        $this->assertStringContainsString('Blood Pressure', $combined);
        $this->assertStringContainsString('OBSERVATION', $combined);
        $this->assertStringContainsString('Primary care vitals', $combined);
        $this->assertStringContainsString('openEHR-EHR-OBSERVATION.blood_pressure.v2', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(DesignOrReviewArchetype::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_archetype', $args['name']);
    }

    public function test_requires_task_type_concept_rm_type_and_clinical_context(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new DesignOrReviewArchetype())->__invoke(
            task_type: 'design',
            concept: 'Blood Pressure',
            rm_type: '',
            clinical_context: 'Primary care vitals',
        );
    }
}
