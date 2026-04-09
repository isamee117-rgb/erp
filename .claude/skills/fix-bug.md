# Skill: Fix a Bug

## When to Use
When something is broken, throwing errors, or behaving unexpectedly.

## Process

### Step 1: Read the Error
- Read the full error message — don't skim
- Note the file name, line number, and error type
- Do not start fixing until you understand what the error says

### Step 2: Find the Root Cause
- Trace back from where the error occurs
- Follow the data — where does it come from, where does it break?
- Check: wrong type? null/undefined? wrong variable? wrong logic?
- State the root cause in plain English before fixing

### Step 3: Fix
- Make the smallest possible change
- Do not touch code outside the affected area
- Do not refactor while fixing

### Step 4: Verify
- Will this fix break anything else?
- Check where this function/component is used
- Run relevant tests

### Step 5: Report
- Tell the user: what was the bug + what was the fix (2 sentences max)

## Common Root Causes (check these first)
- `undefined` or `null` not handled
- Async function not awaited
- Wrong variable name (typo)
- State mutation instead of new object
- Off-by-one error in loops/arrays
- Race condition in async code
- Missing dependency in useEffect
- Environment variable not loaded