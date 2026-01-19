<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\CompletionProviders;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\SpecificationComponents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecificationComponents::class)]
final class SpecificationComponentsTest extends TestCase
{

    public function testGetCompletionsWithEmptyDirectory(): void
    {
        $provider = new SpecificationComponents();
        $result = $provider->getCompletions('');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Expected completions to be not empty for an empty completion.');
        $this->assertContains('AM', $result);
        $this->assertContains('RM', $result);
    }

    public function testGetCompletionsWithValidDirectories(): void
    {
        $provider = new SpecificationComponents();
        $result = $provider->getCompletions('A');

        $this->assertIsArray($result);
        $this->assertCount(2, $result, 'Expected two directories to be listed.');
        $this->assertContains('AM', $result);
    }

    public function testGetCompletionsIgnoresFiles(): void
    {
        $provider = new SpecificationComponents();
        $result = $provider->getCompletions('a-file');

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'Expected no directories to be listed when only files are present.');
    }

    public function testGetCompletionsSkipsDots(): void
    {
        $provider = new SpecificationComponents();
        $result = $provider->getCompletions('.');

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'Expected dot directories to be skipped.');
    }
}