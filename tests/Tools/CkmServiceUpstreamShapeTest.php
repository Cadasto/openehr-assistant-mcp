<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Tests\Helpers\OutputSchemaValidator;
use Cadasto\OpenEHR\MCP\Assistant\Tools\CkmService;
use GuzzleHttp\Psr7\Response;
use Mcp\Capability\Attribute\McpTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * CKM is an external service, so its payload shape is an assumption rather than a contract.
 * These cases feed the mapper the awkward shapes it can legitimately receive and assert the
 * published `outputSchema` still holds — the server, not the client, has to absorb the
 * variation.
 */
#[CoversNothing]
#[AllowMockObjectsWithoutExpectations]
final class CkmServiceUpstreamShapeTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function awkwardArchetypeRowProvider(): array
    {
        $base = [
            'cid' => '1234.5',
            'resourceMainId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v2',
            'resourceMainDisplayName' => 'Blood pressure',
            'projectName' => 'openEHR Foundation',
            'status' => 'PUBLISHED',
        ];

        return [
            // Regression: `array_filter(..., !== null)` dropped absent keys, including the
            // `cid` the schema declares required and `ckm_archetype_get` needs.
            'row without a cid' => [array_diff_key($base, ['cid' => null])],
            'row with a null cid' => [['cid' => null] + $base],
            // `revision` is declared `type: string`; CKM revisions are plausibly numeric.
            'numeric revision' => [['revision' => 7] + $base],
            // The schema documents "ISO 8601 or epoch-ms"; unquoted epoch-ms used to reach
            // yearsSince(?string) as an int and raise a TypeError.
            'epoch-ms creationTime as an integer' => [['creationTime' => 1690000000000, 'status' => 'DRAFT'] + $base],
            'epoch-ms creationTime as a string' => [['creationTime' => '1690000000000', 'status' => 'DRAFT'] + $base],
            'iso8601 creationTime' => [['creationTime' => '2023-07-22T10:00:00Z', 'status' => 'DRAFT'] + $base],
            'boolean where a string is expected' => [['status' => true] + $base],
            'nested object where a string is expected' => [['resourceMainDisplayName' => ['nested' => 'value']] + $base],
            'minimal row' => [['cid' => '9.9']],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    #[DataProvider('awkwardArchetypeRowProvider')]
    public function test_archetype_search_output_stays_schema_conformant(array $row): void
    {
        $service = $this->serviceReturning([$row]);
        $result = $service->archetypeSearch('blood');

        $this->assertNotEmpty($result['items']);
        OutputSchemaValidator::assertValid($result, $this->outputSchemaOf('archetypeSearch'));
    }

    /**
     * @param array<string, mixed> $row
     */
    #[DataProvider('awkwardArchetypeRowProvider')]
    public function test_template_search_output_stays_schema_conformant(array $row): void
    {
        $service = $this->serviceReturning([$row]);
        $result = $service->templateSearch('vital');

        $this->assertNotEmpty($result['items']);
        OutputSchemaValidator::assertValid($result, $this->outputSchemaOf('templateSearch'));
    }

    public function test_a_row_without_a_cid_yields_an_empty_string_rather_than_a_missing_key(): void
    {
        $service = $this->serviceReturning([[
            'resourceMainId' => 'openEHR-EHR-OBSERVATION.blood_pressure.v2',
            'resourceMainDisplayName' => 'Blood pressure',
        ]]);

        $result = $service->archetypeSearch('blood');

        $this->assertArrayHasKey('cid', $result['items'][0]);
        $this->assertSame('', $result['items'][0]['cid']);
    }

    public function test_a_non_scalar_field_is_dropped_rather_than_stringified_to_Array(): void
    {
        $service = $this->serviceReturning([[
            'cid' => '1.1',
            'resourceMainDisplayName' => ['unexpected' => 'shape'],
        ]]);

        $result = $service->archetypeSearch('blood');

        $this->assertNotContains('Array', $result['items'][0], 'a nested value must not be coerced to the literal "Array"');
        $this->assertArrayNotHasKey('name', $result['items'][0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function outputSchemaOf(string $method): array
    {
        $attributes = (new ReflectionMethod(CkmService::class, $method))->getAttributes(McpTool::class);
        /** @var McpTool $mcpTool */
        $mcpTool = $attributes[0]->newInstance();
        $this->assertNotNull($mcpTool->outputSchema);

        return $mcpTool->outputSchema;
    }

    /**
     * @param list<array<string, mixed>> $payload
     */
    private function serviceReturning(array $payload): CkmService
    {
        $client = $this->createMock(CkmClient::class);
        $client->method('get')->willReturn(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        ));

        return new CkmService($client, new NullLogger());
    }
}
