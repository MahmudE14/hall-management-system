# Kabi Jasimuddin Hall Management System

> **Assessment track: Track C — Full-stack (Laravel + React)**

A hall-management application for a fictional public university residential hall. It covers resident and attached students, hall structure, seat allocation and vacancy, house tutors, complaints, and a public notice board with server-side role-based access. The UI is a React application rendered through Inertia inside Laravel.

> **Track C note:** Track C was selected and the delivered stack is Laravel + React. The implementation is one Laravel application with an Inertia React frontend; the brief's versioned `/api/v1` REST API and separately deployed React SPA are not included. See **Project Status**.

## Tech Stack

| Area              | Technology                                                                                      |
| ----------------- | ----------------------------------------------------------------------------------------------- |
| Backend           | PHP 8.3+ (CI uses PHP 8.3), Laravel 13.38.0                                                     |
| Frontend          | React 19.3.1, TypeScript 5.9.3, Inertia Laravel 3.5.1, `@inertiajs/react` 3.9.1                 |
| Styling           | Tailwind CSS 4.3.3, Radix/shadcn-style components                                               |
| Build             | Vite 8.3.4 through vite-plus 0.3.0, Wayfinder 0.1.23 typed routes                               |
| Auth              | Laravel Fortify 1.41.0 (login, registration, password reset, email verification, 2FA, passkeys) |
| Database          | MySQL 8 or SQLite; the test suite uses in-memory SQLite                                         |
| Testing / quality | Pest 5.3.3, PHPUnit 13.4.3, Pint 1.32.1, Larastan/PHPStan 3.13.0, TypeScript, GitHub Actions    |
| Runtime           | Node.js 22.12+ with npm (CI uses Node 22); Composer 2                                           |

**Laravel version note:** Laravel 13 is recorded in this repository as a user-confirmed deviation from the brief's Laravel 11/12 wording. No written approval evidence is claimed.

## Features

### Authentication and registration

- Login/logout, password reset, email verification, two-factor authentication, and passkeys through Fortify.
- Six seeded demo accounts with multi-role support; inactive accounts are blocked at authentication and on protected requests.
- Student self-registration with name, 10-digit registration number, department, session, phone, `@student.du.test` email, and password.
- Unique email and registration-number checks; new students start as Pending and require email verification.
- Staff approve, reject with a reason, or return a rejected registration to Pending; decision history is retained.
- Authenticated users can update their own name, email, and phone; student email and phone formats are enforced and registration/academic fields remain read-only.
- Super Admin user search/filter, role assignment, and account activation/deactivation.

### Hall structure and student records

- Hall, block, room, and seat models with a block → room → seat hierarchy.
- Hall Office Staff and Super Admin create/edit blocks, rooms, and seats; Provost has read-only structure access.
- Seats can be added up to capacity; capacity cannot be reduced below the number of defined seats or current occupancy. Room views show occupied and free seat counts.
- Paginated student directory with name/registration search and block, department, session, and status filters.
- Student profile with personal details, current room, and complete allocation history.
- Student and Club Admin accounts see only their own student record; House Tutors see students currently allocated in assigned blocks.

### Seat allocation and vacancy

- Transactional seat allocation for approved students only.
- One active seat per student and one active student per seat, backed by database constraints and row locking.
- Vacancy records a date and reason, frees the seat, returns the student to Attached, and keeps allocation history.
- Active allocations determine occupancy; vacated allocations remain available in history.

### House tutors

- Provost/Super Admin can create, edit, and deactivate tutors and assign one or more blocks.
- Tutor-scoped student directory and profile access is limited to currently assigned blocks.
- The seeded North Block tutor has an active profile and can sign in.
- Newly created tutor accounts receive a random initial password but no credential-delivery workflow.

### Complaint management

- Approved students submit complaints with a category (water, electricity, internet, cleanliness, furniture, security, or other), title, description, and optional JPG/PNG photo (max 2 MB).
- Students with an active allocation have their room attached automatically; students without one provide a location.
- Unique human-readable tracking IDs (`KJH-YYYY-NNNN`) with an annual sequence reset.
- Role-scoped lists and details: students see their own, tutors see assigned only, and leadership sees all.
- Staff assign complaints to active Hall Office Staff or eligible House Tutors.
- Chronological comments, assignment metadata, and audited status history.
- Enforced workflow through Submitted, Under Review, In Progress, Resolved, Closed, or Rejected, with rejection reason, resolution note, student reopening within 7 days, and overdue flag after 7 days.
- Complaint closure is limited to Provost and Hall Office Staff.
- Private complaint images are served through an authorized route.

### Notice board

- Notice categories are Provost Office, Hall Office, and Club. Hall Office Staff, Provost, and Super Admin can create notices in all categories; Club Admin can create Club-category notices only.
- Notice fields: title, body, category, Draft/Published status, pinned flag, and optional PDF attachment up to 5 MiB.
- Published notices are public at `/notices`; Draft notices are hidden from public lists, detail routes, search, and downloads.
- Public list supports title search, category filtering, pagination, and pinned-first then newest-first ordering.
- Public detail page provides a working PDF download with a safe generated filename.
- Administrative list shows both Draft and Published notices; staff see all notices and Club Admin sees only notices they created.
- Notice authoring is create-only: no edit, delete, unpublish, or later-publish workflow.

### Platform and quality

- Form Requests, policies, enum-backed states, transactions for multi-write flows, eager loading, and pagination.
- Pest feature coverage for business rules, authorization, uploads, and role scoping.
- GitHub Actions CI workflow on pushes to `main` and pull requests.
- No versioned `/api/v1` REST API or standalone SPA.
- Clubs and dashboard metrics are not implemented yet.

## Roles and Permissions

| Role              | Implemented capabilities                                                                                                                                                                                                                                        |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Super Admin       | User/role administration, account activation/deactivation; review registrations; manage structure; allocate/vacate; manage tutors; view all students; assign, comment on, and transition complaints; create notices in all categories. Cannot close complaints. |
| Provost           | Review registrations; view structure; allocate/vacate seats; manage tutors; view all students; assign, comment on, transition, and close complaints; create notices in all categories.                                                                          |
| Hall Office Staff | Review registrations; manage blocks/rooms/seats; allocate/vacate seats; view all students; assign, comment on, transition, and close complaints; create notices in all categories.                                                                              |
| House Tutor       | View students currently allocated in assigned blocks; view/comment on assigned complaints; move assigned complaints Submitted → Under Review → In Progress → Resolved. Cannot reject or close.                                                                  |
| Club Admin        | Create Draft or Published Club-category notices, pin them, and see only own notices in the administrative list. The seeded Club Admin is also a Student, so it can use own profile and complaint features. Club management is not implemented.                  |
| Student           | Own profile and own student record; submit/list/detail/comment on own complaints; reopen own resolved complaint within 7 days; read public notices.                                                                                                             |

Notice category scoping is enforced on the server: Club Admin attempts to create Provost Office or Hall Office notices receive `403`.

## Demo Accounts

All accounts are seeded as active and email-verified. They share the password `password`.

| Role                      | Email                     | Password |
| ------------------------- | ------------------------- | -------- |
| Super Admin               | admin@kjh.test            | password |
| Provost                   | provost@kjh.test          | password |
| Hall Office Staff         | office@kjh.test           | password |
| House Tutor (North Block) | tutor@kjh.test            | password |
| Club Admin (also Student) | clubadmin@student.du.test | password |
| Student                   | student@student.du.test   | password |

All demo data is fictional. The seed creates one hall, one block (North), these six accounts, two student profiles, and one tutor. It does not seed rooms, seats, complaints, notices, or clubs.

## Installation and Setup

### Requirements

- PHP 8.3+ with PDO SQLite or PDO MySQL and `fileinfo`.
- Composer 2.
- Node.js 22.12+ with npm.
- MySQL 8 or SQLite.
- To exercise the full 5 MiB notice-PDF limit, set `upload_max_filesize` to at least `6M` and `post_max_size` to at least `10M`; the common PHP default of `2M` rejects larger uploads before application validation.

### SQLite setup

```bash
git clone https://github.com/MahmudE14/hall-management-system.git
cd hall-management-system

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
```

In `.env`, set:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Then migrate, seed, build, and start:

```bash
php artisan migrate --seed
npm install
npm run build
composer run dev
```

Open <http://localhost:8000>. `composer run dev` starts the Laravel server, queue listener, log tail, and Vite.

For local email verification, `MAIL_MAILER=log` writes messages to `storage/logs/laravel.log`. No queue worker or `storage:link` is required for the implemented features; uploads are served from private storage through application routes.

### MySQL alternative

Create a database, set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`, then run:

```bash
php artisan migrate --seed
npm install
npm run build
composer run dev
```

To reset local data, run `php artisan migrate:fresh --seed`.

## Demo / Evaluation Flow

The seed is intentionally minimal. Create a room and its seats before allocating a seat.

1. Log in as `office@kjh.test`.
2. Go to **Hall structure → Rooms → New room** (North Block), then edit the room and add seats.
3. Go to **Students**, open `student@student.du.test` (Resident Student), allocate an available seat, and confirm the allocation details and room occupancy.
4. Log in as `student@student.du.test` and submit a complaint; the allocated room is used automatically.
5. Log in as `office@kjh.test`, open the complaint, assign it to **North Block House Tutor**, and move it to Under Review if desired.
6. Log in as `tutor@kjh.test`; confirm only the assigned complaint is visible. Add a comment and resolve it with a resolution note.
7. Log in as `student@student.du.test` and reopen the resolved complaint within 7 days.
8. Log in as `office@kjh.test`, resolve again and close it; verify the timeline history.
9. Log in as `office@kjh.test` and create a Published notice with a PDF at **Notices → Create notice**. Open `/notices` in a private window to verify the public board and download.
10. Log in as `clubadmin@student.du.test` and create a Club notice. Confirm only Club category is offered and the administrative list contains only that account's notices.
11. Log in as `provost@kjh.test` to verify full student/complaint visibility, tutor management, and notice authoring.
12. Log in as `admin@kjh.test` to verify user search, role changes, and account activation/deactivation.

The seeded Resident Student has no active seat allocation yet; step 3 creates the allocation used by the complaint flow.

Optional: use **Register** to create a pending student with an `@student.du.test` address; review it under **Registrations** as office staff. New registrations require email verification before authenticated access; local mail is written to `storage/logs/laravel.log`.

## Project Status

Phases 0–6 are complete, and Phase 6 is merged into `main`. Phases 7–9 are pending. This README documents implemented behavior through Phase 6 only.

| Phase | Scope                              | Status   |
| ----- | ---------------------------------- | -------- |
| 0     | Planning and OpenSpec approval     | Complete |
| 1     | Identity and registration          | Complete |
| 2     | Hall structure and student records | Complete |
| 3     | Seat allocation and vacancy        | Complete |
| 4     | House tutors                       | Complete |
| 5     | Complaints                         | Complete |
| 6     | Notice board                       | Complete |
| 7     | Clubs and memberships              | Pending  |
| 8     | Operations dashboard               | Pending  |
| 9     | Assessment data and delivery       | Pending  |

The dashboard route remains a placeholder. Phase 7 adds clubs and memberships, Phase 8 adds role-scoped dashboard metrics, and Phase 9 adds the full assessment seed dataset, responsive/keyboard review, demo evidence, and final delivery checks.

## Assumptions

- Track C was selected; the implementation uses one Laravel application with an Inertia React frontend rather than two deployable applications.
- The installation manages one hall; new blocks are attached to the seeded hall. The demo seed uses Kazi Nazrul Islam Hall.
- A house tutor sees only complaints assigned to them, not every complaint in their assigned blocks.
- The 7-day complaint reopening window is an inclusive 168-hour period measured in UTC; overdue means older than 7 days and not Resolved/Closed/Rejected.
- Students without an active seat allocation provide a free-text location; students with an active seat use its room.
- Club Admin is also a Student in the seed; role combinations are supported.
- Club Admin notice authoring is category-scoped and does not require an approved Student profile; club data and club administration arrive in Phase 7.
- Laravel 13 is used as a user-confirmed deviation from the brief's Laravel 11/12 version requirement.

## Key Design Decisions

- Thin controllers with Form Requests, policies, and transactional Actions for allocation, registration decisions, complaint workflows, and notice creation.
- Database uniqueness constraints and row locking protect active allocations, tracking IDs, and status transitions.
- Server-side authorization is the source of truth; hidden UI controls are not treated as security.
- Complaint images and notice PDFs are stored privately. Complaint images are served only through authorized routes; notice PDFs are downloadable only while the notice is Published.
- Inertia and Wayfinder keep the React pages typed and avoid a separate API/auth/CORS layer; the trade-off is that the Track C API split is not delivered.
- Notice authoring is create-only in this phase, with category and Draft/Published rules enforced in the policy and Form Request.

## Known Limitations

- Notice management is create-only. There is no edit, delete, unpublish, or later-publish action.
- PHP upload settings must permit 5 MiB PDFs. The default `upload_max_filesize=2M` rejects larger notice PDFs before application validation.
- The required assessment seed volumes are not present. The current seed has no rooms, seats, complaints, notices, or clubs; create them through the UI.
- Clubs (Phase 7), dashboard metrics (Phase 8), and the Phase 9 assessment seed, responsive review, and demo evidence remain pending.
- No versioned `/api/v1` REST API, Sanctum token flow, CORS setup, or standalone React SPA is provided.
- Newly created tutor accounts have no credential-delivery or onboarding flow; the seeded tutor is ready to use.
- Pending students can authenticate after email verification but cannot be allocated a seat or submit complaints.
- Responsive Tailwind layouts exist, but the formal 375 px and keyboard-navigation review is not completed.
- Bonus features are not implemented: anonymous complaints, events, polls, notifications, Bangla/English toggle, CSV export, activity log, Docker, or live deployment.
- Payments/fees, dining, native apps, SMS, and university system integrations are out of scope.

## Time Spent

Approximately 12–16 focused hours.

## AI Tools Used

AI-assisted development tools were used for implementation support, code review, debugging, test assistance, and documentation. The repository's project configuration references Claude Code, OpenCode (with Superpowers), JetBrains Junie, and Laravel Boost MCP. Implementation decisions and final verification were reviewed by the developer, who can explain and modify the submitted code.

## Testing and Quality

```bash
# Full Pest suite
php artisan test --compact

# Frontend formatting/lint and TypeScript
npm run check
npm run types:check

# PHP static analysis
composer types:check

# Production frontend build
npm run build

# CI-equivalent quality gate (frontend format/lint, TypeScript, Pint, PHPStan, Pest)
composer ci:check
```

The current suite contains 613 tests. The latest verified Phase 6 gate (23 Oct 2026) reported 613 tests / 3,810 assertions, with `composer ci:check` and `npm run build` passing. The GitHub Actions workflow runs `composer ci:check` on pushes to `main` and pull requests.

## Repository

<https://github.com/MahmudE14/hall-management-system>
