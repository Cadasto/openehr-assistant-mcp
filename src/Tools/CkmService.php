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
     * Search openEHR Archetypes in the Clinical Knowledge Manager (CKM).
     *
     * Use this tool when you need to *discover* candidate archetypes before fetching full definitions.
     * It is typically the first step in an LLM workflow:
     *
     * 1) Search by a domain keyword (e.g. "blood pressure", "medication", "problem list")
     * 2) Inspect the returned metadata for plausible matches
     * 3) Take the returned CKM identifier (CID) and call `ckm_archetype_get` tool to retrieve the full archetype definition.
     *
     * Important notes for MCP/LLM clients:
     * - This tool performs a keyword search on CKM "main data" (server-side filtering).
     * - If you need deterministic fields for downstream reasoning,
     *      treat this as a discovery step and rely on the `ckm_archetype_get` tool for authoritative content.
     *
     * @param string $keyword
     *   A human-oriented search string, one or multiple words, wildcards `*` supported. Prefer meaningful clinical terms over internal codes.
     *   Examples: "blood pressure", "observation", "medication", "diabetes", "body weight".
     *
     * @param int $limit
     *   The maximum number of archetypes returned in the call. Defaults to 10.
     *
     * @param int $offset
     *   The offset into the result set, for paging. Defaults to 0.
     *
     * @param bool $requireAllSearchWords
     *   If multiple search words are supplied, should ALL words be required (`true`), or is ANY sufficient (`false`).
     *   Defaults to `true`.
     *
     * @return array<array<string,mixed>>
     *   A list of CKM Archetype metadata entries as returned by CKM.
     *   Entries usually include a CID identifier, resourceMainId (representing the archetype-id), resourceMainDisplayName, and other descriptive fields.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (network error, upstream outage, invalid response).
     */
    #[McpTool(name: 'ckm_archetype_search')]
    public function archetypeSearch(string $keyword, int $limit = 10, int $offset = 0, bool $requireAllSearchWords = true): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        try {
            $response = $this->apiClient->get('v1/archetypes', [
                RequestOptions::QUERY => [
                    'search-text' => $keyword,
                    'size' => $limit,
                    'offset' => $offset,
                    'restrict-search-to-main-data' => 'true',
                    'require-all-search-words' => $requireAllSearchWords ? 'true' : 'false',
                ],
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Found CKM Archetypes', ['keyword' => $keyword, 'count' => is_countable($data) ? count($data) : null]);
            return $data;
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Archetypes', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to search for CKM Archetypes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve the definition of an identified CKM Archetype, serialized in a specific format.
     *
     * Use this tool after you have identified a candidate archetype (usually from the `ckm_archetype_search` tool).
     * Identification is based on the CKM Archetype identifier (CID), or
     * based on human-readable archetype-id (also known as `resourceMainId` from the search response).
     *
     * It fetches the *full archetype definition* from CKM so an LLM can:
     * - understand the structure and semantic or meaning of nodes/attributes,
     * - extract constraints, translations, and terminology bindings,
     * - generate templates or implementation guidance,
     * - or cite the definition content in downstream reasoning.
     *
     * Returned content and formats:
     * - "adl": ADL source text (best for detailed archetype semantics and constraints)
     * - "xml": XML representation (similar to "adl", but helpful when consuming via XML tooling)
     * - "mindmap": mindmap form (useful for quick visual overview)
     *
     * Implementation detail (relevant to clients):
     * - For best results, pass the CID exactly as returned by `ckm_archetype_search` tool.
     *
     * @param string $identifier
     *   CID identifier (e.g. "1013.1.7850") or archetype-id (e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v1").
     *
     * @param string $format
     *   Desired representation: "adl", "xml" or "mindmap" (this server accepts case-insensitive).
     *   Defaults to "adl".
     *
     * @return TextContent
     *   The archetype definition code block as MCP text content in the chosen format.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (invalid CID, unsupported format mapping, upstream error).
     */
    #[McpTool(name: 'ckm_archetype_get')]
    public function archetypeGet(string $identifier, string $format = 'adl'): TextContent
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $identifier = trim($identifier);
        $cid = null;
        try {
            $archetypeFormat = Map::archetypeFormat($format);
            $contentType = Map::contentType($archetypeFormat);
            // If the identifier is an archetype-id, then resolve it to the corresponding CID
            if (str_contains($identifier, 'openEHR-')) {
                try {
                    $response = $this->apiClient->get("v1/archetypes/citeable-identifier/$identifier");
                    $cid = ($response->getStatusCode() === 200) ? $response->getBody()->getContents() : null;
                } catch (ClientExceptionInterface $e) {
                    $this->logger->error('Failed to resolve CID identifier', ['error' => $e->getMessage(), 'identifier' => $identifier]);
                }
            }
            // if CID is not yet resolved, then normalize the identifier to a CID
            $cid = $cid ?? preg_replace('/[^\d.]/', '-', $identifier);
            // retrieve the archetype definition
            $response = $this->apiClient->get("v1/archetypes/{$cid}/{$archetypeFormat}", [
                RequestOptions::HEADERS => [
                    'Accept' => $contentType,
                ],
            ]);
            $data = trim($response->getBody()->getContents());
            $this->logger->info('CKM Archetype retrieved successfully', ['cid' => $cid, 'format' => $archetypeFormat, 'status' => $response->getStatusCode()]);
            return TextContent::code($data);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to retrieve the CKM Archetype', ['error' => $e->getMessage(), 'identifier' => $identifier, 'cid' => $cid, 'format' => $format]);
            throw new \RuntimeException('Failed to retrieve the CKM Archetype: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search openEHR Templates in the Clinical Knowledge Manager (CKM).
     *
     * Use this tool when you need to *discover* candidate templates (OET or OPT) before fetching full definitions.
     * It is typically the first step in an LLM workflow:
     * 1) Search by a domain keyword (e.g. "vital signs", "discharge summary")
     * 2) Inspect the returned metadata for plausible matches
     * 3) Take the returned CKM identifier (CID) and call `ckm_template_get` tool to retrieve the content.
     *
     * Important notes for MCP/LLM clients:
     * - This tool performs a keyword search on CKM "main data" of Templates.
     * - If you need deterministic fields for downstream reasoning,
     *      treat this as a discovery step and rely on the `ckm_template_get` tool for authoritative content.
     *
     * @param string $keyword
     *   A human-oriented search string, one or multiple words, wildcards `*` supported.
     *
     * @param int $limit
     *   The maximum number of templates returned. Defaults to 10.
     *
     * @param int $offset
     *   The offset into the result set, for paging. Defaults to 0.
     *
     * @param bool $requireAllSearchWords
     *   If multiple search words are supplied, should ALL words be required (`true`), or is ANY sufficient (`false`).
     *   Defaults to `true`.
     *
     * @return array<array<string,mixed>>
     *   A list of CKM Template metadata entries.
     *   Entries usually include a CID identifier.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (network error, upstream outage, invalid response).
     */
    #[McpTool(name: 'ckm_template_search')]
    public function templateSearch(string $keyword, int $limit = 10, int $offset = 0, bool $requireAllSearchWords = true): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        try {
            $response = $this->apiClient->get('v1/templates', [
                RequestOptions::QUERY => [
                    'search-text' => $keyword,
                    'size' => $limit,
                    'offset' => $offset,
                    'template-type' => 'NORMAL',
                    'restrict-search-to-main-data' => 'true',
                    'require-all-search-words' => $requireAllSearchWords ? 'true' : 'false',
                ],
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Found CKM Templates', ['keyword' => $keyword, 'count' => is_countable($data) ? count($data) : null]);
            return $data;
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Templates', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to search for CKM Templates: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve the definition of an identified CKM Template, serialized in a specific format.
     *
     * Use this tool after you have identified a candidate template (usually from the `ckm_template_search` tool).
     *
     * Returned content and formats:
     * - "oet": Template source (XML) - the unflattened version.
     * - "opt": Operational Template (XML) - the flattened version of the template.
     *
     * @param string $identifier
     *   CID identifier (e.g. "1013.26.244").
     *
     * @param string $format
     *   Desired representation: "oet", "opt".
     *   Defaults to "oet".
     *
     * @return TextContent
     *   The template definition content.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails.
     */
    #[McpTool(name: 'ckm_template_get')]
    public function templateGet(string $identifier, string $format = 'opt'): TextContent
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $identifier = trim($identifier);
        $cid = $identifier; // Simplification, CKM templates usually use CID or template name in URL

        try {
            // Mapping format to CKM expected format string and content-type
            $templateFormat = Map::templateFormat($format);
            $contentType = Map::contentType($templateFormat);

            $response = $this->apiClient->get("v1/templates/{$cid}/{$templateFormat}", [
                RequestOptions::HEADERS => [
                    'Accept' => $contentType,
                ],
            ]);
            $data = trim($response->getBody()->getContents());
            $this->logger->info('CKM Template retrieved successfully', ['cid' => $cid, 'format' => $templateFormat, 'status' => $response->getStatusCode()]);
            return TextContent::code($data);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to retrieve the CKM Template', ['error' => $e->getMessage(), 'identifier' => $identifier, 'format' => $format]);
            throw new \RuntimeException('Failed to retrieve the CKM Template: ' . $e->getMessage(), 0, $e);
        }
    }
}
