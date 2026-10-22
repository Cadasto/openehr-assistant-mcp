---
kind: plan
---
# Backlog

Leftovers of merged branches: defects outside their change, suggestions, and findings the maintainer deferred here. Each line is a lead, not a finding: verify it before acting. Themes group what one delivery takes, defects first; `sdd-pr harvest` and `sdd-pr flip --carry` add lines under Unsorted, and `/sdd-triage --backlog` sorts them, merges duplicates and drops what is settled. Line numbers are as of the pull request in `from:`. Edit freely.

## Unsorted
- tests/Content/HttpBoundaryTest.php:79 · URL_READERS covers five readers, so writing to a URL (file_put_contents, the target of copy) and other URL-accepting APIs (SplFileObject, simplexml_load_file, DOMDocument::load, hash_file) still pass; the docblock lists them as blind spots · by: sdd-implementer · from: chore/sdd-informative-profile
- tests/Content/HttpBoundaryTest.php:36 · A namespace alias (use GuzzleHttp as G; new G\Client()) still passes the boundary scan; tracking namespace aliases would close it cheaply · by: sdd-implementer · from: chore/sdd-informative-profile
- tests/phpstan.neon:5 · composer check:phpstan (and make ci) analyses only public/ and src/, so test files are type-checked only by a separate run · by: sdd-implementer · from: chore/sdd-informative-profile
- tests/Content/HttpBoundaryTest.php:56 · GuzzleHttp\Utils::chooseHandler() builds the default cURL handler and still passes the boundary scan; listing the whole Utils class would also flag harmless helpers, so it stays with review · by: sdd-implementer · from: chore/sdd-informative-profile
- composer.json:26 · php-cs-fixer is not a dev dependency, so the PER-CS style check the php-coding standard names cannot run here. · by: sdd-implementer · from: fix/optional-enum-schema
- docs/testing.md:43 · The README half is not a general guide-claim match and is not REQ-N1; the test only rejects a chunking claim on the guide_get bullet. · by: sdd-doc-reviewer · from: chore/sdd-informative-profile
