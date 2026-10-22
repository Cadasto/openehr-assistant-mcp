<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Content;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the install-documentation contract with the public website (REQ-N10, ADR-0007).
 *
 * The website at cadasto/openehr-assistant does not keep its own copy of the install
 * instructions — it fetches this repository's `docs/install.md` at a released tag and
 * publishes the result. That keeps a single source, at the cost of making this file's
 * path and shape an external contract.
 *
 * Nothing would otherwise notice a break. Renaming the file, dropping the hosted-endpoint
 * section, or adding a sibling link that does not resolve all leave this build green and
 * damage a published page one release later — and the consuming site cannot be relied on
 * to catch it either, because it frames the fetched document with prose of its own and
 * checks the rendered result, so its assertions can pass over impoverished content.
 *
 * The assertions pin the contract, not the prose: a section is located by meaning rather
 * than by its exact title, so install.md stays free to be reworded.
 */
#[CoversNothing]
final class InstallDocContractTest extends TestCase
{
    private const REPO_ROOT = __DIR__ . '/../../';

    private const INSTALL_DOC = self::REPO_ROOT . 'docs/install.md';

    /** A reference-style link definition, `[label]: target`; group 1 is the label, group 2 the target. Footnotes are not links. */
    private const REFERENCE_DEFINITION = '/^ {0,3}\[([^\]^][^\]]*)\]:[ \t]*<?([^\s>]+)>?/m';

    private const HOSTED_ENDPOINT = 'openehr-assistant-mcp.apps.cadasto.com';

    /**
     * Heading of the zero-install option, matched by meaning so the title stays free to
     * change. `self-hosted` is excluded deliberately: it names the opposite arrangement,
     * so a section titled that way must not be able to satisfy this guard.
     */
    private const HOSTED_HEADING = '/(?<!self-)hosted/i';

    public function test_the_install_doc_exists_at_the_path_the_website_fetches(): void
    {
        $this->assertFileExists(
            self::INSTALL_DOC,
            'docs/install.md is fetched by the product website (ADR-0007); moving or renaming it '
            . 'breaks a published page — update sources.json in cadasto/openehr-assistant first',
        );
    }

    public function test_it_still_offers_the_hosted_endpoint_as_an_option(): void
    {
        $section = $this->sectionMatching(self::HOSTED_HEADING);

        $this->assertNotNull(
            $section,
            'docs/install.md must keep a top-level section offering the hosted endpoint. The '
            . 'website publishes this file as its install page, so dropping the section removes '
            . 'the zero-install path a reader is most likely to want.',
        );
        $this->assertStringContainsString(
            self::HOSTED_ENDPOINT,
            $section,
            sprintf(
                'the hosted-endpoint section of docs/install.md must name %s. The hostname '
                . 'appearing only in a client-config snippet further down does not tell a '
                . 'reader which endpoint the hosted option is.',
                self::HOSTED_ENDPOINT,
            ),
        );
    }

    public function test_its_code_fences_are_balanced(): void
    {
        $fences = preg_match_all('/^(```|~~~)/m', $this->installDoc());

        $this->assertSame(
            0,
            $fences % 2,
            'docs/install.md leaves a code fence unclosed. The website publishes this file, so '
            . 'every heading after that point renders as code — and the section guard above '
            . 'reads the same run-on block, which would let a hostname anywhere below the '
            . 'hosted section satisfy it.',
        );
    }

    public function test_relative_links_stay_inside_the_docs_directory(): void
    {
        foreach (self::linkTargets($this->installDoc()) as $link) {
            $target = rawurldecode(explode('#', $link, 2)[0]);
            if ($target === '') {
                // A same-file anchor; the fragment test below resolves it.
                continue;
            }
            $resolved = $this->normalise('docs/' . $target);

            $this->assertStringStartsNotWith(
                '..',
                $resolved,
                sprintf(
                    'docs/install.md links to "%s", which escapes the repository root; the website '
                    . 'rewrites relative links to GitHub URLs and cannot resolve that',
                    $target,
                ),
            );
            $this->assertFileExists(
                self::REPO_ROOT . $resolved,
                sprintf('docs/install.md links to "%s", which does not exist', $target),
            );
        }
    }

    public function test_reference_style_links_have_a_definition(): void
    {
        $this->assertSame(
            [],
            self::undefinedReferences($this->installDoc()),
            'docs/install.md uses reference-style links with no matching `[label]: target` definition; '
            . 'the website renders each one as literal brackets instead of a link',
        );
    }

    public function test_link_fragments_match_an_anchor_in_the_target_file(): void
    {
        $broken = [];

        foreach (self::linkTargets($this->installDoc()) as $link) {
            if (!str_contains($link, '#')) {
                continue;
            }
            [$target, $fragment] = explode('#', $link, 2);
            $file = $target === '' ? 'docs/install.md' : $this->normalise('docs/' . rawurldecode($target));
            if (!str_ends_with(strtolower($file), '.md') || !is_file(self::REPO_ROOT . $file)) {
                // Only Markdown has heading anchors to check; a missing file fails the test above.
                continue;
            }
            $anchors = self::anchors((string) file_get_contents(self::REPO_ROOT . $file));
            if (!in_array(rawurldecode($fragment), $anchors, true)) {
                $broken[] = $link;
            }
        }

        $this->assertSame(
            [],
            $broken,
            'docs/install.md links to anchors that match no heading or <a id> in the target file; '
            . 'the published page would land at the top of the document instead',
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function headingSlugProvider(): array
    {
        return [
            'colon and parentheses dropped' => ['Option 1: hosted endpoint (no install)', 'option-1-hosted-endpoint-no-install'],
            'code span and emphasis dropped' => ['`make ci` and *PHPStan*', 'make-ci-and-phpstan'],
            'underscore and hyphen kept, each space a hyphen' => ['snake_case & kebab-case', 'snake_case--kebab-case'],
            'link reduced to its text' => ['See [the guide](guide.md)', 'see-the-guide'],
            'non-ASCII letters kept' => ['Überblick der Ä-Klassen', 'überblick-der-ä-klassen'],
            'closing hashes dropped' => ['Client configurations ##', 'client-configurations'],
        ];
    }

    #[DataProvider('headingSlugProvider')]
    public function test_heading_anchors_follow_github_slug_rules(string $heading, string $anchor): void
    {
        $this->assertSame([$anchor], self::anchors('## ' . $heading . "\n"));
    }

    public function test_anchors_number_repeated_headings_skip_fenced_code_and_include_explicit_ids(): void
    {
        $markdown = "# Intro\n\n## Setup\n\n```sh\n# From the dev container:\n```\n\n### Setup\n\n<a id=\"custom-anchor\"></a>\n";

        $this->assertSame(['intro', 'setup', 'setup-1', 'custom-anchor'], self::anchors($markdown));
    }

    public function test_link_targets_cover_inline_and_reference_style_links(): void
    {
        $markdown = <<<'MD'
            See [dev](development.md), ![logo](img/logo.png "Logo"), [here](#client-configurations)
            and [the ADR][adr], [ext](https://example.org) and [mail](mailto:a@example.org).

            [adr]: <decisions/0007-website.md#decision> "ADR-0007"
            [root]: /absolute/path.md
            [^1]: A footnote, not a link.

            ```md
            [fenced]: not-a-definition.md
            ```
            MD;

        $this->assertSame(
            ['development.md', 'img/logo.png', '#client-configurations', 'decisions/0007-website.md#decision'],
            self::linkTargets($markdown),
        );
    }

    public function test_undefined_reference_labels_are_reported(): void
    {
        $markdown = "Read [the ADR][ADR 7], [b][] and [c][missing].\n\n[adr  7]: decisions/0007.md\n[b]: b.md\n\nCode: `[d][in-code]`\n";

        $this->assertSame(['missing'], self::undefinedReferences($markdown));
    }

    /**
     * The relative targets of every inline link and image and every reference-style link
     * definition, fragment included; targets with a scheme or an absolute path are left out.
     *
     * @return list<string>
     */
    private static function linkTargets(string $markdown): array
    {
        preg_match_all('/\]\(\s*<?([^)\s>]+)/', $markdown, $inline);
        preg_match_all(self::REFERENCE_DEFINITION, self::withoutFences($markdown), $definitions);

        $targets = [];
        foreach ([...$inline[1], ...$definitions[2]] as $target) {
            if (preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/)/i', $target) === 1) {
                continue;
            }
            $targets[] = $target;
        }

        return $targets;
    }

    /**
     * Labels of full (`[text][label]`) and collapsed (`[label][]`) reference links that have
     * no definition, matched case-insensitively as CommonMark does. Code is skipped.
     *
     * @return list<string>
     */
    private static function undefinedReferences(string $markdown): array
    {
        $text = (string) preg_replace('/`[^`\n]*`/', '', self::withoutFences($markdown));
        preg_match_all(self::REFERENCE_DEFINITION, $text, $definitions);
        $defined = array_map(self::referenceLabel(...), $definitions[1]);

        preg_match_all('/\[([^\]]+)\]\[([^\]]*)\]/', $text, $uses, PREG_SET_ORDER);
        $undefined = [];
        foreach ($uses as $use) {
            $label = self::referenceLabel($use[2] === '' ? $use[1] : $use[2]);
            if (!in_array($label, $defined, true)) {
                $undefined[] = $label;
            }
        }

        return array_values(array_unique($undefined));
    }

    private static function referenceLabel(string $label): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $label)));
    }

    /**
     * The anchors a Markdown file offers: one per ATX heading, slugged as GitHub does and
     * numbered `-1`, `-2` on repeats, plus every explicit `<a id>` or `<a name>`. Headings in
     * fenced code are not headings.
     *
     * @return list<string>
     */
    private static function anchors(string $markdown): array
    {
        $text = self::withoutFences($markdown);
        $anchors = [];
        $taken = [];

        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^ {0,3}#{1,6}[ \t]+(.+?)(?:[ \t]+#+)?[ \t]*$/', $line, $matches) !== 1) {
                continue;
            }
            $slug = self::slug($matches[1]);
            $anchor = $slug;
            for ($n = 1; isset($taken[$anchor]); $n++) {
                $anchor = $slug . '-' . $n;
            }
            $taken[$anchor] = true;
            $anchors[] = $anchor;
        }

        preg_match_all('/<a\s[^>]*\b(?:id|name)\s*=\s*["\']([^"\']+)["\']/i', $text, $explicit);

        return [...$anchors, ...$explicit[1]];
    }

    /**
     * GitHub's heading slug: the heading's text, lowercased, without punctuation other than
     * `-` and `_`, each space turned into `-`.
     */
    private static function slug(string $heading): string
    {
        $text = (string) preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $heading);
        $text = (string) preg_replace('/<[^>]+>/', '', $text);
        $text = (string) preg_replace('/[^\p{L}\p{M}\p{N}\p{Pc} -]/u', '', mb_strtolower(trim($text)));

        return str_replace(' ', '-', $text);
    }

    /**
     * The Markdown with every line inside a fenced code block blanked, fence lines included.
     */
    private static function withoutFences(string $markdown): string
    {
        $lines = [];
        $inFence = false;

        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^ {0,3}(```|~~~)/', $line) === 1) {
                $inFence = !$inFence;
                $lines[] = '';
                continue;
            }
            $lines[] = $inFence ? '' : $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Body of the first `##` section whose heading matches, or null if there is none.
     *
     * Fenced blocks are skipped so a `##` inside a shell or JSON sample cannot be read
     * as a heading and truncate the section early. That relies on the fences balancing,
     * which is why an unclosed one is a failure of its own rather than a silent run-on.
     */
    private function sectionMatching(string $headingPattern): ?string
    {
        $body = null;
        $inFence = false;

        foreach (explode("\n", $this->installDoc()) as $line) {
            if (preg_match('/^(```|~~~)/', $line) === 1) {
                $inFence = !$inFence;
            }

            if (!$inFence && str_starts_with($line, '## ')) {
                if ($body !== null) {
                    break;
                }
                if (preg_match($headingPattern, $line) === 1) {
                    $body = $line . "\n";
                }
                continue;
            }

            if ($body !== null) {
                $body .= $line . "\n";
            }
        }

        return $body;
    }

    private function installDoc(): string
    {
        $contents = file_get_contents(self::INSTALL_DOC);
        $this->assertIsString($contents);

        return $contents;
    }

    /**
     * Collapse `.` and `..` segments without touching the filesystem, so a link that
     * escapes the repository is reported rather than silently resolved.
     */
    private function normalise(string $path): string
    {
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..' && $segments !== [] && end($segments) !== '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}
