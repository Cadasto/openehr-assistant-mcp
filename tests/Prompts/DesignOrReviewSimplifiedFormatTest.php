<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewSimplifiedFormat;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DesignOrReviewSimplifiedFormat::class)]
final class DesignOrReviewSimplifiedFormatTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new DesignOrReviewSimplifiedFormat();
        $messages = $prompt->__invoke(
            task_type: 'review',
            template_id: 'vital_signs.v1',
            format_variant: 'flat',
            existing_json: '{"vital_signs/blood_pressure:0/systolic|magnitude": 120}',
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

        $this->assertStringContainsString('openehr://guides/simplified_formats/principles', $combined);
        $this->assertStringContainsString('openehr://guides/simplified_formats/rules', $combined);
        $this->assertStringContainsString('review', $combined);
        $this->assertStringContainsString('vital_signs.v1', $combined);
        $this->assertStringContainsString('flat', $combined);
        $this->assertStringContainsString('vital_signs/blood_pressure:0/systolic|magnitude', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        $rc = new ReflectionClass(DesignOrReviewSimplifiedFormat::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_simplified_format', $args['name']);
    }

    public function test_requires_task_type_template_id_and_format_variant(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new DesignOrReviewSimplifiedFormat())->__invoke(
            task_type: 'review',
            template_id: 'vital_signs.v1',
            format_variant: '',
        );
    }
}
