---
kind: plan
harvested_through: 2026-10-22T23:22:49Z
---
# Backlog

Leftovers of merged branches: defects outside their change, suggestions, and findings the maintainer deferred here. Each line is a lead, not a finding: verify it before acting. Themes group what one delivery takes, defects first; `sdd-pr harvest` and `sdd-pr flip --carry` add lines under Unsorted, and `/sdd-triage --backlog` sorts them, merges duplicates and drops what is settled. Line numbers are as of the pull request in `from:`. Edit freely.

## Return -32602 for a missing prompt argument (REQ-F6)
The MCP prompts spec (2025-06-18, § Error Handling) says a server SHOULD answer a missing required argument with `-32602`; this server answers `-32603`.
- src/Prompts/AbstractPrompt.php:128 · A missing or blank required argument throws PromptGetException, which the SDK's GetPromptHandler maps to -32603 (internal error) for every PromptGetException, so the fix needs an upstream SDK change or a different exception · from: live check of 0.21.0

## Normalise CKM artefact text (REQ-F1)
CKM serves ADL with a UTF-8 byte-order mark and CRLF line endings, and the tools pass both through.
- src/Tools/CkmService.php:288 · ckm_archetype_get (and ckm_template_get at :491) return the CKM body unchanged, so the code block opens with U+FEFF and every line ends in \r; stripping a leading BOM and normalising line endings would remove the noise · from: live check of 0.21.0

## Decide whether a dotted type name is a qualified name (REQ-F5)
The type-specification tools ignore dots, so a package-qualified name finds nothing; reading it by its last segment is a behaviour change that needs a product decision first.
- src/Tools/TypeSpecificationService.php:54 · Dotted inputs such as rm.DV_QUANTITY or org.openehr.rm.datatypes.DV_TEXT find nothing in type_specification_get or _search; reading them as qualified names (last segment) would help clients but changes the published parameter descriptions, so it is a product decision · from: #43

## Pin the CKM tools' transport-failure handling (REQ-F1, REQ-N2)
Only `archetypeSearch` and the archetype-id resolution have tests where the CKM client throws, so the other lookups' failure paths can be deleted with the suite still green.
- src/Tools/CkmService.php:289 · No test fails if the catch (ClientExceptionInterface) wrapping is removed from archetypeGet (:289), templateSearch (:424) or templateGet (:492). · from: #34

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
- docs/testing.md:44 · The README half is not a general guide-claim match and is not REQ-N1; the test only rejects a chunking claim on the guide_get bullet. · from: #41
