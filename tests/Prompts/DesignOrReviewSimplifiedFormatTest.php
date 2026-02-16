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
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new DesignOrReviewSimplifiedFormat();
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

        $this->assertStringContainsString('openehr://guides/simplified_formats/principles', $combined);
        $this->assertStringContainsString('openehr://guides/simplified_formats/rules', $combined);
        $this->assertStringContainsString('{{task_type}}', $combined);
        $this->assertStringContainsString('{{template_id}}', $combined);
        $this->assertStringContainsString('{{format_variant}}', $combined);
        $this->assertStringContainsString('{{existing_json}}', $combined);

        $rc = new ReflectionClass(DesignOrReviewSimplifiedFormat::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_simplified_format', $args['name']);
    }
}
