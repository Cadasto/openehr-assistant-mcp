<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\ExplainAql;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ExplainAql::class)]
final class ExplainAqlTest extends TestCase
{
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new ExplainAql();
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

        $this->assertStringContainsString('openehr://guides/aql/principles', $combined);
        $this->assertStringContainsString('openehr://guides/aql/syntax', $combined);
        $this->assertStringContainsString('archetype path', $combined);
        $this->assertStringContainsString('{{aql_query}}', $combined);
        $this->assertStringContainsString('{{context}}', $combined);

        $rc = new ReflectionClass(ExplainAql::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('explain_aql', $args['name']);
    }
}
