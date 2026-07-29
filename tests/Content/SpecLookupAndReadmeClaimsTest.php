<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Content;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Guards specific prose claims in shipped documentation/guides that are easy to
 * silently regress: the `spec-lookup` how-to's development-stream guidance, and
 * the README's `guide_get` chunking claim. (Examples resource MIME-type behaviour
 * is covered separately by `tests/Resources/ExamplesTest.php`.)
 */
#[CoversNothing]
final class SpecLookupAndReadmeClaimsTest extends TestCase
{
    public function test_spec_lookup_prefers_development_stream(): void
    {
        $content = (string) file_get_contents(__DIR__ . '/../../resources/guides/howto/spec-lookup.md');

        // Assert on the stream segment of every release path, not on the presence of the word
        // "development" — that word occurs in almost any wording, so reverting each
        // `/development/` URL to `/latest/` would leave a substring check green.
        preg_match_all('#releases/[A-Za-z0-9_-]+/([A-Za-z0-9_.-]+)/#', $content, $matches);
        $this->assertNotEmpty($matches[1], 'expected the guide to cite release-stream paths');
        $this->assertNotContains('latest', $matches[1], 'spec-lookup must track the development stream, not `latest`');
        $this->assertContains('development', $matches[1]);
        $this->assertStringNotContainsString('confirm the current `latest` release tag before linking', $content);
    }

    public function test_readme_guide_get_does_not_claim_chunking(): void
    {
        // `guide_get` returns whole files; assert the behaviour, not the absence of one
        // particular phrasing of the old claim.
        $content = (string) file_get_contents(__DIR__ . '/../../README.md');
        preg_match('/^- `guide_get`.*$/m', $content, $matches);
        $this->assertNotEmpty($matches, 'expected README to document guide_get');

        $this->assertDoesNotMatchRegularExpression('/chunk/i', $matches[0]);
        $this->assertMatchesRegularExpression('/\bfull\b/i', $matches[0]);
    }
}
