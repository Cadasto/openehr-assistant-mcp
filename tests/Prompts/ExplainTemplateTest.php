<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainTemplate;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ExplainTemplate::class)]
final class ExplainTemplateTest extends TestCase
{
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new ExplainTemplate();
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

        // Guides references and placeholders
        $this->assertStringContainsString('openehr://guides/templates/principles', $combined);
        $this->assertStringContainsString('openehr://guides/templates/rules', $combined);
        $this->assertStringContainsString('openehr://guides/templates/oet-syntax', $combined);
        $this->assertStringContainsString('{{template_text}}', $combined);
        $this->assertStringContainsString('{{audience}}', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(ExplainTemplate::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('explain_template', $args['name']);
    }
}
