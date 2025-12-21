<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\CompletionProviders;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\ArchetypeGuidelines;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArchetypeGuidelines::class)]
final class ArchetypeGuidelinesTest extends TestCase
{
    public function testListsMarkdownGuidelinesWithoutExtensions(): void
    {
        $provider = new ArchetypeGuidelines();

        $items = $provider->getCompletions('');

        $this->assertIsArray($items);
        $this->assertNotEmpty($items, 'Expected at least one guideline filename');

        // Ensure common known files are present (without .md extension)
        $this->assertContains('checklist', $items);
        $this->assertContains('adl-syntax', $items);
        $this->assertContains('adl-idioms-cheatsheet', $items);

        // Ensure no extensions are included
        foreach ($items as $i) {
            $this->assertDoesNotMatchRegularExpression('/\.md$/', $i);
        }
    }

    public function testPrefixFilteringIsApplied(): void
    {
        $provider = new ArchetypeGuidelines();

        $items = $provider->getCompletions('adl');

        $this->assertNotEmpty($items);
        foreach ($items as $i) {
            $this->assertStringStartsWith('adl', $i);
        }

        // A specific prefix should narrow results
        $anti = $provider->getCompletions('anti');
        $this->assertSame(['anti-patterns'], $anti);
    }
}
