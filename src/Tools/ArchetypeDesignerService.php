<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Apis\ArchetypeDesignerClient;
use GuzzleHttp\RequestOptions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\ToolAnnotations;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Read-only access to archetypes and templates in openEHR Archetype Designer repositories.
 *
 * Unlike CKM (published, governed content), Designer repositories hold work in progress,
 * so these tools let an agent review or explain models while they are being authored.
 */
final readonly class ArchetypeDesignerService
{
    private const int DEFAULT_MAX_RESULTS = 20;
    private const int MAX_RESULTS_LIMIT = 100;

    /** Template `format` value → [export endpoint, Accept media type]. */
    private const array TEMPLATE_EXPORTS = [
        'oet' => ['api/template/export/oet', 'application/oet'],
        'opt' => ['api/template/export/opt', 'application/opt'],
        'web_template' => ['api/template/export/wtp', 'application/wtp'],
    ];

    public function __construct(
        private ArchetypeDesignerClient $apiClient,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * List the Archetype Designer repositories the configured account can access.
     *
     * Use this first to discover a `repositoryId` for the other `designer_*` tools, unless
     * the server has a default repository configured.
     *
     * @return array{items: list<array<string, string>>, total: int}
     *   Repositories with their id (pass as `repositoryId`), name, type and owner.
     *
     * @throws ToolCallException
     *   If the Designer is not configured on this server or the API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'designer_repository_list',
        title: 'List Archetype Designer repositories',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'name'],
                        'properties' => [
                            'id' => ['type' => 'string', 'description' => 'Repository identifier; pass as `repositoryId`'],
                            'name' => ['type' => 'string'],
                            'type' => ['type' => 'string', 'description' => 'Storage type, e.g. github'],
                            'owner' => ['type' => 'string'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0],
            ],
        ]
    )]
    public function repositoryList(): array
    {
        $this->logger->debug('called ' . __METHOD__);
        $rows = $this->getJson('api/repository/list', [], 'list Archetype Designer repositories');
        $items = [];
        foreach ($this->rowList($rows) as $row) {
            $items[] = array_filter([
                'id' => $this->str($row['id'] ?? null) ?? '',
                'name' => $this->str($row['name'] ?? null) ?? '',
                'type' => $this->str($row['type'] ?? null),
                'owner' => $this->str($row['owner'] ?? null),
            ], fn($v) => $v !== null);
        }

        return ['items' => $items, 'total' => count($items)];
    }

    /**
     * List archetypes, templates and folders in an Archetype Designer repository.
     *
     * Use this to browse a repository's contents and find archetype or template IDs to pass
     * to `designer_archetype_get` or `designer_template_get`.
     *
     * @param string|null $repositoryId
     *   Repository identifier from `designer_repository_list`; omit to use the server's default repository.
     *
     * @param string $path
     *   Folder to list from; empty (default) = repository root.
     *
     * @param string|null $type
     *   Only return entries of this type (`archetype`, `template` or `folder`); omit for all.
     *
     * @return array{items: list<array<string, string>>, total: int}
     *   Entries with type, path, name, archetype/template id, RM type and validation status.
     *
     * @throws ToolCallException
     *   If no repository is given or configured, the Designer is not configured, or the API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'designer_entry_list',
        title: 'List Archetype Designer repository entries',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['type', 'path'],
                        'properties' => [
                            'type' => ['type' => 'string', 'description' => 'archetype, template or folder'],
                            'path' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'archetypeId' => ['type' => 'string'],
                            'templateId' => ['type' => 'string'],
                            'rmType' => ['type' => 'string'],
                            'status' => ['type' => 'string', 'description' => 'Designer validation status: valid, recoverable, invalid or pending'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0],
            ],
        ]
    )]
    public function entryList(
        ?string $repositoryId = null,
        string $path = '',
        #[Schema(enum: ['archetype', 'template', 'folder', null])]
        ?string $type = null,
    ): array {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $query = ['repositoryId' => $this->resolveRepositoryId($repositoryId), 'path' => trim($path)];
        if ($type !== null) {
            $query['type'] = $type;
        }
        $rows = $this->getJson('api/repository/entry/list', $query, 'list the Archetype Designer repository entries');
        $items = [];
        foreach ($this->rowList($rows) as $row) {
            $items[] = array_filter([
                'type' => $this->str($row['type'] ?? null) ?? '',
                'path' => $this->str($row['path'] ?? null) ?? '',
                'name' => $this->str($row['name'] ?? null),
                'archetypeId' => $this->str($row['archetypeId'] ?? null),
                'templateId' => $this->str($row['templateId'] ?? null),
                'rmType' => $this->str($row['rmType'] ?? null),
                'status' => $this->str($row['status'] ?? null),
            ], fn($v) => $v !== null);
        }

        return ['items' => $items, 'total' => count($items)];
    }

    /**
     * Full-text search the contents of an Archetype Designer repository.
     *
     * @param string $text
     *   Search text, e.g. a clinical concept ("blood pressure") or a term used inside archetypes.
     *
     * @param string|null $repositoryId
     *   Repository identifier from `designer_repository_list`; omit to use the server's default repository.
     *
     * @param int $maxResults
     *   Maximum number of hits; defaults to 20 and must be between 1 and 100 (values outside that range are rejected, not clamped).
     *
     * @return array{items: list<array<string, string>>, total: int}
     *   Matching repository entries (path and hit class), best first.
     *
     * @throws ToolCallException
     *   If no repository is given or configured, the Designer is not configured, or the API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'designer_search',
        title: 'Search an Archetype Designer repository',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['path'],
                        'properties' => [
                            'path' => ['type' => 'string', 'description' => 'Repository path of the matching entry'],
                            'class' => ['type' => 'string', 'description' => 'Kind of hit as reported by the Designer'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of hits returned; the Designer does not report a wider match count.'],
            ],
        ]
    )]
    public function search(
        string $text,
        ?string $repositoryId = null,
        #[Schema(minimum: 1, maximum: self::MAX_RESULTS_LIMIT)]
        int $maxResults = self::DEFAULT_MAX_RESULTS,
    ): array {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $data = $this->getJson('api/repository/fulltext/search', [
            'repositoryId' => $this->resolveRepositoryId($repositoryId),
            'text' => $text,
            'limit' => $maxResults,
        ], 'search the Archetype Designer repository');
        $hits = is_array($data) && is_array($data['hits'] ?? null) ? $data['hits'] : [];
        $items = [];
        foreach ($this->rowList($hits) as $hit) {
            $items[] = array_filter([
                'path' => $this->str($hit['path'] ?? null) ?? '',
                'class' => $this->str($hit['class'] ?? null),
            ], fn($v) => $v !== null);
        }
        $items = array_slice($items, 0, $maxResults);

        return ['items' => $items, 'total' => count($items)];
    }

    /**
     * Retrieve an archetype from an Archetype Designer repository.
     *
     * Use this to review or explain an archetype that is still being authored; for published
     * archetypes prefer `ckm_archetype_get`.
     * Returned formats:
     * - "adl": the archetype as ADL text, formatted by the Designer.
     * - "json": the Designer's JSON model of the archetype (AOM-based).
     *
     * @param string $archetypeId
     *   Archetype id, e.g. "openEHR-EHR-OBSERVATION.blood_pressure.v2".
     *
     * @param string|null $repositoryId
     *   Repository identifier from `designer_repository_list`; omit to use the server's default repository.
     *
     * @param string $format
     *   Desired representation; defaults to "adl".
     *
     * @return TextContent
     *   The archetype in the chosen format in a text content code block.
     *
     * @throws ToolCallException
     *   If no repository is given or configured, the Designer is not configured, or the API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'designer_archetype_get',
        title: 'Get Archetype Designer archetype',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true)
    )]
    public function archetypeGet(
        string $archetypeId,
        ?string $repositoryId = null,
        #[Schema(enum: ['adl', 'json'])]
        string $format = 'adl',
    ): TextContent {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $repositoryId = $this->resolveRepositoryId($repositoryId);
        $json = $this->getRaw('api/repository/archetype/get', [
            'repositoryId' => $repositoryId,
            'archetypeId' => trim($archetypeId),
        ], 'retrieve the Archetype Designer archetype');
        if ($format === 'json') {
            return TextContent::code($json, 'json');
        }

        return TextContent::code($this->postRaw('api/archetype/format', ['repositoryId' => $repositoryId], $json, 'text/adl', 'format the archetype as ADL'), 'adl');
    }

    /**
     * Retrieve a template from an Archetype Designer repository.
     *
     * Use this to review or explain a template that is still being authored; for published
     * templates prefer `ckm_template_get`.
     * Returned formats:
     * - "oet": template source (XML), the design-time template.
     * - "opt": Operational Template (XML), flattened with all archetype constraints.
     * - "web_template": Web Template (JSON), the basis of the simplified FLAT/STRUCTURED formats.
     * - "json": the Designer's JSON model of the template.
     *
     * @param string $templateId
     *   Template id as shown in the Designer, e.g. "Vital signs".
     *
     * @param string|null $repositoryId
     *   Repository identifier from `designer_repository_list`; omit to use the server's default repository.
     *
     * @param string $format
     *   Desired representation; defaults to "oet".
     *
     * @param string $language
     *   Primary language (ISO 639-1) for "oet", "opt" and "web_template" exports; defaults to "en".
     *
     * @return TextContent
     *   The template in the chosen format in a text content code block.
     *
     * @throws ToolCallException
     *   If no repository is given or configured, the Designer is not configured, or the API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'designer_template_get',
        title: 'Get Archetype Designer template',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true)
    )]
    public function templateGet(
        string $templateId,
        ?string $repositoryId = null,
        #[Schema(enum: ['oet', 'opt', 'web_template', 'json'])]
        string $format = 'oet',
        #[Schema(pattern: '^[a-z]{2}(-[A-Z]{2})?$')]
        string $language = 'en',
    ): TextContent {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $repositoryId = $this->resolveRepositoryId($repositoryId);
        $json = $this->getRaw('api/repository/template/get', [
            'repositoryId' => $repositoryId,
            'templateId' => trim($templateId),
        ], 'retrieve the Archetype Designer template');
        if (!isset(self::TEMPLATE_EXPORTS[$format])) {
            return TextContent::code($json, 'json');
        }
        [$endpoint, $accept] = self::TEMPLATE_EXPORTS[$format];
        $exported = $this->postRaw($endpoint, ['repositoryId' => $repositoryId, 'primaryLanguage' => $language], $json, $accept, "export the template as {$format}");

        return TextContent::code($exported, $format === 'web_template' ? 'json' : 'xml');
    }

    /**
     * Run the Archetype Designer's validation on an archetype or template.
     *
     * Use this when reviewing a model: it reports the Designer's validation status, issues
     * (errors) and smells (modelling warnings), and the concept's direct dependencies.
     *
     * @param string $kind
     *   What to analyze: "archetype" or "template".
     *
     * @param string $identifier
     *   Archetype id or template id.
     *
     * @param string|null $repositoryId
     *   Repository identifier from `designer_repository_list`; omit to use the server's default repository.
     *
     * @return TextContent
     *   JSON with `status`, `issues`, `smells` and `directDependencies`.
     *
     * @throws ToolCallException
     *   If no repository is given or configured, the Designer is not configured, or the API request fails.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'designer_analyze',
        title: 'Analyze Archetype Designer archetype or template',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: true)
    )]
    public function analyze(
        #[Schema(enum: ['archetype', 'template'])]
        string $kind,
        string $identifier,
        ?string $repositoryId = null,
    ): TextContent {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $repositoryId = $this->resolveRepositoryId($repositoryId);
        $idParam = $kind === 'template' ? 'templateId' : 'archetypeId';
        $json = $this->getRaw("api/repository/{$kind}/get", [
            'repositoryId' => $repositoryId,
            $idParam => trim($identifier),
        ], "retrieve the Archetype Designer {$kind}");
        $raw = $this->postRaw("api/{$kind}/analyze", ['repositoryId' => $repositoryId], $json, 'application/json', "analyze the {$kind}");
        $result = $this->decode($raw, "analyze the {$kind}");
        $summary = is_array($result) ? array_intersect_key($result, array_flip(['status', 'issues', 'smells', 'directDependencies'])) : [];

        return TextContent::code((string)json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'json');
    }

    /**
     * @throws ToolCallException
     */
    private function resolveRepositoryId(?string $repositoryId): string
    {
        $repositoryId = trim((string)$repositoryId);
        if ($repositoryId === '') {
            $repositoryId = $this->apiClient->defaultRepositoryId();
        }
        if ($repositoryId === '') {
            throw new ToolCallException('No repositoryId given and no default repository configured; call `designer_repository_list` to find one.');
        }

        return $repositoryId;
    }

    /**
     * @param array<string, mixed> $query
     * @throws ToolCallException
     */
    private function getJson(string $endpoint, array $query, string $action): mixed
    {
        return $this->decode($this->getRaw($endpoint, $query, $action), $action);
    }

    /**
     * @param array<string, mixed> $query
     * @throws ToolCallException
     */
    private function getRaw(string $endpoint, array $query, string $action): string
    {
        $this->assertConfigured();
        try {
            $response = $this->apiClient->get($endpoint, [
                RequestOptions::QUERY => $query,
                RequestOptions::HEADERS => ['Accept' => 'application/json'] + $this->apiClient->repositoryHeaders(),
            ]);

            return trim($response->getBody()->getContents());
        } catch (ClientExceptionInterface $e) {
            $this->logger->error("Failed to {$action}", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            throw new ToolCallException("Failed to {$action}: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * POST a Designer JSON model back to a conversion endpoint, passing it through unchanged
     * (re-encoding it could alter empty objects or number formatting the Designer relies on).
     *
     * @param array<string, mixed> $query
     * @throws ToolCallException
     */
    private function postRaw(string $endpoint, array $query, string $jsonBody, string $accept, string $action): string
    {
        $this->assertConfigured();
        try {
            $response = $this->apiClient->post($endpoint, [
                RequestOptions::QUERY => $query,
                RequestOptions::HEADERS => ['Accept' => $accept, 'Content-Type' => 'application/json'] + $this->apiClient->repositoryHeaders(),
                RequestOptions::BODY => $jsonBody,
            ]);

            return trim($response->getBody()->getContents());
        } catch (ClientExceptionInterface $e) {
            $this->logger->error("Failed to {$action}", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            throw new ToolCallException("Failed to {$action}: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws ToolCallException
     */
    private function decode(string $raw, string $action): mixed
    {
        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ToolCallException("Failed to {$action}: invalid JSON from the Archetype Designer: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws ToolCallException
     */
    private function assertConfigured(): void
    {
        if (!$this->apiClient->isConfigured()) {
            throw new ToolCallException('The Archetype Designer is not configured on this server: set ARCHETYPE_DESIGNER_USERNAME and ARCHETYPE_DESIGNER_PASSWORD (a Designer local account).');
        }
    }

    /**
     * Keep only the object rows of a decoded JSON list; anything else yields [].
     *
     * @return list<array<string, mixed>>
     */
    private function rowList(mixed $rows): array
    {
        if (!is_array($rows) || !array_is_list($rows)) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function str(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string)$value : null;
    }
}
