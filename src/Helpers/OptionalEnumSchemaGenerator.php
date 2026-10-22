<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Helpers;

use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Capability\Discovery\SchemaGeneratorInterface;

/**
 * Schema generator the server publishes tool input schemas with (REQ-N9, issue #28).
 */
final readonly class OptionalEnumSchemaGenerator implements SchemaGeneratorInterface
{
    public function __construct(
        private SchemaGeneratorInterface $inner = new SchemaGenerator(new DocBlockParser()),
    ) {
    }

    #[\Override]
    public function generate(\Reflector $reflection): array
    {
        return $this->inner->generate($reflection);
    }

    #[\Override]
    public function generateOutputSchema(\Reflector $reflection): ?array
    {
        return $this->inner->generateOutputSchema($reflection);
    }
}
