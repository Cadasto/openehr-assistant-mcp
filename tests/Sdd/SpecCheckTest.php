<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Sdd;

use Cadasto\OpenEHR\MCP\Assistant\Sdd\SpecCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecCheck::class)]
final class SpecCheckTest extends TestCase
{
    public function testRealRepositoryTraceabilityMapHasNoDrift(): void
    {
        $errors = (new SpecCheck(APP_DIR))->check();

        $this->assertSame(
            [],
            $errors,
            "Traceability drift between docs/traceability.yaml and the tree:\n" . implode("\n", $errors),
        );
    }

    public function testDetectsAMissingPackagePath(): void
    {
        $root = APP_DIR . '/tests/fixtures/sdd-broken';

        $errors = (new SpecCheck($root))->check();

        $this->assertNotEmpty($errors, 'A map citing a missing package must produce a drift finding.');
        $this->assertStringContainsString('does/not/exist.php', implode("\n", $errors));
    }
}
