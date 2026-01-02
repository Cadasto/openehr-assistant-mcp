# openEHR Archetype Anti-Patterns
**URI:** openehr://guides/archetypes/anti-patterns  
**Version:** 1.0.0  
**Purpose:** Common modelling pitfalls to avoid in archetype creation

---

## 1. Overly Broad Concepts

**Anti-Pattern:** Creating archetypes that combine multiple unrelated clinical domains  
**Example:** An archetype mixing vital signs, clinical assessment, and medication details.  
**Consequence:** Poor reuse, inconsistent RM alignment, harder semantic querying.
**Fix:** Reuse available archetypes. Use slots when appropriate. Create separate archetypes for each domain.

---

## 2. Undocumented Terminology Bindings

**Anti-Pattern:** Adding code bindings without specifying the coding authority or meaning  
**Example:** Using arbitrary codes without referencing SNOMED CT/LOINC.  
**Consequence:** Loss of interoperability and increased ambiguity.
**Fix:** Reference terminology bindings in the archetype.

---

## 3. Excessive Specialisation

**Anti-Pattern:** Creating dozens of specialisations for minor local variations  
**Example:** Specialising a general “serum glucose” archetype for non-clinical uses.  
**Consequence:** Fragmentation and reduced ability to share models across sites.
**Fix:** Use compositions instead of specialisations.

---

## 4. Ignoring RM Semantics

**Anti-Pattern:** Modelling data semantics that conflict with the openEHR Reference Model  
**Example:** Using Entry types incorrectly (e.g., treating Observation as generic record).  
**Consequence:** Data model inconsistencies, runtime processing issues.
**Fix:** Use the appropriate RM types and their attributes.

---

## 5. Hardcoding Workflow Logic

**Anti-Pattern:** Embedding clinical workflow or application UI constraints in archetype definitions  
**Example:** Encoding process ordering as structural constraints.  
**Consequence:** Reduced archetype portability; clashes with use case specificity provided by templates.
**Fix:** Use archetypes only for data capture.

---

## 6. Improper Cardinality Constraints

**Anti-Pattern:** Assigning arbitrary minimum/maximum values without clinical justification  
**Example:** Mandatory multiple occurrences where single instance suffices.  
**Consequence:** Erroneous data capture expectations in implementations.
**Fix:** Use appropriate and documented cardinality constraints.

---

## Revision History

| Version | Date | Notes |
|---------|------|-------|
|1.0.0    |2025-12 |Initial anti-patterns |
