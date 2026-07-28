<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\DesignOrReviewAql;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DesignOrReviewAql::class)]
final class DesignOrReviewAqlTest extends TestCase
{
    public function test_prompt_structure_substitution_and_attribute(): void
    {
        $prompt = new DesignOrReviewAql();
        $messages = $prompt->__invoke(
            task_type: 'review-existing',
            query_intent: 'latest BP per EHR',
            template_or_archetypes: 'vital_signs',
            existing_aql: 'SELECT c FROM EHR e CONTAINS COMPOSITION c',
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

        $this->assertStringContainsString('openehr://guides/aql/principles', $combined);
        $this->assertStringContainsString('openehr://guides/aql/syntax', $combined);
        $this->assertStringContainsString('openehr://guides/aql/idioms-cheatsheet', $combined);
        $this->assertStringContainsString('openehr://guides/aql/checklist', $combined);

        $this->assertStringContainsString('review-existing', $combined);
        $this->assertStringContainsString('latest BP per EHR', $combined);
        $this->assertStringContainsString('vital_signs', $combined);
        $this->assertStringContainsString('SELECT c FROM EHR e CONTAINS COMPOSITION c', $combined);
        $this->assertDoesNotMatchRegularExpression('/\{\{[a-z0-9_]+\}\}/', $combined);

        $rc = new ReflectionClass(DesignOrReviewAql::class);
        $attrs = $rc->getAttributes(McpPrompt::class);
        $this->assertNotEmpty($attrs, 'McpPrompt attribute missing');
        $args = $attrs[0]->getArguments();
        $this->assertArrayHasKey('name', $args);
        $this->assertSame('design_or_review_aql', $args['name']);
    }

    public function test_requires_task_type_and_query_intent(): void
    {
        $this->expectException(\Mcp\Exception\PromptGetException::class);
        (new DesignOrReviewAql())->__invoke(task_type: '', query_intent: 'latest BP per EHR');
    }
}
