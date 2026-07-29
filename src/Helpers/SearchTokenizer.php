<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Helpers;

/**
 * Shared query tokenizer for the corpus search tools (`guide_search`, `examples_search`).
 *
 * Extracted so the two tools cannot drift apart again: `guide_search` was fixed to fold
 * case with `mb_strtolower` and to split on punctuation, while `examples_search` kept
 * byte-wise `strtolower` and whitespace-only splitting — so the same query scored
 * differently depending on which tool the model happened to call.
 */
final readonly class SearchTokenizer
{
    /**
     * Split a query into lower-cased search tokens.
     *
     * `-` and `.` are treated as word characters (as `_` already is) so that openEHR
     * identifiers survive as single tokens: splitting `openEHR-EHR-OBSERVATION` into
     * `openehr`/`ehr`/`observation` would match essentially every artefact in the corpus on
     * its most generic parts. Punctuation that genuinely separates terms — commas, slashes,
     * parentheses — still splits, so `DV_QUANTITY, DV_CODED_TEXT` yields two tokens. Stray
     * leading/trailing `-`/`.` (sentence punctuation) are trimmed off.
     *
     * @return list<string>|null
     *   Null when the query could not be tokenized — only reachable for invalid UTF-8, which
     *   trips `PREG_BAD_UTF8_ERROR` under the `/u` flag. Callers log and score nothing rather
     *   than silently treating it as an empty query, which would match everything.
     */
    public static function tokenize(string $query): ?array
    {
        $normalized = mb_strtolower(trim($query), 'UTF-8');
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/[^\p{L}\p{N}_.\-]+/u', $normalized);
        if ($parts === false) {
            return null;
        }

        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part, '-.');
            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }
}
