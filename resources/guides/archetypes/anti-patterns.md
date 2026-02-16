# openEHR Archetype Anti-Patterns

**Purpose:** Recognisable modelling failures and how to correct them  
**Keywords:** archetype, anti-patterns, modelling, pitfalls, avoid, best practice

---

## 1. Multi-Concept Archetypes

**Problem:** Mixing unrelated clinical concepts  
**Impact:** Poor reuse, broken semantics  
**Correction:** One archetype = one concept. Create separate archetypes; use slots for composition where needed.

---

## 2. Terminology Without Meaning

**Problem:** Undocumented or arbitrary code bindings  
**Impact:** Loss of interoperability  
**Correction:** Bind only authoritative codes with clear semantics.

---

## 3. Over-Specialisation

**Problem:** Specialising for local preference  
**Impact:** Fragmentation  
**Correction:** Use templates or compositions.

---

## 4. RM Misuse

**Problem:** Ignoring RM intent  
**Example:** Using OBSERVATION as generic record.  
**Impact:** Runtime and semantic errors  
**Correction:** Use correct ENTRY and data structures.

---

## 5. Embedded Workflow

**Problem:** Encoding UI or process logic  
**Impact:** Loss of portability  
**Correction:** Move logic to templates or applications.

---

## 6. Arbitrary Cardinality

**Problem:** Magic numbers (unjustified min/max)  
**Impact:** Invalid data assumptions  
**Correction:** Constrain only what is universally true.

---

## 7. Path-Breaking Refactors

**Problem:** Structural clean-ups that change paths  
**Impact:** Query breakage  
**Correction:** Treat paths as public API; path changes require major version.

---
