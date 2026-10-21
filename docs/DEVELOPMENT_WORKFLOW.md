# Development Workflow

1. Requirements: clarify scope, acceptance criteria, and exclusions. Complete when approved.
2. OpenSpec proposal: create one change with proposal, spec deltas, and small tasks. Complete when reviewed and approved.
3. Implementation: execute only approved OpenSpec tasks. Use Superpowers for TDD, debugging, review, and verification. Complete when each task is independently verified.
4. Quality review: run relevant tests and static checks, then review changed files. Complete when results are recorded.
5. Checkpoint: commit only with explicit authorization, then update `PROJECT_STATUS.md`. Complete when the next task is clear.

## START SESSION

Read `AGENTS.md`, `docs/PROJECT_STATUS.md`, and the active OpenSpec change. Inspect Git status.
Summarize the current state and propose the next smallest task. Do not implement until instructed.

## END SESSION

Run relevant verification, summarize changes, update `docs/PROJECT_STATUS.md`, identify unfinished
work, and provide the next-session starting point. Do not commit unless explicitly authorized.
Never discard or overwrite unfinished changes.
