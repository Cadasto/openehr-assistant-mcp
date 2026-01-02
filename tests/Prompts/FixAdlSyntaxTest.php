<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\FixAdlSyntax;
use Mcp\Capability\Attribute\McpPrompt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(FixAdlSyntax::class)]
final class FixAdlSyntaxTest extends TestCase
{
    public function test_prompt_structure_placeholders_and_attribute(): void
    {
        $prompt = new FixAdlSyntax();
        $messages = $prompt->__invoke();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $allowedRoles = ['user', 'assistant'];
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

        // Guides references and placeholders
        $this->assertStringContainsString('openehr://guides/archetypes/adl-syntax', $combined);
        $this->assertStringContainsString('openehr://guides/archetypes/adl-idioms-cheatsheet', $combined);
        $this->assertStringContainsString('{{adl_text}}', $combined);
        $this->assertStringContainsString('{{adl_version}}', $combined);

        // Attribute presence and expected name
        $rc = new ReflectionClass(FixAdlSyntax::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('fix_adl_syntax', $args['name']);
    }
}
