<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\CompletionProviders;

use Mcp\Capability\Completion\ProviderInterface;

class SpecificationComponents implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $directory = APP_RESOURCES_DIR . '/bmm';
        $dirs = scandir($directory);
        $completions = [];

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            if (is_dir($directory . '/'. $dir)) {
                if (!$currentValue || str_starts_with($dir, $currentValue)) {
                    $completions[] = $dir;
                }
            }
        }

        return $completions;
    }
}