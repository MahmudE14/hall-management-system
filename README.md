# Hall Management System

> **Assessment Track: Track C — Full-stack (Laravel + React)**

A hall-management application for a fictional public university residential hall. It covers resident students, hall structure, seat allocation, house tutors, and complaint handling with server-side role-based access. The UI is built with React/Inertia inside a Laravel application.

> **Track C note:** Track C was selected and the stack is Laravel + React. The delivered architecture is an integrated Laravel + Inertia React application; the brief's Track C versioned `/api/v1` REST API and separately deployed React SPA are not included. See **Implementation Status**.

## Tech Stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3+ (CI uses PHP 8.3), Laravel 13.38 |
| Frontend | React 19.2, TypeScript 5.7, Inertia.js 3.0 |
| Styling | Tailwind CSS 4.1, Radix/shadcn-style components |
| Build | Vite 8 via vite-plus, Wayfinder typed routes |
| Auth | Laravel Fortify 1.37 (login, registration, password reset, email verification, 2FA/passkeys) |
| Database | MySQL 8 or SQLite (the test suite uses in-memory SQLite) |
| Testing / quality | Pest 5.3, Pint, Larastan/PHPStan, TypeScript, GitHub Actions |

**Laravel version note:** Laravel 13 is recorded in this repository as a user-confirmed deviation from the brief's Laravel 11/12 wording. No written approval evidence is claimed.

## Features

### Authentication & access

- ✅ Login/logout, password reset, email verification; Fortify 2FA/passkeys available.
- ✅ Six seeded demo accounts with multi-role support; inactive accounts blocked.
- ✅ Role-aware navigation and server-side authorization via middleware, policies, and Form Requests.
- ✅ Super Admin user search/filter, role assignment, account activation/deactivation.
- ✅ Authenticated users can update their own name, email, and phone; student email/phone formats are enforced.

### Registration & student records

- ✅ Student self-registration with name, 10-digit registration number, department, session, phone, `@student.du.test` email, and password.
- ✅ Unique email and registration-number checks; new students start as Pending.
- ✅ Staff can approve, reject with a reason, or return a rejected registration to Pending; decision history is retained.
- ✅ Paginated student directory with name/registration search and block, department, session, and status filters.
- ✅ Student profile with personal details, current room, and complete allocation history.

### Hall structure & allocation

- ✅ Hall, block, room, and seat models with a block → room → seat hierarchy.
- ✅ Staff create/edit blocks, rooms, and seats; room capacity is guarded against defined and occupied seats.
- ✅ Occupied/free seat counts on room views.
- ✅ Transactional seat allocation; only approved students; one active seat per student and one active student per seat.
- ✅ Vacate with date and reason; student returns to Attached; history is retained.

### House tutors

- ✅ Provost/Super Admin can create, edit, and deactivate tutors and assign one or more blocks.
- ✅ Tutor-scoped student directory/profile access limited to assigned blocks.
- ✅ The seeded North Block tutor has an active profile and can sign in.

### Complaints

- ✅ Approved students submit complaints with category, title, description, and optional JPG/PNG photo (max 2 MB).
- ✅ Resident room is autofilled from the active allocation; attached students provide a location.
- ✅ Unique human-readable tracking IDs (`KJH-YYYY-NNNN`) with annual reset.
- ✅ Role-scoped list/detail: students see their own, tutors see assigned only, leadership sees all.
- ✅ Staff assign complaints to active Hall Office Staff or eligible House Tutors.
- ✅ Chronological comments and audited status history.
- ✅ Enforced status workflow, rejection reason, resolution note, student reopening within 7 days, and overdue flag after 7 days.
- ✅ Private complaint images served through an authorized route.

### Platform / quality

- ✅ Form Requests, policies, enum-backed states, transactions for multi-write flows, eager loading, and pagination.
- ✅ Pest feature coverage for business rules and authorization.
- ✅ GitHub Actions CI workflow.
- ❌ No versioned `/api/v1` REST API or standalone SPA.
- ❌ No notice board, clubs, or dashboard metrics (see status below).

## Roles & Permissions

| Role | Main capabilities |
| --- | --- |
| Super Admin | User/role administration, activation/deactivation; full access to registrations, structure, allocations, tutors, students, and complaints. |
| Provost | Review registrations; view structure; allocate/vacate seats; manage tutors; view all students; assign, comment on, transition, and close complaints. |
| Hall Office Staff | Review registrations; manage blocks/rooms/seats; allocate/vacate seats; view all students; assign, comment on, transition, and close complaints. |
| House Tutor | View students in assigned blocks; view/comment on assigned complaints; move assigned complaints Submitted → Under Review → In Progress → Resolved. Cannot reject or close. |
| Club Admin | Student-level access to own profile and own complaints. Club features are not implemented. |
| Student | Own profile; submit/list/detail/comment on own complaints; reopen own resolved complaint within 7 days. |

## Demo Accounts

All accounts use password `password`.

| Role | Email | Password |
| --- | --- | --- |
| Super Admin | admin@kjh.test | password |
| Provost | provost@kjh.test | password |
| Hall Office Staff | office@kjh.test | password |
| House Tutor (North Block) | tutor@kjh.test | password |
| Club Admin (also Student) | clubadmin@student.du.test | password |
| Student (Resident) | student@student.du.test | password |

## Quick Setup

Requirements: PHP 8.3+, Composer 2, Node.js 22+ with npm, and MySQL 8 or SQLite.

SQLite is the fastest path:

```bash
git clone https://github.com/MahmudE14/hall-management-system.git
cd hall-management-system

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
# In .env, set:
# DB_CONNECTION=sqlite
# DB_DATABASE=database/database.sqlite

php artisan migrate --seed
npm install
npm run build

composer run dev
```

Open http://localhost:8000. `composer run dev` starts the Laravel server, queue listener, log tail, and Vite. To reset local data, run `php artisan migrate:fresh --seed`.

MySQL alternative: create a database, set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`, then run `php artisan migrate --seed`.

## Demo / Evaluation Flow

The current seed is minimal: 1 hall, 1 block (North), 6 accounts, 2 student profiles, and 1 tutor. It does not seed rooms, seats, complaints, notices, or clubs. Create a room and its seats first.

1. Log in as `office@kjh.test`.
2. Go to **Hall structure → Rooms → New room** (North Block), then edit the room and add seats.
3. Go to **Students → Resident Student**, allocate an available seat, and confirm the residency/occupancy change.
4. Log in as `student@student.du.test` and submit a complaint; the allocated room is used automatically.
5. Log in as `office@kjh.test`, open the complaint, assign it to **North Block House Tutor**, and move it to Under Review if desired.
6. Log in as `tutor@kjh.test`; confirm only the assigned complaint is visible. Add a comment and resolve it with a resolution note.
7. Log in as `student@student.du.test` and reopen the resolved complaint within 7 days.
8. Log in as `office@kjh.test`, resolve again and close it; verify the timeline history.
9. Log in as `provost@kjh.test` to verify full student/complaint visibility and tutor management.
10. Log in as `admin@kjh.test` to verify user search, role changes, and account activation/deactivation.

Optional: use **Register** to create a pending student with an `@student.du.test` address; review it under **Registrations** as office staff. New registrations require email verification before authenticated access; local mail is written to `storage/logs/laravel.log`.

## Implementation Status

| Area | Status | Notes |
| --- | --- | --- |
| Authentication, roles, demo accounts | ✅ Complete | Six demo accounts, login/logout, password reset, and role-aware server authorization. |
| Student registration & approval | ⚠️ Partial | Pending by default, with approve/reject/reconsider and retained history. Pending accounts can authenticate after email verification but are denied approved-student actions; the brief's stricter "cannot log in to protected areas" wording is not fully matched. |
| User/role administration | ✅ Complete | Super Admin search/filter, role assignment, activate/deactivate. |
| Student directory/profile | ✅ Complete | Search, filters, pagination, current room, allocation history, and role scoping. |
| Hall structure | ✅ Complete | Blocks, rooms, seats, capacity and occupancy rules; create/edit/list. |
| Seat allocation/vacancy | ✅ Complete | Transactional, history retained, one active allocation per student/seat. |
| House tutors | ✅ Complete | Create/edit/deactivate, multi-block assignment, block-scoped student access. New tutor accounts receive a random credential with no delivery workflow; the seeded tutor works. |
| Complaints (M4) | ✅ Complete | Submission, private images, tracking IDs, scoping, assignment, comments, transitions, reopening, overdue, and audit history. |
| Dashboard (M7) | ❌ Not implemented | `/dashboard` is still a placeholder; no metric cards or seeded metrics. |
| Notice board (M5) | ❌ Not implemented | No notice model, admin editor, public list, PDF upload, or draft isolation. |
| Clubs (M6) | ❌ Not implemented | No club directory or membership workflow. |
| Assessment seed dataset | ❌ Not implemented | Minimal seed only; no 3 blocks/30 rooms/60 students/5 tutors/4 clubs/20 complaints/10 notices. |
| Track C REST API + separate SPA | ❌ Not implemented | React is integrated via Inertia; no `/api/v1`, Sanctum tokens, CORS, or separate frontend deployment. |
| Responsive/mobile review | ⚠️ Partial | Responsive Tailwind layouts exist; the formal 375 px/keyboard audit is not completed. |
| Bonus items | ❌ Not implemented | No anonymous complaints, events, polls, notifications, Bangla/English toggle, CSV export, activity log, Docker, or live deployment. CI is present. |

## Assumptions

- Track C was selected; the implementation uses one Laravel application with an Inertia React frontend rather than two deployable applications.
- The installation manages one hall; new blocks are attached to the seeded hall.
- A house tutor sees only complaints assigned to them, not every complaint in their assigned blocks.
- The 7-day complaint reopening window is an inclusive 168-hour period measured in UTC; overdue means older than 7 days and not Resolved/Closed/Rejected.
- Attached students provide a free-text location; resident students use their active room.
- Club Admin is also a Student; role combinations are supported.
- Laravel 13 is used as a user-confirmed deviation from the brief's Laravel 11/12 version requirement.

## Key Design Decisions

- Thin controllers with Form Requests, policies, and transactional Actions for allocation, registration decisions, and complaint workflows.
- Database uniqueness constraints and row locking protect active allocations, tracking IDs, and status transitions.
- Server-side authorization is the source of truth; hidden UI controls are not treated as security.
- Complaint photos are stored privately and streamed through an authorized route.
- Inertia + Wayfinder keep the React pages typed and avoid a separate API/auth/CORS layer; the trade-off is that the Track C API split is not delivered.

## Known Limitations / Not Implemented

- Notice board, clubs, and dashboard metrics are not built.
- The required assessment seed volumes and demo content are not present; evaluators must create rooms/seats and complaints through the UI.
- No versioned REST API or standalone React SPA.
- No anonymous complaints, events, polls, queued email notifications, localisation toggle, CSV export, activity log, Docker setup, or live deployment.
- Formally responsive 375 px and keyboard-navigation review is still outstanding.
- Newly created tutor accounts have no credential-delivery/onboarding flow; the seeded tutor is ready to use.
- Payments/fees, dining, native apps, SMS, and university system integrations are out of scope.

## Time Spent

Approximately 12–16 focused hours.

## AI Tools Used

AI-assisted development tools were used for implementation support, code review, debugging, test assistance, and documentation. The repository's project configuration references Claude Code, OpenCode (with Superpowers), JetBrains Junie, and Laravel Boost MCP. Implementation decisions and final verification were reviewed by the developer, who can explain and modify the submitted code.

## Testing & Quality

```bash
# Full Pest suite
php artisan test --compact

# TypeScript
npm run types:check

# Production frontend build
npm run build

# CI-equivalent quality gate (frontend format/lint, TypeScript, Pint, PHPStan, Pest)
composer ci:check
```

Latest local results (23 Oct 2026): `php artisan test --compact` passed **542 tests / 3,323 assertions**; `npm run types:check` and `npm run build` passed. The GitHub Actions workflow runs `composer ci:check` on pushes to `main` and pull requests.

## Repository

https://github.com/MahmudE14/hall-management-system
