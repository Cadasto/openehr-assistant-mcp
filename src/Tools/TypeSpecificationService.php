<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tools;

use Generator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
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
     * - Only files with the `.bmm.json` naming pattern are considered.
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
     * - locate the exact type specification URL or server resource URI,
     * - or fetch the full definition via the `type_specification_get` tool.
     *
     * @param string $namePattern
     *   A type-name pattern. Matching behaviour: minimal 3 chars, supports a simple `*` wildcard (glob-like). Examples:`ARCHETYPE_SLOT` (exact), `ARCHETYPE_SL*` (wildcard prefix), `DV_*` (family search).
     *
     * @param string $keyword
     *   Optional raw substring filter applied to the JSON content (not normalized; case-insensitive); use this when you want to narrow results to Types containing a concept or attribute name.
     *
     * @return array{items: list<array<string, string>>, total: int}
     *   A list of metadata records (see fields above), or an empty items list if nothing matches. See the `total` note in the outputSchema.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'type_specification_search',
        title: 'Search RM/AM type specifications',
        annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['items', 'total'],
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'List of matching openEHR Type specifications',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'documentation', 'resourceUri', 'component', 'package', 'specUrl'],
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'openEHR Type name (e.g. `DV_QUANTITY`)'],
                            'documentation' => ['type' => 'string', 'description' => 'Documentation or description of the type'],
                            'resourceUri' => ['type' => 'string', 'format' => 'uri', 'description' => 'URI of corresponding resource in the `openehr://spec/type` namespace'],
                            'component' => ['type' => 'string', 'description' => 'openEHR Component name (e.g. `AM`, `RM`, etc.)'],
                            'package' => ['type' => 'string', 'description' => 'Package name (e.g. `org.openehr.rm.datatypes`)'],
                            'specUrl' => ['type' => 'string', 'description' => 'Link to the corresponding openEHR specification page and fragment with more narrative details'],
                        ],
                    ],
                ],
                'total' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Number of items in `items`'],
            ],
        ],
    )]
    public function search(string $namePattern, string $keyword = ''): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $namePattern = trim($namePattern);
        $keyword = trim($keyword);
        if (!$namePattern || strlen($namePattern) < 3) {
            return ['items' => [], 'total' => 0];
        }

        $results = [];
        $skipped = 0;
        foreach ($this->getCandidateFiles($namePattern) as $fileInfo) {
            $json = file_get_contents($fileInfo->getPathname());
            if ($json === false || trim($json) === '') {
                // Distinguished from a decode failure: reporting a read/permissions
                // problem as "invalid JSON" sends the reader to inspect a valid file.
                ++$skipped;
                $this->logger->error('Could not read BMM file', ['file' => $fileInfo->getPathname()]);
                continue;
            }

            // keyword filter on content if provided
            if ($keyword && stripos($json, $keyword) === false) {
                continue;
            }

            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                ++$skipped;
                $this->logger->error('Failed to decode BMM JSON', ['file' => $fileInfo->getPathname(), 'error' => $e->getMessage()]);
                continue;
            }

            $component = strtoupper(basename($fileInfo->getPath()));
            $name = is_array($data) ? $this->bmmString($data['name'] ?? null) : '';
            if ($name === '') {
                ++$skipped;
                $this->logger->error('BMM document has no usable `name`', [
                    'file' => $fileInfo->getPathname(),
                    'decoded' => get_debug_type($data),
                ]);
                continue;
            }

            $results[] = [
                'name' => $name,
                'documentation' => $this->bmmString($data['documentation'] ?? null),
                'resourceUri' => $this->buildTypeUri($component, $name),
                'component' => $component,
                'package' => $this->bmmString($data['package'] ?? null),
                'specUrl' => $this->bmmString($data['specUrl'] ?? null),
            ];
        }

        if ($skipped > 0) {
            // `total` is documented as the item count, so an unreported skip would make
            // a truncated result set look complete.
            $this->logger->warning('Some BMM files were skipped; search results may be incomplete.', [
                'skipped' => $skipped,
                'namePattern' => $namePattern,
            ]);
        }

        $this->logger->info('BMM list results', ['count' => count($results), 'namePattern' => $namePattern, 'keyword' => $keyword]);
        return ['items' => $results, 'total' => count($results)];
    }

    /**
     * Narrow a value decoded from a BMM document to a string.
     *
     * A bare `(string)` cast would turn a nested object into the literal "Array" and
     * publish it as, say, a type name — so non-scalars collapse to '' instead.
     */
    private function bmmString(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        return is_int($value) || is_float($value) || is_bool($value) ? (string) $value : '';
    }

    private function buildTypeUri(string $component, string $name): string
    {
        return 'openehr://spec/type/' . $component . '/' . $name;
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
     * @param string|null $component
     *   Optional openEHR Component name, for better matching or filtering; if omitted, the first matching openEHR Type specification is returned.
     *
     * @return array<string, mixed>
     *   The openEHR Type as BMM JSON, plus a server-synthesised `resourceUri` that is not
     *   part of the BMM source document. `additionalProperties` stays open because BMM
     *   payloads legitimately carry model-specific keys beyond those described below.
     *
     * @throws ToolCallException
     *   If the name is empty after normalization, if no matching specification is found,
     *   or if the matched BMM document is unreadable or malformed.
     */
    #[Schema(additionalProperties: false)]
    #[McpTool(
        name: 'type_specification_get',
        title: 'Get RM/AM type specification',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
        outputSchema: [
            'type' => 'object',
            'additionalProperties' => true,
            'required' => ['name', 'resourceUri'],
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'openEHR Type name (e.g. `DV_QUANTITY`)'],
                'documentation' => ['type' => 'string', 'description' => 'Documentation or description of the type'],
                'is_abstract' => ['type' => 'boolean', 'description' => 'Whether the type is abstract (i.e. cannot be instantiated)'],
                'ancestors' => ['type' => 'array', 'description' => 'List of ancestor types (super-types)'],
                'resourceUri' => ['type' => 'string', 'format' => 'uri', 'description' => 'URI of corresponding resource in the `openehr://spec/type` namespace'],
                'constants' => ['type' => 'object', 'description' => 'List of constants/enum values'],
                'properties' => ['type' => 'object', 'description' => 'List of attributes/properties'],
                'functions' => ['type' => 'object', 'description' => 'List of functions'],
                'invariants' => ['type' => 'object', 'description' => 'List of semantic constraints'],
                'package' => ['type' => 'string', 'description' => 'Package name (e.g. `org.openehr.rm.datatypes`)'],
                'specUrl' => ['type' => 'string', 'description' => 'Link to the corresponding openEHR specification page and fragment with more narrative details'],
            ],
        ],
    )]
    public function get(
        string $name,
        #[Schema(enum: ['AM', 'AM2', 'BASE', 'LANG', 'RM', 'TERM', null])]
        ?string $component = null,
    ): array
    {
        $this->logger->debug('called ' . __METHOD__, func_get_args());
        $name = trim((string)str_replace(['.', '*', '/', '\\'], '', $name));
        $component = strtoupper(trim((string)($component ?? '')));
        if (!$name) {
            throw new ToolCallException('Name cannot be empty');
        }
        foreach ($this->getCandidateFiles($name) as $fileInfo) {
            $this->logger->info('Found BMM', ['pattern' => $fileInfo->getFilename()]);
            // Upper-cased to match both `$component` and the casing `search()` publishes,
            // so the same type yields an identical `resourceUri` from either tool.
            $fileComponent = strtoupper(basename($fileInfo->getPath()));
            if ($component && $component !== $fileComponent) {
                $this->logger->info('Component not matching', ['pattern' => $fileInfo->getFilename()]);
                continue;
            }

            $json = file_get_contents($fileInfo->getPathname());
            if ($json === false) {
                $this->logger->error('Could not read BMM file', ['file' => $fileInfo->getPathname()]);
                throw new ToolCallException('Could not read BMM specification for type: ' . $name);
            }

            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->logger->error('Failed to decode BMM JSON', ['file' => $fileInfo->getPathname(), 'error' => $e->getMessage()]);
                throw new ToolCallException('Failed to decode BMM JSON for type: ' . $name, previous: $e);
            }

            // A document that decodes to null/scalar would either auto-vivify into an
            // array holding only `resourceUri` — breaking the declared `required` keys —
            // or raise a TypeError on the offset write.
            if (!is_array($data)) {
                $this->logger->error('BMM document is not an object', [
                    'file' => $fileInfo->getPathname(),
                    'decoded' => get_debug_type($data),
                ]);
                throw new ToolCallException('Malformed BMM specification for type: ' . $name);
            }

            $resolvedName = $this->bmmString($data['name'] ?? null);
            $data['name'] = $resolvedName !== '' ? $resolvedName : $name;
            $data['resourceUri'] = $this->buildTypeUri($fileComponent, (string) $data['name']);

            return $data;
        }
        $this->logger->info('BMM not found', ['name' => $name, 'component' => $component]);
        throw new ToolCallException("Type '$name' not found (in '$component' component).");
    }
}
