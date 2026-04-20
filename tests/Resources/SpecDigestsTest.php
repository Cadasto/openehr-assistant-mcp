<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Resources;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Validates every file under resources/guides/specs/*.md against the spec-digest schema.
 *
 * Enforces header field completeness, required section headings, body word budget,
 * and canonical URL patterns per docs/plans/2026-04-20-openehr-assistant-mcp-synopsis.md.
 */
#[CoversNothing]
final class SpecDigestsTest extends TestCase
{
    private const string SPECS_DIR = APP_RESOURCES_DIR . '/guides/specs';

    private const array REQUIRED_HEADER_FIELDS = [
        'Scope',
        'Component',
        'Document',
        'Release',
        'Spec URL',
        'Markdown URL',
        'Last updated',
        'Keywords',
    ];

    private const array REQUIRED_SECTIONS = [
        '## Purpose',
        '## Scope',
        '## Key Classes / Constructs',
        '## Relations to Other Specs',
        '## Architectural Placement',
        '## When to Read the Full Spec',
        '## References',
    ];

    private const int MIN_WORDS = 250;
    private const int MAX_WORDS = 900;
    private const string URL_REGEX = '#^https://specifications\.openehr\.org/releases/[A-Z_-]+/[^/]+/[A-Za-z0-9_-]+\.(html|md)$#';

    private const string SKIP_SENTINEL = '__no_digests_yet__';

    public static function digestFilesProvider(): array
    {
        $files = [];
        if (is_dir(self::SPECS_DIR)) {
            foreach (scandir(self::SPECS_DIR) ?: [] as $entry) {
                if (!str_ends_with($entry, '.md')) {
                    continue;
                }
                if ($entry === 'README.md' || str_starts_with($entry, '_')) {
                    continue;
                }
                $path = self::SPECS_DIR . '/' . $entry;
                $files[$entry] = [$path];
            }
        }

        return $files !== [] ? $files : ['no-digests-present' => [self::SKIP_SENTINEL]];
    }

    private function skipIfEmpty(string $path): void
    {
        if ($path === self::SKIP_SENTINEL) {
            $this->markTestSkipped('No spec digests present in resources/guides/specs/.');
        }
    }

    #[DataProvider('digestFilesProvider')]
    public function test_title_is_digest(string $path): void
    {
        $this->skipIfEmpty($path);
        $content = (string)file_get_contents($path);
        $lines = preg_split('/\r?\n/', $content) ?: [];
        $title = trim($lines[0] ?? '');

        $this->assertMatchesRegularExpression(
            '/^# .+ — Digest$/u',
            $title,
            sprintf('%s: title must be `# <Title> — Digest`', basename($path))
        );
    }

    #[DataProvider('digestFilesProvider')]
    public function test_header_fields_present(string $path): void
    {
        $this->skipIfEmpty($path);
        $content = (string)file_get_contents($path);
        $headerBlock = $this->extractHeaderBlock($content);

        foreach (self::REQUIRED_HEADER_FIELDS as $field) {
            $this->assertMatchesRegularExpression(
                '/^\*\*' . preg_quote($field, '/') . ':\*\*\s+\S/mu',
                $headerBlock,
                sprintf('%s: missing or empty header field **%s:**', basename($path), $field)
            );
        }
    }

    #[DataProvider('digestFilesProvider')]
    public function test_required_sections_present(string $path): void
    {
        $this->skipIfEmpty($path);
        $content = (string)file_get_contents($path);
        foreach (self::REQUIRED_SECTIONS as $heading) {
            $this->assertStringContainsString(
                "\n" . $heading . "\n",
                "\n" . $content . "\n",
                sprintf('%s: missing required section heading %s', basename($path), $heading)
            );
        }
    }

    #[DataProvider('digestFilesProvider')]
    public function test_body_word_count_within_budget(string $path): void
    {
        $this->skipIfEmpty($path);
        $content = (string)file_get_contents($path);
        $body = $this->extractBody($content);
        $words = str_word_count(strip_tags($body));

        $this->assertGreaterThanOrEqual(
            self::MIN_WORDS,
            $words,
            sprintf('%s: body has %d words, minimum is %d', basename($path), $words, self::MIN_WORDS)
        );
        $this->assertLessThanOrEqual(
            self::MAX_WORDS,
            $words,
            sprintf('%s: body has %d words, maximum is %d', basename($path), $words, self::MAX_WORDS)
        );
    }

    #[DataProvider('digestFilesProvider')]
    public function test_spec_urls_match_canonical_pattern(string $path): void
    {
        $this->skipIfEmpty($path);
        $content = (string)file_get_contents($path);
        $specUrl = $this->extractHeaderField($content, 'Spec URL');
        $mdUrl = $this->extractHeaderField($content, 'Markdown URL');

        $this->assertMatchesRegularExpression(
            self::URL_REGEX,
            $specUrl,
            sprintf('%s: Spec URL %s does not match canonical pattern', basename($path), $specUrl)
        );
        $this->assertMatchesRegularExpression(
            self::URL_REGEX,
            $mdUrl,
            sprintf('%s: Markdown URL %s does not match canonical pattern', basename($path), $mdUrl)
        );
        $this->assertStringEndsWith('.html', $specUrl, 'Spec URL must end with .html');
        $this->assertStringEndsWith('.md', $mdUrl, 'Markdown URL must end with .md');
    }

    private function extractHeaderBlock(string $content): string
    {
        $parts = preg_split('/^---\s*$/m', $content, 2) ?: [];
        return $parts[0] ?? '';
    }

    private function extractBody(string $content): string
    {
        $parts = preg_split('/^---\s*$/m', $content, 2) ?: [];
        return $parts[1] ?? '';
    }

    private function extractHeaderField(string $content, string $field): string
    {
        $header = $this->extractHeaderBlock($content);
        if (preg_match('/^\*\*' . preg_quote($field, '/') . ':\*\*\s+(.+)$/mu', $header, $m) === 1) {
            return trim($m[1]);
        }
        return '';
    }
}
