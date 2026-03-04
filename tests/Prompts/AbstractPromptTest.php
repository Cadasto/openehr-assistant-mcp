<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\AbstractPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractPrompt::class)]
final class AbstractPromptTest extends TestCase
{
    private string $tempPromptsDir;

    protected function setUp(): void
    {
        $this->tempPromptsDir = '/tmp/temp_prompts';
        if (!is_dir($this->tempPromptsDir)) {
            mkdir($this->tempPromptsDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempPromptsDir)) {
            $files = glob($this->tempPromptsDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempPromptsDir);
        }
    }

    private function getMockPrompt(string $promptsDir): AbstractPrompt
    {
        return new readonly class($promptsDir) extends AbstractPrompt {
            public function __construct(private string $promptsDir)
            {
            }

            protected function getPromptsDir(): string
            {
                return $this->promptsDir;
            }

            /** @return PromptMessage[] */
            public function testLoad(string $name): array
            {
                return $this->loadPromptMessages($name);
            }
        };
    }

    public function testLoadValidPromptWithThreeMessages(): void
    {
        $mdContent = <<<MD
## Role: user

You are an expert. Do this task.

### Tools

- `tool_a` - does something

### Workflow

1. Step one.

## Role: assistant

Understood. I will follow the workflow.

## Role: user

Perform the task.

Input:
{{input_value}}
MD;
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', $mdContent);

        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $messages = $promptInstance->testLoad('test_prompt');

        $this->assertIsArray($messages);
        $this->assertCount(3, $messages);

        // First message: user role with full instructions
        $this->assertInstanceOf(PromptMessage::class, $messages[0]);
        $this->assertEquals(Role::User, $messages[0]->role);
        $this->assertInstanceOf(TextContent::class, $messages[0]->content);
        $this->assertStringContainsString('You are an expert', $messages[0]->content->text);
        $this->assertStringContainsString('### Tools', $messages[0]->content->text);
        $this->assertStringContainsString('### Workflow', $messages[0]->content->text);

        // Second message: assistant acknowledgment
        $this->assertEquals(Role::Assistant, $messages[1]->role);
        $this->assertStringContainsString('Understood', $messages[1]->content->text);

        // Third message: user request with placeholders
        $this->assertEquals(Role::User, $messages[2]->role);
        $this->assertStringContainsString('{{input_value}}', $messages[2]->content->text);
    }

    public function testLoadThrowsOnMissingFile(): void
    {
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt file not found');
        $promptInstance->testLoad('non_existent');
    }

    public function testLoadThrowsOnInvalidFormat(): void
    {
        file_put_contents($this->tempPromptsDir . '/invalid.md', "just some text without roles");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prompt file format');
        $promptInstance->testLoad('invalid');
    }


}
