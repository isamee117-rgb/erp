# Backend Rules (Laravel + PHP)

## Laravel MVC Standard (NON-NEGOTIABLE)
- **Proper Laravel MVC must be followed every single time — no exceptions**
- **Model** — database interaction, relationships, scopes only
- **View** — display only, zero business logic in Blade files
- **Controller** — receives request, delegates to Service, returns response — nothing else
- Every feature must respect this separation — never mix responsibilities

## Core PHP / Laravel Rules (NON-NEGOTIABLE)
- **Controllers are thin** — ALL business logic lives in `app/Services/` — never in controllers
- **Always use Form Requests** for validation — never `$request->validate()` inline in controller
- **Always use API Resources** for JSON responses — never return manual arrays or Model directly

## Laravel Architecture
- Follow Laravel conventions strictly — don't fight the framework
- Folder structure: Controllers → Services → Models (Repository optional)
- Complex queries go in dedicated Query classes or Model scopes
- Never write logic directly in routes/web.php or routes/api.php

## Routing
- RESTful resource routes — use `Route::resource()` wherever possible
- Name all routes — use `route('users.index')` not hardcoded URLs
- Group related routes with `Route::prefix()` and `Route::middleware()`
- API routes in `routes/api.php`, web routes in `routes/web.php`
- Version APIs: `Route::prefix('v1')` in api.php

## Controllers
- One controller per resource — `UserController`, `OrderController`
- Use only standard resource methods: index, create, store, show, edit, update, destroy
- Controllers must not contain any business logic — delegate to Services
- Use Form Request classes for all validation — never validate in controller directly
- Return consistent API responses using a ResponseHelper or API Resource

## Validation (Form Requests)
- Create a Form Request for every store and update action
- Validate ALL inputs — required, type, length, format, unique
- Custom error messages in Form Request `messages()` method
- Example:
  ```php
  public function rules(): array {
      return [
          'name'  => 'required|string|max:255',
          'email' => 'required|email|unique:users,email',
      ];
  }
  ```

## Eloquent & Database
- Always use Eloquent — no raw SQL unless absolutely necessary
- If raw SQL needed, use `DB::select()` with bindings — never string interpolation
- Eager load relationships always — never lazy load in loops (N+1 killer):
  ```php
  User::with(['posts', 'profile'])->get(); // ✅
  ```
- Use Model scopes for reusable query logic: `scopeActive()`, `scopeRecent()`
- Use database transactions for operations touching multiple tables:
  ```php
  DB::transaction(function () { ... });
  ```
- Soft deletes on all main models — never hard delete user data
- Always specify columns: `User::select('id', 'name', 'email')->get()` — no `SELECT *`
- Index: foreign keys, frequently filtered columns, unique fields

## Migrations
- One migration per change — never edit existing migrations in production
- Always write both `up()` and `down()` methods
- Use nullable() thoughtfully — don't make everything nullable
- Migration names must be descriptive: `add_status_to_orders_table`

## API Resources
- Use Laravel API Resources for all JSON responses — never return Model directly
- Consistent response structure:
  ```php
  return response()->json([
      'data'    => new UserResource($user),
      'message' => 'User created successfully',
  ], 201);
  ```
- Use Resource Collections for lists: `UserResource::collection($users)`
- Paginate all list responses: `$users->paginate(20)`

## Error Handling
- Handle exceptions in `app/Exceptions/Handler.php`
- Create custom Exception classes for business logic errors
- Never expose stack traces in production — set `APP_DEBUG=false`
- Log errors with context using Laravel's Log facade:
  ```php
  Log::error('Payment failed', ['user_id' => $user->id, 'amount' => $amount]);
  ```
- Return proper HTTP status codes — 400, 401, 403, 404, 422, 500

## Authentication
- Use Laravel Sanctum for API token auth — not custom token logic
- Use Laravel's built-in Auth middleware — never write custom auth checks
- Gate and Policy classes for authorization — never check roles in controllers
- Policies live in `app/Policies/` — one per model
- Example:
  ```php
  $this->authorize('update', $post); // in controller
  ```

## Jobs & Queues
- Offload all heavy tasks to Jobs: emails, PDF generation, API calls
- Jobs live in `app/Jobs/`
- Always use queues for sending emails — never send synchronously in request
- Set appropriate queue timeouts and retry limits

## Environment & Config
- All sensitive values in `.env` — never hardcode in code
- Access config via `config('app.name')` — never `env()` directly in code
- Add all new env variables to `.env.example` with dummy values
- Different `.env` for local, staging, production

## Caching
- Cache heavy DB queries with `Cache::remember()`:
  ```php
  $users = Cache::remember('active-users', 3600, fn() => User::active()->get());
  ```
- Clear relevant cache on model update/delete using Observer or Service
- Use cache tags for grouped invalidation when using Redis

## Commands & Scheduling
- Artisan commands for all recurring or admin tasks
- Commands live in `app/Console/Commands/`
- Schedule commands in `app/Console/Kernel.php`
- Never run heavy tasks in web requests — use commands + scheduler

## What NOT to Do
- Do not use `dd()` or `dump()` in production code — remove before committing
- Do not put logic in Blade views — views are for display only
- Do not use `$request->all()` — always use `$request->validated()` or specific fields
- Do not return sensitive fields (password, remember_token) in API responses
- Do not skip Form Request validation — never validate manually in controller
- Do not write migrations that could cause data loss without a backup plan
- Do not use `public $timestamps = false` unless there's a genuine reason
- Do not call external APIs synchronously in a web request — use Jobs