<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainSimplifiedFormat;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ExplainSimplifiedFormat::class)]
final class ExplainSimplifiedFormatTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new ExplainSimplifiedFormat();
        $messages = $prompt->__invoke(
            json_payload: '{"vital_signs/blood_pressure:0/systolic|magnitude": 120}',
            template_id: 'vital_signs.v1',
            context: 'clinician-facing summary',
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
        $this->assertStringContainsString('vital_signs/blood_pressure:0/systolic|magnitude', $combined);
        $this->assertStringContainsString('vital_signs.v1', $combined);
        $this->assertStringContainsString('clinician-facing summary', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        $rc = new ReflectionClass(ExplainSimplifiedFormat::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('explain_simplified_format', $args['name']);
    }

    public function test_requires_json_payload(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new ExplainSimplifiedFormat())->__invoke(json_payload: '');
    }
}
