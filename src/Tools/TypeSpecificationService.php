<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Generator;
use Mcp\Schema\Content\TextContent;
use Mcp\Capability\Attribute\McpTool;
use Psr\Log\LoggerInterface;
use SplFileInfo;

readonly final class TypeSpecificationService
{
    public const string BMM_DIR = APP_RESOURCES_DIR . '/bmm';

    public function __construct(
        private LoggerInterface $logger,
    )
    {
        if (!is_dir(self::BMM_DIR) || !is_readable(self::BMM_DIR)) {
            $this->logger->warning('BMM base path not found.', ['dir' => self::BMM_DIR]);
        }
    }

    private function getCandidateFiles(string $namePattern): Generator
    {
        // prepare glob-like regex from the pattern (supports * wildcard)
        $namePattern = str_replace(['\\*', '\\?'], ['[\w-]*', '[\w-]'], preg_quote($namePattern, '/'));
        $regex = '/^org\.openehr\.(?:[\w-]+\.)*' . $namePattern . '\.bmm\.json$/i';

        $results = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::BMM_DIR, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->isReadable()
                && (strtolower($fileInfo->getExtension()) === 'json')
                && $fileInfo->getSize()
                && preg_match($regex, $fileInfo->getFilename())
            ) {
                yield $fileInfo;
            }
        }
    }

    /**
     * Search bundled openEHR type specifications (BMM JSON) by type-name pattern, optionally filtered by a keyword.
     *
     * This tool is designed for LLM workflows that need to:
     * - discover the canonical definition of an openEHR type (class),
     * - locate the exact BMM JSON file that defines a type,
     * - and then fetch the full definition via `type_specification_get`.
     *
     * Matching behaviour (important for predictable client usage):
     * - `namePattern` supports a simple `*` wildcard (glob-like).
     * - `keyword` filtering is a plain substring check against the raw JSON contents.
     *
     * Returned fields (per result):
     * - `type`: the openEHR type name (from the JSON `name` field)
     * - `description`: documentation/description (from JSON `documentation` when present)
     * - `component`: the openEHR component name (e.g. `AM`, `RM`, etc.)
     * - `file`: relative path under the BMM directory (pass this into `type_specification_get` for exact retrieval)
     *
     * If nothing matches, this tool returns a single-element array describing the error condition.
     * Treat that as “no results” rather than an exception.
     *
     * @param string $namePattern
     *   A type-name pattern. Examples:
     *   - `ARCHETYPE_SLOT` (exact)
     *   - `ARCHETYPE_SL*` (wildcard prefix)
     *   - `DV_*` (family search)
     *
     * @param string $keyword
     *   Optional raw substring filter applied to the JSON content (not normalized; case-sensitive).
     *   Use this when you want to narrow results to types containing a concept or attribute name.
     *
     * @return array<int, array<string, string>>
     *   A list of metadata records (see fields above), or a single record with `error: not found`.
     */
    #[McpTool(name: 'type_specification_search')]
    public function search(string $namePattern, string $keyword = ''): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $namePattern = trim($namePattern);
        $keyword = trim($keyword);
        if (!$namePattern) {
            return [];
        }

        $results = [];
        foreach ($this->getCandidateFiles($namePattern) as $fileInfo) {
            try {
                $json = (string)file_get_contents($fileInfo->getPathname());
                if ($json) {
                    // keyword filter on content if provided
                    if ($keyword && stripos($json, $keyword) === false) {
                        continue;
                    }
                    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($data)) {
                        $results[] = [
                            'type' => (string)($data['name'] ?? $fileInfo->getFilename()),
                            'description' => (string)($data['documentation'] ?? $data['name'] ?? ''),
                            'component' => basename($fileInfo->getPath()),
                            'file' => str_replace(self::BMM_DIR . '/', '', $fileInfo->getPathname()),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to read/parse JSON', ['file' => $fileInfo->getPathname(), 'error' => $e->getMessage()]);
            }
        }
        $this->logger->info('BMM list results', ['count' => count($results), 'namePattern' => $namePattern, 'keyword' => $keyword]);
        $this->logger->debug('BMM list results', $results);
        return $results ?: [['error' => 'not found', 'namePattern' => $namePattern, 'keyword' => $keyword]];
    }

    /**
     * Retrieve one openEHR type specification as BMM JSON.
     *
     * Use this tool when you need the full, machine-readable BMM definition for a type so an LLM can:
     * - inspect properties/attributes and their declared types,
     * - understand inheritance (supertypes/subtypes),
     * - reason about constraints and semantics encoded in the BMM model,
     * - or generate client code / mappings based on the canonical model definition.
     *
     * Output:
     * - Returns text with the raw BMM JSON file contents.
     *
     * Error handling:
     * - If nothing matches, returns a JSON object with `{ "error": "not found", "identifier": "..." }` as text content.
     *   (This is a non-exceptional "not found" outcome; clients should handle it explicitly.)
     *
     *
     * @param string $typeOrFile
     *   Either a relative BMM JSON file path under the bundled resources, or a type name or wildcard pattern.
     *
     * @return TextContent
     *   The resolved BMM JSON as `json` text content (or an error JSON payload if not found).
     *
     * @throws \InvalidArgumentException
     *   If the identifier is empty after normalization.
     */
    #[McpTool(name: 'type_specification_get')]
    public function get(string $typeOrFile): TextContent
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        // Normalize identifier
        $typeOrFile = trim((string)str_replace('..', '', $typeOrFile));
        if (!$typeOrFile) {
            throw new \InvalidArgumentException('Identifier cannot be empty');
        }
        // First, try as a relative path
        $candidate = self::BMM_DIR . '/' . str_replace('\\', '/', $typeOrFile);
        if (is_file($candidate) && is_readable($candidate)) {
            $this->logger->info('Found bmm', ['filename' => $candidate]);
            $json = (string)file_get_contents($candidate);
            return TextContent::code($json, 'json');
        }
        // Then, search by type name
        foreach ($this->getCandidateFiles($typeOrFile) as $fileInfo) {
            $this->logger->info('Found bmm', ['pattern' => $fileInfo->getFilename()]);
            $json = (string)file_get_contents($fileInfo->getPathname());
            return TextContent::code($json, 'json');
        }
        $this->logger->info('Bmm not found', ['identifier' => $typeOrFile]);
        $json = (string)json_encode(['error' => 'not found', 'identifier' => $typeOrFile], JSON_PRETTY_PRINT);
        return TextContent::code($json, 'json');
    }
}
