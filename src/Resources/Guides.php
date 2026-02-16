<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Resources;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\Guides as GuidesCompletionProvider;
use FilesystemIterator;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Guides
{

    public const string DIR = APP_DIR . '/resources/guides';

    /**
     * Read a guide markdown file from the resources/guides tree.
     *
     * URI template:
     *  openehr://guides/{category}/{name}
     *
     * Examples:
     *  - openehr://guides/archetypes/checklist
     *  - openehr://guides/archetypes/adl-syntax
     *  - openehr://guides/templates/explain-template
     *  - openehr://guides/aql/syntax
     */
    #[McpResourceTemplate(
        uriTemplate: 'openehr://guides/{category}/{name}',
        name: 'guides',
        description: 'The openEHR Assistant guides document (markdown) identified by category and name',
        mimeType: 'text/markdown'
    )]
    public function read(
        #[CompletionProvider(values: ['archetypes', 'templates', 'aql', 'simplified_formats'])]
        string $category,
        #[CompletionProvider(provider: GuidesCompletionProvider::class)]
        string $name
    ): string
    {
        foreach ([$category, $name] as $segment) {
            if ($segment === '' || !\preg_match('/^[\w-]+$/', $segment)) {
                throw new ResourceReadException(\sprintf('Invalid guide resource identifier: %s', $segment));
            }
        }

        $path = self::DIR . "/$category/$name.md";
        if (!\is_file($path) || !\is_readable($path)) {
            throw new ResourceReadException(\sprintf('Guide not found: %s/%s', $category, $name));
        }

        return \file_get_contents($path) ?: throw new ResourceReadException(\sprintf('Unable to read guide %s/%s content.', $category, $name));
    }

    /**
     * Registers guide markdown files as MCP resources for discoverability.
     *
     * This method scans a predefined directory for markdown files organized in a
     * specific folder structure, parses the files' metadata, and registers them
     * with the provided builder as resources accessible via uniform resource
     * identifiers (URIs).
     *
     * Folder structure:
     * resources/guides/{category}/{name}.md
     *
     * @param Builder $builder The resource builder instance used to register the guides.
     * @return void This method does not return a value.
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
                if ($ext !== 'md') {
                    continue;
                }

                // Expect path like resources/guides/{category}/{name}.md
                $relative = str_replace(self::DIR . '/', '', $fileInfo->getPathname());
                $parts = explode('/', $relative);
                if (count($parts) < 2) {
                    // not matching guides structure
                    continue;
                }

                $content = @file_get_contents($fileInfo->getPathname());
                if (empty($content)) {
                    continue;
                }

                $category = $parts[0];
                $name = $fileInfo->getBasename('.md');

                $lines = explode("\n", $content, 2);
                $description = trim($lines[0], ' #') ?: sprintf('Guide %s for %s', $name, $category);

                $builder->addResource(
                    handler: fn() => (string)$content,
                    uri: sprintf('openehr://guides/%s/%s', $category, $name),
                    name: sprintf('guide_%s_%s', $category, $name),
                    description: $description,
                    mimeType: 'text/markdown',
                    size: strlen($content),
                );
            }
        }
    }
}
