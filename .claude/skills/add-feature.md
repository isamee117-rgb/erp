# Skill: Add New Feature

## When to Use
When asked to build something new — a component, endpoint, page, or functionality.

## Process

### Step 1: Understand (before writing any code)
- What exactly needs to be built?
- Where does it fit in the existing architecture?
- Which existing files/patterns should I follow?
- Read 1-2 similar existing files to match the pattern

### Step 2: Plan (briefly state this before coding)
- Where will the new code live?
- What existing code will it interact with?
- Any edge cases or gotchas?

### Step 3: Build
- Follow existing patterns exactly
- Write the feature code
- Add proper error handling
- Add TypeScript types (if TS project)

### Step 4: Test
- Write a basic test for the happy path
- Write a test for the main failure case
- Run existing tests to confirm nothing broke

### Step 5: Done
- Stop here — do not add "bonus" improvements
- Do not refactor surrounding code
- Report what was built in 2-3 sentences

## Quality Checklist
- [ ] Follows existing code patterns
- [ ] Has error handling
- [ ] Has at least one test
- [ ] No new dependencies added (or approved first)
- [ ] No existing functionality broken