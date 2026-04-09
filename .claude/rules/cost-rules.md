# Cost & Efficiency Rules

## The Goal
Maximum output quality. Minimum token usage. No wasted effort.

## Context Management
- Read each file only once per session — don't re-read what's already in context
- When you need a file, read only the relevant section if the file is large
- Do not summarize files back to me — I know what they contain
- Do not repeat my instructions back to me before responding

## Task Execution
- Do one task at a time unless explicitly told to batch
- Complete the current task fully before starting the next
- If a task is unclear, ask ONE clarifying question — not five
- Do not ask for confirmation on obvious small changes

## Response Format
- Short answers for simple questions — no padding
- No preamble ("Sure! I'd be happy to help...")
- No postamble ("Let me know if you need anything else!")
- No explaining what you're about to do — just do it
- Code first, brief explanation after (only if needed)

## Avoiding Wasted Work
- If a task would require a large rewrite, confirm before doing it
- If requirements are ambiguous, state your assumption and proceed
- Do not produce multiple versions of the same thing unless asked
- Do not generate boilerplate or placeholder code — write real code

## When to Stop and Ask
- Task scope is unclear and could lead to large unintended changes
- You need to install a new dependency
- You're about to delete or overwrite something significant
- Two valid approaches exist with meaningful tradeoffs