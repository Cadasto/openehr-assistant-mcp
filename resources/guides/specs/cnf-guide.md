# openEHR Conformance Guide — Digest

**Scope:** Entry point to the CNF component: goals, stakeholders, testable-product categories, and the framework and methodology for conformance testing of openEHR platforms, clients, and tools.
**Component:** CNF
**Document:** guide
**Release:** development
**Spec URL:** https://specifications.openehr.org/releases/CNF/development/guide.html
**Markdown URL:** https://specifications.openehr.org/releases/CNF/development/guide.md
**Last updated:** 2026-07-18
**Related:** openehr://guides/specs/sm-openehr_platform, openehr://guides/specs/its-rest-api
**Keywords:** CNF, conformance, certification, test framework, test schedule, platform, SUT, API conformance, data validation, procurement

---

## Purpose

Describes the openEHR conformance-testing artefacts and methodology. Conformance testing underpins product and system certification with three stated goals: enabling tendering authorities to state formal compliance criteria, protecting bona fide solution developers by letting them prove quality against competing conformance claims, and protecting procurement by making purchased behaviour contractually guaranteeable. Four stakeholder groups shape the framework: platform specifiers (openEHR International and peers), procuring organisations, solution builders, and independent conformance-assessment agencies. The approach is explicitly not openEHR-specific and can be applied to other components in a platform solution.

## Scope

- In: the three testable product categories — API-exposing platform components (CDR, demographic repository, terminology service, CDS, model repository), API-using platform clients, and standalone tools — and what is assessed for each (API conformance and data-validation conformance for platforms; API compatibility for clients; artefact-representation round-tripping for tools); the possible conformance claims (direct assessment of a deployed system vs inferred conformance of a product); the conformance framework's separation into technology-independent and technology-specific artefacts; the derivation chain from abstract Service Model call → API-technology rendering → abstract test cases → executable test runners; the test environment (test application exercising the System Under Test); and result artefacts (Test Execution Report, Conformance Statement, Conformance Certificate).
- Out: the concrete test cases themselves (`CNF` Platform Conformance Test Schedule), certificate format (`CNF` certificate document), the platform API semantics being tested (`SM/openehr_platform`), concrete REST bindings (`ITS-REST`), and non-functional conformance (performance etc.), which the guide explicitly does not address. Tooling, report, and certification sections are still TBD.

## Key Classes / Constructs

This is a methodology guide, so the entries are framework concepts rather than classes:

- **Product categories** — Platform Component / Platform Client / Tool, each with its own assessment method (regression of API call-in test cases against reference results; simulated-service and functional testing against a reference platform; functional round-trip testing).
- **Data-validation conformance** — assessing a platform's validation of committed data against semantic models (archetypes/templates) by committing variable data sets against reference validity outcomes.
- **Specification "square"** — the four-element derivation pattern illustrated with `I_EHR.create_ehr()`: (1) abstract API call in the Platform Service Model; (2) its REST rendering (`POST {baseUrl}/v1/ehr`); (3) technology-independent test cases; (4) executable test scripts for a concrete technology — keeping call semantics defined once and test logic reusable across API technologies.
- **Test Schedule** — technology-independent test cases and run logic based on the platform Service Model (separate CNF document).
- **Executable test runners** — technology-specific scripts (e.g. REST + JSON) implementable with frameworks such as Cucumber, Robot, or Spock.
- **Result artefacts** — Test Execution Report (results of a test run on an SUT), Conformance Statement (product/system claim against specifications), Conformance Certificate (issued by a recognised testing authority).

## Relations to Other Specs

- Depends on: `SM/openehr_platform` (the abstract Service Model whose calls define what is tested), `ITS-REST` (the principal concrete API technology for platform testing), `BASE/architecture_overview` (orientation prerequisite).
- Companions in CNF: Platform Conformance Test Schedule (test cases), Conformance Certificate, and platform profile documents.
- Consumed by: conformance-assessment agencies, vendors preparing certification, and procurement organisations writing RFI/RFP/RFQ criteria (dedicated guides are flagged as future work).

## Architectural Placement

CNF sits at the end of the specification pipeline: RM/AM/SM/QUERY define semantics, ITS documents bind them to technologies, and the conformance framework closes the loop by deriving executable evidence that a deployed system faithfully implements those bindings. Its two-level design (abstract test logic vs technology-specific runners) mirrors the abstract/ITS split of the main specifications, so adding a new API technology is incremental work rather than a re-specification.

## When to Read the Full Spec

Read the full guide when planning a conformance-testing programme or certification effort, when deciding which product category and assessment method applies to a system, when constructing the derivation chain from Service Model calls to executable test runners, when setting up a test environment around a System Under Test, or when drafting procurement language that references openEHR conformance claims. Expect gaps: several operational sections (tooling, execution report, statement, certification) are TBD in the current DEVELOPMENT state.

## References

- Full spec (HTML): https://specifications.openehr.org/releases/CNF/development/guide.html
- Full spec (Markdown): https://specifications.openehr.org/releases/CNF/development/guide.md
- Related digests: specs/sm-openehr_platform, specs/its-rest-api
