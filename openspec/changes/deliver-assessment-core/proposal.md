# Proposal

## Why

The Laravel starter has no approved hall-management behavior, while the technical assessment defines a mandatory, demonstrable operating baseline. This change converts that baseline into reviewable OpenSpec requirements and dependency-ordered work without expanding into the draft PRD's later product vision.

## What Changes

- Add role-aware authentication, pending student registration, approval decisions, and the six required fictional demo accounts.
- Add student, block, room, seat, allocation-history, and tutor-assignment workflows.
- Add scoped complaint submission, assignment, comments, audited state changes, tracking IDs, and overdue handling.
- Add public and administrative notices with draft isolation and safe attachment downloads.
- Add club membership requests and own-club administration.
- Add role-scoped operational dashboard metrics.
- Add mandatory seed data, authorization coverage, business-rule tests, responsive review, and assessment documentation.
- Preserve unresolved permission and lifecycle questions as explicit blockers; do not infer behavior beyond the source documents.

### Acceptance Criteria

- A fresh installation can create all required fictional data with the documented migration and seed command.
- All six demo accounts can authenticate and can access only documented functionality.
- Pending students cannot access protected student features.
- Seat uniqueness, capacity, allocation history, complaint transitions, tutor scoping, club scoping, and draft notice isolation are enforced server-side and covered by focused tests.
- Dashboard values match the seeded records and the interface is usable at a 375 px viewport.
- Setup, test, demo-account, assumption, completion-status, trade-off, time-spent, and AI-use documentation is present with no committed secrets.

### Out of Scope

- PRD-only homepage, hall history, biography, map, events, Parliament pages, achievements, alumni, magazine/blog, gallery, jobs, contacts, rules, and handover operations.
- Assessment bonus features including anonymous complaints, polls, notifications, localization, exports, activity logs, Docker, CI, and live deployment.
- Payments, dining, native applications, real university integrations, real SMS, and a separate public API.
- New packages, repository abstractions, a generic service layer, or replacement of the existing Laravel/Inertia starter.

## Capabilities

### New Capabilities

- `identity-access`: Role-aware authentication, student registration decisions, protected-area eligibility, and demo identities.
- `student-accommodation`: Student records, hall structure, occupancy, seat allocation, vacancy, and retained history.
- `tutor-oversight`: House-tutor records, block assignments, and tutor-scoped access.
- `complaint-management`: Complaint submission, tracking, assignment, comments, audited transitions, privacy, and overdue behavior.
- `notice-board`: Draft and published notices, public discovery, pinning, and attachments.
- `club-membership`: Club directory, club administration, and membership decisions.
- `operations-dashboard`: Role-scoped accommodation, registration, and complaint metrics.

### Modified Capabilities

None. The project has no existing capability specifications.

## Impact

- Adds hall-domain migrations, Eloquent models, factories, seeders, policies, Form Requests, controllers, named routes, and focused transactional actions where a workflow performs multiple writes.
- Adds Inertia React pages and role-aware navigation using the existing layouts, components, flash handling, and Wayfinder routes.
- Extends the existing Fortify-backed user model and registration behavior without replacing the web guard or authentication stack.
- Adds private complaint media and controlled public notice attachments using Laravel Storage.
- Adds Pest feature coverage and updates assessment-facing project documentation.
- Keeps Laravel 13 as an authority-approved deviation from the assessment's Laravel 11/12 wording; the approval source is user-reported communication and no written evidence is claimed.
