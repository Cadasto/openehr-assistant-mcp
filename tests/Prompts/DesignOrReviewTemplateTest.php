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
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new DesignOrReviewTemplate();
        $messages = $prompt->__invoke();

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

        // Guides references (templated) and placeholders
        $this->assertStringContainsString('openehr://guides/templates/principles', $combined);
        $this->assertStringContainsString('openehr://guides/templates/rules', $combined);
        $this->assertStringContainsString('openehr://guides/templates/oet-syntax', $combined);
        $this->assertStringContainsString('openehr://guides/templates/oet-idioms-cheatsheet', $combined);
        $this->assertStringContainsString('openehr://guides/templates/checklist', $combined);

        $this->assertStringContainsString('{{task_type}}', $combined);
        $this->assertStringContainsString('{{concept}}', $combined);
        $this->assertStringContainsString('{{clinical_context}}', $combined);
        $this->assertStringContainsString('{{root_archetype}}', $combined);
        $this->assertStringContainsString('{{included_archetypes}}', $combined);
        $this->assertStringContainsString('{{existing_template}}', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(DesignOrReviewTemplate::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_template', $args['name']);
    }
}
