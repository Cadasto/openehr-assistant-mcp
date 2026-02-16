<?php

namespace Cadasto\OpenEHR\MCP\Assistant\CompletionProviders;

use Mcp\Capability\Completion\ProviderInterface;

class Guides implements ProviderInterface
{

    /**
     * Retrieves a list of files from the specified directories.
     *
     * @param array<string> $directories An array of directory paths to scan for files.
     * @return array<string> An array of file names found in the specified directories.
     */
    private function getFiles(array $directories): array
    {
        $files = [];
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $files = array_merge($files, scandir($directory));
        }
        return $files;
    }

    public function getCompletions(string $currentValue): array
    {
        $files = $this->getFiles([
            APP_RESOURCES_DIR . '/guides/archetypes',
            APP_RESOURCES_DIR . '/guides/templates',
            APP_RESOURCES_DIR . '/guides/aql',
        ]);
        $completions = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (str_ends_with($file, '.md')) {
                $filename = substr($file, 0, -3);
                if (!$currentValue || str_starts_with($filename, $currentValue)) {
                    $completions[] = $filename;
                }
            }
        }

        return $completions;
    }
}
