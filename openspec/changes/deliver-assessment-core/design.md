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

## Clarifications Required Before Affected Implementation

These source-document ambiguities are not confirmed requirements. The affected policy or transition task remains blocked until clarified:

- Whether Super Admin may close complaints despite closure being explicitly limited to Provost or Hall Office Staff.
- How to reconcile M4 naming Office Staff or Provost as complaint assigners with the mandatory permission matrix also granting assignment to Super Admin.
- Which roles may reject complaints and which transitions an assigned House Tutor may perform.
- Whether the seven-day reopening boundary includes the exact seventh-day instant and which timezone governs it.
- Which roles qualify as a complaint-assignment target described as a "staff member," and which staff roles may add complaint comments.
- Whether and how a rejected registration may be reconsidered, edited, or resubmitted.
- Which student profile fields remain editable after registration.
- How a newly created tutor receives an authenticated account or initial credential.
- Which user-management operations are required beyond Super Admin user listing and role assignment.
- How an Attached student's free-form complaint location contributes to a House Tutor's block-scoped dashboard.
- Which Resolved timestamp is used for resolution time when a complaint is reopened and resolved again.
- Whether using the preserved Laravel 13 starter is accepted despite Track A naming Laravel 11 or 12.

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

Before production use, back up existing data and run migrations normally. Rollback uses each migration's down path during development; after real allocation, complaint, or membership history exists, prefer corrective forward migrations rather than destructive rollback.
