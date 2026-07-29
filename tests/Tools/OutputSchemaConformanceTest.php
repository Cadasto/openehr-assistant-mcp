<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers\OutputSchemaValidator;
use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\ExamplesService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\GuideService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\TerminologyService;
use Cadasto\OpenEHR\MCP\Assistant\Tools\TypeSpecificationService;
use GuzzleHttp\Psr7\Response;
use Mcp\Capability\Attribute\McpTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Validates a representative, successful invocation of every structured tool against its
 * published `outputSchema`. Guards against schema/return-envelope drift (unknown properties,
 * missing required keys, wrong item types) that clients would otherwise only discover at runtime.
 */
#[CoversNothing]
#[AllowMockObjectsWithoutExpectations]
final class OutputSchemaConformanceTest extends TestCase
{
    private const string TOOLS_NAMESPACE = 'Cadasto\\OpenEHR\\MCP\\Assistant\\Tools\\';

    public function test_guide_search_result_matches_output_schema(): void
    {
        $service = new GuideService(new NullLogger());
        $this->assertConforms($service, 'search', ['cardinality']);
    }

    public function test_guide_adl_idiom_lookup_result_matches_output_schema(): void
    {
        $service = new GuideService(new NullLogger());
        $this->assertConforms($service, 'adlIdiomLookup', ['cardinality']);
    }

    public function test_examples_search_result_matches_output_schema(): void
    {
        $service = new ExamplesService(new NullLogger());
        $this->assertConforms($service, 'search', ['blood pressure']);
    }

    public function test_ckm_archetype_search_result_matches_output_schema(): void
    {
        $payload = [
            ['resourceMainId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v1', 'cid' => '123.45', 'resourceMainDisplayName' => 'Blood pressure', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $client = $this->createMock(CkmClient::class);
        $client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));
        $service = new CkmService($client, new NullLogger());
        $this->assertConforms($service, 'archetypeSearch', ['blood']);
    }

    public function test_ckm_template_search_result_matches_output_schema(): void
    {
        $payload = [
            ['cid' => '123.45', 'resourceMainDisplayName' => 'Vital signs', 'projectName' => 'Test', 'status' => 'PUBLISHED'],
        ];
        $client = $this->createMock(CkmClient::class);
        $client->method('get')->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR)));
        $service = new CkmService($client, new NullLogger());
        $this->assertConforms($service, 'templateSearch', ['vital']);
    }

    public function test_terminology_resolve_result_matches_output_schema(): void
    {
        $service = new TerminologyService(new NullLogger());
        $this->assertConforms($service, 'resolve', ['433']);
    }

    public function test_type_specification_search_result_matches_output_schema(): void
    {
        $service = new TypeSpecificationService(new NullLogger());
        $this->assertConforms($service, 'search', ['composition']);
    }

    public function test_type_specification_get_result_matches_output_schema(): void
    {
        $service = new TypeSpecificationService(new NullLogger());
        $this->assertConforms($service, 'get', ['COMPOSITION']);
    }

    /**
     * Every `#[McpTool]` that declares an `outputSchema` must have a case above. The list of
     * tools is discovered by reflection rather than hand-maintained, so adding a ninth
     * structured tool fails here instead of silently going unvalidated.
     */
    public function test_every_structured_tool_has_a_conformance_case(): void
    {
        $covered = [];
        foreach (get_class_methods($this) as $testMethod) {
            if (str_starts_with($testMethod, 'test_')) {
                $covered[] = $testMethod;
            }
        }
        $coveredSource = implode("\n", $covered);

        $structuredTools = [];
        foreach (glob(__DIR__ . '/../../src/Tools/*.php') ?: [] as $path) {
            $className = self::TOOLS_NAMESPACE . basename($path, '.php');
            if (!class_exists($className)) {
                continue;
            }

            foreach ((new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(McpTool::class);
                if ($attributes === []) {
                    continue;
                }
                /** @var McpTool $mcpTool */
                $mcpTool = $attributes[0]->newInstance();
                if ($mcpTool->outputSchema === null) {
                    continue;
                }
                $structuredTools[] = (string) $mcpTool->name;
            }
        }

        $this->assertNotEmpty($structuredTools);
        foreach ($structuredTools as $toolName) {
            $this->assertStringContainsString(
                'test_' . $toolName . '_result_matches_output_schema',
                $coveredSource,
                sprintf('Tool `%s` declares an outputSchema but has no conformance case in %s', $toolName, self::class),
            );
        }
    }

    /**
     * @param array<int, mixed> $args
     */
    private function assertConforms(object $service, string $method, array $args): void
    {
        $reflection = new \ReflectionMethod($service, $method);
        $attributes = $reflection->getAttributes(McpTool::class);
        $this->assertNotEmpty($attributes, sprintf('%s::%s is not annotated with #[McpTool]', $service::class, $method));

        /** @var McpTool $mcpTool */
        $mcpTool = $attributes[0]->newInstance();
        $this->assertNotNull($mcpTool->outputSchema, sprintf('%s::%s does not declare an outputSchema', $service::class, $method));

        $result = $reflection->invoke($service, ...$args);

        // Item-level `required` / `additionalProperties` / type checks are skipped entirely
        // for an empty list, so an envelope with no items would pass vacuously — and these
        // fixtures depend on live corpus content that could be renamed or reorganised.
        if (is_array($result) && array_key_exists('items', $result)) {
            $this->assertNotEmpty(
                $result['items'],
                sprintf('%s::%s returned no items; the item-level schema checks would be vacuous', $service::class, $method),
            );
        }

        OutputSchemaValidator::assertValid($result, $mcpTool->outputSchema);
    }
}
