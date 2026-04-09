# Core Coding Rules

## The Prime Directive
Do ONLY what is explicitly asked. Nothing more, nothing less.

## Scope Rules
- Fix only the broken thing — do not "improve" surrounding code
- Change the minimum number of lines needed to solve the problem
- If fixing X would require changing Y, stop and ask first
- Do not refactor, rename, or reorganize unless explicitly told to

## Before Writing Any Code
- Read the relevant existing files first
- Understand the pattern already used in the codebase
- Follow that pattern — don't introduce new ones
- If you need to read a file, read it once and remember it

## Code Quality Standards
- No magic numbers — use named constants
- No duplicate code — extract shared logic
- No dead code — don't leave unused variables or functions
- No TODO comments — either do it now or create a task
- Functions do one thing only
- Names must be descriptive: `getUserById` not `getUser` not `fetchData`

## Error Handling
- Every async operation must have error handling
- Never use empty catch blocks
- Errors must be logged with context
- User-facing errors must be human-readable

## Comments
- Only comment WHY, never WHAT (the code shows what)
- Delete commented-out code
- Complex algorithms get a short explanation comment above them

## Git Commits (when asked)
- One logical change per commit
- Commit message format: `type: short description`
- Types: feat, fix, refactor, docs, test, chore