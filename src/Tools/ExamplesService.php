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
     * Search openEHR example artefacts (AQL queries, FLAT/STRUCTURED JSON payloads, native ADL archetypes) and return short snippets plus canonical openehr://examples URIs.
     *
     * Use this tool to discover curated, ready-to-reference examples that illustrate specific patterns
     * (e.g. "latest per patient", "time-window", "aggregation", "FLAT vs STRUCTURED pair").
     * Each hit returns the example's title, kind, canonical URI, and a short snippet so the model can decide which to pull with `examples_get`.
     *
     * @param string $query
     *   The query string describing what you need (e.g. "blood pressure", "latest per patient", "DV_QUANTITY projection").
     *   Leave empty to list all examples in the optional kind filter.
     *
     * @param string|null $kind
     *   Optional artefact-kind filter (AQL query, FLAT/STRUCTURED JSON payload, or native archetype). Omit to search all kinds.
     *
     * @param int $maxResults
     *   The maximum number of examples to return; defaults to 10 and must be between 1 and 30 (values outside that range are rejected, not clamped). `total` reports how many examples matched before this cap.
     *
     * @param int $snippetChars
     *   The maximum length of each returned snippet in characters; defaults to 220 and must be between 80 and 1200 (values outside that range are rejected, not clamped).
     *
     * @return array{items: list<array<string, string|int>>, total: int}
     *   A list of matching examples with short snippets and URIs, plus `total` — the number
     *   of matches before the `maxResults` cap, which may exceed the number of returned
     *   `items` (see the `total` note in the outputSchema).
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'examples_search',
        title: 'Search openEHR examples',
        annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of matching example snippets and canonical example URIs',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'kind', 'name', 'resourceUri', 'snippet', 'score'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'kind' => ['type' => 'string', 'description' => 'Example kind: aql | flat | structured | archetypes'],
                            'name' => ['type' => 'string'],
                            'resourceUri' => ['type' => 'string', 'format' => 'uri', 'description' => 'Canonical example URI in openehr://examples namespace'],
                            'snippet' => ['type' => 'string', 'description' => 'Short, task-relevant snippet'],
                            'score' => ['type' => 'integer', 'description' => 'Relative match score (higher is better)'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Total matching examples before the maxResults cap is applied; may exceed items.length'],
            ],
        ],
    )]
    public function search(
        string $query = '',
        #[Schema(enum: ['aql', 'flat', 'structured', 'archetypes', null])]
        ?string $kind = null,
        #[Schema(minimum: 1, maximum: self::MAX_RESULTS_LIMIT)]
        int $maxResults = self::DEFAULT_MAX_RESULTS,
        #[Schema(minimum: 80, maximum: self::MAX_SNIPPET_CHARS)]
        int $snippetChars = self::DEFAULT_SNIPPET_CHARS,
    ): array {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $query = trim($query);
        $kind = trim((string)($kind ?? ''));
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
        // Count matches before the `maxResults` cap so `total` reflects how many
        // examples matched, not merely how many were returned in `items`.
        $totalMatches = count($scored);
        $items = array_slice($scored, 0, $maxResults);
        return ['items' => $items, 'total' => $totalMatches];
    }

    /**
     * Fetch the full content of an openEHR example artefact by canonical URI or by specifying kind and name.
     *
     * Use this tool to retrieve a curated example. The payload is one of two shapes: for the `aql`, `flat` and
     * `structured` kinds a Markdown wrapper (`text/markdown`) with a short metadata header — what pattern it
     * demonstrates, related specs/guides — around a fenced code block; for the `archetypes` kind a native
     * CKM-published `.adl` file (`text/plain`), with no metadata header and no fence.
     *
     * @param string $uri
     *   Canonical example URI (openehr://examples/{kind}/{name}). Optional when kind and name are provided.
     *
     * @param string|null $kind
     *   Artefact kind (AQL query, FLAT/STRUCTURED JSON payload, or native archetype). Optional when URI is provided.
     *
     * @param string $name
     *   Example filename without extension. Optional when URI is provided.
     *
     * @return EmbeddedResource
     *   The selected example content: Markdown for `aql`/`flat`/`structured`, native ADL for `archetypes`.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'examples_get',
        title: 'Get openEHR example',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    )]
    public function get(
        string $uri = '',
        #[Schema(enum: ['aql', 'flat', 'structured', 'archetypes', null])]
        ?string $kind = null,
        string $name = '',
    ): EmbeddedResource
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $uri = trim($uri);
        $kind = trim((string)($kind ?? ''));
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
        if (!is_dir(self::EXAMPLES_DIR) || !is_readable(self::EXAMPLES_DIR)) {
            // The examples corpus ships with the server, so an unusable directory is a
            // packaging/permissions fault, not "no examples matched". Returning an empty
            // index made every search indistinguishable from a legitimate miss.
            throw new ToolCallException(sprintf('Examples directory is missing or unreadable: %s', self::EXAMPLES_DIR));
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

            $content = file_get_contents($fileInfo->getPathname());
            if ($content === false) {
                // Without this the entry still shipped: title degraded to the filename and
                // metadata went empty, so `examples_search` advertised a hollow result whose
                // `resourceUri` `examples_get` would then reject.
                $this->logger->warning('Could not read example file while indexing; skipping.', [
                    'path' => $fileInfo->getPathname(),
                ]);
                continue;
            }

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
        // Shares `guide_search`'s tokenizer: byte-wise `strtolower` left non-ASCII uppercase
        // unfolded (so `Å` scored 0 corpus-wide), and splitting on whitespace alone glued
        // punctuation to terms, making `pressure,` in "blood pressure, weight" match nothing.
        // Both were fixed in the sibling tool while this one kept the old behaviour.
        $haystack = mb_strtolower($title . ' ' . $metadata, 'UTF-8');
        $lowerTitle = mb_strtolower($title, 'UTF-8');
        $keywords = SearchTokenizer::tokenize($query);
        if ($keywords === null) {
            $this->logger->warning('Could not tokenize examples search query.', [
                'query' => $query,
                'error' => preg_last_error_msg(),
            ]);

            return 0;
        }

        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($lowerTitle, $keyword)) {
                $score += 5;
            }
            $score += min(substr_count($haystack, $keyword), 6);
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

    private function limitText(string $text, int $maxChars): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $maxChars) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $maxChars - 1, 'UTF-8')) . '…';
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
