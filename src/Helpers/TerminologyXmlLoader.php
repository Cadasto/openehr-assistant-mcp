<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Helpers;

use RuntimeException;
use SimpleXMLElement;

final class TerminologyXmlLoader
{
    /** @var array<string, SimpleXMLElement> Parsed documents by the path they were read from. */
    private static array $documents = [];

    public static function load(string $path): SimpleXMLElement
    {
        if (isset(self::$documents[$path])) {
            return self::$documents[$path];
        }

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException('Terminology file not found or not readable.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read terminology file.');
        }

        // Collect libxml errors instead of letting each one surface as a PHP warning: on the
        // stdio transport a warning printed to stdout would corrupt the JSON-RPC stream.
        $previousMode = libxml_use_internal_errors(true);
        try {
            $xml = new SimpleXMLElement($content);
        } catch (\Exception $e) {
            throw new RuntimeException('Error parsing terminology XML: ' . $e->getMessage(), previous: $e);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousMode);
        }

        $groups = $xml->xpath('/terminology/group');
        if (empty($groups)) {
            throw new RuntimeException('No terminology groups found.');
        }

        return self::$documents[$path] = $xml;
    }
}
