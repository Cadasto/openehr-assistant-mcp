<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\AbstractPrompt;
use InvalidArgumentException;
use Mcp\Exception\PromptGetException;
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
        // Unique per test: a fixed path is not safe for concurrent runs in one
        // container, and a leftover directory yields confusing failures.
        $this->tempPromptsDir = sys_get_temp_dir() . '/openehr-mcp-prompts-' . bin2hex(random_bytes(6));
        if (!mkdir($this->tempPromptsDir . '/shared', 0777, true) && !is_dir($this->tempPromptsDir . '/shared')) {
            self::fail('Could not create temporary prompts directory: ' . $this->tempPromptsDir);
        }
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

            /**
             * @param array<string, mixed> $values
             * @param list<string> $requiredKeys
             * @return PromptMessage[]
             */
            public function testLoadWith(string $name, array $values, array $requiredKeys = []): array
            {
                return $this->loadPromptMessages($name, $values, $requiredKeys);
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

    public function testLoadThrowsWhenSharedPolicyHasNoUserBlock(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: assistant\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\nTask instruction.");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Shared prompt policy must contain a user block.');
        $promptInstance->testLoad('test_prompt');
    }

    public function testLoadThrowsPromptGetExceptionOnBlankRequiredArgument(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\n{{task_type}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        // PromptGetException is the type GetPromptHandler preserves the message of;
        // a bare InvalidArgumentException would be swallowed into a generic error.
        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage('Required prompt argument "task_type" must not be blank.');
        $promptInstance->testLoadWith('test_prompt', ['task_type' => ''], ['task_type']);
    }

    public function testLoadRejectsWhitespaceOnlyRequiredArgument(): void
    {
        // Without the trim() the template would render three spaces and the model
        // would silently be asked to work on nothing.
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\n{{task_type}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage('Required prompt argument "task_type" must not be blank.');
        $promptInstance->testLoadWith('test_prompt', ['task_type' => "  \t\n "], ['task_type']);
    }

    public function testLoadReportsAnOmittedRequiredArgumentByName(): void
    {
        // A required argument the client omits is bound to null by the SDK's
        // ReferenceHandler (prompt parameters are `mixed`, which allows null) rather
        // than aborting with a RegistryException that GetPromptHandler would flatten
        // into a generic "Error while handling prompt".
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\n{{task_type}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage('Missing required prompt argument: task_type');
        $promptInstance->testLoadWith('test_prompt', ['task_type' => null], ['task_type']);
    }

    public function testLoadRejectsNonStringArgumentRatherThanStringifyingIt(): void
    {
        // Regression: a `string`-typed parameter would let the SDK coerce an array to
        // the literal "Array", handing the model that word in place of the artefact.
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\n{{adl_text}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage('Prompt argument "adl_text" must be a string, array given.');
        $promptInstance->testLoadWith('test_prompt', ['adl_text' => ['ELEMENT[at0001]']], ['adl_text']);
    }

    public function testLoadAcceptsAJsonNumberAsAStringValue(): void
    {
        // A number is losslessly representable as a string, so a client sending
        // `adl_version: 1.4` unquoted is accepted rather than rejected.
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\nv={{adl_version}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $messages = $promptInstance->testLoadWith('test_prompt', ['adl_version' => 1.4], ['adl_version']);

        $this->assertStringContainsString('v=1.4', $messages[array_key_last($messages)]->content->text);
    }

    public function testLoadSubstitutesOmittedOptionalArgumentsWithAnEmptyString(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\nA={{required}} B=[{{optional}}]");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $messages = $promptInstance->testLoadWith(
            'test_prompt',
            ['required' => 'here', 'optional' => null],
            ['required'],
        );

        $this->assertStringContainsString('A=here B=[]', $messages[array_key_last($messages)]->content->text);
    }

    public function testLoadThrowsPromptGetExceptionOnUnresolvedPlaceholder(): void
    {
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\n{{unknown_key}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $this->expectException(PromptGetException::class);
        $this->expectExceptionMessage('Unresolved prompt placeholder "unknown_key" in test_prompt');
        $promptInstance->testLoadWith('test_prompt', []);
    }

    public function testLoadDoesNotFlagLiteralPlaceholderSyntaxWithinASubstitutedValue(): void
    {
        // Regression: the resolved-placeholder check must scan the *template* for
        // `{{...}}` tokens before substitution, not the substituted output — otherwise
        // a legitimate value containing literal `{{...}}` text (e.g. a pasted example
        // artefact) falsely trips the unresolved-placeholder guard.
        file_put_contents($this->tempPromptsDir . '/shared/policy.md', "## Role: user\n\nShared policy.");
        file_put_contents($this->tempPromptsDir . '/test_prompt.md', "## Role: user\n\n{{existing_artefact}}");
        $promptInstance = $this->getMockPrompt($this->tempPromptsDir);

        $messages = $promptInstance->testLoadWith('test_prompt', ['existing_artefact' => 'contains a literal {{token}} in it']);

        $this->assertStringContainsString('contains a literal {{token}} in it', $messages[array_key_last($messages)]->content->text);
    }
}
