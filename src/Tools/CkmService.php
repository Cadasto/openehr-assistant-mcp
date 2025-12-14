<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Helpers\Map;
use GuzzleHttp\RequestOptions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\TextContent;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

final readonly class CkmService
{
    public function __construct(
        private CkmClient $apiClient,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * Search openEHR archetypes in the Clinical Knowledge Manager (CKM).
     *
     * Use this tool when you need to *discover* candidate archetypes before fetching full definitions.
     * It is typically the first step in an LLM workflow:
     *
     * 1) Search by a domain keyword (e.g. "blood pressure", "medication", "problem list")
     * 2) Inspect the returned metadata for plausible matches
     * 3) Take the returned CKM identifier (CID) and call {@see archetypeGet()} to retrieve the full archetype definition.
     *
     * Important notes for MCP/LLM clients:
     * - This tool performs a keyword search on CKM "main data" (server-side filtering).
     * - The returned structure is the upstream CKM JSON response decoded into PHP arrays. It is *not normalized* by this server.
     * - If you need deterministic fields for downstream reasoning, treat this as a discovery step and rely on {@see archetypeGet()} for authoritative content.
     *
     * @param string $keyword
     *   A human-oriented search string. Prefer meaningful clinical terms over internal codes.
     *   Examples: "blood pressure", "observation", "medication", "diabetes", "body weight".
     *
     * @return array<array<string,mixed>>
     *   A list of CKM archetype metadata entries as returned by CKM. Entries usually include a CID-like identifier and descriptive fields.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (network error, upstream outage, invalid response).
     */
    #[McpTool(name: 'ckm_archetype_search')]
    public function archetypeSearch(string $keyword): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        try {
            $response = $this->apiClient->get('v1/archetypes', [
                RequestOptions::QUERY => [
                    'search-text' => $keyword,
                    'restrict-search-to-main-data' => 'true',
                ],
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Found CKM Archetypes', ['keyword' => $keyword, 'count' => is_countable($data) ? count($data) : null]);
            $this->logger->debug(__METHOD__, ['response' => $data]);
            return $data;
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Archetypes', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to search for CKM Archetypes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve a CKM archetype definition by CID, in a specific representation.
     *
     * Use this tool after you have identified a candidate archetype (usually from {@see archetypeSearch()}).
     * It fetches the *full archetype definition* from CKM so an LLM can:
     * - understand the structure and meaning of nodes/attributes,
     * - extract constraints and terminology bindings,
     * - generate templates or implementation guidance,
     * - or cite the definition content in downstream reasoning.
     *
     * Returned content and formats:
     * - "adl": ADL source text (best for detailed archetype semantics and constraints)
     * - "xml": XML representation (helpful when consuming via XML tooling)
     * - "mindmap": mindmap form (useful for quick navigation / conceptual overview)
     *
     * Implementation detail (relevant to clients):
     * - The CID is normalized before building the upstream URL (characters outside digits and dots are replaced with "-").
     *   For best results, pass the CID exactly as returned by CKM search.
     *
     * @param string $cid
     *   CKM archetype identifier (CID). Example: "openEHR-EHR-OBSERVATION.blood_pressure.v2".
     *
     * @param string $format
     *   Desired representation: "adl", "xml" or "mindmap" (case-insensitive is accepted by this server).
     *   Defaults to "adl".
     *
     * @return TextContent
     *   The archetype definition as MCP text content. The content-type is set according to the chosen format.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (invalid CID, unsupported format mapping, upstream error).
     */
    #[McpTool(name: 'ckm_archetype_get')]
    public function archetypeGet(string $cid, string $format = 'adl'): TextContent
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        try {
            $archetypeFormat = Map::archetypeFormat($format);
            $contentType = Map::contentType($archetypeFormat);
            $cid = preg_replace('/[^\d.]/', '-', $cid);
            $response = $this->apiClient->get("v1/archetypes/{$cid}/{$archetypeFormat}", [
                RequestOptions::HEADERS => [
                    'Accept' => $contentType,
                ],
            ]);
            $data = $response->getBody()->getContents();
            $this->logger->info('CKM Archetype retrieved successfully', ['cid' => $cid, 'format' => $archetypeFormat, 'status' => $response->getStatusCode()]);
            $this->logger->debug(__METHOD__, [$contentType => $data]);
            return TextContent::code($data, $contentType);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to retrieve the CKM Archetype', ['error' => $e->getMessage(), 'cid' => $cid, 'format' => $format]);
            throw new \RuntimeException('Failed to retrieve the CKM Archetype: ' . $e->getMessage(), 0, $e);
        }
    }
}
