<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\ToolAnnotations;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class GuideService
{
    private const int DEFAULT_MAX_RESULTS = 10;
    private const int MAX_RESULTS_LIMIT = 50;
    private const int DEFAULT_TOP_CANDIDATES = 24;
    private const int DEFAULT_SECTION_LIMIT = 5;
    private const int DEFAULT_SNIPPET_CHARS = 220;
    private const int MAX_SNIPPET_CHARS = 1200;

    public const string GUIDE_DIR = APP_RESOURCES_DIR . '/guides';

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
     * @param string $category
     *   Optional guide category filter (e.g. "archetypes", "templates"). Leave empty to search all guides.
     *
     * @param string $taskType
     *   Optional task hint (e.g. "lint", "review", "refactor", "author"). If supplied, matches guides containing it.
     *
     * @return array<string, array<int, array<string, string|int>>>
     *   A list of matching guides with short snippets and URIs.
     */
    #[McpTool(
        name: 'guide_search',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of matching guide snippets and canonical guide URIs',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'category' => ['type' => 'string', 'description' => 'Guide category, e.g. archetypes/templates'],
                            'name' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string', 'description' => 'Canonical guide URI in openehr://guides namespace'],
                            'snippet' => ['type' => 'string', 'description' => 'Short, task-relevant snippet'],
                            'score' => ['type' => 'integer', 'description' => 'Relative match score for sorting (higher is better)'],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function search(
        string $query = '',
        string $category = '',
        string $taskType = '',
        int $maxResults = self::DEFAULT_MAX_RESULTS,
        int $snippetChars = self::DEFAULT_SNIPPET_CHARS,
        int $topCandidates = self::DEFAULT_TOP_CANDIDATES,
    ): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $query = trim($query);
        $category = trim($category);
        $taskType = trim($taskType);
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $snippetChars = max(80, min($snippetChars, self::MAX_SNIPPET_CHARS));
        $topCandidates = max($maxResults, $topCandidates);

        $indexedGuides = $this->loadGuideIndex();
        $results = [];
        foreach ($indexedGuides as $guide) {
            if ($category !== '' && $guide['category'] !== $category) {
                continue;
            }

            $metadataText = sprintf('%s %s %s', $guide['title'], $guide['abstract'], implode(' ', $guide['headings']));
            $results[] = [
                'guide' => $guide,
                'indexScore' => $this->scoreGuide($query, $guide['title'], $metadataText, $guide['category'])
                    + $this->taskTypeBoost($metadataText, $taskType),
            ];
        }

        usort(
            $results,
            static fn(array $a, array $b): int => $b['indexScore'] <=> $a['indexScore'] ?: strcmp($a['guide']['name'], $b['guide']['name'])
        );
        $results = array_slice($results, 0, $topCandidates);

        $scored = [];
        foreach ($results as $candidate) {
            $guide = $candidate['guide'];
            $path = $this->guidePath($guide['category'], $guide['name']);
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $content = (string)file_get_contents($path);
            if ($content === '') {
                continue;
            }

            if ($taskType !== '' && stripos($content, $taskType) === false) {
                continue;
            }

            $score = $candidate['indexScore'] + $this->scoreGuide($query, $guide['title'], $content, $guide['category']);

            $scored[] = [
                'title' => $guide['title'],
                'category' => $guide['category'],
                'name' => $guide['name'],
                'resourceUri' => $guide['resourceUri'],
                'snippet' => $this->buildSnippet($content, $query, $snippetChars),
                'score' => $score,
            ];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $scored = array_slice($scored, 0, $maxResults);

        return ['items' => $scored];
    }

    /**
     * Fetch the full content of an openEHR guide by its canonical URI or by specifying its category and name.
     *
     * Use this tool to retrieve an openEHR guide for a specific processing or implementation task around Archetype, Templates or specifications.
     * Such guides describe modeling workflows, best practices, syntax checklists, principal rules, antipatterns and other guidance on demand.
     *
     * @param string $uri
     *   Canonical guide URI (openehr://guides/{category}/{name}). Optional when category and name are provided.
     *
     * @param string $category
     *   Guide category (e.g. "archetypes" or "templates"). Optional when URI is provided.
     *
     * @param string $name
     *   Guide filename without extension. Optional when URI is provided.
     *
     * @return EmbeddedResource
     *   The selected guide markdown content.
     */
    #[McpTool(
        name: 'guide_get',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(string $uri = '', string $category = '', string $name = ''): EmbeddedResource
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $uri = trim($uri);
        $category = trim($category);
        $name = trim($name);

        if ($uri) {
            [$category, $name] = $this->parseGuideUri($uri);
        }

        if (!$category || !$name) {
            throw new ToolCallException('Guide category and name are required when URI is not provided.');
        }

        $path = $this->guidePath($category, $name);
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
     * @return array<string, array<int, array<string, string>>>
     *   Matching idiom snippets with headings and canonical guide URIs.
     */
    #[McpTool(
        name: 'guide_adl_idiom_lookup',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'Matching ADL idiom snippets',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'snippet' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string'],
                            'section' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function adlIdiomLookup(string $pattern): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $pattern = trim($pattern);
        if ($pattern === '') {
            return ['items' => []];
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

        usort($matches, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $matches = array_slice($matches, 0, self::DEFAULT_SECTION_LIMIT + 2);

        return ['items' => array_map(static function (array $match): array {
            unset($match['score']);
            return $match;
        }, $matches)];
    }

    /** @return array<int, array{title: string, category: string, name: string, resourceUri: string, abstract: string, headings: array<int, string>}> */
    private function loadGuideIndex(): array
    {
        return $this->buildGuideIndex();
    }

    /** @return array<int, array{title: string, category: string, name: string, resourceUri: string, abstract: string, headings: array<int, string>}> */
    private function buildGuideIndex(): array
    {
        if (!is_dir(self::GUIDE_DIR)) {
            return [];
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

            $content = (string)file_get_contents($fileInfo->getPathname()) ?: '';
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

    private function scoreGuide(string $query, string $title, string $content, string $category = ''): int
    {
        $content = strtolower($content);
        $title = strtolower($title);
        $keywords = array_filter(preg_split('/\s+/', trim($query)) ?: []);

        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($title, $keyword)) {
                $score += 4;
            }
            if ($category && str_contains($category, $keyword)) {
                $score += 3;
            }
            $score += min(substr_count($content, $keyword), 6);
        }

        return $score;
    }

    private function buildSnippet(string $content, string $query, int $snippetChars = self::DEFAULT_SNIPPET_CHARS): string
    {
        $lower = strtolower($content);
        $needle = strtolower($query);
        $pos = strpos($lower, $needle);
        if ($pos === false) {
            return $this->limitText($content, $snippetChars);
        }

        $start = max(0, $pos - (int)($snippetChars / 2));
        $snippet = substr($content, $start, $snippetChars);
        return trim($snippet);
    }

    private function taskTypeBoost(string $metadataText, string $taskType): int
    {
        if ($taskType === '') {
            return 0;
        }

        return stripos($metadataText, $taskType) === false ? 0 : 2;
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
        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return rtrim(substr($text, 0, $maxChars - 1)) . '…';
    }

    /**
     * @param string $uri
     * @return array{string, string}
     */
    private function parseGuideUri(string $uri): array
    {
        $pattern = '#^openehr://guides/([\w-]+)/([\w-]+)$#';
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

    private function buildGuideUri(string $category, string $name): string
    {
        return sprintf('openehr://guides/%s/%s', $category, $name);
    }
}
