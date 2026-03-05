<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\TerminologyExplorer;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TerminologyExplorer::class)]
final class TerminologyExplorerTest extends TestCase
{
    public function test_invoke_returns_expected_structure(): void
    {
        $prompt = new TerminologyExplorer();
        $messages = $prompt();

        $this->assertIsArray($messages);
        $this->assertGreaterThanOrEqual(3, count($messages));

        $this->assertEquals(Role::User, $messages[0]->role);
        $combined = implode("\n", array_map(static fn ($message): string => $message->content->text, $messages));
        $this->assertStringContainsString('openEHR Terminology definitions', $combined);
        $this->assertStringContainsString('openehr://terminology', $combined);

        $this->assertEquals(Role::User, $messages[array_key_last($messages)]->role);
        $this->assertStringContainsString('Help me find and retrieve an openEHR Terminology', $messages[array_key_last($messages)]->content->text);
    }
}
