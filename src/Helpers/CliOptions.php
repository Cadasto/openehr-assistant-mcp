<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Helpers;

readonly final class CliOptions
{
    /**
     * Parses optional CLI option: --transport=stdio|streamable-http (or "--transport stdio").
     *
     * @return string '' when not provided
     */
    public static function transportOption(): string
    {
        if (PHP_SAPI !== 'cli') {
            return '';
        }

        $opts = getopt('', ['transport:']);
        if ($opts === false) {
            return '';
        }

        if (!array_key_exists('transport', $opts)) {
            return '';
        }

        $value = $opts['transport'];

        if (is_array($value)) {
            throw new \InvalidArgumentException('Invalid --transport option: must be provided at most once.');
        }

        $value = strtolower(trim((string)$value));
        if ($value === '') {
            throw new \InvalidArgumentException('Invalid --transport option: value cannot be empty.');
        }

        $allowed = ['stdio', 'streamable-http'];
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException(
                'Invalid --transport option: expected one of: stdio | streamable-http.'
            );
        }

        return $value;
    }
}