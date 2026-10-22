# Design

## Context

See `proposal.md` for motivation and scope. The codebase is an authenticated Laravel 13 and Inertia React starter with one `User` model, a web session guard, Fortify, Wayfinder, reusable application layouts, and Pest feature tests. It has no hall-domain schema, roles, policies, or business workflows. MySQL is the intended application database and SQLite is used by tests.

The technical assessment is the mandatory baseline. The draft PRD is contextual product vision and does not authorize later modules. MySQL is a project architecture decision, while the assessment itself also permits PostgreSQL or SQLite. Source-document ambiguities remain visible below and must not be resolved by silently broadening privileges.

## Goals / Non-Goals

**Goals:**

- Deliver each capability as a small end-to-end slice with server authorization, persisted data, Inertia UI, and focused feature tests.
- Preserve the starter's authentication, layouts, route generation, components, and quality tooling.
- Enforce allocation, transition, tracking-ID, and membership invariants under concurrent requests.
- Keep migrations and behavior compatible with MySQL application use and SQLite tests.
- Make seeded demonstration data deterministic enough to verify dashboard values.

**Non-Goals:**

- Introduce a module framework, repository pattern, generic service layer, permission package, public API, or separate frontend.
- Redesign Fortify authentication or add assessment bonus and PRD-only capabilities.
- Resolve client-owned permission and lifecycle ambiguities without confirmation.

## Decisions

### 1. Keep a conventional Laravel monolith

Use standard Eloquent models, Form Requests, policies, thin controllers, named web routes, and Inertia pages in the existing directories. Add focused action classes only for multi-write workflows requiring a transaction.

Alternative considered: domain modules or repositories. Rejected because the current application and assessment size do not demonstrate enough complexity to justify another abstraction layer.

### 2. Extend the existing identity rather than creating separate guards

Keep one `users` table and web guard. Store reusable roles in `roles` and `role_user`; store student-specific fields and approval state in a one-to-one student profile. Represent tutor-specific attributes in a profile linked to the same user identity so a tutor can authenticate normally.

Alternative considered: separate student, tutor, and staff guards. Rejected because multi-role users are required and separate guards would duplicate identity and authentication behavior.

### 3. Use policies as the authorization boundary

Policies enforce record-level access for students, tutors, complaints, notices, clubs, and memberships. Middleware may enforce authentication, verification, or approved-student eligibility, but frontend navigation is only a presentation aid.

Alternative considered: role checks only in controllers or React. Rejected because the assessment explicitly requires server-side authorization and record scoping.

### 4. Model accommodation explicitly and retain allocations

Use blocks, rooms, explicit numbered seats, and seat allocations. An active allocation uses a nullable active marker with unique constraints for both student and seat; completed allocations clear the marker and retain dates and vacancy reason. Allocation and vacancy lock the relevant records and execute in a transaction.

Room capacity controls the intended seat count. Capacity reduction is refused when it is below occupancy; reconciliation of unused seats must not delete allocation history.

Alternative considered: deriving seats only from a room capacity integer. Rejected because allocation must target an identifiable seat and preserve history.

### 5. Centralize complaint status transitions

Represent statuses and categories as PHP enums. A focused transition action validates the documented state graph, actor authorization, required rejection/resolution notes, and reopening window, then updates the complaint and appends its status log in one transaction.

Alternative considered: status changes in each controller. Rejected because that would duplicate a safety-critical state graph and make audit logging inconsistent.

### 6. Generate complaint IDs through a locked annual counter

Allocate `KJH-YYYY-NNNN` values from an annual counter row inside the complaint-creation transaction. Locking the counter provides a single concurrency boundary and a unique database index remains the final safeguard.

Alternative considered: counting existing complaints. Rejected because concurrent submissions could receive the same number.

### 7. Treat uploaded files according to their visibility

Store complaint images on a private disk under generated filenames and serve them through an authorized controller. Validate notice files as PDFs under generated filenames; expose an attachment only when its notice is Published. Validate optional club logos as non-executable images under generated filenames.

Alternative considered: direct public storage for every upload. Rejected because complaint images are private and draft notice files must not leak.

### 8. Use database-backed queries for dashboard metrics

Calculate dashboard values from current persisted records, with tutor queries constrained to assigned blocks. Display unavailable average resolution time when the 30-day period has no resolved complaints. Avoid caches until measurements demonstrate a need.

Alternative considered: denormalized counters or caching. Rejected because the assessment data volume is small and direct queries are simpler to verify.

### 9. Keep frontend state server-driven

Use Inertia props, existing form patterns, Wayfinder routes/actions, shared layout components, and role-aware navigation. Laravel remains authoritative for validation, permissions, filtering, and pagination. Loading, empty, error, validation, and success states are implemented without adding a client data library.

Alternative considered: a versioned API plus client query library. Rejected because Track A permits Inertia and the separate contract adds no assessment value.

### 10. Organize delivery as dependent vertical slices

Implement identity and approval first; hall structure before allocation; tutor scope before complaint assignment; domain modules before dashboard aggregation; and final seed/documentation work after behavior stabilizes. Each slice includes its own regression tests before the next begins.

### 11. Gate implementation by explicit phase authorization

`docs/PROJECT_STATUS.md` is the authorization ledger for this change. An implementation session may execute tasks only from the single phase named as authorized there. `NONE` authorizes no implementation, even when tasks are visible or OpenSpec reports that planning is complete.

Completing a phase does not authorize another phase. The implementer MUST stop after the authorized phase exit criteria pass, update `docs/PROJECT_STATUS.md`, and wait for explicit user approval. `/openspec-apply-change` MUST read the authorization ledger before changing application code and MUST never implement a future phase or a task blocked by an unresolved clarification.

Alternative considered: treating approval of the whole OpenSpec change as approval of every task. Rejected because it permits accidental scope expansion and prevents deliberate review between independently testable slices.

### 12. Preserve registration decision history during reconsideration

An authorized registration reviewer may transition Rejected to Pending. Store registration decisions as retained history so reconsideration never overwrites the original rejection reason, actor, or time. Students receive no self-resubmission action, and reconsideration introduces no new role privilege.

Alternative considered: making rejection terminal or allowing student resubmission. Rejected because the approved decision requires staff reconsideration while excluding student-controlled lifecycle changes.

### 13. Reuse Fortify profile and verification behavior

Extend the existing profile workflow so Students can update name, email, and phone. Keep registration number, department, session, approval state, and residency state read-only. Email changes retain uniqueness and `@student.du.test` validation and use the starter's existing email-verification safeguards rather than a parallel workflow.

Alternative considered: a separate student-profile editor or editing every registration field. Rejected because it duplicates Fortify behavior and weakens academic identity integrity.

### 14. Separate account activity from registration approval

Super Admin can list/search users, manage role assignments, and activate/deactivate accounts. Account activity is independent from the student's Pending, Approved, or Rejected registration decision so temporary deactivation does not erase approval history. Inactive accounts are denied at authentication and on protected server requests; reactivation retains roles and restores only the access allowed by the registration state.

Administrative deletion, password reset, and unrestricted CRUD remain unavailable. Existing Fortify self-service recovery remains unchanged.

Alternative considered: reusing one status for both approval and activity or adding full user CRUD. Rejected because it loses lifecycle meaning and exceeds the approved scope.

### 15. Continue with authority-approved Laravel 13

Continue planning and implementation against the existing Laravel 13 application. This is an approved deviation from the assessment's Laravel 11/12 wording, based on user-reported communication with the assessment authority. No written approval evidence has been provided or claimed. No framework downgrade or migration is permitted without separate explicit authorization.

### 16. Integrate each phase through a reviewable Git branch and pull request

Keep `main` as the stable integration branch and never implement assessment features directly on it. Each explicitly authorized phase uses one branch named `phase/NN-short-description`, created from the latest verified `main`; no later phase branch begins while the current phase branch or pull request remains active.

Keep completed tasks traceable through cohesive Conventional Commits that reference their OpenSpec task IDs and include the corresponding tests. When a phase is implementation-complete, run the full phase quality gates, review capability acceptance and the complete diff against `main`, then push and open a pull request only with explicit authorization. The pull request maps tasks to commits and records acceptance and verification evidence. Existing CI checks and review must be satisfactory before the phase is review-complete and the user is asked for separate merge approval. Adding CI remains out of scope; when no CI exists, record that state and rely on the complete local quality-gate evidence. Unverified remote review state blocks review completion unless the user explicitly confirms it.

Prefer a merge commit so the phase boundary and its task history remain visible. After an approved merge, synchronize local `main`, update `docs/PROJECT_STATUS.md`, clear the active branch and pull request, and reset implementation authorization to `NONE`. The phase is then integrated. Only an integrated prerequisite satisfies a later phase entry, and integration never authorizes the next phase.

A phase with **documented cross-phase deferrals** may become foundation-complete and integration-eligible before its Exit statement is fully satisfied, subject to the Phase-Gate Rules in `tasks.md`: independently implementable requirements are complete and verified; every unfinished requirement depends explicitly on a later phase; deferred tasks remain unchecked; each dependency has a named future completion checkpoint in `docs/PROJECT_STATUS.md`; and the user explicitly authorizes integration and progression. The phase is not fully implementation-complete, its PR records the deferrals, and integration satisfies the next phase entry only for the implemented foundation. It does not authorize the next phase or complete deferred tasks.

Alternative considered: implementing on `main` or squashing an entire phase into an opaque commit. Rejected because both approaches weaken task-level traceability, review, rollback boundaries, and phase authorization controls.

## Implementation Phases

The table's exit criteria define **implementation-complete**, not review-complete or integrated. References to a prior phase being complete in an entry criterion mean that phase is integrated into `main` through the branch and pull-request workflow above, including an integration-eligible phase with documented cross-phase deferrals.

| Phase | Scope | Entry criteria | Implementation exit criteria |
|---|---|---|---|
| Phase 0 | Planning approval | Client sources and current starter are available | Strict OpenSpec validation passes; requirements remain traceable; tasks are interruption-safe; authorization remains `NONE`; explicit final Phase 0 approval is received |
| Phase 1 | Identity and Registration | Phase 0 approved; Phase 1 explicitly authorized | All Phase 1 tasks complete; six demo identities authenticate with documented roles; registration, profile, activity, and authorization tests pass; status updated; work stops |
| Phase 2 | Hall Structure and Student Records | Phase 1 complete; Phase 2 explicitly authorized | All Phase 2 tasks complete; structure, occupancy, directory, profile, and access tests pass; status updated; work stops |
| Phase 3 | Seat Allocation and Vacancy | Phase 2 complete; Phase 3 explicitly authorized | All Phase 3 tasks complete; allocation, vacancy, history, capacity, and concurrency checks pass; status updated; work stops |
| Phase 4 | House Tutors | Phase 3 complete; Phase 4 explicitly authorized; C8 resolved before account onboarding | All Phase 4 tasks complete; tutor administration and scoping tests pass; status updated; work stops |
| Phase 5 | Complaints | Phases 1, 3, and 4 complete; Phase 5 explicitly authorized; C1-C5 and C11 resolved before affected tasks | All Phase 5 tasks complete; submission, privacy, assignment, comments, transitions, audit, and UI checks pass; status updated; work stops |
| Phase 6 | Notice Board | Phase 1 complete; Phase 6 explicitly authorized | All Phase 6 tasks complete; authoring, public discovery, draft isolation, and download tests pass; status updated; work stops |
| Phase 7 | Clubs and Memberships | Phases 1 and 2 complete; Phase 7 explicitly authorized | All Phase 7 tasks complete; directory, request, administration, upload, and scope tests pass; status updated; work stops |
| Phase 8 | Operations Dashboard | Phases 3-7 complete; Phase 8 explicitly authorized; C10-C11 resolved | All Phase 8 tasks complete; leadership and tutor-scoped metrics match deterministic records; status updated; work stops |
| Phase 9 | Assessment Data and Delivery | Phases 1-8 complete; Phase 9 explicitly authorized | All Phase 9 tasks complete; the approved Laravel 13 deviation, seed minima, responsive review, documentation, evidence, and quality pipeline are verified; status updated; work stops |

## Requirement Traceability

| Assessment area | Capability | Implementation phase |
|---|---|---|
| M1 authentication and registration | `identity-access` | Phase 1 |
| M2 students, rooms, and allocation | `student-accommodation` | Phases 2-3 |
| M3 house tutors | `tutor-oversight` | Phase 4 |
| M4 complaint management and BR-06-BR-10 | `complaint-management` | Phase 5 |
| M5 notice board and BR-11/BR-13 | `notice-board` | Phase 6 |
| M6 clubs and BR-12/BR-13 | `club-membership` | Phase 7 |
| M7 dashboard | `operations-dashboard` | Phase 8 |
| BR-01-BR-05 | `identity-access`, `student-accommodation` | Phases 1-3 |
| Track A quality, seed data, and submission deliverables | All capabilities | Embedded per phase and finalized in Phase 9 |

## Outstanding Clarifications

These source-document ambiguities are not confirmed requirements. The affected task remains blocked until its referenced decision is approved:

- **C1:** Whether Super Admin may close complaints despite closure being explicitly limited to Provost or Hall Office Staff.
- **C2:** How to reconcile M4 naming Office Staff or Provost as complaint assigners with the mandatory permission matrix also granting assignment to Super Admin.
- **C3:** Which roles may reject complaints and which transitions an assigned House Tutor may perform.
- **C4:** Whether the seven-day reopening boundary includes the exact seventh-day instant and which timezone governs it.
- **C5:** Which roles qualify as a complaint-assignment target described as a "staff member," and which staff roles may add complaint comments.
- **C8:** How a newly created tutor receives an authenticated account or initial credential.
- **C10:** How an Attached student's free-form complaint location contributes to a House Tutor's block-scoped dashboard.
- **C11:** Which Resolved timestamp is used for resolution time when a complaint is reopened and resolved again.

## Resolved Clarifications

- **C6 — Resolved:** Authorized registration-decision roles may transition Rejected to Pending with original rejection reason and complete decision history retained. Student self-resubmission is excluded.
- **C7 — Resolved:** Students may edit name, email, and phone. Email uniqueness, institutional domain, and verification safeguards remain; all other registration and academic identity fields are read-only.
- **C9 — Resolved:** Super Admin may list/search users, manage roles, and activate/deactivate accounts. Inactive access is blocked server-side. Administrative deletion, password resets, and unrestricted CRUD are excluded.
- **C12 — Resolved, authority approved:** Continue with Laravel 13; no downgrade is required. Approval source is user-confirmed communication with the authority. Evidence is recorded as user-reported, not written evidence.

Bengali localization remains deferred because it is a PRD requirement but not a mandatory assessment requirement. This is a scope classification, not a rejection of the longer-term requirement.

The assessment requires size validation for every upload but does not give a club-logo limit. A 2 MB JPG/PNG limit is the provisional low-risk implementation assumption and must be documented as an assumption unless the client provides another limit.

## Risks / Trade-offs

- [Unresolved authorization details could produce incorrect access] -> Block affected tasks and tests until the role behavior is confirmed; implement no implied privilege.
- [MySQL and SQLite enforce concurrency differently] -> Combine transactions and locks with portable unique constraints, then run focused tests against SQLite and verify critical migrations on MySQL when credentials are available.
- [Seeded metrics can drift as factories evolve] -> Use explicit scenario seeders and assert expected dashboard totals in feature tests.
- [A broad change can encourage parallel partial modules] -> Follow task order and complete each vertical slice, including tests, before starting its dependents.
- [Private files may leak through generated URLs] -> Use authorized download controllers and test forbidden access directly.
- [Assessment deadline can encourage optional scope] -> Keep every bonus and PRD-only item outside this change.

## Migration Plan

1. Add identity, role, and profile schema while preserving existing users and Fortify behavior.
2. Add each domain schema immediately before its vertical slice and seed only data supported by completed behavior.
3. Replace the placeholder dashboard only after its source modules exist.
4. Complete the deterministic assessment seeder, documentation, and responsive review after all mandatory slices pass focused tests.
5. Run the complete quality pipeline and verify a fresh migrate-and-seed flow before assessment delivery.

The implementation baseline remains Laravel 13. A framework downgrade or migration is outside this change unless separately authorized.

Before production use, back up existing data and run migrations normally. Rollback uses each migration's down path during development; after real allocation, complaint, or membership history exists, prefer corrective forward migrations rather than destructive rollback.
