# Skill: Code Review

## When to Use
When asked to review, audit, or give feedback on existing code.

## Process

### Step 1: Read Completely First
- Read the entire file/PR before commenting on anything
- Understand intent before judging implementation

### Step 2: Review in Priority Order

**🔴 Critical (must fix)**
- Security vulnerabilities
- Data loss risks
- Broken logic / incorrect behavior
- Missing error handling on critical paths

**🟡 Important (should fix)**
- Performance issues
- Code duplication
- Unclear naming
- Missing tests for important paths

**🟢 Minor (nice to fix)**
- Style inconsistencies
- Minor naming improvements
- Small optimizations

### Step 3: Give Feedback
- Lead with what's done well (1-2 things)
- Group feedback by priority level
- For each issue: explain the problem + suggest the fix
- Be specific — reference line numbers or function names

### Step 4: Summary
- Overall verdict: ready to merge / needs minor fixes / needs major work
- Top 3 most important changes if needed

## Rules
- Do not rewrite the whole thing — give targeted feedback
- Separate opinion from fact — label opinions as "suggestion"
- Do not flag style issues if a linter/formatter handles them
- Maximum 10 feedback points — prioritize ruthlessly