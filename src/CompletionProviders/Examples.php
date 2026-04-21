<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\CompletionProviders;

use Mcp\Capability\Completion\ProviderInterface;

class Examples implements ProviderInterface
{

    /**
     * @param array<string> $directories
     * @return array<string>
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
            APP_RESOURCES_DIR . '/examples/aql',
            APP_RESOURCES_DIR . '/examples/flat',
            APP_RESOURCES_DIR . '/examples/structured',
            APP_RESOURCES_DIR . '/examples/archetypes',
        ]);
        $completions = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'README.md') {
                continue;
            }
            if (str_starts_with($file, '_')) {
                continue;
            }

            $filename = null;
            if (str_ends_with($file, '.md')) {
                $filename = substr($file, 0, -3);
            } elseif (str_ends_with($file, '.adl')) {
                $filename = substr($file, 0, -4);
            }
            if ($filename !== null && (!$currentValue || str_starts_with($filename, $currentValue))) {
                $completions[] = $filename;
            }
        }

        return array_values(array_unique($completions));
    }
}
