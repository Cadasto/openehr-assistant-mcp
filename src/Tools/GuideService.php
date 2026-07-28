<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

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
     *   Optional task hint (e.g. "lint", "review", "refactor", "author"). When supplied it adds a small ranking boost to guides whose title, abstract, or indexed headings mention it. It never filters, and never makes a guide the query did not match appear in the results.
     *
     * @return array{items: list<array<string, string|int>>, total: int}
     *   A list of matching guides with short snippets and URIs, plus `total` — the number
     *   of matches found within the scored candidate window (see the `total` note in the
     *   outputSchema), which may exceed the number of returned `items`.
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
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of guides that matched within the top-`topCandidates` relevance window, before the `maxResults` cap; may exceed items.length. Bounded by `topCandidates` — raise that to widen both the search and this count.'],
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
        $topCandidates = max($maxResults, $topCandidates);

        $indexedGuides = $this->loadGuideIndex();
        $candidates = [];
        foreach ($indexedGuides as $guide) {
            if ($category !== '' && $guide['category'] !== $category) {
                continue;
            }

            $metadataText = sprintf('%s %s %s', $guide['title'], $guide['abstract'], implode(' ', $guide['headings']));
            // Query relevance and the taskType hint are tracked separately. The hint may
            // reorder results, but it must never contribute to the relevance floor below
            // — otherwise a +2 boost alone would resurrect a guide the query never matched.
            $candidates[] = [
                'guide' => $guide,
                'queryScore' => $this->scoreGuide($query, $guide['title'], $metadataText, $guide['category']),
                'taskTypeBoost' => $this->taskTypeBoost($metadataText, $taskType),
            ];
        }

        usort(
            $candidates,
            static fn(array $a, array $b): int => ($b['queryScore'] + $b['taskTypeBoost']) <=> ($a['queryScore'] + $a['taskTypeBoost'])
                ?: strcmp($a['guide']['name'], $b['guide']['name'])
        );
        $candidates = array_slice($candidates, 0, $topCandidates);

        $scored = [];
        foreach ($candidates as $candidate) {
            $guide = $candidate['guide'];
            $path = $this->guidePath($guide['category'], $guide['name']);
            if (!is_file($path) || !is_readable($path)) {
                // The index just enumerated this file, so a miss here means the tree
                // changed underneath us or permissions are wrong — never silent.
                $this->logger->warning('Indexed guide is missing or unreadable; excluded from search results.', [
                    'category' => $guide['category'],
                    'name' => $guide['name'],
                    'path' => $path,
                ]);
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                $this->logger->warning('Could not read guide file; excluded from search results.', ['path' => $path]);
                continue;
            }
            if (trim($content) === '') {
                $this->logger->warning('Guide file is empty; excluded from search results.', ['path' => $path]);
                continue;
            }

            $queryScore = $candidate['queryScore'] + $this->scoreGuide($query, $guide['title'], $content, $guide['category']);
            if ($query !== '' && $queryScore <= 0) {
                continue;
            }

            $scored[] = [
                'title' => $guide['title'],
                'category' => $guide['category'],
                'name' => $guide['name'],
                'resourceUri' => $guide['resourceUri'],
                'snippet' => $this->buildSnippet($content, $query, $snippetChars),
                'score' => $queryScore + $candidate['taskTypeBoost'],
            ];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score'] ?: strcmp($a['name'], $b['name']));
        // Count matches before the `maxResults` cap so `total` tells the caller whether
        // widening the request would surface more, not merely how many were returned.
        $totalMatches = count($scored);
        $scored = array_slice($scored, 0, $maxResults);

        return ['items' => $scored, 'total' => $totalMatches];
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
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Total matching idiom sections before the 7-section cap is applied; may exceed items.length'],
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
        $path = $this->guidePath($category, $name);
        if (!is_file($path) || !is_readable($path)) {
            throw new ToolCallException('ADL idioms cheatsheet not found.');
        }

        $content = (string)file_get_contents($path);
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
        // A cheatsheet symptom often spans adjacent sections, so return a couple more
        // than the general section limit; `total` above reports how many were dropped.
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
     * Split a query into lower-cased search tokens.
     *
     * `-` and `.` are treated as word characters (as `_` already is) so that openEHR
     * identifiers survive as single tokens: splitting `openEHR-EHR-OBSERVATION` into
     * `openehr`/`ehr`/`observation` would match essentially every guide in the corpus
     * on its most generic parts. Punctuation that genuinely separates terms — commas,
     * slashes, parentheses — still splits, so `DV_QUANTITY, DV_CODED_TEXT` yields two
     * tokens. Stray leading/trailing `-`/`.` (sentence punctuation) are trimmed off.
     *
     * @return list<string>
     */
    private function tokenizeQuery(string $query): array
    {
        $normalized = mb_strtolower(trim($query), 'UTF-8');
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/[^\p{L}\p{N}_.\-]+/u', $normalized);
        if ($parts === false) {
            $this->logger->warning('Could not tokenize guide search query.', [
                'query' => $query,
                'error' => preg_last_error_msg(),
            ]);

            return [];
        }

        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part, '-.');
            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
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
