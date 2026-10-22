<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Tools\TerminologyService;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(TerminologyService::class)]
final class TerminologyServiceTest extends TestCase
{
    private TerminologyService $service;

    private ?string $dir = null;

    private ?bool $libxmlInternalErrors = null;

    protected function setUp(): void
    {
        $this->service = new TerminologyService(new NullLogger());
    }

    protected function tearDown(): void
    {
        if ($this->libxmlInternalErrors !== null) {
            libxml_clear_errors();
            libxml_use_internal_errors($this->libxmlInternalErrors);
        }
        if ($this->dir !== null) {
            foreach (glob($this->dir . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->dir);
        }
    }

    public function test_resolve_id_to_rubric(): void
    {
        // 433 is 'event' in composition_category group
        $result = $this->service->resolve('433');

        $this->assertEquals('433', $result['id']);
        $this->assertEquals('event', $result['rubric']);
        $this->assertEquals('composition_category', $result['groupId']);
        $this->assertEquals('composition category', $result['groupName']);
    }

    public function test_resolve_rubric_to_id(): void
    {
        $result = $this->service->resolve('event');

        $this->assertEquals('433', $result['id']);
        $this->assertEquals('event', $result['rubric']);
    }

    public function test_resolve_case_insensitive_rubric(): void
    {
        $result = $this->service->resolve('EVENT');

        $this->assertEquals('433', $result['id']);
        $this->assertEquals('event', $result['rubric']);
    }

    public function test_resolve_with_group_id(): void
    {
        $result = $this->service->resolve('433', 'composition_category');

        $this->assertEquals('433', $result['id']);
        $this->assertEquals('event', $result['rubric']);
        $this->assertEquals('composition_category', $result['groupId']);
    }

    public function test_resolve_with_wrong_group_id(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Could not resolve "433" within group "attestation_reason"');
        
        $this->service->resolve('433', 'attestation_reason');
    }

    public function test_resolve_invalid_group_id(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Terminology group "invalid_group" not found.');
        
        $this->service->resolve('433', 'invalid_group');
    }

    public function test_resolve_rejects_a_group_id_that_would_change_the_xpath_query(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid terminology group ID');

        $this->service->resolve('433', 'x"] | //*[@id="');
    }

    public function test_resolve_not_found(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Could not resolve "non_existent_rubric" in openEHR terminology.');
        
        $this->service->resolve('non_existent_rubric');
    }

    public function test_resolve_empty_input(): void
    {
        $this->expectException(ToolCallException::class);
        $this->service->resolve('');
    }

    public function test_resolve_reports_an_unparseable_terminology_file_once(): void
    {
        $path = $this->temporaryFile('broken.xml', 'not xml');
        // libxml reports each parse error as a PHP warning as well; keep them out of the run.
        $this->libxmlInternalErrors = libxml_use_internal_errors(true);

        // Matched whole: the loader's message already names the parse failure.
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageMatches('/^Error parsing terminology XML: String could not be parsed as XML$/');

        (new TerminologyService(new NullLogger(), $path))->resolve('433');
    }

    public function test_resolve_reports_a_missing_terminology_file_as_such(): void
    {
        $path = $this->temporaryFile('unused.xml', '') . '.missing';

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageMatches('/^Terminology file not found or not readable\.$/');

        (new TerminologyService(new NullLogger(), $path))->resolve('433');
    }

    private function temporaryFile(string $name, string $content): string
    {
        $this->dir ??= sys_get_temp_dir() . '/terminology-service-' . bin2hex(random_bytes(6));
        if (!is_dir($this->dir)) {
            mkdir($this->dir);
        }
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $content);

        return $path;
    }
}
