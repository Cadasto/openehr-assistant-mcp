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
 * Nothing in this repository would otherwise notice a break: renaming the file, dropping
 * the hosted-endpoint section, or converting the sibling links to some other form all
 * leave this build green and silently damage a published page one release later. These
 * assertions are deliberately shallow — they pin the contract, not the prose.
 */
#[CoversNothing]
final class InstallDocContractTest extends TestCase
{
    private const INSTALL_DOC = __DIR__ . '/../../docs/install.md';

    public function test_the_install_doc_exists_at_the_path_the_website_fetches(): void
    {
        $this->assertFileExists(
            self::INSTALL_DOC,
            'docs/install.md is fetched by the product website (ADR-0007); moving or renaming it '
            . 'breaks a published page — update sources.json in cadasto/openehr-assistant first',
        );
    }

    public function test_it_still_documents_the_hosted_endpoint(): void
    {
        $this->assertStringContainsString(
            'openehr-assistant-mcp.apps.cadasto.com',
            $this->installDoc(),
            'the website asserts the published install page mentions the hosted endpoint; '
            . 'removing it here fails that downstream check instead of this one',
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
