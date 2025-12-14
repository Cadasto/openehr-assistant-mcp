<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Prompts;

use Cadasto\OpenEHR\MCP\Assistant\Prompts\CkmArchetypeExplorer;
use PHPUnit\Framework\TestCase;

final class CkmArchetypeExplorerTest extends TestCase
{
    public function testPromptReturnsWellFormedMessagesAndReferencesTools(): void
    {
        $prompt = new CkmArchetypeExplorer();
        $messages = $prompt->__invoke();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $allowedRoles = ['system','user','assistant'];
        $combinedContent = '';
        foreach ($messages as $msg) {
            $this->assertIsArray($msg);
            $this->assertArrayHasKey('role', $msg);
            $this->assertArrayHasKey('content', $msg);
            $this->assertContains($msg['role'], $allowedRoles);
            $this->assertIsString($msg['content']);
            $this->assertNotSame('', trim($msg['content']));
            $combinedContent .= "\n" . $msg['content'];
        }

        $this->assertStringContainsString('ckm_archetype_search', $combinedContent);
        $this->assertStringContainsString('ckm_archetype_get', $combinedContent);
    }
}
