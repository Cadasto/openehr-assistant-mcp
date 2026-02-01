<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Generator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
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

    /**
     * Retrieves candidate files from the BMM directory matching a specified name pattern.
     *
     * This method generates a list of file objects from the defined BMM directory that match the given name pattern.
     * The name pattern supports a simple `*` wildcard and case-insensitive matching for `.bmm.json` files.
     *
     * Matching behavior:
     * - The `namePattern` is transformed into a case-insensitive regular expression.
     * - The pattern supports `*` as a wildcard for multiple characters.
     * - Only files with the `.json` extension are considered.
     * - Files must be readable and non-empty to be included in the results.
     *
     * @param string $namePattern
     *   The name pattern to match file names against. The pattern supports:
     *   - Wildcard `*` for zero or more characters.
     *   - Exact matching for specified strings.
     *   The file extension `.bmm.json` is automatically appended during the match.
     *
     * @return Generator
     *   A generator yielding `SplFileInfo` objects for matching files.
     */
    public function getCandidateFiles(string $namePattern): Generator
    {
        // prepare glob-like regex from the pattern (supports * wildcard)
        $namePattern = strtoupper(trim($namePattern));
        $namePattern = str_replace(['\\*', '\\?', '.', '\\/'], ['[\w-]*', '[\w-]', '', ''], preg_quote($namePattern, '/'));
        $regex = '/^' . $namePattern . '\.bmm\.json$/i';

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
     * Search for and discover openEHR Type specifications by name pattern with an optional keyword filter to locate canonical definitions and resource URIs.
     *
     * This tool is designed for LLM workflows that need to:
     * - discover the canonical definition of an openEHR Type (class),
     * - locate the exact type specification server resource uri,
     * - or fetch the full definition via the `type_specification_get` tool.
     *
     * @param string $namePattern
     *   A type-name pattern. Matching behaviour: minimal 3 chars, supports a simple `*` wildcard (glob-like). Examples:`ARCHETYPE_SLOT` (exact), `ARCHETYPE_SL*` (wildcard prefix), `DV_*` (family search).
     *
     * @param string $keyword
     *   Optional raw substring filter applied to the JSON content (not normalized; case-insensitive); use this when you want to narrow results to Types containing a concept or attribute name.
     *
     * @return array<string, array<int, array<string, string|null>>>
     *   A list of metadata records (see fields above), or an empty array if nothing matches.
     */
    #[McpTool(
        name: 'type_specification_search',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of matching openEHR Type specifications',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'openEHR Type name (e.g. `DV_QUANTITY`)'],
                            'documentation' => ['type' => 'string', 'description' => 'Documentation or description of the type'],
                            'resourceUri' => ['type' => 'string', 'description' => 'URI of corresponding resource in the `openehr://spec/type` namespace'],
                            'component' => ['type' => 'string', 'description' => 'openEHR Component name (e.g. `AM`, `RM`, etc.)'],
                            'package' => ['type' => 'string', 'description' => 'Package name (e.g. `org.openehr.rm.datatypes`)'],
                            'specUrl' => ['type' => 'string', 'description' => 'Link to the corresponding openEHR specification page and fragment with more narrative details'],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function search(string $namePattern, string $keyword = ''): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $namePattern = trim($namePattern);
        $keyword = trim($keyword);
        if (!$namePattern || strlen($namePattern) < 3) {
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
                        $name = (string)($data['name'] ?? $fileInfo->getFilename());
                        $component = strtoupper(basename($fileInfo->getPath()));
                        $results[] = [
                            'name' => $name,
                            'documentation' => $data['documentation'] ?? null,
                            'resourceUri' => 'openehr://spec/type/' . $component . '/' . $name,
                            'component' => $component,
                            'package' => $data['package'] ?? null,
                            'specUrl' => $data['specUrl'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to read/parse JSON', ['file' => $fileInfo->getPathname(), 'error' => $e->getMessage()]);
            }
        }
        $this->logger->info('BMM list results', ['count' => count($results), 'namePattern' => $namePattern, 'keyword' => $keyword]);
        return ['items' => $results ?: []];
    }

    /**
     * Retrieve the full specification of a specific openEHR Type (class) as BMM JSON, including attributes, semantic constraints and documentation.
     *
     * Use this tool when you need to retrieve the full, machine-readable BMM definition for a type so an LLM can:
     * - inspect properties/attributes and their declared types,
     * - understand inheritance (super-types/sub-types),
     * - or generate client code / mappings based on the canonical model definition.
     *
     * @param string $name
     *   The openEHR Type name (e.g. `DV_QUANTITY`, `COMPOSITION`, etc.)
     *
     * @param string $component
     *   Optional, the openEHR Component name (e.g. `RM`, `AM`, `BASE`, etc.), for better matching or filtering; if omitted, the first matching openEHR Type specification is returned.
     *
     * @return array<string, mixed>
     *   The openEHR Type as BMM JSON.
     *
     * @throws ToolCallException
     *   If the name is empty after normalization, or if no matching specification is found.
     */
    #[McpTool(
        name: 'type_specification_get',
        annotations: new ToolAnnotations(readOnlyHint: true),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'openEHR Type name (e.g. `DV_QUANTITY`)'],
                'documentation' => ['type' => 'string', 'description' => 'Documentation or description of the type'],
                'is_abstract' => ['type' => 'boolean', 'description' => 'Whether the type is abstract (i.e. cannot be instantiated)'],
                'ancestors' => ['type' => 'array', 'description' => 'List of ancestor types (super-types)'],
                'resourceUri' => ['type' => 'string', 'description' => 'URI of corresponding resource in the `openehr://spec/type` namespace'],
                'constants' => ['type' => 'array', 'description' => 'List of constants/enum values'],
                'properties' => ['type' => 'array', 'description' => 'List of attributes/properties'],
                'functions' => ['type' => 'array', 'description' => 'List of functions'],
                'invariants' => ['type' => 'array', 'description' => 'List of semantic constraints'],
                'package' => ['type' => 'string', 'description' => 'Package name (e.g. `org.openehr.rm.datatypes`)'],
                'specUrl' => ['type' => 'string', 'description' => 'Link to the corresponding openEHR specification page and fragment with more narrative details'],
            ],
        ],
    )]
    public function get(string $name, string $component = ''): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $name = trim((string)str_replace(['.', '*', '/', '\\'], '', $name));
        if (!$name) {
            throw new ToolCallException('Name cannot be empty');
        }
        foreach ($this->getCandidateFiles($name) as $fileInfo) {
            $this->logger->info('Found BMM', ['pattern' => $fileInfo->getFilename()]);
            if ($component && $component !== basename($fileInfo->getPath())) {
                $this->logger->info('Component not matching', ['pattern' => $fileInfo->getFilename()]);
                continue;
            }
            $json = (string)file_get_contents($fileInfo->getPathname());
            try {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->logger->error('Failed to decode BMM JSON', ['file' => $fileInfo->getPathname(), 'error' => $e->getMessage()]);
                throw new ToolCallException('Failed to decode BMM JSON for type: ' . $name, previous: $e);
            }
        }
        $this->logger->info('BMM not found', ['name' => $name, 'component' => $component]);
        throw new ToolCallException("Type '$name' not found (in '$component' component).");
    }
}
