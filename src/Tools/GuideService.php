<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Cadasto\OpenEHR\MCP\Assistant\Helpers\SearchTokenizer;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\ToolAnnotations;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class GuideService
{
    private const int DEFAULT_MAX_RESULTS = 10;
    private const int MAX_RESULTS_LIMIT = 50;
    private const int DEFAULT_TOP_CANDIDATES = 24;
    private const int DEFAULT_SECTION_LIMIT = 5;
    private const int DEFAULT_SNIPPET_CHARS = 220;
    private const int MAX_SNIPPET_CHARS = 1200;

    public const string GUIDE_DIR = APP_RESOURCES_DIR . '/guides';
    /** @var array<int, array{title: string, category: string, name: string, resourceUri: string, abstract: string, headings: array<int, string>}>|null */
    private ?array $guideIndex = null;

    public function __construct(
        private LoggerInterface $logger,
    )
    {
        if (!is_dir(self::GUIDE_DIR) || !is_readable(self::GUIDE_DIR)) {
            $this->logger->warning('Guides directory not found or not readable.', ['dir' => self::GUIDE_DIR]);
        }
    }

    /**
     * Search openEHR guides metadata and content to retrieve small, model-ready snippets plus canonical openehr://guides URIs.
     *
     * Use this tool when you need to locate the right guidance on demand.
     * It returns short, task-relevant chunks and meta-data so the model can decide which guide to pull next with `guide_get`.
     *
     * @param string $query
     *   The query string describing what guidance you need (e.g. "cardinality vs occurrences", "slot constraints"). Leave empty to search all guides.
     *
     * @param string|null $category
     *   Optional guide category filter. Categories: authoring guides for archetypes/templates/AQL/simplified_formats, plus "specs" (per-document openEHR spec digests) and "howto" (toolchain how-to guides). Omit to search all categories.
     *
     * @param string|null $taskType
     *   Optional task hint (e.g. "lint", "review", "refactor", "author"). When supplied it adds a small ranking boost to guides whose title, abstract, or indexed headings mention it. It only reorders guides the query already matched: it never filters, and never makes a guide the query did not match appear in the results.
     *
     * @param int $topCandidates
     *   Retained for backward compatibility and no longer limits what is searched. Every
     *   guide in scope is scored over its full body text, so recall does not depend on
     *   this value.
     *
     * @return array{items: list<array<string, string|int>>, total: int}
     *   A list of matching guides with short snippets and URIs, plus `total` — the total
     *   number of guides that matched, which may exceed the number of returned `items`.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'guide_search',
        title: 'Search openEHR guides',
        annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of matching guide snippets and canonical guide URIs',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'category', 'name', 'resourceUri', 'snippet', 'score'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'category' => ['type' => 'string', 'description' => 'Guide category: archetypes | templates | aql | simplified_formats | specs | howto'],
                            'name' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string', 'format' => 'uri', 'description' => 'Canonical guide URI in openehr://guides namespace'],
                            'snippet' => ['type' => 'string', 'description' => 'Short, task-relevant snippet'],
                            'score' => ['type' => 'integer', 'description' => 'Relative match score for sorting (higher is better)'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Total number of guides that matched the query, counted before the `maxResults` cap; may exceed items.length. Raise `maxResults` to see more of them.'],
            ],
        ],
    )]
    public function search(
        string $query = '',
        #[Schema(enum: ['archetypes', 'templates', 'aql', 'simplified_formats', 'specs', 'howto', null])]
        ?string $category = null,
        ?string $taskType = null,
        #[Schema(minimum: 1, maximum: self::MAX_RESULTS_LIMIT)]
        int $maxResults = self::DEFAULT_MAX_RESULTS,
        #[Schema(minimum: 80, maximum: self::MAX_SNIPPET_CHARS)]
        int $snippetChars = self::DEFAULT_SNIPPET_CHARS,
        // Intentionally unread: kept in the signature so existing callers (and the
        // published inputSchema, which is now `additionalProperties: false`) keep working.
        // It used to bound which guides were content-scored, which cost recall — see the
        // note in the body. Removing it would hard-fail any client that still passes it.
        #[Schema(minimum: 1)]
        int $topCandidates = self::DEFAULT_TOP_CANDIDATES,
    ): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $query = trim($query);
        $category = trim((string)($category ?? ''));
        $taskType = trim((string)($taskType ?? ''));
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $snippetChars = max(80, min($snippetChars, self::MAX_SNIPPET_CHARS));

        $matches = [];
        foreach ($this->loadGuideIndex() as $guide) {
            if ($category !== '' && $guide['category'] !== $category) {
                continue;
            }

            $content = $this->readGuideContent($guide['category'], $guide['name']);
            if ($content === null) {
                continue;
            }

            // Relevance is scored over metadata *and* the full body of every guide in
            // scope. Ranking a metadata-only window first and scoring bodies only within
            // it made a term appearing solely in a guide's body unreachable: with the
            // zero-score floor below, the caller then got an empty envelope, which an
            // agent reads as "no such guidance exists" rather than "look harder".
            // `buildGuideIndex()` already reads every file, so the window saved no I/O.
            $metadataText = sprintf('%s %s %s', $guide['title'], $guide['abstract'], implode(' ', $guide['headings']));
            $queryScore = $this->scoreGuide($query, $guide['title'], $metadataText, $guide['category'])
                + $this->scoreGuide($query, $guide['title'], $content, $guide['category']);
            if ($query !== '' && $queryScore <= 0) {
                continue;
            }

            // The taskType hint is added only after the relevance floor above, so it can
            // reorder matches but never resurrect a guide the query did not match.
            $matches[] = [
                'guide' => $guide,
                'content' => $content,
                'score' => $queryScore + $this->taskTypeBoost($metadataText, $taskType),
            ];
        }

        // Ties break on `category/name`, not `name` alone: guide names are unique only
        // within a category (`checklist` and `principles` each occur in four), so the
        // narrower comparison left 13 files ordered by filesystem iteration order.
        usort(
            $matches,
            static fn(array $a, array $b): int => $b['score'] <=> $a['score']
                ?: strcmp(
                    $a['guide']['category'] . '/' . $a['guide']['name'],
                    $b['guide']['category'] . '/' . $b['guide']['name'],
                )
        );

        // Count every match before the `maxResults` cap, so `total` reports how much
        // relevant guidance exists rather than how much this call chose to return.
        $totalMatches = count($matches);

        $items = [];
        // Snippets are built only for the returned slice: snippet extraction is the
        // per-result cost, and computing it for matches nobody sees is pure waste.
        foreach (array_slice($matches, 0, $maxResults) as $match) {
            $items[] = [
                'title' => $match['guide']['title'],
                'category' => $match['guide']['category'],
                'name' => $match['guide']['name'],
                'resourceUri' => $match['guide']['resourceUri'],
                'snippet' => $this->buildSnippet($match['content'], $query, $snippetChars),
                'score' => $match['score'],
            ];
        }

        return ['items' => $items, 'total' => $totalMatches];
    }

    /**
     * Read an indexed guide's body, logging every reason it cannot be used.
     *
     * The index enumerated these files moments earlier, so any failure here means the
     * tree changed underneath us or permissions are wrong. Returning null (never '')
     * keeps "unusable" distinguishable from "empty" at the call site.
     */
    private function readGuideContent(string $category, string $name): ?string
    {
        $path = $this->guidePath($category, $name);
        if (!is_file($path) || !is_readable($path)) {
            $this->logger->warning('Indexed guide is missing or unreadable; excluded from search results.', [
                'category' => $category,
                'name' => $name,
                'path' => $path,
            ]);

            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            $this->logger->warning('Could not read guide file; excluded from search results.', ['path' => $path]);

            return null;
        }
        if (trim($content) === '') {
            $this->logger->warning('Guide file is empty; excluded from search results.', ['path' => $path]);

            return null;
        }

        return $content;
    }

    /**
     * Fetch the full content of an openEHR guide by its canonical URI or by specifying its category and name.
     *
     * Use this tool to retrieve an openEHR guide for a specific processing or implementation task around archetypes, templates, AQL, simplified formats, spec digests, or toolchain how-tos.
     * Such guides describe modelling workflows, best practices, syntax checklists, principal rules, anti-patterns, normative spec digests, and other guidance on demand.
     *
     * @param string $uri
     *   Canonical guide URI (openehr://guides/{category}/{name}). Optional when category and name are provided.
     *
     * @param string|null $category
     *   Guide category (authoring guides for archetypes/templates/AQL/simplified_formats, plus "specs" per-document spec digests and "howto" toolchain guides). Optional when URI is provided.
     *
     * @param string $name
     *   Guide filename without extension. Optional when URI is provided.
     *
     * @return EmbeddedResource
     *   The selected guide markdown content.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'guide_get',
        title: 'Get openEHR guide',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    )]
    public function get(
        string $uri = '',
        #[Schema(enum: ['archetypes', 'templates', 'aql', 'simplified_formats', 'specs', 'howto', null])]
        ?string $category = null,
        string $name = '',
    ): EmbeddedResource
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $uri = trim($uri);
        $category = trim((string)($category ?? ''));
        $name = trim($name);

        if ($uri) {
            [$category, $name] = $this->parseGuideUri($uri);
        }

        if (!$category || !$name) {
            throw new ToolCallException('Guide category and name are required when URI is not provided.');
        }

        $category = $this->validateGuideSegment($category, 'category');
        $name = $this->validateGuideSegment($name, 'name');

        $path = $this->guidePath($category, $name);
        $this->assertPathWithinGuides($path);
        if (!is_file($path) || !is_readable($path)) {
            throw new ToolCallException(sprintf('Guide not found: %s/%s', $category, $name));
        }

        $content = (string)file_get_contents($path);
        if (!$content) {
            throw new ToolCallException(sprintf('Guide content is empty: %s/%s', $category, $name));
        }

        return new EmbeddedResource(
            resource: new TextResourceContents(
                uri: $this->buildGuideUri($category, $name),
                mimeType: 'text/markdown',
                text: $content,
            ),
        );
    }

    /**
     * Lookup ADL idiom snippets for a symptom or pattern to prevent generic prompting.
     *
     * This tool is a targeted cheatsheet retrieval for common ADL constraint idioms.
     * Provide the symptom or pattern (e.g. "occurrences vs cardinality", "coded text", "slots") to receive matching examples.
     *
     * @param string $pattern
     *   Symptom or pattern string to search within the ADL idioms cheatsheet.
     *
     * @return array{items: list<array<string, string>>, total: int}
     *   Matching idiom snippets with headings and canonical guide URIs, plus `total` — the
     *   number of matching sections before the section cap, which may exceed items.length.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'guide_adl_idiom_lookup',
        title: 'Look up ADL idiom',
        annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'Matching ADL idiom snippets',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'snippet', 'resourceUri', 'section'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'snippet' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string', 'format' => 'uri'],
                            'section' => ['type' => 'string'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Total matching idiom sections before the section cap is applied; may exceed items.length'],
            ],
        ],
    )]
    public function adlIdiomLookup(string $pattern): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $pattern = trim($pattern);
        if ($pattern === '') {
            return ['items' => [], 'total' => 0];
        }

        $category = 'archetypes';
        $name = 'adl-idioms-cheatsheet';
        $content = $this->readGuideContent($category, $name);
        if ($content === null) {
            // Unlike `search()`, this tool has a single source file. If it cannot be read
            // there is no degraded answer to give — only a wrong "no idiom matched" for
            // every pattern — so fail loudly instead of returning an empty envelope.
            throw new ToolCallException('ADL idioms cheatsheet is missing or unreadable.');
        }

        $title = $this->extractTitle($content, $name);
        $sections = $this->parseSections($content);

        $matches = [];
        foreach ($sections as $section) {
            $score = $this->scoreGuide($pattern, $section['title'], $section['content']);
            if ($score === 0) {
                continue;
            }
            $matches[] = [
                'title' => $title,
                'snippet' => $this->buildSnippet($section['content'], $pattern),
                'resourceUri' => $this->buildGuideUri($category, $name),
                'section' => $section['title'],
                'score' => $score,
            ];
        }

        usort($matches, static fn(array $a, array $b): int => $b['score'] <=> $a['score'] ?: strcmp($a['section'], $b['section']));
        $totalMatches = count($matches);
        // A cheatsheet symptom often spans adjacent sections, so return a couple more than
        // the general section limit; `total` reports how many matched, so the caller can
        // derive how many were dropped as `total - items.length`.
        $matches = array_slice($matches, 0, self::DEFAULT_SECTION_LIMIT + 2);

        $items = array_map(static function (array $match): array {
            unset($match['score']);
            return $match;
        }, $matches);

        return ['items' => $items, 'total' => $totalMatches];
    }

    /** @return array<int, array{title: string, category: string, name: string, resourceUri: string, abstract: string, headings: array<int, string>}> */
    private function loadGuideIndex(): array
    {
        if ($this->guideIndex === null) {
            $this->guideIndex = $this->buildGuideIndex();
        }

        return $this->guideIndex;
    }

    /** @return array<int, array{title: string, category: string, name: string, resourceUri: string, abstract: string, headings: array<int, string>}> */
    private function buildGuideIndex(): array
    {
        if (!is_dir(self::GUIDE_DIR) || !is_readable(self::GUIDE_DIR)) {
            // The guides corpus ships with the server, so an unusable directory is a
            // packaging/permissions fault, not "no guides matched". Say so loudly —
            // otherwise every search returns an empty result indistinguishable from
            // a legitimate miss.
            throw new ToolCallException(sprintf('Guides directory is missing or unreadable: %s', self::GUIDE_DIR));
        }

        $index = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::GUIDE_DIR, \FilesystemIterator::SKIP_DOTS)
        );
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            if (strtolower($fileInfo->getExtension()) !== 'md') {
                continue;
            }

            $name = $fileInfo->getBasename('.md');
            if ($name === 'README' || str_starts_with($name, '_')) {
                // skip per-category README files and underscore-prefixed
                // templates/scaffolding — authoring artifacts, not guides
                continue;
            }

            $content = file_get_contents($fileInfo->getPathname());
            if ($content === false) {
                // Without this the title silently degrades to the filename and the
                // abstract/headings go empty, leaving a hollow index entry.
                $this->logger->warning('Could not read guide file while indexing; skipping.', [
                    'path' => $fileInfo->getPathname(),
                ]);
                continue;
            }

            $relative = str_replace(self::GUIDE_DIR . '/', '', $fileInfo->getPathname());
            $parts = explode('/', $relative);
            $category = $parts[0] ?: 'unknown';
            $title = $this->extractTitle($content, $name);

            $index[] = [
                'title' => $title,
                'category' => $category,
                'name' => $name,
                'resourceUri' => $this->buildGuideUri($category, $name),
                'abstract' => $this->extractAbstract($content),
                'headings' => $this->extractHeadings($content),
            ];
        }

        return $index;
    }

    private function extractTitle(string $content, string $fallback): string
    {
        foreach (preg_split('/\r?\n/', $content) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, '# ')) {
                return trim(substr($line, 2));
            }
        }

        return $fallback;
    }

    /** @return array<int, array{title: string, level: int, content: string}> */
    private function parseSections(string $content): array
    {
        $lines = preg_split('/\r?\n/', $content) ?: [];
        $sections = [];
        $current = [
            'title' => 'Introduction',
            'level' => 2,
            'content' => '',
        ];

        foreach ($lines as $line) {
            if (preg_match('/^(#{2,3})\s+(.*)$/', trim($line), $matches)) {
                if (trim($current['content']) !== '') {
                    $sections[] = $current;
                }
                $current = [
                    'title' => trim($matches[2]),
                    'level' => strlen($matches[1]),
                    'content' => '',
                ];
                continue;
            }

            $current['content'] .= $line . "\n";
        }

        if (trim($current['content']) !== '') {
            $sections[] = $current;
        }

        return $sections;
    }

    /**
     * @return list<string>
     * @see SearchTokenizer::tokenize() for the tokenization rules.
     */
    private function tokenizeQuery(string $query): array
    {
        $tokens = SearchTokenizer::tokenize($query);
        if ($tokens === null) {
            $this->logger->warning('Could not tokenize guide search query.', [
                'query' => $query,
                'error' => preg_last_error_msg(),
            ]);

            return [];
        }

        return $tokens;
    }

    private function scoreGuide(string $query, string $title, string $content, string $category = ''): int
    {
        $content = mb_strtolower($content, 'UTF-8');
        $title = mb_strtolower($title, 'UTF-8');
        $category = mb_strtolower($category, 'UTF-8');
        $keywords = $this->tokenizeQuery($query);

        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($title, $keyword)) {
                $score += 4;
            }
            if ($category !== '' && str_contains($category, $keyword)) {
                $score += 3;
            }
            $score += min(substr_count($content, $keyword), 6);
        }

        return $score;
    }

    /**
     * Build a snippet centred on the first occurrence of the query.
     *
     * Slicing is multibyte-aware throughout: byte offsets would cut UTF-8 sequences
     * mid-character, and the malformed string then fails `json_encode` for the whole
     * JSON-RPC envelope — the client gets no response at all rather than a bad snippet.
     */
    private function buildSnippet(string $content, string $query, int $snippetChars = self::DEFAULT_SNIPPET_CHARS): string
    {
        $needle = trim($query);
        $pos = $needle === '' ? false : mb_stripos($content, $needle, 0, 'UTF-8');
        if ($pos === false) {
            return $this->limitText($content, $snippetChars);
        }

        $start = max(0, $pos - intdiv($snippetChars, 2));
        return trim(mb_substr($content, $start, $snippetChars, 'UTF-8'));
    }

    /**
     * Small ranking nudge when a guide's indexed metadata mentions the caller's task
     * hint. Deliberately never used as a filter, and never folded into the relevance
     * score that `search()` thresholds on — see the note there.
     */
    private function taskTypeBoost(string $metadataText, string $taskType): int
    {
        if ($taskType === '') {
            return 0;
        }

        return mb_stripos($metadataText, $taskType, 0, 'UTF-8') === false ? 0 : 2;
    }

    private function extractAbstract(string $content): string
    {
        $lines = preg_split('/\r?\n/', $content) ?: [];
        $paragraph = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || preg_match('/^[-*]\s/', $trimmed) === 1) {
                if ($paragraph !== []) {
                    break;
                }
                continue;
            }

            $paragraph[] = $trimmed;
            if (strlen(implode(' ', $paragraph)) > 280) {
                break;
            }
        }

        return $this->limitText(implode(' ', $paragraph), 280);
    }

    /** @return array<int, string> */
    private function extractHeadings(string $content): array
    {
        preg_match_all('/^#{2,3}\s+(.+)$/m', $content, $matches);
        $headings = array_map(static fn(string $heading): string => trim($heading), $matches[1]);

        return array_slice($headings, 0, 12);
    }

    private function limitText(string $text, int $maxChars): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $maxChars) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxChars - 1, 'UTF-8')) . '…';
    }

    /**
     * @param string $uri
     * @return array{string, string}
     */
    private function parseGuideUri(string $uri): array
    {
        $pattern = '#^openehr://guides/([\w-]+)/([\w.-]+)$#';
        if (!preg_match($pattern, $uri, $matches)) {
            throw new ToolCallException(sprintf('Invalid guide URI: %s', $uri));
        }

        return [$matches[1], $matches[2]];
    }

    private function guidePath(string $category, string $name): string
    {
        $category = trim($category);
        $name = trim($name);
        if (!$category || !$name) {
            return '';
        }

        return self::GUIDE_DIR . '/' . $category . '/' . $name . '.md';
    }

    private function validateGuideSegment(string $segment, string $label): string
    {
        $value = trim($segment);
        if ($value === '' || preg_match('/^[\w.-]+$/', $value) !== 1) {
            throw new ToolCallException(sprintf('Invalid guide %s: %s', $label, $segment));
        }

        return $value;
    }

    private function assertPathWithinGuides(string $path): void
    {
        $guideRoot = realpath(self::GUIDE_DIR);
        $resolvedPath = realpath($path);
        if ($guideRoot === false || $resolvedPath === false || !str_starts_with($resolvedPath, $guideRoot . '/')) {
            throw new ToolCallException('Guide path is outside guides directory.');
        }
    }

    private function buildGuideUri(string $category, string $name): string
    {
        return sprintf('openehr://guides/%s/%s', $category, $name);
    }
}
