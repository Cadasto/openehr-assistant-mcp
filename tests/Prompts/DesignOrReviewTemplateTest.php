<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewTemplate;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DesignOrReviewTemplate::class)]
final class DesignOrReviewTemplateTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new DesignOrReviewTemplate();
        $messages = $prompt->__invoke(
            task_type: 'design',
            concept: 'Vital Signs encounter',
            clinical_context: 'Outpatient primary care',
            root_archetype: 'openEHR-EHR-COMPOSITION.encounter.v1',
            included_archetypes: 'openEHR-EHR-OBSERVATION.blood_pressure.v2',
            existing_template: '',
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
        $this->assertStringContainsString('openehr://guides/templates/principles', $combined);
        $this->assertStringContainsString('openehr://guides/templates/rules', $combined);
        $this->assertStringContainsString('openehr://guides/templates/oet-syntax', $combined);
        $this->assertStringContainsString('openehr://guides/templates/oet-idioms-cheatsheet', $combined);
        $this->assertStringContainsString('openehr://guides/templates/checklist', $combined);

        $this->assertStringContainsString('design', $combined);
        $this->assertStringContainsString('Vital Signs encounter', $combined);
        $this->assertStringContainsString('Outpatient primary care', $combined);
        $this->assertStringContainsString('openEHR-EHR-COMPOSITION.encounter.v1', $combined);
        $this->assertStringContainsString('openEHR-EHR-OBSERVATION.blood_pressure.v2', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(DesignOrReviewTemplate::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_template', $args['name']);
    }

    public function test_requires_task_type_concept_clinical_context_and_root_archetype(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new DesignOrReviewTemplate())->__invoke(
            task_type: 'design',
            concept: 'Vital Signs encounter',
            clinical_context: 'Outpatient primary care',
            root_archetype: '',
        );
    }
}
