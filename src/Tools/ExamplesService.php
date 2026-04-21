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

final readonly class ExamplesService
{
    private const int DEFAULT_MAX_RESULTS = 10;
    private const int MAX_RESULTS_LIMIT = 30;
    private const int DEFAULT_SNIPPET_CHARS = 220;
    private const int MAX_SNIPPET_CHARS = 1200;

    public const string EXAMPLES_DIR = APP_RESOURCES_DIR . '/examples';

    /** Extensions the scanner/reader recognise, in preferred-lookup order. */
    private const array SUPPORTED_EXTENSIONS = ['md', 'adl'];

    public function __construct(
        private LoggerInterface $logger,
    ) {
        if (!is_dir(self::EXAMPLES_DIR) || !is_readable(self::EXAMPLES_DIR)) {
            $this->logger->warning('Examples directory not found or not readable.', ['dir' => self::EXAMPLES_DIR]);
        }
    }

    /**
     * Search openEHR example artefacts (AQL queries, FLAT/STRUCTURED JSON payloads) and return short snippets plus canonical openehr://examples URIs.
     *
     * Use this tool to discover curated, ready-to-reference examples that illustrate specific patterns
     * (e.g. "latest per patient", "time-window", "aggregation", "FLAT vs STRUCTURED pair").
     * Each hit returns the example's title, kind, canonical URI, and a short snippet so the model can decide which to pull with `examples_get`.
     *
     * @param string $query
     *   The query string describing what you need (e.g. "blood pressure", "latest per patient", "DV_QUANTITY projection").
     *   Leave empty to list all examples in the optional kind filter.
     *
     * @param string $kind
     *   Optional kind filter: "aql" | "flat" | "structured" | "archetypes". Leave empty to search all kinds.
     *
     * @return array<string, array<int, array<string, string|int>>>
     *   A list of matching examples with short snippets and URIs.
     */
    #[McpTool(
        name: 'examples_search',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of matching example snippets and canonical example URIs',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'kind' => ['type' => 'string', 'description' => 'Example kind: aql | flat | structured | archetypes'],
                            'name' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string', 'description' => 'Canonical example URI in openehr://examples namespace'],
                            'snippet' => ['type' => 'string', 'description' => 'Short, task-relevant snippet'],
                            'score' => ['type' => 'integer', 'description' => 'Relative match score (higher is better)'],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function search(
        string $query = '',
        string $kind = '',
        int $maxResults = self::DEFAULT_MAX_RESULTS,
        int $snippetChars = self::DEFAULT_SNIPPET_CHARS,
    ): array {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $query = trim($query);
        $kind = trim($kind);
        $maxResults = max(1, min($maxResults, self::MAX_RESULTS_LIMIT));
        $snippetChars = max(80, min($snippetChars, self::MAX_SNIPPET_CHARS));

        $index = $this->loadExamplesIndex();
        $scored = [];

        foreach ($index as $example) {
            if ($kind !== '' && $example['kind'] !== $kind) {
                continue;
            }

            $score = $this->scoreExample($query, $example['title'], $example['metadata']);
            if ($query !== '' && $score === 0) {
                continue;
            }

            $scored[] = [
                'title' => $example['title'],
                'kind' => $example['kind'],
                'name' => $example['name'],
                'resourceUri' => $example['resourceUri'],
                'snippet' => $this->buildSnippet($example['metadata'], $query, $snippetChars),
                'score' => $score,
            ];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score'] ?: strcmp($a['name'], $b['name']));
        return ['items' => array_slice($scored, 0, $maxResults)];
    }

    /**
     * Fetch the full content of an openEHR example artefact by canonical URI or by specifying kind and name.
     *
     * Use this tool to retrieve a curated example — the example file wraps the AQL query or FLAT/STRUCTURED JSON payload
     * in a Markdown file with a short metadata header (what pattern it demonstrates, related specs/guides) and a fenced code block.
     *
     * @param string $uri
     *   Canonical example URI (openehr://examples/{kind}/{name}). Optional when kind and name are provided.
     *
     * @param string $kind
     *   Example kind: "aql" | "flat" | "structured" | "archetypes". Optional when URI is provided.
     *
     * @param string $name
     *   Example filename without extension. Optional when URI is provided.
     *
     * @return EmbeddedResource
     *   The selected example markdown content.
     */
    #[McpTool(
        name: 'examples_get',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(string $uri = '', string $kind = '', string $name = ''): EmbeddedResource
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $uri = trim($uri);
        $kind = trim($kind);
        $name = trim($name);

        if ($uri) {
            [$kind, $name] = $this->parseExampleUri($uri);
        }

        if (!$kind || !$name) {
            throw new ToolCallException('Example kind and name are required when URI is not provided.');
        }

        $kind = $this->validateExampleSegment($kind, 'kind');
        $name = $this->validateExampleSegment($name, 'name');

        $path = $this->examplePath($kind, $name);
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new ToolCallException(sprintf('Example not found: %s/%s', $kind, $name));
        }
        $this->assertPathWithinExamples($path);

        $content = (string)file_get_contents($path);
        if (!$content) {
            throw new ToolCallException(sprintf('Example content is empty: %s/%s', $kind, $name));
        }

        $mimeType = str_ends_with(strtolower($path), '.adl') ? 'text/plain' : 'text/markdown';

        return new EmbeddedResource(
            resource: new TextResourceContents(
                uri: $this->buildExampleUri($kind, $name),
                mimeType: $mimeType,
                text: $content,
            ),
        );
    }

    /** @return array<int, array{title: string, kind: string, name: string, resourceUri: string, metadata: string}> */
    private function loadExamplesIndex(): array
    {
        if (!is_dir(self::EXAMPLES_DIR)) {
            return [];
        }

        $index = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::EXAMPLES_DIR, \FilesystemIterator::SKIP_DOTS)
        );
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
                continue;
            }

            $name = $fileInfo->getBasename('.' . $ext);
            if ($name === 'README' || str_starts_with($name, '_')) {
                continue;
            }

            $content = (string)file_get_contents($fileInfo->getPathname()) ?: '';
            $relative = str_replace(self::EXAMPLES_DIR . '/', '', $fileInfo->getPathname());
            $parts = explode('/', $relative);
            if (count($parts) < 2) {
                continue;
            }

            $kind = $parts[0];
            $index[] = [
                'title' => $this->extractTitle($content, $name),
                'kind' => $kind,
                'name' => $name,
                'ext' => $ext,
                'resourceUri' => $this->buildExampleUri($kind, $name),
                'metadata' => $this->extractMetadataBlock($content),
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

    /**
     * Extract the metadata block (everything up to the first `---` separator or the first code fence).
     * This is what we search over — the fenced code block itself is the example content.
     */
    private function extractMetadataBlock(string $content): string
    {
        $parts = preg_split('/^---\s*$/m', $content, 2) ?: [];
        $head = $parts[0] ?? '';
        // If no header separator, stop at first code fence.
        $fencePos = strpos($head, '```');
        if ($fencePos !== false) {
            $head = substr($head, 0, $fencePos);
        }
        return $head;
    }

    private function scoreExample(string $query, string $title, string $metadata): int
    {
        if ($query === '') {
            return 1;
        }
        $haystack = strtolower($title . ' ' . $metadata);
        $keywords = array_filter(preg_split('/\s+/', trim(strtolower($query))) ?: []);
        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains(strtolower($title), $keyword)) {
                $score += 5;
            }
            $score += min(substr_count($haystack, $keyword), 6);
        }
        return $score;
    }

    private function buildSnippet(string $content, string $query, int $snippetChars = self::DEFAULT_SNIPPET_CHARS): string
    {
        if ($query === '') {
            return $this->limitText($content, $snippetChars);
        }
        $lower = strtolower($content);
        $needle = strtolower($query);
        $pos = strpos($lower, $needle);
        if ($pos === false) {
            return $this->limitText($content, $snippetChars);
        }
        $start = max(0, $pos - (int)($snippetChars / 2));
        return trim(substr($content, $start, $snippetChars));
    }

    private function limitText(string $text, int $maxChars): string
    {
        $text = trim($text);
        if (strlen($text) <= $maxChars) {
            return $text;
        }
        return rtrim(substr($text, 0, $maxChars - 1)) . '…';
    }

    /** @return array{string, string} */
    private function parseExampleUri(string $uri): array
    {
        $pattern = '#^openehr://examples/([\w-]+)/([\w.-]+)$#';
        if (!preg_match($pattern, $uri, $matches)) {
            throw new ToolCallException(sprintf('Invalid example URI: %s', $uri));
        }
        return [$matches[1], $matches[2]];
    }

    private function examplePath(string $kind, string $name): string
    {
        $kind = trim($kind);
        $name = trim($name);
        if (!$kind || !$name) {
            return '';
        }
        $base = self::EXAMPLES_DIR . '/' . $kind . '/' . $name;
        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $candidate = $base . '.' . $ext;
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        // Return the default `.md` path so the "not found" error is still meaningful
        return $base . '.md';
    }

    private function validateExampleSegment(string $segment, string $label): string
    {
        $value = trim($segment);
        if ($value === '' || preg_match('/^[\w.-]+$/', $value) !== 1) {
            throw new ToolCallException(sprintf('Invalid example %s: %s', $label, $segment));
        }

        return $value;
    }

    private function assertPathWithinExamples(string $path): void
    {
        $examplesRoot = realpath(self::EXAMPLES_DIR);
        $resolvedPath = realpath($path);
        if ($examplesRoot === false || $resolvedPath === false || !str_starts_with($resolvedPath, $examplesRoot . '/')) {
            throw new ToolCallException('Example path is outside examples directory.');
        }
    }

    private function buildExampleUri(string $kind, string $name): string
    {
        return sprintf('openehr://examples/%s/%s', $kind, $name);
    }
}
