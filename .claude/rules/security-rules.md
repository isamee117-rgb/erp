# Security Rules

## Non-Negotiables
- Never hardcode secrets, API keys, passwords, or tokens in code
- Never commit .env files — always use .env.example with dummy values
- Never trust user input — validate and sanitize everything server-side
- Never expose stack traces or internal errors to API responses

## Authentication & Authorization
- Every protected route must verify the session/token
- Check permissions at the data layer, not just the route layer
- Use short-lived tokens — implement refresh token rotation
- Invalidate sessions on password change or logout

## Data Handling
- Sensitive data (passwords) must be hashed with bcrypt (min 12 rounds)
- PII must never appear in logs or error messages
- Soft delete sensitive records — never hard delete user data permanently
- Encrypt sensitive fields at rest if they contain PII

## API Security
- Rate limit all public endpoints
- Use HTTPS everywhere — never allow HTTP in production
- Set proper CORS headers — never use wildcard (*) in production
- Validate Content-Type headers on POST/PUT requests

## Dependencies
- Do not add dependencies with known CVEs
- Prefer well-maintained packages with recent activity
- Keep dependencies minimal — more packages = more attack surface