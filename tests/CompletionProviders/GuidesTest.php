<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\CompletionProviders;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\Guides;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Guides::class)]
final class GuidesTest extends TestCase
{
    public function testListsMarkdownGuidesWithoutExtensions(): void
    {
        $provider = new Guides();

        $items = $provider->getCompletions('');

        $this->assertIsArray($items);
        $this->assertNotEmpty($items, 'Expected at least one guide filename');

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
        $provider = new Guides();

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
