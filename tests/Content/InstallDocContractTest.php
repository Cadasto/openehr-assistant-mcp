<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Content;

use PHPUnit\Framework\Attributes\CoversNothing;
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
    private const INSTALL_DOC = __DIR__ . '/../../docs/install.md';

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
        preg_match_all(
            '/\]\((?!https?:|mailto:|#|\/)([^)\s#]+)/',
            $this->installDoc(),
            $matches,
        );

        foreach ($matches[1] as $target) {
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
                __DIR__ . '/../../' . $resolved,
                sprintf('docs/install.md links to "%s", which does not exist', $target),
            );
        }
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
