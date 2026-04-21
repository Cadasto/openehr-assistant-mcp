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
    private const int DEFAULT_MAX_RESULTS = 20;
    private const int MAX_RESULTS_LIMIT = 50;
    /** Multiplier for API fetch size so ranking/sorting has more candidates; then slice to maxResults. */
    private const float FETCH_SIZE_MULTIPLIER = 1.5;

    // Scoring weights (wider scale for clearer ranking)
    private const int SCORE_ARCHETYPE_ID_MATCH = 90;
    private const int SCORE_NAME_MATCH = 60;
    private const int SCORE_PROJECT_NAME_MATCH = 25;
    private const int SCORE_PROJECT_BUCKET = 10;
    private const int SCORE_ALL_KEYWORDS_BONUS = 80;
    private const int SCORE_STATUS_PUBLISHED = 50;
    private const int SCORE_STATUS_TEAMREVIEW = 25;
    private const int SCORE_STATUS_DRAFT = -15;
    private const int SCORE_STATUS_INITIAL = -50;
    /** Extra penalty per year since last modification (only for DRAFT/INITIAL). */
    private const int SCORE_PENALTY_PER_YEAR_SINCE_MODIFICATION = 7;
    /** Extra penalty per year since creation (only for DRAFT/INITIAL). */
    private const int SCORE_PENALTY_PER_YEAR_SINCE_CREATION = 3;

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
     *   The maximum number of result items to be returned; defaults to 20.
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
                'total' => ['type' => 'integer', 'description' => 'Total number of Archetypes found'],
            ],
        ]
    )]
    public function archetypeSearch(string $keyword, int $maxResults = self::DEFAULT_MAX_RESULTS, bool $requireAllSearchWords = true): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $fetchSize = min(self::MAX_RESULTS_LIMIT, (int) ceil($maxResults * self::FETCH_SIZE_MULTIPLIER));
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
            if (!is_array($data)) {
                throw new \RuntimeException('Unexpected CKM archetype response payload.');
            }
            $this->logger->info('Found CKM Archetypes', ['keyword' => $keyword, 'count' => count($data)]);

            // Map each item to a simpler structure and score
            $data = array_map(function (array $item) use ($keyword): array {
                $new = [
                    'cid' => $item['cid'] ?? null,
                    'archetypeId' => $item['resourceMainId'] ?? null,
                    'name' => $item['resourceMainDisplayName'] ?? null,
                    'projectName' => $item['projectName'] ?? null,
                    'status' => $item['status'] ?? null,
                    'revision' => $item['revision'] ?? null,
                    'creationTime' => $item['creationTime'] ?? null,
                    'modificationTime' => $item['modificationTime'] ?? $item['creationTime'] ?? null,
                    'score' => $this->scoreArchetypeItem($item, $keyword),
                ];
                return array_filter($new, fn($v) => $v !== null);
            }, $data);

            // Sort by score (highest first), then slice to requested maxResults
            usort($data, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $data = array_slice($data, 0, $maxResults);

            $totalHeader = $response->getHeaderLine('X-Total-Count');
            return [
                'items' => $data,
                'total' => $totalHeader !== '' ? (integer) $totalHeader : count($data),
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
     *   Query search string, one or multiple words, wildcards `*` supported.
     *
     * @param int $maxResults
     *   The maximum number of result items to be returned; defaults to 20.
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
                'total' => ['type' => 'integer', 'description' => 'Total number of Templates found'],
            ],
        ]
    )]
    public function templateSearch(string $keyword, int $maxResults = self::DEFAULT_MAX_RESULTS, bool $requireAllSearchWords = true): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $fetchSize = min(self::MAX_RESULTS_LIMIT, (int) ceil($maxResults * self::FETCH_SIZE_MULTIPLIER));
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
            if (!is_array($data)) {
                throw new \RuntimeException('Unexpected CKM template response payload.');
            }
            $this->logger->info('Found CKM Templates', ['keyword' => $keyword, 'count' => count($data)]);

            // Map each item to a simpler structure and score
            $data = array_map(function (array $item) use ($keyword): array {
                $new = [
                    'cid' => $item['cid'] ?? null,
                    'name' => $item['resourceMainDisplayName'] ?? null,
                    'projectName' => $item['projectName'] ?? null,
                    'status' => $item['status'] ?? null,
                    'version' => $item['versionAsset'] ?? null,
                    'creationTime' => $item['creationTime'] ?? null,
                    'modificationTime' => $item['modificationTime'] ?? $item['creationTime'] ?? null,
                    'score' => $this->scoreTemplateItem($item, $keyword),
                ];
                return array_filter($new, fn($v) => $v !== null);
            }, $data);

            // Sort by score (highest first), then slice to requested maxResults
            usort($data, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $data = array_slice($data, 0, $maxResults);

            $totalHeader = $response->getHeaderLine('X-Total-Count');
            return [
                'items' => $data,
                'total' => $totalHeader !== '' ? (integer) $totalHeader : count($data),
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
    public function templateGet(string $identifier, string $format = 'oet'): TextContent
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

    /**
     * Score one archetype search result (archetypeId, name, projectName, status, match quality, all-keywords bonus).
     *
     * @param array<string, mixed> $item Raw CKM API item (resourceMainId, resourceMainDisplayName, projectName, status, etc.)
     */
    private function scoreArchetypeItem(array $item, string $keyword): int
    {
        $archetypeId = $item['resourceMainId'] ?? null;
        $name = $item['resourceMainDisplayName'] ?? null;
        $projectName = $item['projectName'] ?? null;
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
        $score += $this->projectBucketBonus($projectName);
        $score += $this->scoreStatus($item['status'] ?? null);
        $score += $this->agePenalty(
            $item['modificationTime'] ?? $item['creationTime'] ?? null,
            $item['creationTime'] ?? null,
            $item['status'] ?? null
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
        $name = $item['resourceMainDisplayName'] ?? null;
        $projectName = $item['projectName'] ?? null;
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
        $score += $this->projectBucketBonus($projectName);
        $score += $this->scoreStatus($item['status'] ?? null);
        $score += $this->agePenalty(
            $item['modificationTime'] ?? $item['creationTime'] ?? null,
            $item['creationTime'] ?? null,
            $item['status'] ?? null
        );
        return $score;
    }

    /**
     * Extra penalty for DRAFT/INITIAL items that are old: per year since last modification (-10), per year since creation (-5).
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
