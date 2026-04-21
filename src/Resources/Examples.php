<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Resources;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\Examples as ExamplesCompletionProvider;
use FilesystemIterator;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Examples
{

    public const string DIR = APP_DIR . '/resources/examples';

    /** Extensions the scanner and reader recognise, in preferred-lookup order. */
    private const array SUPPORTED_EXTENSIONS = ['md', 'adl'];

    /**
     * Read an openEHR example artefact (AQL query, FLAT/STRUCTURED JSON payload, ADL archetype)
     * from the resources/examples tree.
     *
     * URI template:
     *  openehr://examples/{kind}/{name}
     *
     * Examples:
     *  - openehr://examples/aql/latest_blood_pressure_per_ehr
     *  - openehr://examples/flat/vital_signs_blood_pressure
     *  - openehr://examples/structured/vital_signs_blood_pressure
     *  - openehr://examples/archetypes/openEHR-EHR-OBSERVATION.blood_pressure.v2
     */
    #[McpResourceTemplate(
        uriTemplate: 'openehr://examples/{kind}/{name}',
        name: 'examples',
        description: 'Curated openEHR example artefact — AQL query, FLAT/STRUCTURED JSON payload (Markdown-wrapped) or ADL archetype (native .adl)',
        mimeType: 'text/markdown'
    )]
    public function read(
        #[CompletionProvider(values: ['aql', 'flat', 'structured', 'archetypes'])]
        string $kind,
        #[CompletionProvider(provider: ExamplesCompletionProvider::class)]
        string $name
    ): string
    {
        foreach ([$kind, $name] as $segment) {
            if ($segment === '' || !\preg_match('/^[\w.-]+$/', $segment)) {
                throw new ResourceReadException(\sprintf('Invalid example resource identifier: %s', $segment));
            }
        }

        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $path = self::DIR . "/$kind/$name.$ext";
            if (\is_file($path) && \is_readable($path)) {
                return \file_get_contents($path) ?: throw new ResourceReadException(\sprintf('Unable to read example %s/%s content.', $kind, $name));
            }
        }

        throw new ResourceReadException(\sprintf('Example not found: %s/%s', $kind, $name));
    }

    /**
     * Registers example files as MCP resources for discoverability.
     *
     * Folder structure:
     * resources/examples/{kind}/{name}.{md|adl}
     *
     * @param Builder $builder The resource builder instance used to register the examples.
     * @return void
     */
    public static function addResources(Builder $builder): void
    {
        if (is_dir(self::DIR) && is_readable(self::DIR)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(self::DIR, FilesystemIterator::SKIP_DOTS)
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

                $relative = str_replace(self::DIR . '/', '', $fileInfo->getPathname());
                $parts = explode('/', $relative);
                if (count($parts) < 2) {
                    continue;
                }

                $basename = $fileInfo->getBasename('.' . $ext);
                if ($basename === 'README' || str_starts_with($basename, '_')) {
                    // skip per-kind README files and underscore-prefixed scaffolding
                    continue;
                }

                $content = @file_get_contents($fileInfo->getPathname());
                if (empty($content)) {
                    continue;
                }

                $kind = $parts[0];
                $name = $basename;

                // Description: Markdown first heading, or archetype identifier line for .adl, else fallback
                $description = self::extractDescription($content, $ext, $kind, $name);

                $builder->addResource(
                    handler: fn() => (string)$content,
                    uri: sprintf('openehr://examples/%s/%s', $kind, $name),
                    name: sprintf('example_%s_%s', $kind, $name),
                    description: $description,
                    mimeType: $ext === 'adl' ? 'text/plain' : 'text/markdown',
                    size: strlen($content),
                );
            }
        }
    }

    private static function extractDescription(string $content, string $ext, string $kind, string $name): string
    {
        if ($ext === 'md') {
            $lines = explode("\n", $content, 2);
            $first = trim($lines[0], " #");
            return $first !== '' ? $first : sprintf('openEHR example %s for %s', $name, $kind);
        }
        // ADL: archetype identifier is a more meaningful label than the first keyword line
        return sprintf('openEHR %s archetype: %s', $kind, $name);
    }
}
