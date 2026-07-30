<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\CkmClient;
use Cadasto\OpenEHR\MCP\Assistant\Helpers\Map;
use GuzzleHttp\RequestOptions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\ToolAnnotations;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

final readonly class CkmService
{
    private const int DEFAULT_MAX_RESULTS = 20;
    /** Caps the number of items *returned* to the caller after re-ranking. */
    private const int MAX_RESULTS_LIMIT = 50;
    /** Fetch a wider candidate window than requested so re-ranking can surface concepts CKM ranked low. */
    private const float FETCH_SIZE_MULTIPLIER = 3.0;
    /**
     * Floor on the API fetch window, so a small `maxResults` still scores a usefully wide
     * candidate set. Without it the window scaled purely with `maxResults`, which made the
     * ranking depend on how many results the caller asked for: a generic keyword scored
     * against 9 candidates (`maxResults` 3) surfaced a different top 3 than the same keyword
     * scored against 30 (`maxResults` 10). At 30 the whole 1..10 range shares one window, so
     * results are stable across it. Above 10 the multiplier takes over and the window widens
     * again, so top-N is not a strict prefix of top-M across that boundary — a deliberate
     * trade-off against always fetching FETCH_SIZE_LIMIT rows.
     */
    private const int FETCH_SIZE_MIN = 30;
    /** Hard cap on the API fetch window, decoupled from MAX_RESULTS_LIMIT (the returned-count cap). */
    private const int FETCH_SIZE_LIMIT = 60;

    // Scoring weights (wider scale for clearer ranking)
    private const int SCORE_ARCHETYPE_ID_MATCH = 100;
    private const int SCORE_NAME_MATCH = 50;
    private const int SCORE_PROJECT_NAME_MATCH = 25;
    private const int SCORE_PROJECT_BUCKET = 10;
    private const int SCORE_ALL_KEYWORDS_BONUS = 80;
    /** Bonus when the full normalized query equals the candidate's display name or its concept-from-id. */
    private const int SCORE_EXACT_CONCEPT_BONUS = 120;
    private const int SCORE_STATUS_PUBLISHED = 75;
    private const int SCORE_STATUS_TEAMREVIEW = 25;
    private const int SCORE_STATUS_DRAFT = -25;
    private const int SCORE_STATUS_INITIAL = -75;
    /** Extra penalty per year since last modification (only for DRAFT/INITIAL). */
    private const int SCORE_PENALTY_PER_YEAR_SINCE_MODIFICATION = 5;
    /** Extra penalty per year since creation (only for DRAFT/INITIAL). */
    private const int SCORE_PENALTY_PER_YEAR_SINCE_CREATION = 1;

    public function __construct(
        private CkmClient $apiClient,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * Search and discover candidate openEHR Archetypes in the Clinical Knowledge Manager (CKM).
     *
     * Use this tool when you need to *discover* candidate archetypes before fetching their full definitions.
     * It is typically the first step in an LLM workflow:
     * 1) Search by a domain keyword (e.g. "blood pressure", "medication", "problem list")
     * 2) Inspect the returned metadata for plausible matches
     * 3) Take the returned CKM identifier (CID) and call `ckm_archetype_get` tool to retrieve the full archetype definition.
     *
     * @param string $keyword
     *   Query search string (one or multiple words); wildcards `*` supported; prefer meaningful clinical terms over internal codes, e.g. "blood pressure", "medication", "diabetes", "body weight".
     *
     * @param int $maxResults
     *   The maximum number of result items to be returned; defaults to 20 and must be between 1 and 50 (values outside that range are rejected, not clamped).
     *
     * @param bool $requireAllSearchWords
     *   Determines if the search should match all provided keywords (true) or any of them (false); defaults to true.
     *
     * @param string $rmClass
     *   Optional RM class filter on the archetype-id (e.g. `COMPOSITION`, `OBSERVATION`, `CLUSTER`); case-insensitive; empty (default) = no filter.
     *
     * @return array{items: list<array<string, string|int>>, total: int}
     *   A list of CKM Archetype metadata entries — each with a CID identifier, and usually
     *   archetypeId, display name, status and other descriptive fields — plus `total` (see
     *   the `total` note in the outputSchema).
     *
     * @throws ToolCallException
     *   If the CKM API request fails (network error, upstream outage, invalid response), or
     *   if $rmClass is provided but is malformed (it is shape-checked, not validated against
     *   the RM class list). `ToolCallException` specifically: `CallToolHandler` preserves its
     *   message as a tool-level error result the model can act on, and replaces every other
     *   throwable with a generic protocol error most clients never surface.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'ckm_archetype_search',
        title: 'Search CKM archetypes',
        annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: true),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of CKM Archetypes matching the search criteria',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['cid', 'score'],
                        'properties' => [
                            'cid' => ['type' => 'string', 'description' => 'CKM Archetype identifier'],
                            'archetypeId' => ['type' => 'string'],
                            'name' => ['type' => 'string', 'description' => 'Archetype display or concept name'],
                            'projectName' => ['type' => 'string', 'description' => 'Project name where the Archetype belongs to'],
                            'status' => ['type' => 'string'],
                            'revision' => ['type' => 'string'],
                            'creationTime' => ['type' => 'string', 'description' => 'ISO 8601 or epoch-ms string from CKM'],
                            'modificationTime' => ['type' => 'string', 'description' => 'ISO 8601 or epoch-ms string from CKM'],
                            'score' => ['type' => 'integer', 'description' => 'Score of the match, based on the search keywords'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => "Upstream CKM match count for the keyword search (`X-Total-Count`), falling back to the number of matches when that header is absent or malformed. When `rmClass` is supplied the count reflects the locally filtered matches instead. May exceed items.length."],
            ],
        ]
    )]
    public function archetypeSearch(
        string $keyword,
        #[Schema(minimum: 1, maximum: self::MAX_RESULTS_LIMIT)]
        int $maxResults = self::DEFAULT_MAX_RESULTS,
        bool $requireAllSearchWords = true,
        string $rmClass = '',
    ): array {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $fetchSize = min(
            self::FETCH_SIZE_LIMIT,
            max(self::FETCH_SIZE_MIN, (int) ceil($maxResults * self::FETCH_SIZE_MULTIPLIER)),
        );
        $rmClass = $this->normalizeRmClassFilter($rmClass);
        try {
            $response = $this->apiClient->get('v1/archetypes', [
                RequestOptions::QUERY => [
                    'search-text' => $keyword,
                    'size' => $fetchSize,
                    'offset' => 0,
                    'restrict-search-to-main-data' => 'true',
                    'require-all-search-words' => $requireAllSearchWords ? 'true' : 'false',
                    'sort-key' => 'RELEVANCE',
                ],
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertCkmRowList($data, 'archetype');
            $this->logger->info('Found CKM Archetypes', ['keyword' => $keyword, 'count' => count($data)]);

            // Map each item to a simpler structure and score
            $data = array_map(function (array $item) use ($keyword): array {
                $new = [
                    // `cid` is declared `required` in the outputSchema and is the handle
                    // `ckm_archetype_get` needs, so it is never filtered out below — an
                    // upstream row without one yields '' rather than a missing key.
                    'cid' => $this->ckmString($item['cid'] ?? null, 'cid') ?? '',
                    'archetypeId' => $this->ckmString($item['resourceMainId'] ?? null, 'resourceMainId'),
                    'name' => $this->ckmString($item['resourceMainDisplayName'] ?? null, 'resourceMainDisplayName'),
                    'projectName' => $this->ckmString($item['projectName'] ?? null, 'projectName'),
                    'status' => $this->ckmString($item['status'] ?? null, 'status'),
                    'revision' => $this->ckmString($item['revision'] ?? null, 'revision'),
                    'creationTime' => $this->ckmString($item['creationTime'] ?? null, 'creationTime'),
                    'modificationTime' => $this->ckmString($item['modificationTime'] ?? $item['creationTime'] ?? null, 'modificationTime'),
                    'score' => $this->scoreArchetypeItem($item, $keyword),
                ];
                if ($new['cid'] === '') {
                    $this->logger->warning('CKM archetype row has no cid; it cannot be retrieved.', ['name' => $new['name']]);
                }

                return array_filter($new, fn($v) => $v !== null);
            }, $data);

            // Optional RM-class filter: keep only items whose archetype-id RM-class segment matches.
            if ($rmClass !== '') {
                $data = array_values(array_filter(
                    $data,
                    fn(array $item): bool => $this->archetypeRmClass($item['archetypeId'] ?? null) === $rmClass
                ));
            }

            // Sort by score (highest first), then slice to requested maxResults
            usort($data, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $matchCount = count($data);
            $data = array_slice($data, 0, $maxResults);

            return [
                'items' => $data,
                'total' => $this->resolveTotal($response->getHeaderLine('X-Total-Count'), $matchCount, $rmClass !== ''),
            ];
        } catch (\JsonException $e) {
            $this->logger->error('Failed to decode CKM Archetype response', ['error' => $e->getMessage()]);
            throw new ToolCallException('Failed to decode CKM Archetype response: ' . $e->getMessage(), previous: $e);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Archetypes', ['error' => $e->getMessage()]);
            throw new ToolCallException('Failed to search for CKM Archetypes: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Retrieve the full definition of an Archetype from CKM, serialized in a specified format.
     *
     * Use this tool after you have identified a candidate archetype (usually from the `ckm_archetype_search` tool),
     * or when you already know the archetype CID (e.g. "1013.1.7850") or archetype-id (e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v1").
     * It fetches the *full archetype definition* from CKM so an LLM can process it according to relevant guides, e.g.:
     * - understand the structure and semantics of nodes/attributes,
     * - extract constraints, translations, and terminology bindings,
     * - generate templates or implementation guidance,
     * - or cite the definition content in downstream reasoning.
     * When guides are not yet available, use the `guide_search` tool to discover them applicable to the archetype and the user request.
     * Returned content and formats:
     * - "adl": ADL source text (best for detailed archetype semantics and constraints)
     * - "xml": XML representation (similar to "adl", but helpful when consuming via XML tooling)
     * - "mindmap": mindmap form (useful for quick visual overview)
     *
     * @param string $identifier
     *   Archetype CID identifier (e.g. "1013.1.7850") or archetype-id (e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v1").
     *
     * @param string $format
     *   Desired representation (case-insensitive); see the returned content/formats above for what each value means. Defaults to "adl".
     *
     * @return TextContent
     *   The Archetype definition in the chosen format in a text content code block.
     *
     * @throws ToolCallException
     *   If the CKM API request fails (invalid CID, unsupported format mapping, upstream error).
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'ckm_archetype_get',
        title: 'Get CKM archetype',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true)
    )]
    public function archetypeGet(
        string $identifier,
        #[Schema(enum: ['adl', 'xml', 'mindmap'])]
        string $format = 'adl',
    ): TextContent
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $identifier = trim($identifier);
        $cid = null;
        try {
            $archetypeFormat = Map::archetypeFormat($format);
            $contentType = Map::contentType($archetypeFormat);
            // An archetype-id must be resolved to a CID; there is no meaningful fallback.
            // The previous code replaced every non-digit with `-`, turning
            // `openEHR-EHR-OBSERVATION.blood_pressure.v1` into
            // `-------------------.-------------.--`, requested *that*, and reported the
            // resulting 404 as "Failed to retrieve the CKM Archetype" — blaming the
            // archetype rather than the identifier resolution that actually failed.
            if (str_contains($identifier, 'openEHR-')) {
                $cid = $this->resolveCitableIdentifier($identifier);
            } elseif ($this->looksLikeCid($identifier)) {
                $cid = $identifier;
            }

            if ($cid === null) {
                throw new ToolCallException(sprintf(
                    'Could not resolve "%s" to a CKM citeable identifier (CID). Pass a CID (digits and dots, as returned by ckm_archetype_search) or a full archetype-id containing "openEHR-".',
                    $identifier,
                ));
            }

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
            throw new ToolCallException('Failed to retrieve the CKM Archetype: ' . $e->getMessage(), previous: $e);
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
     *   Query search string, one or multiple words, wildcards `*` supported.
     *
     * @param int $maxResults
     *   The maximum number of result items to be returned; defaults to 20 and must be between 1 and 50 (values outside that range are rejected, not clamped).
     *
     * @param bool $requireAllSearchWords
     *   Determines if the search should match all provided keywords (true) or any of them (false); defaults to true.
     *
     * @return array<string,mixed>
     *   A list of CKM Template metadata entries.
     *   Entries usually include a Template CID identifier, display name, status, and other descriptive fields.
     *
     * @throws ToolCallException
     *   If the CKM API request fails (network error, upstream outage, invalid response).
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'ckm_template_search',
        title: 'Search CKM templates',
        annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: true),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of CKM Templates matching the search criteria',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['cid', 'score'],
                        'properties' => [
                            'cid' => ['type' => 'string', 'description' => 'CKM Template identifier'],
                            'name' => ['type' => 'string', 'description' => 'Template display name'],
                            'projectName' => ['type' => 'string', 'description' => 'Project name where the Template belongs to'],
                            'status' => ['type' => 'string'],
                            'version' => ['type' => 'string'],
                            'creationTime' => ['type' => 'string', 'description' => 'ISO 8601 or epoch-ms string from CKM'],
                            'modificationTime' => ['type' => 'string', 'description' => 'ISO 8601 or epoch-ms string from CKM'],
                            'score' => ['type' => 'integer', 'description' => 'Score of the match, based on the search keywords'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => "Upstream CKM match count for the keyword search (`X-Total-Count`), falling back to the number of matches when that header is absent or malformed. May exceed items.length."],
            ],
        ]
    )]
    public function templateSearch(
        string $keyword,
        #[Schema(minimum: 1, maximum: self::MAX_RESULTS_LIMIT)]
        int $maxResults = self::DEFAULT_MAX_RESULTS,
        bool $requireAllSearchWords = true,
    ): array {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $fetchSize = min(
            self::FETCH_SIZE_LIMIT,
            max(self::FETCH_SIZE_MIN, (int) ceil($maxResults * self::FETCH_SIZE_MULTIPLIER)),
        );
        try {
            $response = $this->apiClient->get('v1/templates', [
                RequestOptions::QUERY => [
                    'search-text' => $keyword,
                    'size' => $fetchSize,
                    'offset' => 0,
                    'template-type' => 'NORMAL',
                    'restrict-search-to-main-data' => 'true',
                    'require-all-search-words' => $requireAllSearchWords ? 'true' : 'false',
                    'sort-key' => 'RELEVANCE',
                ],
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertCkmRowList($data, 'template');
            $this->logger->info('Found CKM Templates', ['keyword' => $keyword, 'count' => count($data)]);

            // Map each item to a simpler structure and score
            $data = array_map(function (array $item) use ($keyword): array {
                $new = [
                    // See the `cid` note in archetypeSearch(): declared `required`, so it
                    // is always present even when the upstream row omits it.
                    'cid' => $this->ckmString($item['cid'] ?? null, 'cid') ?? '',
                    'name' => $this->ckmString($item['resourceMainDisplayName'] ?? null, 'resourceMainDisplayName'),
                    'projectName' => $this->ckmString($item['projectName'] ?? null, 'projectName'),
                    'status' => $this->ckmString($item['status'] ?? null, 'status'),
                    'version' => $this->ckmString($item['versionAsset'] ?? null, 'versionAsset'),
                    'creationTime' => $this->ckmString($item['creationTime'] ?? null, 'creationTime'),
                    'modificationTime' => $this->ckmString($item['modificationTime'] ?? $item['creationTime'] ?? null, 'modificationTime'),
                    'score' => $this->scoreTemplateItem($item, $keyword),
                ];
                if ($new['cid'] === '') {
                    $this->logger->warning('CKM template row has no cid; it cannot be retrieved.', ['name' => $new['name']]);
                }

                return array_filter($new, fn($v) => $v !== null);
            }, $data);

            // Sort by score (highest first), then slice to requested maxResults
            usort($data, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $matchCount = count($data);
            $data = array_slice($data, 0, $maxResults);

            return [
                'items' => $data,
                'total' => $this->resolveTotal($response->getHeaderLine('X-Total-Count'), $matchCount, false),
            ];
        } catch (\JsonException $e) {
            $this->logger->error('Failed to decode CKM Template response', ['error' => $e->getMessage()]);
            throw new ToolCallException('Failed to decode CKM Template response: ' . $e->getMessage(), previous: $e);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to search for CKM Templates', ['error' => $e->getMessage()]);
            throw new ToolCallException('Failed to search for CKM Templates: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Retrieve the full definition of an openEHR Template (OET or OPT) from CKM by its identifier, serialized in a specified format.
     *
     * Use this tool to *retrieve* an openEHR Template from CKM after you have identified a candidate template (usually from the `ckm_template_search` tool),
     * or when you already know the template CID (e.g. "1013.26.244").
     * It fetches the *full Template definition* from CKM so an LLM can process it according to relevant guides, e.g.:
     * - understand the structure and semantics of nodes/attributes,
     * - extract constraints, translations, and terminology bindings,
     * - or cite the definition content in downstream reasoning.
     * When guides are not yet available, use the `guide_search` tool to discover them applicable to the Template and the user request.
     * Returned content and formats:
     * - "oet": Template source (XML) - the unflattened version (design-time template).
     * - "opt": Operational Template (XML) - the flattened version of the Template, containing all archetype constraints.
     *
     * @param string $identifier
     *   Template CID identifier (e.g. "1013.26.244").
     *
     * @param string $format
     *   Desired representation; see the returned content/formats above for what each value means. Defaults to "oet".
     *
     * @return TextContent
     *   The Template definition in the chosen format in a text content code block.
     *
     * @throws ToolCallException
     *   If the CKM API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'ckm_template_get',
        title: 'Get CKM template',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true)
    )]
    public function templateGet(
        string $identifier,
        #[Schema(enum: ['oet', 'opt'])]
        string $format = 'oet',
    ): TextContent
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
            throw new ToolCallException('Failed to retrieve the CKM Template: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Score one archetype search result (archetypeId, name, projectName, status, match quality, all-keywords bonus).
     *
     * @param array<string, mixed> $item Raw CKM API item (resourceMainId, resourceMainDisplayName, projectName, status, etc.)
     */
    private function scoreArchetypeItem(array $item, string $keyword): int
    {
        // Narrowed here rather than trusted: CKM is external, and every helper below
        // takes `?string`, so an unexpected int/bool/object would raise a TypeError.
        $archetypeId = $this->ckmString($item['resourceMainId'] ?? null, 'resourceMainId');
        $name = $this->ckmString($item['resourceMainDisplayName'] ?? null, 'resourceMainDisplayName');
        $projectName = $this->ckmString($item['projectName'] ?? null, 'projectName');
        $keywords = array_filter(explode(' ', trim($keyword)));
        $score = 0;
        $keywordsMatched = 0;
        foreach ($keywords as $k) {
            $sId = $this->scoreFieldMatch($k, $archetypeId, self::SCORE_ARCHETYPE_ID_MATCH);
            $sName = $this->scoreFieldMatch($k, $name, self::SCORE_NAME_MATCH);
            $sProject = $this->scoreFieldMatch($k, $projectName, self::SCORE_PROJECT_NAME_MATCH);
            $score += $sId + $sName + $sProject;
            if ($sId > 0 || $sName > 0 || $sProject > 0) {
                $keywordsMatched++;
            }
        }
        if ($keywords !== [] && $keywordsMatched === count($keywords)) {
            $score += self::SCORE_ALL_KEYWORDS_BONUS;
        }
        // Exact-concept boost: query equals the display name or the concept token derived from the archetype-id.
        if ($this->matchesExactConcept($keyword, [$name, $this->conceptFromArchetypeId($archetypeId)])) {
            $score += self::SCORE_EXACT_CONCEPT_BONUS;
        }
        $score += $this->projectBucketBonus($projectName);
        $status = $this->ckmString($item['status'] ?? null, 'status');
        $creationTime = $this->ckmString($item['creationTime'] ?? null, 'creationTime');
        $score += $this->scoreStatus($status);
        $score += $this->agePenalty(
            $this->ckmString($item['modificationTime'] ?? null, 'modificationTime') ?? $creationTime,
            $creationTime,
            $status
        );
        return $score;
    }

    /**
     * Score one template search result (name, projectName, status, match quality, all-keywords bonus).
     *
     * @param array<string, mixed> $item Raw CKM API item (resourceMainDisplayName, projectName, status, etc.)
     */
    private function scoreTemplateItem(array $item, string $keyword): int
    {
        // See the note in scoreArchetypeItem(): narrow before scoring.
        $name = $this->ckmString($item['resourceMainDisplayName'] ?? null, 'resourceMainDisplayName');
        $projectName = $this->ckmString($item['projectName'] ?? null, 'projectName');
        $keywords = array_filter(explode(' ', trim($keyword)));
        $score = 0;
        $keywordsMatched = 0;
        foreach ($keywords as $k) {
            $sName = $this->scoreFieldMatch($k, $name, self::SCORE_NAME_MATCH);
            $sProject = $this->scoreFieldMatch($k, $projectName, self::SCORE_PROJECT_NAME_MATCH);
            $score += $sName + $sProject;
            if ($sName > 0 || $sProject > 0) {
                $keywordsMatched++;
            }
        }
        if ($keywords !== [] && $keywordsMatched === count($keywords)) {
            $score += self::SCORE_ALL_KEYWORDS_BONUS;
        }
        // Exact-concept boost: templates have no archetype-id, so match the display name only.
        if ($this->matchesExactConcept($keyword, [$name])) {
            $score += self::SCORE_EXACT_CONCEPT_BONUS;
        }
        $score += $this->projectBucketBonus($projectName);
        $status = $this->ckmString($item['status'] ?? null, 'status');
        $creationTime = $this->ckmString($item['creationTime'] ?? null, 'creationTime');
        $score += $this->scoreStatus($status);
        $score += $this->agePenalty(
            $this->ckmString($item['modificationTime'] ?? null, 'modificationTime') ?? $creationTime,
            $creationTime,
            $status
        );
        return $score;
    }

    /**
     * True when the normalized keyword equals any candidate (with SOAP↔SOEP scoring aliases applied).
     *
     * @param array<int, mixed> $candidates Display name and/or concept-from-id values.
     */
    private function matchesExactConcept(string $keyword, array $candidates): bool
    {
        $needle = $this->normalizeConcept($keyword);
        if ($needle === '') {
            return false;
        }
        $needleAlias = $this->applyScoringAlias($needle);
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            $value = $this->normalizeConcept($candidate);
            if ($value === '') {
                continue;
            }
            if ($value === $needle || $this->applyScoringAlias($value) === $needleAlias) {
                return true;
            }
        }
        return false;
    }

    /** Concept token from an archetype-id: `…COMPOSITION.health_summary.v1` → `health summary`; null if not derivable. */
    private function conceptFromArchetypeId(?string $archetypeId): ?string
    {
        if ($archetypeId === null || $archetypeId === '') {
            return null;
        }
        $segments = explode('.', $archetypeId);
        if (count($segments) < 3) {
            return null;
        }
        $concept = $segments[1];
        if ($concept === '') {
            return null;
        }
        return str_replace('_', ' ', $concept);
    }

    /** Lowercase, trim, and collapse internal whitespace for concept/name comparison. */
    private function normalizeConcept(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return (string) preg_replace('/\s+/', ' ', $value);
    }

    /** Minimal scoring aliases (SOAP↔SOEP) for matching only — never sent to the CKM API query. Extend as needed. */
    private function applyScoringAlias(string $value): string
    {
        $aliases = [
            'soap' => 'soap',
            'soep' => 'soap',
        ];
        return $aliases[$value] ?? $value;
    }

    /**
     * Resolve a full archetype-id to its CKM citeable identifier, or null if it cannot be.
     *
     * Returns null (rather than a guess) for every failure mode, so the caller can name the
     * resolution step in the error instead of issuing a request built from a mangled path.
     */
    private function resolveCitableIdentifier(string $archetypeId): ?string
    {
        try {
            $response = $this->apiClient->get("v1/archetypes/citeable-identifier/$archetypeId");
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Failed to resolve CID identifier', ['error' => $e->getMessage(), 'identifier' => $archetypeId]);

            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->error('CID resolution returned a non-200 status', [
                'identifier' => $archetypeId,
                'status' => $response->getStatusCode(),
            ]);

            return null;
        }

        // A 200 with an empty body used to survive the `??` fallback as '' and produce the
        // request path `v1/archetypes//adl`. Quotes are trimmed because the endpoint may
        // return the CID as a JSON string rather than bare text.
        $cid = trim($response->getBody()->getContents(), " \t\n\r\0\x0B\"'");
        if (!$this->looksLikeCid($cid)) {
            $this->logger->error('CID resolution returned an unexpected body', [
                'identifier' => $archetypeId,
                'body' => $cid,
            ]);

            return null;
        }

        return $cid;
    }

    /** A CKM citeable identifier is a dot-separated numeric path, e.g. `1249.32.1234`. */
    private function looksLikeCid(string $value): bool
    {
        return preg_match('/^\d+(\.\d+)*$/', $value) === 1;
    }

    /** Validate/uppercase an RM-class filter token; throws ToolCallException on a malformed non-empty value. */
    private function normalizeRmClassFilter(string $rmClass): string
    {
        $rmClass = strtoupper(trim($rmClass));
        if ($rmClass === '') {
            return '';
        }
        if (preg_match('/^[A-Z][A-Z_]*$/', $rmClass) !== 1) {
            throw new ToolCallException(sprintf('Invalid RM class filter: "%s".', $rmClass));
        }
        return $rmClass;
    }

    /** RM-class segment of an archetype-id: `…-COMPOSITION.x.v1` → `COMPOSITION`; null if not parseable. */
    private function archetypeRmClass(?string $archetypeId): ?string
    {
        if ($archetypeId === null || $archetypeId === '') {
            return null;
        }
        $head = strstr($archetypeId, '.', true);
        if ($head === false || $head === '') {
            return null;
        }
        $lastDash = strrpos($head, '-');
        $token = $lastDash === false ? $head : substr($head, $lastDash + 1);
        if ($token === '' || preg_match('/^[A-Za-z][A-Za-z_]*$/', $token) !== 1) {
            return null;
        }
        return strtoupper($token);
    }

    /**
     * Extra penalty for DRAFT/INITIAL items that are old: per year since last modification (-5), per year since creation (-1).
     */
    private function agePenalty(?string $modificationTime, ?string $creationTime, ?string $status): int
    {
        if ($status === null) {
            return 0;
        }
        $statusUpper = strtoupper($status);
        if ($statusUpper !== 'DRAFT' && $statusUpper !== 'REVIEWSUSPENDED' && $statusUpper !== 'INITIAL') {
            return 0;
        }
        $now = new \DateTimeImmutable('now');
        $yearsSinceMod = $this->yearsSince($modificationTime, $now);
        $yearsSinceCreation = $this->yearsSince($creationTime, $now);
        $penalty = 0;
        $penalty -= self::SCORE_PENALTY_PER_YEAR_SINCE_MODIFICATION * $yearsSinceMod;
        $penalty -= self::SCORE_PENALTY_PER_YEAR_SINCE_CREATION * $yearsSinceCreation;
        return $penalty;
    }

    /**
     * Parse CKM date string (ISO 8601 or numeric ms) and return full years since reference time.
     *
     * @return int Zero if parse fails or date is in the future.
     */
    private function yearsSince(?string $dateString, \DateTimeImmutable $reference): int
    {
        if ($dateString === null || $dateString === '') {
            return 0;
        }
        $dt = null;
        if (is_numeric($dateString)) {
            $ts = (int) $dateString;
            if ($ts > 2_000_000_000) {
                $ts = (int) floor($ts / 1000);
            }
            $dt = \DateTimeImmutable::createFromFormat('U', (string) $ts);
        }
        if ($dt === null || $dt === false) {
            $dt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $dateString)
                ?: \DateTimeImmutable::createFromFormat(\DateTimeInterface::ISO8601, $dateString)
                ?: @\DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u\Z', $dateString)
                ?: @\DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $dateString);
        }
        if (!$dt instanceof \DateTimeImmutable) {
            return 0;
        }
        $interval = $reference->diff($dt);
        if ($interval->invert === 0) {
            return 0;
        }
        return (int) $interval->y;
    }

    /**
     * Assert that a decoded CKM search payload is a flat list of row objects.
     *
     * `is_array()` alone accepted two shapes that then failed far from the cause. A
     * pagination envelope (`{"content": [...]}` — the most common way a REST response
     * drifts) passed the check and handed `array_map` the inner list as a single "row",
     * yielding one bogus schema-conformant item with an empty `cid`. Add a sibling scalar
     * (`{"totalElements": 482, "content": [...]}`) and the typed closure raised a TypeError
     * that neither catch arm here matches, surfacing as a generic internal error.
     *
     * @phpstan-assert list<array<string, mixed>> $data
     */
    private function assertCkmRowList(mixed $data, string $kind): void
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw new ToolCallException(sprintf(
                'Unexpected CKM %s response payload: expected a JSON array of rows, got %s.',
                $kind,
                is_array($data) ? 'an object' : get_debug_type($data),
            ));
        }

        foreach ($data as $index => $row) {
            if (!is_array($row)) {
                throw new ToolCallException(sprintf(
                    'Unexpected CKM %s response payload: row %d is %s, not an object.',
                    $kind,
                    $index,
                    get_debug_type($row),
                ));
            }
        }
    }

    /**
     * Narrow a value from a CKM payload to a string, preserving "absent" as null so the
     * caller's `array_filter` can still drop optional keys.
     *
     * Every mapped field is declared `type: string` in the outputSchema, and the ageing
     * helpers take `?string` — CKM has been observed to return `creationTime` as either an
     * ISO 8601 string or an epoch-ms value, and an unquoted number would otherwise reach
     * `yearsSince()` as an int and raise a TypeError; the narrowing is defensive either way.
     * Non-scalars collapse to null rather than to "Array".
     *
     * `$field` is mandatory because a silent collapse is the dangerous case: the caller's
     * `array_filter` then drops the key entirely. When that key is `resourceMainId` and a
     * `rmClass` filter is active, `archetypeRmClass(null)` returns null for every row, the
     * filter deletes them all, and the tool reports "0 matching archetypes" with no error —
     * a confident falsehood. One log line per collapse is what makes that diagnosable.
     */
    private function ckmString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        $this->logger->warning('CKM field has an unexpected non-scalar shape and was dropped.', [
            'field' => $field,
            'type' => get_debug_type($value),
        ]);

        return null;
    }

    /**
     * Resolve the `total` companion to a search result set.
     *
     * CKM reports the size of the *keyword* match in `X-Total-Count`. That is the useful
     * figure when it is the only filter, but it says nothing about a filter this server
     * applies locally, and a malformed header must never report fewer matches than the
     * items actually returned.
     */
    private function resolveTotal(string $totalHeader, int $matchCount, bool $locallyFiltered): int
    {
        if ($locallyFiltered) {
            return $matchCount;
        }

        $header = trim($totalHeader);
        if ($header === '' || ctype_digit($header) === false) {
            return $matchCount;
        }

        return max((int) $header, $matchCount);
    }

    /**
     * Score a single keyword against a field: word-boundary match = full score, substring = half, else 0.
     */
    private function scoreFieldMatch(string $keyword, ?string $fieldValue, int $fullScore): int
    {
        if ($fieldValue === null || $fieldValue === '') {
            return 0;
        }
        $quoted = preg_quote($keyword, '/');
        if (preg_match('/\b' . $quoted . '\b/ui', $fieldValue) === 1) {
            return $fullScore;
        }
        if (mb_stripos($fieldValue, $keyword) !== false) {
            return (int) ($fullScore / 2);
        }
        return 0;
    }

    private function scoreStatus(?string $status): int
    {
        if ($status === null) {
            return 0;
        }
        return match (strtoupper($status)) {
            'PUBLISHED' => self::SCORE_STATUS_PUBLISHED,
            'TEAMREVIEW' => self::SCORE_STATUS_TEAMREVIEW,
            'DRAFT', 'REVIEWSUSPENDED' => self::SCORE_STATUS_DRAFT,
            'INITIAL' => self::SCORE_STATUS_INITIAL,
            default => 0,
        };
    }

    private function projectBucketBonus(?string $projectName): int
    {
        if ($projectName === null) {
            return 0;
        }
        return in_array(strtolower($projectName), ['common resources', 'structural archetypes'], true)
            ? self::SCORE_PROJECT_BUCKET
            : 0;
    }
}
