<?php

/**
 * CLI entrypoint for the SDD traceability drift gate (REQ-N8).
 *
 * Runs `Cadasto\OpenEHR\MCP\Assistant\Sdd\SpecCheck` against the repository root
 * and exits non-zero on drift, so `composer check:spec` / `make spec-check` /
 * CI all fail the build when the traceability map and the tree disagree.
 */

declare(strict_types=1);

use Cadasto\OpenEHR\MCP\Assistant\Sdd\SpecCheck;

require_once dirname(__DIR__) . '/vendor/autoload.php';

exit((new SpecCheck(dirname(__DIR__)))->run());
