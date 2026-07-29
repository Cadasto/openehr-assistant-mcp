<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Content;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Guards the MCP discovery-cache namespacing (REQ-N4).
 *
 * The capability set — tool schemas, prompt argument lists, resource templates — is cached
 * by Symfony Cache under a pool name, and a stale pool serves capability advertisements
 * that no longer match the handlers. Namespacing the pool by `APP_VERSION` makes a release
 * invalidate it automatically, but nothing in the suite referenced `APP_VERSION` or the pool
 * name, so the whole mechanism could be reverted to a constant `'mcp-server'` silently.
 */
#[CoversNothing]
final class DiscoveryCacheNamespaceTest extends TestCase
{
    public function test_the_discovery_cache_pool_is_namespaced_by_app_version(): void
    {
        $entrypoint = file_get_contents(__DIR__ . '/../../public/index.php');
        $this->assertIsString($entrypoint);

        $this->assertMatchesRegularExpression(
            "/new PhpFilesAdapter\(\s*'mcp-server-'\s*\.\s*APP_VERSION/",
            $entrypoint,
            'the discovery cache pool must stay namespaced by APP_VERSION, or a release serves a stale capability set',
        );
    }

    public function test_app_version_is_a_legal_cache_pool_name(): void
    {
        // The pool name becomes a *directory* segment (Symfony Cache
        // FilesystemCommonTrait), so a release-prep typo like `0.20.0 rc1` or `0.20/beta`
        // takes the server down at boot. src/constants.php states this constraint in a
        // comment; this makes it machine-checked.
        $this->assertMatchesRegularExpression('/^[-+_.A-Za-z0-9]+$/', APP_VERSION);
        $this->assertNotSame('', APP_VERSION);
    }
}
