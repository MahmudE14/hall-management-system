# Tasks

## Phase-Gate Rules

- `docs/PROJECT_STATUS.md` names the only implementation phase currently authorized for this change.
- Authorization `NONE` permits no application-code, migration, seeder, or business-test implementation.
- Visible tasks are planned work, not blanket authorization. `/openspec-apply-change` MUST execute only the authorized phase.
- Implement an authorized phase only on its `phase/NN-short-description` branch created from the latest verified `main`; never implement feature tasks directly on `main`, and keep only one phase branch active.
- Complete each task with its focused verification and one or more cohesive Conventional Commits that reference the task ID. Do not mark a task complete without traceable commit history or create commits without explicit authorization.
- A phase section's Exit statement defines implementation-complete only. Run full quality gates, capability acceptance review, and complete diff review before its branch is pushed and a PR to `main` is opened with explicit authorization.
- **Documented cross-phase deferrals:** A phase may be pushed, reviewed, and integrated while its Exit statement is not fully satisfied only when all independently implementable requirements are complete and verified; every unfinished requirement has an explicit dependency on a later phase; deferred tasks remain unchecked; each dependency is recorded in `docs/PROJECT_STATUS.md` with a named future completion checkpoint; and the user explicitly authorizes integration and progression. Such a phase is **foundation-complete** and **integration-eligible**, not fully implementation-complete. Its PR must list the deferrals, and integration does not authorize the next phase or mark deferred tasks complete. Deferred work is completed at its named checkpoint before the change is archived.
- Wait for configured CI checks and review before marking the phase review-complete. If CI is not configured, record that fact and use full local quality-gate evidence; unverified remote state blocks review completion unless the user explicitly confirms it.
- Obtain separate explicit user approval before merge. Prefer a merge commit, synchronize local `main`, update `docs/PROJECT_STATUS.md`, and reset authorization to `NONE`; only then is the phase integrated.
- Later-phase prerequisites require earlier phases to be integrated. An implementation-complete, review-complete, or integrated phase does not authorize the next phase; stop and wait for explicit user approval.
- A task referencing an outstanding clarification MUST remain untouched until that clarification is approved and captured in the planning artifacts.

## Phase 0: Planning Approval

**Entry:** Client requirement sources and the current starter are available.

**Exit:** Strict OpenSpec validation passes; capability-to-phase traceability, task numbering, phase gates, and six demo accounts are verified; implementation authorization remains `NONE`; the user explicitly grants final Phase 0 approval.

There are no implementation tasks in Phase 0.

## Phase 1: Identity And Registration

**Entry:** Phase 0 is approved and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 1.

- [x] 1.1 Add roles and multi-role assignments with model factories; verify focused Pest tests cover role relationships and duplicate-role prevention.
- [x] 1.2 Add student profiles and approval states with model factories; verify profile relationships, status defaults, and hashed passwords.
- [x] 1.3 Change student self-registration to validate all required fields and create a Pending student; verify valid, invalid-domain, invalid-phone, duplicate-email, and duplicate-registration feature tests pass.
- [x] 1.4 Enforce approval-gated protected capabilities and server-side role authorization; verify Pending access denial and at least one explicit `403` feature test pass.
- [x] 1.5 Add the Pending registration list and initial approval/rejection workflow with required rejection reason; verify Hall Office Staff/Provost/Super Admin permissions, validation, and retained decision records.
- [x] 1.6 Add authorized Rejected-to-Pending reconsideration; verify the original rejection reason/actor/time and reconsideration actor/time remain in history, no new role receives permission, and student self-resubmission is refused.
- [x] 1.7 Add role-aware shared props and navigation using the existing Inertia shell and Wayfinder; verify each role receives only documented navigation while direct unauthorized requests return `403`.
- [x] 1.8 Seed and authenticate the six exact fictional accounts: `admin@kjh.test`, `provost@kjh.test`, `office@kjh.test`, `tutor@kjh.test`, `clubadmin@student.du.test`, and `student@student.du.test`; verify documented roles and that Club Admin also has Student.
- [x] 1.9 Add Super Admin user listing and search; verify supported identity filters, returned roles/status, pagination, and other-role `403` responses.
- [x] 1.10 Add Super Admin role assignment and update; verify multi-role changes, retained identity, invalid-role validation, and other-role `403` responses.
- [x] 1.11 Add Super Admin account activation and deactivation independent from registration approval; verify roles and decision history remain unchanged and every non-Super-Admin receives `403` for both operations.
- [x] 1.12 Enforce Inactive restrictions server-side; verify otherwise valid login is refused and a previously authenticated session loses protected access after deactivation.
- [x] 1.13 Keep administrative user deletion, administrative password reset, and unspecified user mutations unavailable; verify Super Admin cannot invoke any excluded operation and existing Fortify self-service recovery remains unchanged.
- [x] 1.14 Extend the existing Fortify/profile workflow for authenticated Student name, email, and phone changes; verify email uniqueness/domain and phone validation, email-change verification safeguards, own-profile scope, and read-only registration/academic fields.

**Exit:** Tasks 1.1-1.14 are complete; all six demo identities authenticate with documented roles; registration, reconsideration history, profile, scoped user management, inactive-account, and authorization tests pass; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 2: Hall Structure And Student Records

**Entry:** Phase 1 is complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 2.

- [x] 2.1 Add Hall and Block migrations, models, relationships, and factories; verify relationship and persistence tests pass.
- [x] 2.2 Add Room and Seat migrations, models, relationships, factories, capacity validation, foreign keys, and indexes; verify room/seat relationship and capacity tests pass on SQLite.
- [x] 2.3 Add Form Requests, policies, routes, and controllers for block, room, and seat management; verify Hall Office Staff and Super Admin management, Provost view-only access, and other-role `403` responses.
- [x] 2.4 Add block and room list/create/edit pages with occupied and free seat counts; verify Inertia props, validation feedback, and occupancy calculations.
- [x] 2.5 Add explicit seat management and safe room-capacity reconciliation; verify seat creation/editing cannot invalidate occupancy or allocation history.
- [ ] 2.6 Add the eager-loaded paginated student directory with name/registration search and block/department/session/status filters; verify own-only, all-record, combined-filter, pagination, and bounded-query behavior. Tutor assigned-block scope is completed in task 4.4 after tutor assignments exist.
- [ ] 2.7 Add the student profile page with personal details, current room, and allocation history; verify authorized visibility and student self-record access.

**Exit:** Tasks 2.1-2.7 are complete; structure, capacity, occupancy, directory, profile, and authorization tests pass; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 3: Seat Allocation And Vacancy

**Entry:** Phase 2 is complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 3.

- [x] 3.1 Add allocation-history persistence and active student/seat uniqueness safeguards; verify database constraints and model relationships.
- [x] 3.2 Add the transactional allocation action; verify Office Staff/Provost/Super Admin authorization, approved allocation, duplicate student allocation, occupied seat, and Pending-student refusal.
- [x] 3.3 Add allocation controls to the student/room workflow; verify allocation changes Attached to Resident and updates occupancy.
- [x] 3.4 Add transactional vacancy with required date and reason; verify the student returns to Attached, the seat becomes free, and history remains intact.
- [x] 3.5 Protect room-capacity changes from current occupancy and retained history; verify capacity cannot fall below occupancy and no history is deleted.
- [ ] 3.6 Verify allocation concurrency and migrations against configured MySQL while retaining SQLite feature coverage.

**Exit:** Tasks 3.1-3.6 are complete; allocation, vacancy, history, capacity, authorization, and concurrency checks pass; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 4: House Tutors

**Entry:** Phase 3 is complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 4. Resolve C8 before implementing tutor account onboarding.

- [ ] 4.1 Add tutor-profile and tutor/block-assignment migrations, models, relationships, and factories; verify persistence and many-to-many relationship tests.
- [ ] 4.2 Add Provost/Super Admin tutor create, edit, and deactivate behavior with policies and Form Requests; verify authorization and validation tests. Add account onboarding only after C8 is resolved.
- [ ] 4.3 Add tutor block-assignment behavior; verify multiple tutors per block and multiple blocks per tutor.
- [ ] 4.4 Add tutor-scoped student list/profile access; verify assigned-block visibility and out-of-scope `403` responses.
- [ ] 4.5 Add tutor administration and block-assignment pages using existing components and Wayfinder; verify authorized controls and expected Inertia data.

**Exit:** Tasks 4.1-4.5 are complete; tutor administration, assignment, scoping, and UI tests pass; C8-dependent behavior is resolved; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 5: Complaints

**Entry:** Phases 1, 3, and 4 are complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 5. Resolve C1-C5 and C11 before their dependent tasks.

- [ ] 5.1 Add complaint, status-log, and comment migrations plus status/category enums, foreign keys, and indexes; verify schema and enum tests.
- [ ] 5.2 Add complaint models, relationships, factories, supported categories, and derived aging behavior; verify model and days-open/overdue tests.
- [ ] 5.3 Add the annual tracking-counter persistence and transactional `KJH-YYYY-NNNN` generator; verify yearly reset and simultaneous-submission uniqueness.
- [ ] 5.4 Add approved-student complaint creation with Resident room autofill and Attached location input; verify Pending refusal and location behavior.
- [ ] 5.5 Add private JPG/PNG upload validation, generated filenames, 2 MB limit, and cleanup on failed creation; verify unsafe and oversized files are rejected.
- [ ] 5.6 Add policy-scoped complaint lists with status/category filters and days-open/overdue values; verify students list only their own complaints and leadership receives documented scope.
- [ ] 5.7 Add complaint detail and authorized private-image delivery; verify another student cannot view the complaint or download its image.
- [ ] 5.8 Add complaint assignment using only roles and assignee types confirmed by C2 and C5; verify assignment authorization and tutor scope.
- [ ] 5.9 Add chronological comments using only staff roles confirmed by C5; verify participant authorization, ordering, and cross-record denial.
- [ ] 5.10 Add the transactional transition engine and audit-log write for the documented state graph; verify allowed edges, forbidden edges, and atomic logging independent of actor permissions.
- [ ] 5.11 Add transition authorization, rejection reason, resolution note, closure authority, and tutor scope using C1 and C3; verify each role boundary and failure mode.
- [ ] 5.12 Add student reopening and resolution-time behavior using C4 and C11; verify exact boundary, timezone, repeated resolution, and unauthorized reopening.
- [ ] 5.13 Add the student complaint-submission page; verify room/location behavior, file validation, field errors, and success feedback.
- [ ] 5.14 Add complaint list, detail, timeline, and overdue interfaces; verify role-scoped Inertia props, filters, empty states, and private-media links.
- [ ] 5.15 Add staff assignment and comment controls; verify controls follow C2/C5 permissions and successful actions preserve timeline order.
- [ ] 5.16 Add transition controls and perform focused browser review; verify controls follow C1/C3/C4, validation errors remain visible, and no unauthorized control grants server access.

**Exit:** Tasks 5.1-5.16 are complete; all C1-C5/C11 decisions are captured; complaint submission, privacy, tracking, assignment, comments, transitions, audit, timing, and UI checks pass; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 6: Notice Board

**Entry:** Phase 1 is complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 6.

- [ ] 6.1 Add notice migration, model, enums, relationships, and factory; verify Draft/Published state and category behavior.
- [ ] 6.2 Add PDF storage validation with generated filenames, executable rejection, and 5 MB limit; verify accepted and rejected uploads.
- [ ] 6.3 Add notice Form Requests, policies, routes, and create/list controllers; verify Office Staff/Provost/Super Admin publishing, Club Admin category restriction, and unauthorized `403` responses.
- [ ] 6.4 Add authorized notice create/list pages; verify role-aware controls, validation errors, and success feedback.
- [ ] 6.5 Add the public paginated notice list and detail with title search, category filter, pinned-first/newest ordering, and eager loading; verify Draft notices are absent from public queries and direct routes.
- [ ] 6.6 Add Published attachment download and Draft attachment isolation; verify valid PDF download and direct Draft attachment refusal.

**Exit:** Tasks 6.1-6.6 are complete; authoring, authorization, upload, public discovery, ordering, draft isolation, and download tests pass; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 7: Clubs And Memberships

**Entry:** Phases 1 and 2 are complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 7.

- [ ] 7.1 Add club and membership migrations, state enums, models, relationships, and factories; verify persistence and state tests.
- [ ] 7.2 Add the eager-loaded club-directory query with administrator and active-member counts; verify documented fields, counts, and bounded-query behavior.
- [ ] 7.3 Add safe JPG/PNG logo storage with generated filenames, executable rejection, and the documented provisional 2 MB limit; verify accepted and rejected uploads without treating the limit as a confirmed client requirement.
- [ ] 7.4 Add transactional approved-student membership requests; verify Pending/Active duplicates, concurrent duplicates, and unapproved-student refusal.
- [ ] 7.5 Add Super Admin club-administrator assignment restricted to approved Students; verify eligibility and authorization.
- [ ] 7.6 Add own-club Club Admin approval, rejection, and removal; verify cross-club `403` responses.
- [ ] 7.7 Add Super Admin membership approval only; verify no undocumented Super Admin rejection or removal is granted.
- [ ] 7.8 Add own-club page editing; verify Club Admin can update only an administered club.
- [ ] 7.9 Add club directory and membership-request interfaces; verify role-aware requests, validation feedback, and empty states.
- [ ] 7.10 Add club editing and membership-administration interfaces; verify Club Admin and Super Admin controls match their distinct server permissions.

**Exit:** Tasks 7.1-7.10 are complete; directory, upload, request, duplicate prevention, administration, editing, and authorization tests pass; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 8: Operations Dashboard

**Entry:** Phases 3-7 are complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 8. Resolve C10 and C11 first.

- [ ] 8.1 Add total-seat, occupied-seat, occupancy-percentage, and Pending-registration queries; verify Hall Office Staff, Provost, and Super Admin values with deterministic records.
- [ ] 8.2 Add open-by-category, overdue, and 30-day average-resolution queries using C11; verify calculations and the no-resolutions unavailable state.
- [ ] 8.3 Add tutor block-scoping using C10; verify tutor category counts, overdue counts, and average resolution time exclude other blocks, while Student-only accounts receive `403`.
- [ ] 8.4 Replace the placeholder dashboard with role-appropriate metric cards and complaint summaries; verify Inertia props, empty states, and seeded metric presentation through feature tests and browser review.

**Exit:** Tasks 8.1-8.4 are complete; C10-C11 are captured; leadership and tutor-scoped metrics match deterministic records; `docs/PROJECT_STATUS.md` is updated; implementation stops.

## Phase 9: Assessment Data And Delivery

**Entry:** Phases 1-8 are complete and `docs/PROJECT_STATUS.md` explicitly authorizes Phase 9.

- [ ] 9.1 Add deterministic Hall, Block, Room, Seat, Student, and allocation seeders for 1 hall, 3 blocks, at least 30 rooms with capacities 2-4, about 40 Resident, 15 Attached, and 5 Pending students; verify counts and distributions.
- [ ] 9.2 Add deterministic tutor and club seeders for 5 tutors across all blocks and 4 clubs with active and Pending members; verify relationships and distributions.
- [ ] 9.3 Add deterministic complaint and notice seeders for 20 complaints across all statuses including 3 overdue and 10 notices including 2 Draft and 2 pinned; verify counts and distributions.
- [ ] 9.4 Integrate the six exact demo accounts into the complete seed graph; verify each authenticates with documented roles and scoped data.
- [ ] 9.5 Add aggregate seed assertions and verify both `php artisan migrate:fresh --seed --no-interaction` and `php artisan migrate --seed --no-interaction` in their appropriate database states.
- [ ] 9.6 Review Identity and Registration screens at 375 px with keyboard navigation; correct only assessment-scope labels, focus, contrast, loading, empty, validation, and success states, then record results.
- [ ] 9.7 Review Hall Structure, Student Record, and Allocation screens at 375 px with keyboard navigation; correct only assessment-scope usability and record results.
- [ ] 9.8 Review House Tutor screens at 375 px with keyboard navigation; correct only assessment-scope usability and record results.
- [ ] 9.9 Review Complaint and Notice screens at 375 px with keyboard navigation; correct only assessment-scope usability and record results.
- [ ] 9.10 Review Club and Dashboard screens at 375 px with keyboard navigation; correct only assessment-scope usability and record results.
- [ ] 9.11 Update `README.md` and `.env.example` with track, Laravel 13 approved deviation, user-reported authority-approval source, versions, setup, migration/seeding, run/test commands, demo accounts, completion status, assumptions, trade-offs, time spent, and AI use; verify no written approval evidence is claimed and documented commands contain no secrets.
- [ ] 9.12 Produce either a 3-5 minute demonstration recording or 6-10 assessment screenshots covering mandatory workflows; verify all evidence uses fictional data.
- [ ] 9.13 Run `vendor/bin/pint --dirty --format agent`, focused Pest tests, `php artisan test --compact`, static analysis, frontend checks, type checks, and production build; record exact results and resolve assessment-scope failures.

**Exit:** Tasks 9.1-9.13 are complete; the authority-approved Laravel 13 deviation is accurately documented as user-reported; seed minima, all six accounts, responsive reviews, documentation, evidence, and quality checks are verified; `docs/PROJECT_STATUS.md` is updated; implementation stops pending archive approval.

## Workflow Follow-up

- Include an OpenSpec task-to-commit map and exact quality-gate evidence in every phase PR.
- Preserve task-level commit history and the phase boundary; do not squash the entire implementation into one opaque commit.
- Review the completed implementation against every capability scenario before synchronizing or archiving the change.
- Archive the change only after implementation, verification, and explicit approval.
