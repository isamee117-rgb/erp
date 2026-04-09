# LeanERP — Claude Project Instructions

---

## 0. MCP Tools: code-review-graph

**IMPORTANT: This project has a knowledge graph. ALWAYS use the
code-review-graph MCP tools BEFORE using Grep/Glob/Read to explore
the codebase.** The graph is faster, cheaper (fewer tokens), and gives
you structural context (callers, dependents, test coverage) that file
scanning cannot.

### When to use graph tools FIRST

- **Exploring code**: `semantic_search_nodes` or `query_graph` instead of Grep
- **Understanding impact**: `get_impact_radius` instead of manually tracing imports
- **Code review**: `detect_changes` + `get_review_context` instead of reading entire files
- **Finding relationships**: `query_graph` with callers_of/callees_of/imports_of/tests_for
- **Architecture questions**: `get_architecture_overview` + `list_communities`

Fall back to Grep/Glob/Read **only** when the graph doesn't cover what you need.

### Key Tools

| Tool | Use when |
|------|----------|
| `detect_changes` | Reviewing code changes — gives risk-scored analysis |
| `get_review_context` | Need source snippets for review — token-efficient |
| `get_impact_radius` | Understanding blast radius of a change |
| `get_affected_flows` | Finding which execution paths are impacted |
| `query_graph` | Tracing callers, callees, imports, tests, dependencies |
| `semantic_search_nodes` | Finding functions/classes by name or keyword |
| `get_architecture_overview` | Understanding high-level codebase structure |
| `refactor_tool` | Planning renames, finding dead code |

### Workflow

1. The graph auto-updates on file changes (via hooks in `.claude/settings.json`).
2. Use `detect_changes` for code review.
3. Use `get_affected_flows` to understand impact.
4. Use `query_graph` pattern="tests_for" to check coverage.

---

## 1. Project Overview

**Name:** LeanERP (Lean Enterprise Resource Planning)
**Type:** Multi-tenant SaaS ERP + POS System
**Local URL:** `http://localhost/erppos`
**Database:** MySQL — `lean_erp`
**Environment:** XAMPP (Apache + PHP 8.2+, Windows)

### Tech Stack
| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Database | MySQL via Eloquent ORM |
| Auth | API Token (Bearer) + Cookie (Web) |
| Frontend | Vanilla JavaScript (no framework) |
| UI | Tabler CSS (Bootstrap 5), ApexCharts, Tom Select |
| Templates | Laravel Blade |
| HTTP Client | Fetch API |

### User Roles
| Role | company_id | Access |
|------|-----------|--------|
| Super Admin | `null` | All companies, creates tenants |
| Company Admin | set | Full access within own company |
| Staff | set | Permission-controlled via `CustomRole` |

> **Critical:** Super Admin has `company_id = null`. Always check before scoping queries or calling `Company::find($user->company_id)`.

---

## 2. Architecture & Directory Structure

```
erppos/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/                         # Saare API Controllers (thin — logic Services mein)
│   │   │       ├── AuthController.php        # Login + syncCore/syncMaster/syncTransactions
│   │   │       ├── CompanyController.php     # Company CRUD
│   │   │       ├── ProductController.php     # Product + Stock adjustment + barcode
│   │   │       ├── PartyController.php       # Customer + Vendor
│   │   │       ├── SaleController.php        # Sales + Returns → SaleService
│   │   │       ├── PurchaseController.php    # PO + GRN + Returns → PurchaseService
│   │   │       ├── PaymentController.php     # Payments
│   │   │       ├── SettingsController.php    # Settings + Document Sequences
│   │   │       ├── UserController.php        # User management
│   │   │       ├── RoleController.php        # Custom roles + permissions
│   │   │       └── Concerns/
│   │   │           └── CamelCaseResponse.php # toCamel/toSnake utilities (transform* removed)
│   │   │
│   │   ├── Resources/                        # 23 API Resource classes (camelCase JSON output)
│   │   │   ├── CompanyResource.php
│   │   │   ├── UserResource.php
│   │   │   ├── ProductResource.php
│   │   │   ├── PartyResource.php
│   │   │   ├── SaleOrderResource.php
│   │   │   ├── SaleItemResource.php
│   │   │   ├── SaleReturnResource.php
│   │   │   ├── SaleReturnItemResource.php
│   │   │   ├── PurchaseOrderResource.php
│   │   │   ├── PurchaseItemResource.php
│   │   │   ├── PurchaseReceiveResource.php
│   │   │   ├── PurchaseReceiveItemResource.php
│   │   │   ├── PurchaseReturnResource.php
│   │   │   ├── PurchaseReturnItemResource.php
│   │   │   ├── PaymentResource.php
│   │   │   ├── InventoryLedgerResource.php
│   │   │   ├── InventoryCostLayerResource.php
│   │   │   ├── CustomRoleResource.php
│   │   │   ├── CategoryResource.php
│   │   │   ├── UnitOfMeasureResource.php
│   │   │   ├── EntityTypeResource.php
│   │   │   ├── BusinessCategoryResource.php
│   │   │   └── DocumentSequenceResource.php
│   │   │
│   │   ├── Requests/                         # 11 Form Request validation classes
│   │   │   ├── StoreCompanyRequest.php
│   │   │   ├── UpdateCompanyDetailsRequest.php
│   │   │   ├── StorePartyRequest.php
│   │   │   ├── UpdatePartyRequest.php
│   │   │   ├── StorePaymentRequest.php
│   │   │   ├── StoreUserRequest.php
│   │   │   ├── UpdateUserRequest.php
│   │   │   ├── UpdatePasswordRequest.php
│   │   │   ├── StoreSaleRequest.php
│   │   │   ├── StorePurchaseOrderRequest.php
│   │   │   └── ReceivePurchaseOrderRequest.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── ApiTokenAuth.php              # Bearer token → validates + sets auth_user
│   │   │   └── WebAuth.php                   # Cookie check for web pages
│   │   └── Controller.php
│   │
│   ├── Models/                               # 24 Eloquent models (string PKs, not int)
│   │   ├── Company.php                       # Relationships: users, products, parties, etc.
│   │   ├── User.php                          # Relationships: company, customRole
│   │   ├── CustomRole.php
│   │   ├── Product.php                       # Scopes: outOfStock, lowStock, inStock, forCompany
│   │   ├── Category.php
│   │   ├── UnitOfMeasure.php
│   │   ├── Party.php                         # Relationships: saleOrders, purchaseOrders, payments, returns
│   │   ├── SaleOrder.php                     # Scopes: pending, returned, partiallyReturned, forCompany
│   │   ├── SaleItem.php                      # Relationships: saleOrder, product
│   │   ├── SaleReturn.php                    # Relationships: items, originalSale, customer
│   │   ├── SaleReturnItem.php
│   │   ├── PurchaseOrder.php                 # Scopes: draft, pending, received, returned, forVendor
│   │   ├── PurchaseItem.php
│   │   ├── PurchaseReceive.php
│   │   ├── PurchaseReceiveItem.php
│   │   ├── PurchaseReturn.php                # Relationships: items, originalPurchase, vendor
│   │   ├── PurchaseReturnItem.php
│   │   ├── Payment.php                       # Relationships: company, party
│   │   ├── InventoryLedger.php
│   │   ├── InventoryCostLayer.php
│   │   ├── DocumentSequence.php
│   │   ├── Setting.php
│   │   ├── EntityType.php
│   │   └── BusinessCategory.php
│   │
│   └── Services/
│       ├── DocumentSequenceService.php       # Auto-numbering: PO, Invoice, SKU, customer_no etc.
│       ├── InventoryCostingService.php       # FIFO + Moving Average costing logic
│       ├── SaleService.php                   # createSale(), createReturn()
│       ├── PurchaseService.php               # createOrder(), receiveOrder(), createReturn()
│       └── SyncService.php                   # getCoreData(), getMasterData(), getTransactionData()
│
├── bootstrap/
│   └── app.php                               # withRouting(), middleware config
│
├── config/
│   └── (app, auth, cache, cors, database, filesystems, logging, mail, queue, services, session)
│
├── database/
│   ├── migrations/                           # 15 migration files (see timeline in section 6)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── ErpSeeder.php
│
├── routes/
│   ├── api.php                               # 50+ API routes (all under /api prefix)
│   └── web.php                               # Web page routes (Blade views)
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                 # Main layout (sidebar + topnav)
│       │   └── auth.blade.php                # Login layout
│       ├── auth/
│       │   └── login.blade.php
│       └── pages/                            # 19 Blade page templates (no inline JS)
│
├── public/
│   ├── index.php                             # Laravel entry point
│   ├── css/
│   │   └── app.css                           # Centralized CSS (inline styles replaced)
│   ├── js/
│   │   ├── app.js                            # window.ERP — global state, sync, auth, permissions
│   │   ├── api.js                            # window.ERP.api — all Fetch API wrappers
│   │   └── pages/                            # 19 per-page JS files (extracted from Blade)
│   │       ├── dashboard.js
│   │       ├── inventory.js
│   │       ├── sales.js
│   │       ├── purchases.js
│   │       ├── pos.js
│   │       ├── payments.js
│   │       ├── parties.js
│   │       ├── reports.js
│   │       ├── settings.js
│   │       ├── user-management.js
│   │       ├── company-management.js
│   │       ├── role-management.js
│   │       ├── sale-returns.js
│   │       ├── purchase-returns.js
│   │       ├── party-ledger.js
│   │       ├── inventory-ledger.js
│   │       ├── outstanding.js
│   │       ├── adjustments.js
│   │       └── company-profile.js
│   └── logos/                                # Uploaded company logos
│
├── tests/
│   ├── Feature/
│   │   ├── ApiTestCase.php                   # Base helper: createCompany, createAdminUser, auth()
│   │   ├── AuthTest.php
│   │   ├── UserTest.php
│   │   ├── PartyTest.php
│   │   ├── SaleTest.php
│   │   ├── PurchaseTest.php
│   │   └── PaymentTest.php
│   └── Unit/
│
├── .claude/
│   ├── CLAUDE.md                             # This file
│   ├── rules/code-style.md
│   ├── rules/frontend/vanilla-js.md
│   └── settings.json
│
├── .env                                      # Local config (gitignored)
├── phpunit.xml                               # Test config (DB_DATABASE=erppos_test)
├── PROJECT_OVERVIEW.md                       # Detailed project reference doc
├── lean_erp.sql                              # DB dump/backup
└── index.php                                 # Root redirect → public/
```

### Database Tables (24 total)

| Group | Tables |
|-------|--------|
| Company & Auth | `companies`, `users`, `custom_roles` |
| Master Data | `products`, `categories`, `units_of_measure`, `parties`, `entity_types`, `business_categories` |
| Sales | `sale_orders`, `sale_items`, `sale_returns`, `sale_return_items` |
| Purchases | `purchase_orders`, `purchase_items`, `purchase_receives`, `purchase_receive_items`, `purchase_returns`, `purchase_return_items` |
| Financial & Inventory | `payments`, `inventory_ledger`, `inventory_cost_layers` |
| Config | `settings`, `document_sequences` |

---

## 3. Coding Conventions & Style Rules

### PHP / Laravel
- **Controllers are thin** — all business logic lives in `app/Services/`
- **Always use Form Requests** for validation — never `$request->validate()` inline
- **Always use API Resources** for JSON responses — never manual arrays
- Get authenticated user via `$request->get('auth_user')` (set by `ApiTokenAuth` middleware)
- IDs are **string prefixed** (`'SO-'.Str::random(9)`), never auto-increment integers
- Multi-tenant: every query on tenant tables **must be scoped** by `company_id`
- `$fillable` must be explicit on every model — no `$guarded = []`
- Never store plain text passwords — `'password' => 'hashed'` cast handles hashing

### API Responses
- All JSON keys are **camelCase** (handled by Resource classes)
- Error format: `response()->json(['error' => 'message'], 4xx)`
- Services throw `\RuntimeException` for business rule violations; controllers catch them

### Naming
| Thing | Convention | Example |
|-------|-----------|---------|
| Controller | `{Model}Controller` | `SaleController` |
| Service | `{Domain}Service` | `SaleService` |
| Resource | `{Model}Resource` | `SaleOrderResource` |
| Form Request | `{Action}{Model}Request` | `StoreSaleRequest` |
| Model scope | `scope{Name}` | `scopeForCompany` |
| DB columns | `snake_case` | `company_id` |

---

## 4. Commands Claude Should Know

```bash
# Run with XAMPP PHP (not system PHP)
/c/xampp/php/php artisan serve
/c/xampp/php/php artisan migrate
/c/xampp/php/php artisan migrate:fresh --seed
/c/xampp/php/php artisan test
/c/xampp/php/php artisan route:list
/c/xampp/php/php artisan tinker

# Composer
/c/xampp/php/php /c/xampp/php/composer.phar install
/c/xampp/php/php /c/xampp/php/composer.phar require vendor/package

# MySQL (XAMPP)
/c/xampp/mysql/bin/mysql -u root -h 127.0.0.1 -P 3306 lean_erp
```

### Key .env values
```
APP_URL=http://localhost/erppos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=lean_erp
DB_USERNAME=root
DB_PASSWORD=          # blank (XAMPP default)
```

### Test DB
```
DB_DATABASE=erppos_test   # used by phpunit.xml when running php artisan test
```

---

## 5. What NOT to Do

- **Do NOT** store or expose plain text passwords — `password_plain` column was dropped via migration
- **Do NOT** use the `CamelCaseResponse` trait — it was removed; use API Resources instead
- **Do NOT** call `/api/sync` (legacy endpoint) — use the split endpoints:
  - `/api/sync/core` → companies, users, roles, settings (fast)
  - `/api/sync/master` → products, parties, categories (medium)
  - `/api/sync/transactions` → sales, purchases, payments, ledger (slow)
- **Do NOT** call `Company::find($user->company_id)` without checking Super Admin case (`company_id` is null)
- **Do NOT** put business logic in controllers — delegate to Services
- **Do NOT** use `$guarded = []` on models
- **Do NOT** add speculative features, extra error handling, or abstractions not asked for
- **Do NOT** use `bcrypt()` manually in tests — User model's `'password' => 'hashed'` cast does it automatically

---

## 6. Environment & Dependencies Context

### Runtime
- **OS:** Windows 11
- **Web Server:** Apache via XAMPP
- **PHP:** `/c/xampp/php/php` (8.2+)
- **MySQL:** `/c/xampp/mysql/bin/mysql` on `127.0.0.1:3306`
- **App path:** `C:\xampp\htdocs\erppos`

### Frontend CDN Dependencies
```
Tabler CSS + Icons  @1.0.0-beta20
ApexCharts          (dashboard + reports charts)
xlsx                (Excel export)
jsPDF               (PDF export)
Tom Select          (local: /dist/libs/tom-select/)
```

### No Build Tool
- No Webpack / Vite — all JS files are plain and loaded directly via `<script src="">` in Blade
- No npm build step needed

### Auth Flow
```
POST /api/login → Hash::check() → token returned
Token saved in localStorage['leanerp_token'] + cookie
All API requests → Authorization: Bearer <token>
ApiTokenAuth middleware → validates token → sets auth_user on request
```

---

## 7. Testing & Quality Standards

### Setup
- **Framework:** PHPUnit via `php artisan test`
- **Test DB:** `erppos_test` (MySQL, same server) — configured in `phpunit.xml`
- **Base class:** `Tests\TestCase` uses `RefreshDatabase` trait (wipes + migrates before each test)
- **Helper base:** `Tests\Feature\ApiTestCase` — provides `createCompany()`, `createAdminUser()`, `loginAndGetToken()`, `auth()`, `createProduct()`, `createParty()`

### Test Files
| File | What it tests |
|------|--------------|
| `tests/Feature/AuthTest.php` | Login success/fail, inactive user, suspended company, sync endpoints |
| `tests/Feature/UserTest.php` | Create user, password hashing, update, deactivate, duplicate username |
| `tests/Feature/PartyTest.php` | Create customer/vendor, invalid type, update, delete |
| `tests/Feature/SaleTest.php` | Cash sale, stock reduction, ledger entry, credit sale, return, stock restore |
| `tests/Feature/PurchaseTest.php` | Create PO, receive (stock increase), ledger, purchase return |
| `tests/Feature/PaymentTest.php` | Receipt/payment, balance changes, delete reversal, validation |

### Standards
- Test method names are descriptive: `sale_reduces_product_stock`
- Each test is independent — no test depends on another
- Use `assertDatabaseHas()` / `assertDatabaseMissing()` to verify DB state
- Use PHPUnit 11 attributes (`#[Test]`) — `/** @test */` doc-comments are deprecated
