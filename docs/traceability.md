# Traceability Matrix

> Part of the [Specification-Driven Development docs](README.md). This is the
> backbone of the SDD chain: every requirement traces forward to the code that
> implements it, the test that verifies it, and the decision that shaped it —
> and every component traces back to a requirement. No tooling: the matrix is
> kept current by hand as part of normal change review.

## How to keep this current

When you change behaviour, walk the chain in order:

1. **Requirement** — add/edit a `REQ-#` in [requirements.md](requirements.md).
2. **Design** — reflect it in [architecture.md](architecture.md) and, if it is
   architecturally significant, an [ADR](decisions/).
3. **Code** — implement under `src/`.
4. **Test** — add/extend the mirrored `*Test`.
5. **Matrix** — add or update the row(s) below.

A row whose Test column is empty is a coverage gap; a `src/` class absent from
the Implementation column is either dead code or an undocumented requirement.

## Requirements → implementation → tests → decisions

| REQ | Requirement (short) | Implementation | Test(s) | ADR |
|-----|---------------------|----------------|---------|-----|
| **REQ-F1** | CKM archetype/template search & get | `src/Tools/CkmService.php`, `src/Apis/CkmClient.php` | `tests/Tools/CkmServiceTest.php`, `tests/Clients/CkmClientTest.php` | 0002 |
| **REQ-F2** | Guide discovery / retrieval / ADL idioms | `src/Tools/GuideService.php`, `src/Resources/Guides.php` | `tests/Tools/GuideServiceTest.php`, `tests/Resources/GuidesTest.php` | — |
| **REQ-F3** | Curated examples search & get | `src/Tools/ExamplesService.php`, `src/Resources/Examples.php` | `tests/Tools/ExamplesServiceTest.php`, `tests/Resources/ExamplesTest.php` | — |
| **REQ-F4** | Terminology resolution | `src/Tools/TerminologyService.php`, `src/Resources/Terminologies.php`, `src/Helpers/TerminologyXmlLoader.php` | `tests/Tools/TerminologyServiceTest.php`, `tests/Resources/TerminologiesTest.php` | — |
| **REQ-F5** | Type specification lookup (BMM) | `src/Tools/TypeSpecificationService.php`, `src/Resources/TypeSpecifications.php` | `tests/Tools/TypeSpecificationServiceTest.php`, `tests/Resources/TypeSpecificationsTest.php`, `tests/Resources/SpecDigestsTest.php` | 0005 |
| **REQ-F6** | Guided MCP prompts (15) | `src/Prompts/*.php` (extend `AbstractPrompt`) | `tests/Prompts/*Test.php`, `tests/Prompts/AbstractPromptTest.php` | 0003 |
| **REQ-F7** | Resource exposure via `openehr://` URIs | `src/Resources/*.php` | `tests/Resources/*Test.php` | — |
| **REQ-F8** | Argument auto-completion | `src/CompletionProviders/{Examples,Guides,SpecificationComponents}.php` | `tests/CompletionProviders/{GuidesTest,SpecificationComponentsTest}.php` | — |
| **REQ-F9** | Dual transport (http / stdio) | `public/index.php`, `src/Helpers/CliOptions.php` | (covered via startup / conformance) | 0001 |
| **REQ-F10** | Global server instructions | `resources/server-instructions.md` | `tests/Prompts/PromptPolicySeparationTest.php` | 0003 |
| **REQ-N1** | Authoritative spec retrieval | `resources/guides/howto/spec-lookup.md`, content under `resources/` | content review | 0005 |
| **REQ-N2** | Test mirror + mocked HTTP | all `tests/`, `CkmClient` mocking | whole suite (`composer test`) | 0002 |
| **REQ-N3** | PSR-12 + PHPStan | `phpstan.*`, CS config | `composer check:phpstan` | — |
| **REQ-N4** | Cached discovery / fast startup | `public/index.php` (Symfony Cache) | startup | 0001 |
| **REQ-N5** | Docker-only runtime | `.docker/`, `Makefile` | CI / `make` targets | 0004 |
| **REQ-N6** | MCP conformance | `make conformance`, `node` service | `tests/conformance-baseline.yml` | — |
| **REQ-N7** | Concise AI-facing content | guide/prompt bodies, policy split | `tests/Prompts/PromptCompositionTest.php` | 0003 |

## Guard tests (cross-cutting invariants)

| Test | Invariant guarded | REQ |
|------|-------------------|-----|
| `tests/Prompts/PromptCompositionTest.php` | Prompt size stays within baselines (`tests/fixtures/prompt_lengths_before_shared.json`) | REQ-N7 |
| `tests/Prompts/PromptPolicySeparationTest.php` | Global policy lives only in `server-instructions.md`, not in prompt files | REQ-F10, REQ-N7 |

> The near 1:1 `src/` ↔ `tests/` mirror means most traceability links already
> exist in the tree; this matrix makes the requirement layer explicit on top of
> them.
