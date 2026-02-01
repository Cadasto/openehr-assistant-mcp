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
        $this->tempPromptsDir = APP_DIR . '/tests/temp_prompts';
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

    public function testLoadValidPrompt(): void
    {
        $yamlContent = <<<YAML
messages:
  - role: assistant
    content: "You are a helpful assistant."
  - role: user
    content: "Hello!"
YAML;
        file_put_contents($this->tempPromptsDir . '/test_prompt.yaml', $yamlContent);

        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $messages = $promptInstance->testLoad('test_prompt');

        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        $this->assertInstanceOf(PromptMessage::class, $messages[0]);
        $this->assertEquals(Role::Assistant, $messages[0]->role);
        $this->assertInstanceOf(TextContent::class, $messages[0]->content);
        $this->assertEquals('You are a helpful assistant.', $messages[0]->content->text);

        $this->assertEquals(Role::User, $messages[1]->role);
        $this->assertEquals('Hello!', $messages[1]->content->text);
    }

    public function testLoadThrowsOnMissingFile(): void
    {
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt file not found');
        $promptInstance->testLoad('non_existent');
    }

    public function testLoadThrowsOnInvalidYaml(): void
    {
        file_put_contents($this->tempPromptsDir . '/invalid.yaml', "not: an: array: [");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $this->expectException(\Exception::class);
        $promptInstance->testLoad('invalid');
    }

    public function testLoadThrowsOnMissingMessages(): void
    {
        file_put_contents($this->tempPromptsDir . '/no_messages.yaml', "foo: bar");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt file missing messages');
        $promptInstance->testLoad('no_messages');
    }

    public function testLoadThrowsOnInvalidMessageFormat(): void
    {
        $yamlContent = <<<YAML
messages:
  - role: system
    # missing content
YAML;
        file_put_contents($this->tempPromptsDir . '/invalid_message.yaml', $yamlContent);
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid message format in prompt file');
        $promptInstance->testLoad('invalid_message');
    }
}
