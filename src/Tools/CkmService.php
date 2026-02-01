<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Helpers\Map;
use GuzzleHttp\RequestOptions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\ToolAnnotations;
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
     * Search for and discover candidate openEHR Archetypes in the Clinical Knowledge Manager (CKM) matching a given criteria.
     *
     * Use this tool when you need to *discover* candidate archetypes before fetching full definitions.
     * It is typically the first step in an LLM workflow:
     * 1) Search by a domain keyword (e.g. "blood pressure", "medication", "problem list")
     * 2) Inspect the returned metadata for plausible matches
     * 3) Take the returned CKM identifier (CID) and call `ckm_archetype_get` tool to retrieve the full archetype definition.
     *
     * @param string $keyword
     *   A human-oriented search string (one or multiple words); wildcards `*` supported; prefer meaningful clinical terms over internal codes, e.g. "blood pressure", "medication", "diabetes", "body weight".
     *
     * @param int $limit
     *   The maximum number of Archetypes returned in the call; defaults to 20.
     *
     * @param int $offset
     *   The offset into the result set, for paging; defaults to 0.
     *
     * @param bool $requireAllSearchWords
     *   Determines if the search should match all provided keywords (true) or any of them (false); defaults to true.
     *
     * @return array<string,mixed>
     *   A list of CKM Archetype metadata entries.
     *   Entries usually include a CID identifier, archetypeId, display name, status, and other descriptive fields.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (network error, upstream outage, invalid response).
     */
    #[McpTool(
        name: 'ckm_archetype_search',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of CKM Archetypes matching the search criteria',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'cid' => ['type' => 'string', 'description' => 'CKM Archetype identifier'],
                            'archetypeId' => ['type' => 'string'],
                            'name' => ['type' => 'string', 'description' => 'Archetype display or concept name'],
                            'projectName' => ['type' => 'string', 'description' => 'Project name where the Archetype belongs to'],
                            'status' => ['type' => 'string'],
                            'revision' => ['type' => 'string'],
                            'creationTime' => ['type' => 'string'],
                            'modificationTime' => ['type' => 'string'],
                            'score' => ['type' => 'integer', 'description' => 'Score of the match, based on the search keywords'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'description' => 'Total number of Templates found'],
            ],
        ]
    )]
    public function archetypeSearch(string $keyword, int $limit = 20, int $offset = 0, bool $requireAllSearchWords = true): array
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
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new \RuntimeException('Unexpected CKM archetype response payload.');
            }
            $this->logger->info('Found CKM Archetypes', ['keyword' => $keyword, 'count' => count($data)]);

            // Map each item to a simpler structure
            $data = array_map(function ($item) use ($keyword) {
                $new = [
                    'cid' => $item['cid'] ?? null,
                    'archetypeId' => $item['resourceMainId'] ?? null,
                    'name' => $item['resourceMainDisplayName'] ?? null,
                    'projectName' => $item['projectName'] ?? null,
                    'status' => $item['status'] ?? null,
                    'revision' => $item['revision'] ?? null,
                    'creationTime' => $item['creationTime'] ?? null,
                    'modificationTime' => $item['modificationTime'] ?? null,
                    'score' => 0,
                ];
                foreach (explode(' ', trim($keyword)) as $k) {
                    if (isset($new['archetypeId']) && stripos($new['archetypeId'], $k) !== false) {
                        $new['score'] += 4;
                    }
                    if (isset($new['name']) && stripos($new['name'], $k) !== false) {
                        $new['score'] += 3;
                    }
                    if (isset($new['projectName']) && stripos($new['projectName'], $k) !== false) {
                        $new['score'] += 2;
                    }
                }
                if (isset($new['projectName']) && in_array(strtolower($new['projectName']), ['common resources', 'structural archetypes'])) {
                    $new['score'] += 1;
                }
                if (isset($new['status'])) {
                    $new['score'] += match(strtoupper($new['status'])) {
                        'PUBLISHED' => 3,
                        'TEAMREVIEW' => 2,
                        'DRAFT' => 1,
                        default => 0,
                    };
                }
                return $new;
            }, $data);

            // Calculate score for each item and sort
            usort($data, function ($a, $b) {
                // Sort in descending order (highest score first)
                return $b['score'] <=> $a['score'];
            });

            return [
                'items' => $data,
                'total' => (integer)$response->getHeaderLine('X-Total-Count')
            ];
        } catch (\JsonException $e) {
            $this->logger->error('Failed to decode CKM Archetype response', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to decode CKM Archetype response: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Archetypes', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to search for CKM Archetypes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve the full definition of an Archetype from CKM by its identifier, serialized in a specified format.
     *
     * Use this tool after you have identified a candidate archetype (usually from the `ckm_archetype_search` tool),
     * or when you already know the archetype CID (e.g. "1013.1.7850") or archetype-id (e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v1").
     * It fetches the *full archetype definition* from CKM so an LLM can:
     * - understand the structure and semantic or meaning of nodes/attributes,
     * - extract constraints, translations, and terminology bindings,
     * - generate templates or implementation guidance,
     * - or cite the definition content in downstream reasoning.
     * Returned content and formats:
     * - "adl": ADL source text (best for detailed archetype semantics and constraints)
     * - "xml": XML representation (similar to "adl", but helpful when consuming via XML tooling)
     * - "mindmap": mindmap form (useful for quick visual overview)
     *
     * @param string $identifier
     *   Archetype CID identifier (e.g. "1013.1.7850") or archetype-id (e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v1").
     *
     * @param string $format
     *   Desired representation: "adl", "xml" or "mindmap" (case-insensitive); defaults to "adl".
     *
     * @return TextContent
     *   The Archetype definition in the chosen format in a text content code block.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (invalid CID, unsupported format mapping, upstream error).
     */
    #[McpTool(
        name: 'ckm_archetype_get',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
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
     * Search for and discover candidate openEHR Templates in the Clinical Knowledge Manager (CKM) matching a given criteria.
     *
     * Use this tool when you need to *discover* candidate openEHR Templates (OET or OPT) before fetching their full definitions.
     * It is typically the first step in an LLM workflow:
     * 1) Search by one or more domain keywords (e.g. "vital signs", "discharge summary")
     * 2) Inspect the returned metadata for plausible matches
     * 3) Take the returned CKM identifier (CID) and call `ckm_template_get` tool to retrieve the content.
     *
     * @param string $keyword
     *   A human-oriented search string, one or multiple words, wildcards `*` supported.
     *
     * @param int $limit
     *   The maximum number of Templates returned; defaults to 20.
     *
     * @param int $offset
     *   The offset into the result set, for paging; defaults to 0.
     *
     * @param bool $requireAllSearchWords
     *   Determines if the search should match all provided keywords (true) or any of them (false); defaults to true.
     *
     * @return array<string,mixed>
     *   A list of CKM Template metadata entries.
     *   Entries usually include a Template CID identifier, display name, status, and other descriptive fields.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails (network error, upstream outage, invalid response).
     */
    #[McpTool(
        name: 'ckm_template_search',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of CKM Templates matching the search criteria',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'cid' => ['type' => 'string', 'description' => 'CKM Template identifier'],
                            'name' => ['type' => 'string', 'description' => 'Template display name'],
                            'projectName' => ['type' => 'string', 'description' => 'Project name where the Template belongs to'],
                            'status' => ['type' => 'string'],
                            'version' => ['type' => 'string'],
                            'creationTime' => ['type' => 'string'],
                            'modificationTime' => ['type' => 'string'],
                            'score' => ['type' => 'integer', 'description' => 'Score of the match, based on the search keywords'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'description' => 'Total number of Archetypes found'],
            ],
        ]
    )]
    public function templateSearch(string $keyword, int $limit = 20, int $offset = 0, bool $requireAllSearchWords = true): array
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
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new \RuntimeException('Unexpected CKM template response payload.');
            }
            $this->logger->info('Found CKM Templates', ['keyword' => $keyword, 'count' => count($data)]);

            // Map each item to a simpler structure
            $data = array_map(function ($item) use ($keyword) {
                $new = [
                    'cid' => $item['cid'] ?? null,
                    'name' => $item['resourceMainDisplayName'] ?? null,
                    'projectName' => $item['projectName'] ?? null,
                    'status' => $item['status'] ?? null,
                    'version' => $item['versionAsset'] ?? null,
                    'creationTime' => $item['creationTime'] ?? null,
                    'modificationTime' => $item['modificationTime'] ?? null,
                    'score' => 0,
                ];
                foreach (explode(' ', trim($keyword)) as $k) {
                    if (isset($new['name']) && stripos($new['name'], $k) !== false) {
                        $new['score'] += 3;
                    }
                    if (isset($new['projectName']) && stripos($new['projectName'], $k) !== false) {
                        $new['score'] += 2;
                    }
                }
                if (isset($new['projectName']) && in_array(strtolower($new['projectName']), ['common resources', 'structural archetypes'])) {
                    $new['score'] += 1;
                }
                if (isset($new['status'])) {
                    $new['score'] += match(strtoupper($new['status'])) {
                        'PUBLISHED' => 3,
                        'TEAMREVIEW' => 2,
                        'DRAFT' => 1,
                        default => 0,
                    };
                }
                return $new;
            }, $data);

            // Calculate score for each item and sort
            usort($data, function ($a, $b) {
                // Sort in descending order (highest score first)
                return $b['score'] <=> $a['score'];
            });

            return [
                'items' => $data,
                'total' => (integer)$response->getHeaderLine('X-Total-Count')
            ];
        } catch (\JsonException $e) {
            $this->logger->error('Failed to decode CKM Template response', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to decode CKM Template response: ' . $e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Templates', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to search for CKM Templates: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve the full definition of an openEHR Template (OET or OPT) from CKM by its identifier, serialized in a specified format.
     *
     * Use this tool to *retrieve* an openEHR Template from CKM after you have identified a candidate template (usually from the `ckm_template_search` tool),
     * or when you already know the template CID (e.g. "1013.26.244").
     * Returned content and formats:
     * - "oet": Template source (XML) - the unflattened version (design-time template).
     * - "opt": Operational Template (XML) - the flattened version of the Template, containing all archetype constraints.
     *
     * @param string $identifier
     *   Template CID identifier (e.g. "1013.26.244").
     *
     * @param string $format
     *   Desired representation: "oet" (design-time template source), "opt" (flattened operational template, containing all archetype constraints); defaults to "oet".
     *
     * @return TextContent
     *   The Template definition in the chosen format in a text content code block.
     *
     * @throws \RuntimeException
     *   If the CKM API request fails.
     */
    #[McpTool(
        name: 'ckm_template_get',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
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
