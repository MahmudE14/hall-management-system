# Development Workflow

The delivery path is:

1. Requirements: clarify scope, acceptance criteria, and exclusions. Complete when approved.
2. OpenSpec proposal: create one change with proposal, spec deltas, and small tasks. Complete when reviewed and approved.
3. Phase authorization: name exactly one implementation phase in `docs/PROJECT_STATUS.md`. Authorization `NONE` permits no implementation.
4. Phase branch: start `phase/NN-short-description` from the latest verified `main`. Only one phase branch may be active, and feature implementation never occurs directly on `main`.
5. Task delivery: execute only authorized OpenSpec tasks using TDD, debugging, review, and verification as applicable. Complete each task with its tests and traceable Conventional Commit history.
6. Phase quality gate: run the required test, static-analysis, formatting, type, build, and acceptance checks; review the complete branch diff against `main`.
7. Pull request: with explicit push/PR authorization, push the phase branch and open a PR to `main` that maps tasks to commits and records verification evidence.
8. Review and merge: wait for CI and review, resolve findings on the phase branch, and obtain explicit user approval before merging. Prefer a merge commit.
9. Integration checkpoint: synchronize local `main`, update `docs/PROJECT_STATUS.md`, set implementation authorization to `NONE`, and wait for separate authorization for the next phase.

Lifecycle terms are strict: **implementation-complete** means every phase task and implementation exit criterion is verified and committed; **review-complete** means its PR has satisfactory review and all available remote checks; **integrated** means the approved PR is merged and local `main` is synchronized. A later phase prerequisite is satisfied only when the earlier phase is integrated.

## Branch Policy

- `main` is the stable integration branch. Planning-only documentation may be refined there when authorized, but application features must use a phase branch.
- Phase branches use `phase/NN-short-description`, for example `phase/01-identity-registration`.
- Create a phase branch only after its prerequisite phase is merged, local `main` is synchronized with the remote, the planning checkpoint is verified, and the phase is explicitly authorized.
- Do not start another phase branch while one is active or while its PR is awaiting review or merge.
- An unverified remote branch or PR state blocks phase-branch creation unless the user explicitly confirms that no conflicting phase work is active.
- Never force-push `main`. Do not rewrite published phase history without explicit approval.

## Task Commits

- Prefer one cohesive Conventional Commit per completed OpenSpec task, such as `feat(identity): add role assignments (task 1.1)`.
- A task's implementation, focused tests, and task-checkbox update belong in the same commit when practical. Stage the checkbox only after the acceptance behavior and focused verification pass; if the commit cannot be created, the checkbox must remain unchecked. If a task needs multiple commits, each commit must remain cohesive and reference the same task ID.
- Do not combine unrelated task IDs in one commit merely to reduce commit count.
- Do not mark a task complete until its acceptance behavior is implemented, its focused verification passes, and its commit is reviewable.
- Commits require explicit authorization. If task-commit authorization is not already part of the approved phase instructions, stop and request it before marking the task complete.

## Quality Gates

The baseline full phase gate is `vendor/bin/pint --dirty --format agent`, the phase's focused tests, `composer ci:check`, and `npm run build`. Add any phase-specific checks named by OpenSpec, including MySQL verification where required. If planning artifacts changed, also run `openspec validate <change> --strict` and `git diff --check`. Record exact commands, results, and justified environmental blockers in `docs/PROJECT_STATUS.md` and the PR.

CI means only checks already configured for the repository or hosting provider; adding CI remains outside this change. If no CI exists, record `not configured` and use the complete local quality-gate evidence. If remote check or review state cannot be inspected, the phase cannot become review-complete until access is restored or the user explicitly confirms that state.

## Phase Pull Request

Before opening a phase PR:

1. Complete every authorized phase task and its task-commit mapping.
2. Run the phase's full quality gates and record exact results.
3. Review the implementation against the applicable capability scenarios and phase exit criteria.
4. Review the complete diff and commit list against `main`; remove no unrelated work.
5. Update `docs/PROJECT_STATUS.md` with the phase branch and intended PR state.
6. Obtain explicit authorization to push and create the PR if it was not already granted.

The PR must target `main` and summarize scope, OpenSpec task-to-commit mapping, acceptance evidence, quality-gate results, unresolved risks, and any deliberately deferred work. Wait for available CI checks and review. Address findings with additional traceable commits on the same phase branch.

Merging requires separate explicit user approval after CI and review are satisfactory. Prefer a merge commit so the phase boundary and task history remain visible. After merge, synchronize local `main`, record the merge and verification state, clear the active phase branch/PR from `docs/PROJECT_STATUS.md`, and reset implementation authorization to `NONE`. A merged phase never authorizes the next phase.

## START SESSION

1. Read `AGENTS.md`, `docs/DEVELOPMENT_WORKFLOW.md`, `docs/PROJECT_STATUS.md`, and the active OpenSpec change.
2. Inspect the current branch, working tree, recent commits, remote tracking state, and pending PR state. If remote PR state cannot be checked, record it as unverified rather than assuming none exists.
3. Confirm the authorization ledger separately records the permitted phase implementation, branch creation, task commits, push/PR action, and merge action. Authorization `NONE`, an insufficient authorization scope, or a branch mismatch blocks the corresponding action.
4. Confirm prerequisite phases are integrated into the latest verified `main` and no other phase branch or PR is active. Unverified remote state blocks a new phase unless the user explicitly clears it.
5. Summarize the current state and propose the next smallest authorized task. Do not implement until instructed.

## END SESSION

1. Run the narrowest relevant verification and record exact results; run the full phase quality gate when implementation is complete.
2. Review the diff for scope, generated artifacts, secrets, and unrelated changes without discarding work that is not yours.
3. Stage an OpenSpec task checkbox only after that task's behavior and focused verification pass, include it in the same authorized task commit when practical, and confirm the task-to-commit mapping afterward.
4. Update `docs/PROJECT_STATUS.md` with current phase, authorization, branch, task/commit progress, verification, blockers, and pending PR state.
5. Create only explicitly authorized commits. Do not leave a task marked complete without its required commit; request authorization instead.
6. If the phase is implementation-complete, follow the Phase Pull Request procedure. After it becomes review-complete, stop for explicit merge approval. Otherwise, identify unfinished work and the next-session starting point.

Never discard or overwrite unfinished changes. Never merge a PR, begin another phase, or implement on `main` without the required explicit authorization.
