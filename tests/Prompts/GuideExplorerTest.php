<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\GuideExplorer;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuideExplorer::class)]
final class GuideExplorerTest extends TestCase
{
    public function test_invoke_returns_expected_structure(): void
    {
        $prompt = new GuideExplorer();
        $messages = $prompt();

        $this->assertIsArray($messages);
        $this->assertCount(3, $messages);

        // First message: user role with instructions, tools, guidance, workflow, examples
        $this->assertEquals(Role::User, $messages[0]->role);
        $this->assertStringContainsString('openEHR implementation guides', $messages[0]->content->text);
        $this->assertStringContainsString('guide_search', $messages[0]->content->text);
        $this->assertStringContainsString('### Tools', $messages[0]->content->text);
        $this->assertStringContainsString('### Workflow', $messages[0]->content->text);
        $this->assertStringContainsString('### Examples', $messages[0]->content->text);

        // Second message: assistant acknowledgment
        $this->assertEquals(Role::Assistant, $messages[1]->role);

        // Third message: user request
        $this->assertEquals(Role::User, $messages[2]->role);
        $this->assertStringContainsString('Help me find and retrieve openEHR implementation guidance', $messages[2]->content->text);
    }
}
