<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Resources;

use Cadasto\OpenEHR\MCP\Assistant\CompletionProviders\ArchetypeGuidelines;
use FilesystemIterator;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Mcp\Server\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Guidelines
{

    public const string DIR = APP_DIR . '/resources/guidelines';

    /**
     * Read a guideline markdown file from the resources/guidelines tree.
     *
     * URI template:
     *  openehr://guidelines/{category}/{version}/{name}
     *
     * Examples:
     *  - openehr://guidelines/archetypes/v1/checklist
     *  - openehr://guidelines/archetypes/v1/adl-syntax
     */
    #[McpResourceTemplate(
        uriTemplate: 'openehr://guidelines/{category}/{version}/{name}',
        name: 'guideline',
        description: 'The openEHR Assistant guideline document (markdown) identified by category/version/name',
        mimeType: 'text/markdown'
    )]
    public function read(
        #[CompletionProvider(values: ['archetypes'])]
        string $category,
        #[CompletionProvider(values: ['v1'])]
        string $version,
        #[CompletionProvider(provider: ArchetypeGuidelines::class)]
        string $name
    ): string
    {
        foreach ([$category, $version, $name] as $segment) {
            if ($segment === '' || !\preg_match('/^[\w-]+$/', $segment)) {
                throw new \InvalidArgumentException(\sprintf('Invalid guideline resource identifier: %s', $segment));
            }
        }

        $path = self::DIR . "/$category/$version/$name.md";
        if (!\is_file($path) || !\is_readable($path)) {
            throw new ResourceReadException(\sprintf('Guideline not found: %s/%s/%s', $category, $version, $name));
        }

        return \file_get_contents($path) ?: throw new ResourceReadException(\sprintf('Unable to read guideline %s/%s/%s content.', $category, $version, $name));
    }

    /**
     * Registers guideline markdown files as MCP resources for discoverability.
     *
     * This method scans a predefined directory for markdown files organized in a
     * specific folder structure, parses the files' metadata, and registers them
     * with the provided builder as resources accessible via uniform resource
     * identifiers (URIs).
     *
     * Folder structure:
     * resources/guidelines/{category}/{version}/{name}.md
     *
     * @param Builder $builder The resource builder instance used to register the guidelines.
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

                // Expect path like resources/guidelines/{category}/{version}/{name}.md
                $relative = str_replace(self::DIR . '/', '', $fileInfo->getPathname());
                $parts = explode('/', $relative);
                if (count($parts) < 3) {
                    // not matching guideline structure
                    continue;
                }

                $content = @file_get_contents($fileInfo->getPathname());
                if (empty($content)) {
                    continue;
                }

                $category = $parts[0];
                $version = $parts[1];
                $name = $fileInfo->getBasename('.md');

                $lines = explode("\n", $content, 2);
                $description = trim($lines[0], ' #') ?: sprintf('Guideline %s for %s', $name, $category);

                $builder->addResource(
                    handler: fn() => (string)$content,
                    uri: sprintf('openehr://guidelines/%s/%s/%s', $category, $version, $name),
                    name: sprintf('guideline_%s_%s', $category, $name),
                    description: $description,
                    mimeType: 'text/markdown',
                    size: strlen($content),
                );
            }
        }
    }
}
