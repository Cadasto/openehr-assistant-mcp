<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\AbstractPrompt;
use InvalidArgumentException;
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
        @mkdir($this->tempPromptsDir . '/shared', 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tempPromptsDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempPromptsDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir((string) $item);
                continue;
            }

            unlink((string) $item);
        }

        rmdir($this->tempPromptsDir);
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

    public function testLoadValidPromptComposesSharedAndTaskSpecificBlocks(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\nTask instruction.\n\n## Role: user\n\nHello!");

        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);
        $messages = $promptInstance->testLoad('test_prompt');

        $this->assertCount(3, $messages);
        $this->assertEquals(Role::User, $messages[0]->role);
        $this->assertEquals('Shared policy.', $messages[0]->content->text);
        $this->assertEquals(Role::User, $messages[1]->role);
        $this->assertEquals('Task instruction.', $messages[1]->content->text);
        $this->assertEquals(Role::User, $messages[2]->role);
        $this->assertEquals('Hello!', $messages[2]->content->text);
        $this->assertInstanceOf(TextContent::class, $messages[0]->content);
    }

    public function testLoadThrowsOnMissingFile(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt file not found');
        $promptInstance->testLoad('non_existent');
    }

    public function testLoadThrowsOnInvalidFormat(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/invalid.md', 'just some text without roles');
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prompt file format');
        $promptInstance->testLoad('invalid');
    }
}
