---
kind: plan
harvested_through: 2026-10-22T21:15:23Z
---
# Backlog

Leftovers of merged branches: defects outside their change, suggestions, and findings the maintainer deferred here. Each line is a lead, not a finding: verify it before acting. Themes group what one delivery takes, defects first; `sdd-pr harvest` and `sdd-pr flip --carry` add lines under Unsorted, and `/sdd-triage --backlog` sorts them, merges duplicates and drops what is settled. Line numbers are as of the pull request in `from:`. Edit freely.

## Unsorted
- src/Tools/TypeSpecificationService.php:49 · Dotted inputs such as rm.DV_QUANTITY or org.openehr.rm.datatypes.DV_TEXT find nothing in type_specification_get or _search; reading them as qualified names (last segment) would help clients but changes the published parameter descriptions, so it is a product decision · from: #43

## Pin the CKM tools' transport-failure handling (REQ-F1, REQ-N2)
Only `archetypeSearch` has tests where the CKM client throws, so the other tools' failure paths can be deleted with the suite still green.
- src/Tools/CkmService.php:287 · No test fails if the catch (ClientExceptionInterface) wrapping is removed from archetypeGet (:287), templateSearch (:417) or templateGet (:478). · from: #34
- src/Tools/CkmService.php:638 · No test covers resolveCitableIdentifier turning a transport failure into null; every test that reaches it mocks a returned response. · from: #34

## Close the HTTP boundary scan's blind spots (REQ-N2, ADR-0002)
`HttpBoundaryTest` guards the constitution's single outbound boundary, but, as its docblock says, some ways of opening a connection still pass it.
- tests/Content/HttpBoundaryTest.php:36 · A namespace alias (use GuzzleHttp as G; new G\Client()) still passes the boundary scan; tracking namespace aliases would close it cheaply · from: #41
- tests/Content/HttpBoundaryTest.php:79 · URL_READERS covers five readers, so writing to a URL (file_put_contents, the target of copy) and other URL-accepting APIs (SplFileObject, simplexml_load_file, DOMDocument::load, hash_file) still pass; the docblock lists them as blind spots · from: #41
- tests/Content/HttpBoundaryTest.php:56 · GuzzleHttp\Utils::chooseHandler() builds the default cURL handler and still passes the boundary scan; listing the whole Utils class would also flag harmless helpers, so it stays with review · from: #41

## Run the static checks over all the code (REQ-N3)
REQ-N3 names PSR-12 and PHPStan, but PHPStan skips the tests and no style checker is installed.
- tests/phpstan.neon:5 · composer check:phpstan (and make ci) analyses only public/ and src/, so test files are type-checked only by a separate run · from: #41
- composer.json:26 · php-cs-fixer is not a dev dependency, so the PER-CS style check the php-coding standard names cannot run here. · from: #35

## Make the guard-test table say what each test checks (REQ-N1)
The table in `docs/testing.md` claims more for one guard test than it checks.
- docs/testing.md:43 · The README half is not a general guide-claim match and is not REQ-N1; the test only rejects a chunking claim on the guide_get bullet. · from: #41
