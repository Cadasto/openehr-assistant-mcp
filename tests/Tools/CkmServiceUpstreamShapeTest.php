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
        return $this->serviceReturningRaw(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function serviceReturningRaw(string $body): CkmService
    {
        $client = $this->createMock(CkmClient::class);
        $client->method('get')->willReturn(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $body,
        ));

        return new CkmService($client, new NullLogger());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function driftedEnvelopeProvider(): array
    {
        // `is_array()` alone accepted every one of these. The pagination wrapper is the
        // realistic case, and it was the worst: it produced one bogus item with an empty
        // `cid` that satisfied the output schema, so both the conformance test and the
        // row-shape cases above stayed green while the tool lied to the model.
        return [
            'pagination wrapper' => ['{"content": [{"cid": "1.2.3", "resourceMainId": "openEHR-EHR-OBSERVATION.bp.v1"}]}'],
            'wrapper with sibling scalar' => ['{"totalElements": 482, "content": [{"cid": "1.2.3"}]}'],
            'list of scalars' => ['["openEHR-EHR-OBSERVATION.bp.v1", "openEHR-EHR-OBSERVATION.pulse.v1"]'],
            'list with a scalar row' => ['[{"cid": "1.2.3"}, 482]'],
            'bare scalar' => ['482'],
        ];
    }

    #[DataProvider('driftedEnvelopeProvider')]
    public function test_archetype_search_rejects_a_drifted_envelope(string $body): void
    {
        $service = $this->serviceReturningRaw($body);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unexpected CKM archetype response payload/');
        $service->archetypeSearch('blood pressure');
    }

    #[DataProvider('driftedEnvelopeProvider')]
    public function test_template_search_rejects_a_drifted_envelope(string $body): void
    {
        $service = $this->serviceReturningRaw($body);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unexpected CKM template response payload/');
        $service->templateSearch('discharge');
    }

    public function test_a_non_scalar_field_is_logged_when_it_is_dropped(): void
    {
        // Silent field loss is the dangerous half of the shape hardening: with `rmClass`
        // set, a wrapped `resourceMainId` drops `archetypeId` from every row, the RM-class
        // filter then deletes them all, and `total` confirms 0 — with no error anywhere.
        $logger = new class extends \Psr\Log\AbstractLogger {
            /** @var list<string> */
            public array $records = [];

            /** @param mixed[] $context */
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = (string) $message . ' ' . json_encode($context);
            }
        };

        $client = $this->createMock(CkmClient::class);
        $client->method('get')->willReturn(new Response(
            200,
            ['Content-Type' => 'application/json'],
            '[{"cid": "1.2.3", "resourceMainId": {"value": "openEHR-EHR-OBSERVATION.bp.v1"}}]',
        ));
        (new CkmService($client, $logger))->archetypeSearch('blood pressure');

        $dropped = array_filter($logger->records, static fn (string $r): bool => str_contains($r, 'unexpected non-scalar shape'));
        $this->assertNotEmpty($dropped, 'dropping a field must be logged, not silent');
        $this->assertStringContainsString('resourceMainId', implode("\n", $dropped));
    }
}
