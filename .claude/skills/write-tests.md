# Skill: Write Tests

## When to Use
When asked to add tests, increase coverage, or test a specific function/component.

## Process

### Step 1: Understand What to Test
- Read the function/component fully
- Identify: what does it do? what can go wrong?
- Check if tests already exist — don't duplicate them

### Step 2: Plan Tests
For each function/component, cover:
- ✅ Happy path — normal expected input/output
- ❌ Error case — invalid input, network failure, null values
- 🔲 Edge case — empty array, zero, very large values, boundary conditions

### Step 3: Write Tests
- One `describe` block per function/component
- One `it/test` per scenario — keep them focused
- Test behavior, not implementation details
- Use realistic test data, not `foo`, `bar`, `123`
- Arrange → Act → Assert structure in every test

### Step 4: Verify
- All new tests pass
- Existing tests still pass
- Coverage meaningfully increased

## Test Naming Convention
```
it('should [expected behavior] when [condition]')

Examples:
it('should return null when user is not found')
it('should throw an error when email is invalid')  
it('should render loading state while fetching data')
```

## What NOT to Test
- Implementation details (internal state, private methods)
- Third-party library behavior
- Simple getters/setters with no logic
- Code that's about to be deleted