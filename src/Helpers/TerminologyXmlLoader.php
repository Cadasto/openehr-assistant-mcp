<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Helpers;

use RuntimeException;
use SimpleXMLElement;

final class TerminologyXmlLoader
{
    private static ?SimpleXMLElement $xml = null;

    public static function load(string $path): SimpleXMLElement
    {
        if (self::$xml !== null) {
            return self::$xml;
        }

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException('Terminology file not found or not readable.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read terminology file.');
        }

        try {
            $xml = new SimpleXMLElement($content);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error parsing terminology XML: ' . $e->getMessage(), previous: $e);
        }

        $groups = $xml->xpath('/terminology/group');
        if (empty($groups)) {
            throw new RuntimeException('No terminology groups found.');
        }

        self::$xml = $xml;

        return self::$xml;
    }
}
