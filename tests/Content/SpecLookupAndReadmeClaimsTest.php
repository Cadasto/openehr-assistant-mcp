<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Content;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_spec_lookup_instruction_names_development_as_the_stream_to_prefer(): void
    {
        // The example paths above can all say `development` while the instruction that tells
        // readers which stream to use says `latest`; this reads the instruction itself.
        $content = (string) file_get_contents(__DIR__ . '/../../resources/guides/howto/spec-lookup.md');

        $this->assertSame([], self::streamPreferenceProblems($content), 'spec-lookup must direct lookups to the `development` stream (constitution § Data, ADR-0005)');
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function streamPreferenceProvider(): array
    {
        return [
            'prefers development' => ["- prefer `development` stream URLs unless the user requests a fixed release tag.\n", true],
            'prefers latest' => ["- prefer `latest` stream URLs unless the user requests a fixed release tag.\n", false],
            'prefers the latest release' => ["- prefer the latest release unless the user asks otherwise.\n", false],
            'prefers development, then latest elsewhere' => ["- prefer `development` stream URLs.\n- When in doubt, prefer latest.\n", false],
            'names no stream to prefer' => ["- resolve a user's phrase to a canonical URL.\n", false],
        ];
    }

    #[DataProvider('streamPreferenceProvider')]
    public function test_stream_preference_check(string $guide, bool $acceptable): void
    {
        $this->assertSame($acceptable, self::streamPreferenceProblems($guide) === []);
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

    /**
     * What is wrong with a guide's stream instruction: no line says which stream to prefer,
     * a line that does names something other than `development` first, or any wording tells
     * readers to prefer `latest`.
     *
     * @return list<string>
     */
    private static function streamPreferenceProblems(string $guide): array
    {
        $problems = [];

        preg_match_all('/^.*\bprefer\w*\b.*\b(?:stream|branch|release)s?\b.*$/mi', $guide, $matches);
        if ($matches[0] === []) {
            $problems[] = 'no line tells readers which stream to prefer';
        }
        foreach ($matches[0] as $line) {
            if (preg_match('/\bprefer\w*\s+(?:the\s+)?`?development`?/i', $line) !== 1) {
                $problems[] = 'does not name `development` as the stream to prefer: ' . trim($line);
            }
        }

        preg_match_all('/^.*\bprefer\w*\s+(?:the\s+)?`?latest\b.*$/mi', $guide, $latest);
        foreach ($latest[0] as $line) {
            $problems[] = 'tells readers to prefer `latest`: ' . trim($line);
        }

        return $problems;
    }
}
