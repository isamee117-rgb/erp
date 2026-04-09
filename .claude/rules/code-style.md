# Code Style Rules — LeanERP

## PHP / Laravel

- **PSR-12** coding standard
- Class names: `PascalCase` | Methods: `camelCase` | DB columns: `snake_case`
- Controllers stay thin — business logic goes in `app/Services/`
- Always use **Form Requests** for validation (not `$request->validate()` in controllers)
- Always use **API Resources** for JSON responses (not `->toArray()` or manual arrays)
- Use `$request->get('auth_user')` to get the authenticated user (set by `ApiTokenAuth` middleware)
- IDs are string-prefixed (e.g. `'SO-' . Str::random(9)`), never auto-increment
- Multi-tenant: every query on tenant tables must be scoped by `company_id`
- Super Admin (`system_role === 'Super Admin'`) has `company_id = null` — handle separately

## Eloquent

- Use relationships (`hasMany`, `belongsTo`) instead of manual joins
- Use model scopes (`scopeForCompany`, `scopeActive`) for reusable query filters
- `$fillable` must be explicit — no `$guarded = []`
- Never store plain text passwords

## Error Handling

- Return `response()->json(['error' => '...'], 4xx)` for client errors
- Throw `\RuntimeException` in services for business rule violations
- Controllers catch `RuntimeException` and return appropriate HTTP status

## Naming

| Thing | Convention | Example |
|-------|-----------|---------|
| Controller | `{Model}Controller` | `SaleController` |
| Service | `{Domain}Service` | `SaleService` |
| Resource | `{Model}Resource` | `SaleOrderResource` |
| Form Request | `{Action}{Model}Request` | `StoreSaleRequest` |
| Model scope | `scope{Name}` | `scopeForCompany` |
