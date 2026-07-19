<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Sdd;

use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * The Specification-Driven Development drift gate (REQ-N8, ADR-0006).
 *
 * Validates the machine-readable traceability map (`docs/traceability.yaml`)
 * against the working tree and the requirements index. It fails when the map
 * and reality disagree — a cited package/test/plan path is missing, an ADR
 * reference does not resolve, a requirement appears in the index but not the
 * map (or vice-versa), or a `landed` requirement lists neither code nor tests.
 * It carries no normative prose; it only checks referential integrity.
 */
final class SpecCheck
{
    private const VALID_STATUS = ['draft', 'stable', 'deprecated'];
    private const VALID_IMPLEMENTATION = ['planned', 'partial', 'landed', 'proposed', 'in_progress', 'shipped', 'deferred'];
    private const REQUIRED_FIELDS = ['id', 'title', 'canonical', 'status', 'implementation'];

    public function __construct(private readonly string $root)
    {
    }

    /**
     * Run the gate as a CLI check: report findings and return a process exit code.
     */
    public function run(): int
    {
        $errors = $this->check();

        if ($errors === []) {
            fwrite(STDOUT, "spec-check: OK — traceability map matches the tree.\n");
            return 0;
        }

        fwrite(STDERR, sprintf("spec-check: %d drift finding(s):\n", count($errors)));
        foreach ($errors as $error) {
            fwrite(STDERR, '  - ' . $error . "\n");
        }
        return 1;
    }

    /**
     * @return list<string> drift findings; empty when the map is clean
     */
    public function check(): array
    {
        $errors = [];

        $descriptor = $this->parseYaml('docs/.sdd.yaml', $errors);
        if ($descriptor === null) {
            return $errors;
        }

        $sdd = $this->asArray($descriptor['sdd'] ?? null);
        $paths = $this->asArray($sdd['paths'] ?? null);
        $tracePath = $this->asString($sdd['traceability'] ?? null, 'docs/traceability.yaml');
        $adrDir = $this->asString($paths['adr'] ?? null, 'docs/decisions');
        $reqIndex = $this->asString($paths['requirements'] ?? null, 'docs/requirements.md');
        $areas = $this->stringList($sdd['req_areas'] ?? null);

        $map = $this->parseYaml($tracePath, $errors);
        if ($map === null) {
            return $errors;
        }
        $records = is_array($map['requirements'] ?? null) ? $map['requirements'] : [];

        $indexIds = $this->requirementIdsIn($reqIndex, $areas);
        $matrixIds = $this->requirementIdsIn('docs/traceability.md', $areas);
        $idPattern = $areas === []
            ? '/^REQ-[A-Z]+\d+$/'
            : '/^REQ-(?:' . implode('|', array_map(static fn (string $a): string => preg_quote($a, '/'), $areas)) . ')\d+$/';

        /** @var array<string, true> $mapIds */
        $mapIds = [];
        foreach ($records as $position => $record) {
            if (!is_array($record)) {
                $errors[] = sprintf('record #%d is not a mapping', (int) $position);
                continue;
            }

            $id = $this->asString($record['id'] ?? null, '');
            $label = $id !== '' ? $id : sprintf('record #%d', (int) $position);

            foreach (self::REQUIRED_FIELDS as $field) {
                $value = $record[$field] ?? null;
                if ($value === null || $value === '') {
                    $errors[] = sprintf('%s: missing required field "%s"', $label, $field);
                }
            }
            if ($id === '') {
                continue;
            }

            if (preg_match($idPattern, $id) !== 1) {
                $errors[] = sprintf('%s: id does not match req_style (areas: %s)', $id, implode(', ', $areas));
            }
            if (isset($mapIds[$id])) {
                $errors[] = sprintf('%s: duplicate id in the map', $id);
            }
            $mapIds[$id] = true;

            $status = $record['status'] ?? null;
            if (is_string($status) && !in_array($status, self::VALID_STATUS, true)) {
                $errors[] = sprintf('%s: invalid status "%s"', $id, $status);
            }
            $implementation = $record['implementation'] ?? null;
            if (is_string($implementation) && !in_array($implementation, self::VALID_IMPLEMENTATION, true)) {
                $errors[] = sprintf('%s: invalid implementation "%s"', $id, $implementation);
            }

            $canonical = $this->asString($record['canonical'] ?? null, '');
            if ($canonical !== '') {
                $canonicalFile = explode('#', $canonical, 2)[0];
                if (!$this->pathExists($canonicalFile)) {
                    $errors[] = sprintf('%s: canonical file missing: %s', $id, $canonicalFile);
                }
            }

            $packages = $this->stringList($record['packages'] ?? null);
            $tests = $this->stringList($record['tests'] ?? null);
            $plans = $this->stringList($record['plans'] ?? null);
            foreach (['packages' => $packages, 'tests' => $tests, 'plans' => $plans] as $kind => $items) {
                foreach ($items as $path) {
                    if (!$this->pathExists($path)) {
                        $errors[] = sprintf('%s: %s path missing: %s', $id, $kind, $path);
                    }
                }
            }

            foreach ($this->stringList($record['adr'] ?? null) as $adr) {
                if ($this->globRoot($adrDir . '/' . $adr . '-*.md') === []) {
                    $errors[] = sprintf('%s: ADR reference "%s" does not resolve under %s/', $id, $adr, $adrDir);
                }
            }

            if (is_string($implementation) && in_array($implementation, ['landed', 'shipped'], true) && $packages === [] && $tests === []) {
                $errors[] = sprintf('%s: marked %s but lists neither packages nor tests', $id, $implementation);
            }

            if (!in_array($id, $indexIds, true)) {
                $errors[] = sprintf('%s: in the map but not in %s', $id, $reqIndex);
            }
            if (!in_array($id, $matrixIds, true)) {
                $errors[] = sprintf('%s: in the map but not in docs/traceability.md', $id);
            }
        }

        foreach ($indexIds as $indexId) {
            if (!isset($mapIds[$indexId])) {
                $errors[] = sprintf('%s: in %s but not traced in %s', $indexId, $reqIndex, $tracePath);
            }
        }

        return $errors;
    }

    /**
     * @param list<string> $errors
     * @return array<mixed>|null
     */
    private function parseYaml(string $relativePath, array &$errors): ?array
    {
        $absolute = $this->root . '/' . $relativePath;
        if (!is_file($absolute)) {
            $errors[] = sprintf('missing file: %s', $relativePath);
            return null;
        }

        try {
            $parsed = Yaml::parseFile($absolute);
        } catch (Throwable $exception) {
            $errors[] = sprintf('%s: YAML parse error: %s', $relativePath, $exception->getMessage());
            return null;
        }

        if (!is_array($parsed)) {
            $errors[] = sprintf('%s: expected a mapping at the top level', $relativePath);
            return null;
        }

        return $parsed;
    }

    /**
     * @param list<string> $areas
     * @return list<string>
     */
    private function requirementIdsIn(string $relativePath, array $areas): array
    {
        $absolute = $this->root . '/' . $relativePath;
        if (!is_file($absolute)) {
            return [];
        }

        $content = (string) file_get_contents($absolute);
        $pattern = $areas === []
            ? '/REQ-[A-Z]+\d+/'
            : '/REQ-(?:' . implode('|', array_map(static fn (string $a): string => preg_quote($a, '/'), $areas)) . ')\d+/';

        if (preg_match_all($pattern, $content, $matches) === false) {
            return [];
        }

        return array_values(array_unique($matches[0]));
    }

    private function pathExists(string $relativePath): bool
    {
        if ($relativePath === '') {
            return false;
        }

        $absolute = $this->root . '/' . $relativePath;
        if (is_file($absolute) || is_dir($absolute)) {
            return true;
        }

        return $this->globRoot($relativePath) !== [];
    }

    /**
     * @return list<string>
     */
    private function globRoot(string $relativeGlob): array
    {
        $result = glob($this->root . '/' . $relativeGlob);

        return $result === false ? [] : $result;
    }

    /**
     * @return array<mixed>
     */
    private function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function asString(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
