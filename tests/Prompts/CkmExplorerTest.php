<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\CkmExplorer;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CkmExplorer::class)]
final class CkmExplorerTest extends TestCase
{
    public function testPromptReturnsWellFormedMessagesAndReferencesTools(): void
    {
        $prompt = new CkmExplorer();
        $messages = $prompt->__invoke();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $combinedContent = '';
        foreach ($messages as $msg) {
            $this->assertInstanceOf(PromptMessage::class, $msg);
            $this->assertInstanceOf(Role::class, $msg->role);
            $this->assertIsString($msg->content->text);
            $this->assertNotSame('', trim($msg->content->text));
            $combinedContent .= "\n" . $msg->content->text;
        }

        $this->assertStringContainsString('ckm_archetype_search', $combinedContent);
        $this->assertStringContainsString('ckm_archetype_get', $combinedContent);
        $this->assertStringContainsString('ckm_template_search', $combinedContent);
        $this->assertStringContainsString('ckm_template_get', $combinedContent);
        $this->assertStringContainsString('openehr://guides/templates/principles', $combinedContent);
    }
}
