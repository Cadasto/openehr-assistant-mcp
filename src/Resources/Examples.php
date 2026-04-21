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

    /**
     * Read an openEHR example artefact (AQL query, FLAT JSON payload, STRUCTURED JSON payload, etc.)
     * from the resources/examples tree.
     *
     * URI template:
     *  openehr://examples/{kind}/{name}
     *
     * Examples:
     *  - openehr://examples/aql/latest_blood_pressure
     *  - openehr://examples/flat/blood_pressure_vital_signs
     *  - openehr://examples/structured/blood_pressure_vital_signs
     */
    #[McpResourceTemplate(
        uriTemplate: 'openehr://examples/{kind}/{name}',
        name: 'examples',
        description: 'Curated openEHR example artefact (AQL query, FLAT/STRUCTURED JSON payload) wrapped in a Markdown file with metadata header and fenced code block',
        mimeType: 'text/markdown'
    )]
    public function read(
        #[CompletionProvider(values: ['aql', 'flat', 'structured'])]
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

        $path = self::DIR . "/$kind/$name.md";
        if (!\is_file($path) || !\is_readable($path)) {
            throw new ResourceReadException(\sprintf('Example not found: %s/%s', $kind, $name));
        }

        return \file_get_contents($path) ?: throw new ResourceReadException(\sprintf('Unable to read example %s/%s content.', $kind, $name));
    }

    /**
     * Registers example markdown files as MCP resources for discoverability.
     *
     * Folder structure:
     * resources/examples/{kind}/{name}.md
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
                if (strtolower($fileInfo->getExtension()) !== 'md') {
                    continue;
                }

                $relative = str_replace(self::DIR . '/', '', $fileInfo->getPathname());
                $parts = explode('/', $relative);
                if (count($parts) < 2) {
                    continue;
                }

                $basename = $fileInfo->getBasename('.md');
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

                $lines = explode("\n", $content, 2);
                $description = trim($lines[0], ' #') ?: sprintf('openEHR example %s for %s', $name, $kind);

                $builder->addResource(
                    handler: fn() => (string)$content,
                    uri: sprintf('openehr://examples/%s/%s', $kind, $name),
                    name: sprintf('example_%s_%s', $kind, $name),
                    description: $description,
                    mimeType: 'text/markdown',
                    size: strlen($content),
                );
            }
        }
    }
}
