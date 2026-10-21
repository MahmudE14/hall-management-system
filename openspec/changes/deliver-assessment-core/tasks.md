# Tasks

## 1. Clarification Register

- [ ] 1.1 Resolve complaint assignment, rejection, closure, tutor-transition, eligible-assignee, and staff-comment permissions; update only the affected complaint scenarios and tasks, then verify `openspec validate deliver-assessment-core --strict` passes. Unrelated slices may proceed independently.
- [ ] 1.2 Resolve the exact reopening boundary and repeated-resolution metric; update the affected complaint and dashboard scenarios, then verify strict OpenSpec validation passes.
- [ ] 1.3 Resolve rejected-registration reconsideration and post-registration editable-profile fields; update the identity spec only with confirmed behavior, then verify strict OpenSpec validation passes.
- [ ] 1.4 Resolve tutor account onboarding and update the design/tasks without changing the documented tutor profile behavior; verify strict OpenSpec validation passes.
- [ ] 1.5 Resolve block association for Attached-student complaints on tutor dashboards and update the dashboard scenario; verify strict OpenSpec validation passes.
- [ ] 1.6 Confirm Track A acceptance of the preserved Laravel 13/Inertia starter, record the result in assessment assumptions, and verify the chosen track and version deviation are stated consistently.
- [ ] 1.7 Resolve which Super Admin user-management operations are required beyond user listing and role assignment; update identity requirements and tasks only with confirmed behavior, then verify strict OpenSpec validation passes.

## 2. Identity And Registration

- [ ] 2.1 Add roles, multi-role assignment, student profiles, approval states, and model factories; verify focused Pest tests cover role assignment, profile relationships, and hashed passwords.
- [ ] 2.2 Change student self-registration to validate all required fields and create a Pending student; verify valid, invalid-domain, invalid-phone, duplicate-email, and duplicate-registration feature tests pass.
- [ ] 2.3 Enforce approval-gated protected capabilities and server-side role authorization; verify Pending access denial and at least one explicit `403` feature test pass.
- [ ] 2.4 Add the Pending registration list and approval/rejection workflow with required rejection reason; verify decision permissions and validation through focused feature tests. Do not add reconsideration behavior unless task 1.3 confirms it.
- [ ] 2.5 Add role-aware shared props and navigation using the existing Inertia shell and Wayfinder; verify each role receives only its documented navigation while direct unauthorized requests still return `403`.
- [ ] 2.6 Seed the six exact fictional demo accounts and roles; verify each account authenticates with the documented email and password and the Club Admin account also has the Student role.
- [ ] 2.7 Add Super Admin user listing and role assignment/removal plus any additional operations confirmed by task 1.7; verify Super Admin can perform approved operations and every other role receives `403`.
- [ ] 2.8 Extend profile settings with the student fields confirmed editable by task 1.3; verify a Student can update only that student's permitted fields while uniqueness and format validation remain enforced.

## 3. Hall Structure And Student Records

- [ ] 3.1 Add the single Hall, block, room, and explicit seat schema, models, factories, validation, and relationships; verify relationship and capacity validation tests pass on SQLite.
- [ ] 3.2 Add block, room, and seat create/edit/list pages with occupied and free seat counts; verify Hall Office Staff and Super Admin management, Provost view-only access, other-role denial, valid seat reconciliation, and occupancy calculations through feature tests.
- [ ] 3.3 Add the eager-loaded paginated student directory with name/registration search and block/department/session/status filters; verify own-only, assigned-block, all-record, combined-filter, pagination, and bounded-query behavior in feature tests.
- [ ] 3.4 Add the student profile page with personal details, current room, and allocation history; verify authorized visibility and student self-record access in feature tests.

## 4. Seat Allocation And Vacancy

- [ ] 4.1 Add allocation history storage and a transactional seat-allocation action with active student/seat uniqueness safeguards; verify Office Staff/Provost/Super Admin authorization, approved allocation, duplicate student allocation, occupied seat, and Pending student refusal tests pass.
- [ ] 4.2 Add allocation controls to the student/room workflow; verify a successful allocation changes the student from Attached to Resident and updates occupancy.
- [ ] 4.3 Add transactional vacancy with required date and reason; verify the student returns to Attached, the seat becomes free, and historical allocation data remains intact.
- [ ] 4.4 Protect room capacity changes from current occupancy and retained seat history; verify capacity cannot fall below occupancy and no allocation history is deleted.
- [ ] 4.5 Verify the allocation concurrency approach and migrations against configured MySQL once credentials are available, while retaining the SQLite feature-test coverage.

## 5. House Tutors

- [ ] 5.1 Add tutor profiles, active state, block assignments, factories, and Provost/Super Admin administration; verify create, edit, deactivate, multi-block assignment, and unauthorized access tests pass. Implement account onboarding only as confirmed by task 1.4.
- [ ] 5.2 Add tutor-scoped student list/profile access; verify tutors see residents in assigned blocks and receive `403` for out-of-scope students.
- [ ] 5.3 Add the tutor list and block-assignment interface using existing components and Wayfinder; verify the Inertia responses expose only authorized controls and expected assignment data.

## 6. Complaints

- [ ] 6.1 Add complaint, status-log, comment, and annual-counter schema with enums, relationships, factories, indexes, and generated media paths; verify model and validation tests cover all documented categories and upload limits.
- [ ] 6.2 Add transactional approved-student complaint submission with resident room autofill, Attached location input, private image storage, and locked `KJH-YYYY-NNNN` generation; verify Pending refusal, location behavior, filename safety, yearly reset, and ID uniqueness tests pass.
- [ ] 6.3 Add policy-scoped complaint lists and details with status/category filters, days-open display, and private image delivery; verify students cannot list, view, or download another student's complaint.
- [ ] 6.4 Add complaint assignment and chronological comments using the roles and assignee types confirmed by task 1.1; verify assignment authorization, all-record leadership visibility, tutor assignment scope, comment ordering, and unauthorized access tests pass.
- [ ] 6.5 Add the transactional complaint transition action using only the actor permissions confirmed by task 1.1 and time/metric rules confirmed by task 1.2; verify every allowed edge, every forbidden edge, rejection reason, resolution note, closure authority, tutor scope, reopening, resolution timing, and audit-log atomicity.
- [ ] 6.6 Add complaint submission, list, detail, timeline, assignment, comment, and transition interfaces; verify role-specific controls, field errors, success feedback, empty states, and overdue flags through Inertia feature assertions and browser review.

## 7. Notice Board

- [ ] 7.1 Add notice schema, enums, relationships, factory, PDF storage, Form Requests, and policies; verify Draft/Published state, Office Staff/Provost/Super Admin publishing, Club Admin category restriction, file type, generated filename, executable rejection, and 5 MB limit tests pass.
- [ ] 7.2 Add authorized notice create/list interfaces; verify documented roles can create permitted categories and unauthorized category changes return `403`.
- [ ] 7.3 Add the public paginated notice list with title search, category filter, pinned-first/newest ordering, and public detail; verify Draft notices are absent from every public query and direct route.
- [ ] 7.4 Add Published attachment download and Draft attachment isolation; verify valid PDF download and direct Draft attachment refusal in feature tests.

## 8. Clubs And Memberships

- [ ] 8.1 Add club and membership schema, states, relationships, factories, safely generated JPG/PNG logo storage using the documented provisional 2 MB limit, and eager-loaded member-count query; verify directory fields, active-member counts, image validation, generated filename, and executable rejection.
- [ ] 8.2 Add approved-student membership requests with transactional duplicate prevention; verify Pending/Active duplicates, concurrent duplicates, and unapproved-student requests are refused.
- [ ] 8.3 Add Super Admin club-admin assignment and membership approval plus own-club Club Admin decision/removal workflows; verify approved-Student administrator eligibility, Super Admin approval, Club Admin approval/rejection/removal scope, and cross-club `403` tests pass without granting undocumented Super Admin rejection or removal.
- [ ] 8.4 Add own-club page editing; verify Club Admin can update only an administered club and cross-club updates return `403`.
- [ ] 8.5 Add club directory, request state, club editing, and membership administration interfaces; verify role-aware controls, validation feedback, and empty states through Inertia feature assertions.

## 9. Operations Dashboard

- [ ] 9.1 Add dashboard queries for seats, occupancy, Pending registrations, open complaints by category, overdue complaints, and 30-day average resolution time; verify Hall Office Staff, Provost, and Super Admin calculations plus the no-resolutions unavailable state using deterministic records.
- [ ] 9.2 Add tutor block-scoping using the Attached-student association confirmed by task 1.5 and documented role authorization; verify tutor category counts, overdue counts, and average resolution time exclude other blocks and Student-only accounts receive `403`.
- [ ] 9.3 Replace the placeholder dashboard with role-appropriate metric cards and complaint summaries using existing components; verify Inertia props and seeded metric presentation through feature tests and browser review.

## 10. Assessment Data And Delivery

- [ ] 10.1 Build deterministic fictional seeders for 1 hall; 3 blocks; at least 30 rooms with capacities 2-4; about 40 Resident, 15 Attached, and 5 Pending students; 5 tutors assigned across all blocks; 4 clubs with active and Pending members; 20 complaints across all statuses including 3 overdue; 10 notices including 2 Draft and 2 pinned; and the six demo accounts. Verify both `php artisan migrate:fresh --seed --no-interaction` and `php artisan migrate --seed --no-interaction` succeed in their appropriate database states and assertion tests confirm every minimum and distribution.
- [ ] 10.2 Review every mandatory screen at a 375 px viewport and with keyboard navigation; correct only assessment-scope usability, labels, focus, contrast, loading, empty, validation, and success states, then record the reviewed screens.
- [ ] 10.3 Update `README.md` and `.env.example` with the chosen track, versions, fresh-clone setup, migration/seeding, run/test commands, demo accounts, done/partial/not-done status, assumptions, trade-offs, time spent, and AI use; verify every documented command against a clean local setup and confirm no secrets are present.
- [ ] 10.4 Produce either a 3-5 minute demonstration recording or 6-10 assessment screenshots covering the mandatory workflows and verify all evidence uses fictional data.
- [ ] 10.5 Run `vendor/bin/pint --dirty --format agent`, focused Pest tests, `php artisan test --compact`, static analysis, frontend checks, type checks, and production build; record exact results and resolve assessment-scope failures before completion.

## Workflow Follow-up

- Build a meaningful commit history only when the user explicitly authorizes commits; do not squash all implementation into one commit.
- Review the completed implementation against every capability scenario before synchronizing or archiving the change.
- Archive the change only after implementation, verification, and explicit approval.
