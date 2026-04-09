# LeanERP - Project Overview & Structure Documentation

> **Maqsad:** Yeh file code structure theek karne se pehle poore project ko samajhne ke liye hai.  
> **Last Updated:** 2026-03-31 (evening)

---

## 1. PROJECT KYA HAI?

**Naam:** LeanERP (Lean Enterprise Resource Planning)  
**Type:** Multi-tenant SaaS ERP + POS System  
**Database:** MySQL (`lean_erp`)  
**Local URL:** `http://localhost/erppos`  
**Environment:** XAMPP (Apache + PHP 8.2+)

---

## 2. TECH STACK

| Layer | Technology |
|-------|-----------|
| Backend Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | MySQL (Eloquent ORM) |
| Auth | API Token (Bearer) + Cookie (Web) |
| Frontend | Vanilla JavaScript |
| UI Framework | Tabler CSS (Bootstrap 5 based) |
| Charts | ApexCharts |
| Select Dropdown | Tom Select |
| Templates | Laravel Blade |
| HTTP Client (JS) | Fetch API |

---

## 3. PURI DIRECTORY STRUCTURE

```
erppos/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/                         # Saare API Controllers
│   │   │       ├── AuthController.php        # Login + Sync (SyncService use karta hai)
│   │   │       ├── CompanyController.php     # Company CRUD
│   │   │       ├── ProductController.php     # Product + Stock
│   │   │       ├── PartyController.php       # Customer + Vendor
│   │   │       ├── SaleController.php        # Sales + Returns (SaleService use karta hai)
│   │   │       ├── PurchaseController.php    # Purchase + GRN + Returns (PurchaseService)
│   │   │       ├── PaymentController.php     # Payments
│   │   │       ├── SettingsController.php    # Settings + Sequences
│   │   │       ├── UserController.php        # Users
│   │   │       ├── RoleController.php        # Roles + Permissions
│   │   │       └── Concerns/
│   │   │           └── CamelCaseResponse.php # toCamel/toSnake utilities (transform* methods removed)
│   │   │
│   │   ├── Resources/                       # API Resource classes (NEW)
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
│   │   ├── Requests/                        # Form Request validation classes
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
│   │   │   ├── ApiTokenAuth.php              # Bearer token check
│   │   │   └── WebAuth.php                  # Cookie-based check
│   │   └── Controller.php                   # Base Controller
│   │
│   ├── Models/                              # 24 Eloquent Models
│   │   ├── Company.php                      # Relationships: users, products, parties, etc.
│   │   ├── User.php                         # Relationships: company, customRole
│   │   ├── CustomRole.php
│   │   ├── Product.php                      # Scopes: outOfStock, lowStock, inStock, forCompany
│   │   ├── Category.php
│   │   ├── UnitOfMeasure.php
│   │   ├── Party.php                        # Relationships: saleOrders, purchaseOrders, payments, returns
│   │   ├── SaleOrder.php                    # Relationships: items, customer | Scopes: pending, returned, partiallyReturned
│   │   ├── SaleItem.php                     # Relationships: saleOrder, product
│   │   ├── SaleReturn.php                   # Relationships: items, originalSale, customer
│   │   ├── SaleReturnItem.php
│   │   ├── PurchaseOrder.php                # Scopes: draft, pending, received, returned, forVendor
│   │   ├── PurchaseItem.php
│   │   ├── PurchaseReceive.php
│   │   ├── PurchaseReceiveItem.php
│   │   ├── PurchaseReturn.php               # Relationships: items, originalPurchase, vendor
│   │   ├── PurchaseReturnItem.php
│   │   ├── Payment.php                      # Relationships: company, party
│   │   ├── InventoryLedger.php
│   │   ├── InventoryCostLayer.php
│   │   ├── DocumentSequence.php
│   │   ├── Setting.php
│   │   ├── EntityType.php
│   │   └── BusinessCategory.php
│   │
│   └── Services/
│       ├── DocumentSequenceService.php      # Auto-numbering (PO, Invoice, etc.)
│       ├── InventoryCostingService.php      # FIFO + Moving Average logic
│       ├── SaleService.php                  # Sale create + return logic (NEW)
│       ├── PurchaseService.php              # PO create + receive + return logic (NEW)
│       └── SyncService.php                  # AuthController sync() ki 23 queries (NEW)
│
├── bootstrap/
│   └── app.php
│
├── config/
│   └── (app, auth, cache, cors, database, filesystems, logging, mail, queue, services, session)
│
├── database/
│   ├── migrations/                          # 15 migration files
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── ErpSeeder.php
│
├── routes/
│   ├── api.php                              # All API routes (~50+)
│   └── web.php                              # Web routes (page rendering)
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                # Main layout (sidebar + nav)
│       │   └── auth.blade.php               # Login layout
│       ├── auth/
│       │   └── login.blade.php
│       └── pages/                           # 19 page templates (JS inline nahi, alag files mein)
│
├── public/
│   ├── index.php                            # Laravel entry point
│   ├── css/
│   │   └── app.css                          # Centralized CSS classes (inline styles replace)
│   ├── js/
│   │   ├── api.js                           # API client wrapper
│   │   ├── app.js                           # Global state + init
│   │   └── pages/                           # Per-page JS files (NEW - extracted from Blade)
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
│   └── logos/                               # Company logos
│
├── .env
├── artisan
├── composer.json
├── lean_erp.sql
└── index.php                                # Root redirect to public/
```

---

## 4. DATABASE TABLES (24 Tables)

### Company & Users
| Table | Description |
|-------|-------------|
| `companies` | Multi-tenant companies |
| `users` | System users (linked to company) |
| `custom_roles` | Role definitions with JSON permissions |

### Master Data
| Table | Description |
|-------|-------------|
| `products` | Product master (SKU, barcode, item_no, stock, cost, price) |
| `categories` | Product categories |
| `units_of_measure` | UOMs (kg, ltr, pcs, etc.) |
| `parties` | Customers & Vendors (code, type, balance, credit_limit) |
| `entity_types` | Party entity type lookup |
| `business_categories` | Party business category |

### Sales
| Table | Description |
|-------|-------------|
| `sale_orders` | Sales invoices |
| `sale_items` | Line items (qty, price, discount, COGS) |
| `sale_returns` | Credit memos |
| `sale_return_items` | Return line items |

### Purchases
| Table | Description |
|-------|-------------|
| `purchase_orders` | Purchase orders |
| `purchase_items` | Line items (qty, unit_cost, received_qty) |
| `purchase_receives` | GRN - Goods Received Notes |
| `purchase_receive_items` | GRN line items |
| `purchase_returns` | Debit memos |
| `purchase_return_items` | Return line items |

### Financial & Inventory
| Table | Description |
|-------|-------------|
| `payments` | Payment records (party_id nullable) |
| `inventory_ledger` | Stock movement history |
| `inventory_cost_layers` | FIFO / Moving Avg cost layers |

### Configuration
| Table | Description |
|-------|-------------|
| `settings` | Key-value settings (currency, invoice_format, costing_method) |
| `document_sequences` | Auto-numbering (PO, Invoice, Return, SKU, etc.) |

---

## 5. MIGRATIONS TIMELINE

```
2025-01-01  → create_erp_tables.php
2025-01-01  → add_api_token_to_users.php
2025-01-02  → add_costing_and_partial_receiving.php
2025-01-03  → create_document_sequences.php
2025-01-04  → add_item_number_to_products.php
2026-02-13  → add_admin_password_to_companies_table.php
2026-02-13  → add_password_plain_to_users_table.php
2026-02-14  → add_barcode_to_products_table.php
2026-02-14  → add_unique_barcode_per_company.php
2026-02-14  → add_returned_quantity_to_items.php
2026-03-17  → add_display_numbers_to_order_tables.php  (invoice_no, po_no, return_no)
2026-03-17  → add_name_to_users_table.php
2026-03-18  → change_info_logo_url_to_text.php
2026-03-18  → make_payments_party_id_nullable.php
2026-03-31  → clear_plain_text_passwords.php           (NULL out existing plain passwords - ran)
2026-03-31  → drop_plain_password_columns.php          (DROP password_plain + admin_password_plain - ran)
```

---

## 6. API ROUTES (routes/api.php)

### Public Routes
```
POST   /api/login
```

### Protected Routes (Bearer Token Required)
```
GET    /api/sync

# Companies
POST   /api/companies
PUT    /api/companies/{id}/status
PUT    /api/companies/{id}/limit
PUT    /api/companies/{id}/admin-password
PUT    /api/companies/{id}/details
PUT    /api/company-info
POST   /api/company-logo

# Products
POST   /api/products
PUT    /api/products/{id}
DELETE /api/products/{id}
GET    /api/products/barcode
POST   /api/products/adjust-stock

# Parties (Customer + Vendor)
POST   /api/parties
PUT    /api/parties/{id}
DELETE /api/parties/{id}

# Sales
POST   /api/sales
POST   /api/sales/return

# Purchases
POST   /api/purchases
PUT    /api/purchases/{id}/receive
POST   /api/purchases/return

# Payments
POST   /api/payments
DELETE /api/payments/{id}

# Settings
PUT    /api/settings/currency
PUT    /api/settings/invoice-format
PUT    /api/settings/costing-method
GET    /api/settings/document-sequences
PUT    /api/settings/document-sequences
POST   /api/categories
DELETE /api/categories/{id}
POST   /api/uoms
DELETE /api/uoms/{id}
POST   /api/entity-types
DELETE /api/entity-types/{id}
POST   /api/business-categories
DELETE /api/business-categories/{id}

# Users
POST   /api/users
PUT    /api/users/{id}
PUT    /api/users/{id}/status
PUT    /api/users/{id}/password

# Roles
POST   /api/roles
PUT    /api/roles/{id}
DELETE /api/roles/{id}
```

---

## 7. WEB ROUTES (routes/web.php)

```
GET  /              → dashboard
GET  /login         → login page
POST /login         → authenticate
GET  /logout        → logout
GET  /inventory, /parties, /sales, /purchases, /pos, /payments ... (isi pattern mein)
```

---

## 8. FRONTEND STATE (public/js/app.js)

```javascript
window.ERP = {
  state: {
    companies, customRoles, users, currentUser,
    products, categories, uoms, entityTypes, businessCategories,
    parties, purchaseOrders, sales, salesReturns, purchaseReturns,
    payments, ledger, costLayers,
    currency, invoiceFormat, costingMethod, documentSequences
  },
  init()
  sync()
  hasPermission(module, action)
  logout()
  formatCurrency(amount)
}
```

---

## 9. AUTHENTICATION FLOW

```
1. User → POST /api/login (username + password)
2. Server → Hash::check() verify → Returns API token
3. Token stored in → localStorage['leanerp_token']
4. All API calls → Authorization: Bearer <token>
5. Middleware ApiTokenAuth → token validate + user active + company active check
6. Web pages → WebAuth middleware → Cookie check → Redirect to /login
```

---

## 10. USER ROLES & PERMISSIONS

### System Roles
- **Super Admin** — Saari companies dekh sakta hai, companies create kar sakta hai
- **Company Admin** — Sirf apni company ka data

### Custom Roles
- Per-company define hote hain
- JSON format mein permissions: `{"inventory": {"view": true, "create": true}, ...}`

---

## 11. BUSINESS LOGIC (Services)

### DocumentSequenceService.php
- Auto-increment numbers: `po_number`, `sale_invoice`, `customer_no`, `vendor_no`, `item_no`, `sku`
- Thread-safe locking mechanism, customizable prefix

### InventoryCostingService.php
- **FIFO:** Oldest cost layer pehle consume hota hai
- **Moving Average:** Weighted average cost update hoti hai
- Methods: `consumeFIFO()`, `consumeMovingAverage()`, `addCostLayer()`, `restoreFIFOLayers()`

### SaleService.php *(NEW)*
- `createSale(User, array)` — items loop, COGS calculate, stock deduct, ledger entry, auto payment record
- `createReturn(User, array)` — return validation, stock restore, FIFO restore, return status update
- Private helpers: `recordSalePayment()`, `buildReturnItems()`, `updateSaleReturnStatus()`

### PurchaseService.php *(NEW)*
- `createOrder(User, array)` — PO create with items
- `receiveOrder(User, id, items, notes)` — GRN, stock update, moving avg cost, cost layer, ledger, vendor balance
- `createReturn(User, array)` — stock deduct, ledger, vendor balance reduce
- Private helpers: `buildDefaultReceiveItems()`, `resolvePoItem()`, `buildReturnItems()`, `updatePurchaseReturnStatus()`

### SyncService.php *(NEW)*
- `getData(User)` — Super Admin vs tenant scope, 23+ model queries organize karke return karta hai
- Private: `fetchTenantData()`, `scopedQuery()`

---

## 12. ENVIRONMENT CONFIG (.env)

```
APP_NAME=LeanERP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/erppos

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=lean_erp
DB_USERNAME=root
DB_PASSWORD=         (blank - XAMPP default)
```

---

## 13. PROJECT STATISTICS

| Item | Count |
|------|-------|
| PHP Models | 24 |
| API Controllers | 11 |
| Service Classes | 5 |
| Form Request Classes | 11 |
| API Resource Classes | 23 |
| Migrations | 15 |
| Database Tables | 24 |
| API Endpoints | 50+ |
| Blade Views | 23 |
| Per-page JS Files | 19 |
| Config Files | 10 |

---

## 14. CHANGES MADE (2026-03-31)

### Security Fix — Plain Text Passwords
- `users.password_plain` aur `companies.admin_password_plain` columns ab save nahi hote
- `CamelCaseResponse` se `passwordPlain` aur `adminPasswordPlain` frontend ko nahi bheji jaati
- `User.$fillable` se `password_plain` remove, `Company.$fillable` se `admin_password_plain` remove
- `user-management.js` — edit form mein password field blank rehta hai (pre-fill band)
- `company-management.js` — admin password field `••••••` dikhata hai
- Migration `clear_plain_text_passwords` run hua — existing plain text data NULL kar diya

### JS Extraction — Inline to Separate Files
- Saare blade files ka `@push('scripts')` inline JS nikal ke `public/js/pages/*.js` mein dala
- 19 per-page JS files create hue
- Cache-busting: `?v={{ filemtime(...) }}` har JS asset par

### CSS Centralization
- Inline `style=""` attributes replace kiye `public/css/app.css` classes se
- 10+ blade files — hundreds of inline styles removed
- Dynamic color variables (`style="color:'+varName+'"`) correctly inline rakhe

### Model Relationships Added
- `Party` — saleOrders, purchaseOrders, payments, saleReturns, purchaseReturns, company
- `Payment` — company, party
- `SaleItem` — saleOrder, product
- `SaleReturn` — originalSale, customer
- `PurchaseReturn` — originalPurchase, vendor

### API Resource Classes Added (23 classes)
- `app/Http/Resources/` folder create hua
- Har model ke liye dedicated Resource class — `CompanyResource`, `UserResource`, `ProductResource`, `PartyResource`, `PaymentResource`, `CustomRoleResource`, `CategoryResource`, `UnitOfMeasureResource`, `EntityTypeResource`, `BusinessCategoryResource`, `InventoryLedgerResource`, `InventoryCostLayerResource`, `DocumentSequenceResource`
- Nested resources properly linked via `whenLoaded()`: `SaleOrderResource` → `SaleItemResource`, `PurchaseOrderResource` → `PurchaseItemResource` + `PurchaseReceiveResource`, returns → item resources
- `CamelCaseResponse` trait ke saare `transform*()` methods replaced — controllers mein `use CamelCaseResponse` aur `transformX()` calls completely removed
- `SyncService` ab Resource collections return karta hai — manual array building khatam

### Model Scopes Added
- `Product` — `outOfStock()`, `lowStock()`, `inStock()`, `forCompany()`
- `SaleOrder` — `pending()`, `returned()`, `partiallyReturned()`, `forCompany()`, `forCustomer()`
- `PurchaseOrder` — `draft()`, `pending()`, `received()`, `partiallyReceived()`, `returned()`, `forCompany()`, `forVendor()`

### Service Layer Extracted (Fat Controllers → Services)
- `SaleController` — 287 lines → 30 lines (SaleService mein gaya)
- `PurchaseController` — 372 lines → 55 lines (PurchaseService mein gaya)
- `AuthController` sync() — 90 lines → SyncService mein gaya

### Form Request Validation (11 Classes)
- `app/Http/Requests/` folder create hua
- StoreCompanyRequest, UpdateCompanyDetailsRequest, StorePartyRequest, UpdatePartyRequest
- StorePaymentRequest, StoreUserRequest, UpdateUserRequest, UpdatePasswordRequest
- StoreSaleRequest, StorePurchaseOrderRequest, ReceivePurchaseOrderRequest
- Inline/missing validation → proper `rules()` mein
- `StoreUserRequest` mein `unique:users,username` — manual duplicate check hat gaya

---

## 15. KNOWN ISSUES / REMAINING

- [ ] `index.php` root mein aur `public/index.php` dono exist karte hain
- [ ] No frontend build tool (webpack/vite) — sab CDN se
- [x] `password_plain` aur `admin_password_plain` columns DB se DROP ho gayi — migration `2026_03_31_000002_drop_plain_password_columns` run hua ✓

---

## 16. CDN DEPENDENCIES (Frontend)

```html
<!-- CSS -->
@tabler/core@1.0.0-beta20/dist/css/tabler.min.css
@tabler/core@1.0.0-beta20/dist/css/tabler-icons.min.css
Google Fonts - Inter

<!-- JS -->
@tabler/core@1.0.0-beta20/dist/js/tabler.min.js
apexcharts (charts - dashboard, reports)
xlsx (Excel export - reports, settings)
jspdf + jspdf-autotable (PDF export - reports)
Tom Select (local: /dist/libs/tom-select/)
```

---

*Yeh file code changes se pehle reference ke liye hai. Structure mein koi bhi change karne se pehle is file ko update karo.*
