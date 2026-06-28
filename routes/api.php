<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\FieldSettingController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\AccountMappingController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportBuilderController;
use App\Http\Controllers\Api\ReportDataController;
use App\Http\Controllers\Api\JobCardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Middleware\ApiTokenAuth;

// Login: 5 attempts/minute per IP (unchanged)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(ApiTokenAuth::class)->group(function () {

    // ── Heavy sync: 10 req/min per user ──────────────────────────────────────
    Route::middleware('throttle:sync-heavy')->group(function () {
        Route::get('/sync',              [AuthController::class, 'sync']);
        Route::get('/sync/transactions', [AuthController::class, 'syncTransactions']);
    });

    // ── Light sync: 30 req/min per user ──────────────────────────────────────
    Route::middleware('throttle:sync-light')->group(function () {
        Route::get('/sync/core',   [AuthController::class, 'syncCore']);
        Route::get('/sync/master', [AuthController::class, 'syncMaster']);
    });

    // ── Mutations: 60 req/min per user ───────────────────────────────────────
    Route::middleware('throttle:api-mutations')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/companies',                    [CompanyController::class, 'store']);
        Route::put('/companies/{id}/status',         [CompanyController::class, 'updateStatus']);
        Route::put('/companies/{id}/limit',          [CompanyController::class, 'updateLimit']);
        Route::put('/companies/{id}/admin-password', [CompanyController::class, 'updateAdminPassword']);
        Route::put('/companies/{id}/details',        [CompanyController::class, 'updateDetails']);
        Route::put('/company-info',                  [CompanyController::class, 'updateInfo']);
        Route::post('/company-logo',                 [CompanyController::class, 'uploadLogo']);

        Route::post('/products',                              [ProductController::class, 'store']);
        Route::put('/products/{id}',                          [ProductController::class, 'update']);
        Route::delete('/products/{id}',                       [ProductController::class, 'destroy']);
        Route::post('/products/adjust-stock',                 [ProductController::class, 'adjustStock']);
        Route::post('/products/{id}/uom-conversions',         [ProductController::class, 'storeUomConversion']);
        Route::put('/products/{id}/uom-conversions/{cid}',    [ProductController::class, 'updateUomConversion']);
        Route::delete('/products/{id}/uom-conversions/{cid}', [ProductController::class, 'destroyUomConversion']);
        Route::post('/products/{id}/price-tiers',             [ProductController::class, 'storePriceTier']);
        Route::put('/products/{id}/price-tiers/{tid}',        [ProductController::class, 'updatePriceTier']);
        Route::delete('/products/{id}/price-tiers/{tid}',     [ProductController::class, 'destroyPriceTier']);

        Route::post('/parties',        [PartyController::class, 'store']);
        Route::put('/parties/{id}',    [PartyController::class, 'update']);
        Route::delete('/parties/{id}', [PartyController::class, 'destroy']);

        Route::post('/sales',        [SaleController::class, 'store']);
        Route::post('/sales/return', [SaleController::class, 'createReturn']);

        Route::post('/purchases',                      [PurchaseController::class, 'store']);
        Route::put('/purchases/{id}/receive',          [PurchaseController::class, 'receive']);
        Route::post('/purchases/{id}/partial-receive', [PurchaseController::class, 'receive']);
        Route::post('/purchases/return',               [PurchaseController::class, 'createReturn']);

        Route::post('/payments',        [PaymentController::class, 'store']);
        Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);

        Route::post('/job-cards',                       [JobCardController::class, 'store']);
        Route::put('/job-cards/{id}',                   [JobCardController::class, 'update']);
        Route::post('/job-cards/{id}/items',            [JobCardController::class, 'addItem']);
        Route::put('/job-cards/{id}/items/{itemId}',    [JobCardController::class, 'updateItem']);
        Route::delete('/job-cards/{id}/items/{itemId}', [JobCardController::class, 'removeItem']);
        Route::post('/job-cards/{id}/finalize',         [JobCardController::class, 'finalize']);
        Route::delete('/job-cards/{id}',                [JobCardController::class, 'destroy']);

        Route::put('/settings/job-card-mode',      [SettingsController::class, 'updateJobCardMode']);
        Route::put('/settings/currency',           [SettingsController::class, 'updateCurrency']);
        Route::put('/settings/invoice-format',     [SettingsController::class, 'updateInvoiceFormat']);
        Route::put('/settings/costing-method',     [SettingsController::class, 'updateCostingMethod']);
        Route::put('/settings/document-sequences', [SettingsController::class, 'updateDocumentSequence']);
        Route::post('/categories',                 [SettingsController::class, 'createCategory']);
        Route::delete('/categories/{id}',          [SettingsController::class, 'deleteCategory']);
        Route::post('/uoms',                       [SettingsController::class, 'createUOM']);
        Route::delete('/uoms/{id}',                [SettingsController::class, 'deleteUOM']);
        Route::post('/entity-types',               [SettingsController::class, 'createEntityType']);
        Route::delete('/entity-types/{id}',        [SettingsController::class, 'deleteEntityType']);
        Route::post('/business-categories',        [SettingsController::class, 'createBusinessCategory']);
        Route::delete('/business-categories/{id}', [SettingsController::class, 'deleteBusinessCategory']);

        Route::post('/users',              [UserController::class, 'store']);
        Route::put('/users/{id}',          [UserController::class, 'update']);
        Route::put('/users/{id}/status',   [UserController::class, 'setStatus']);
        Route::put('/users/{id}/password', [UserController::class, 'updatePassword']);

        Route::post('/roles',        [RoleController::class, 'store']);
        Route::put('/roles/{id}',    [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

        Route::put('/field-settings/{fieldKey}', [FieldSettingController::class, 'update']);

        Route::post('/accounting/coa',                [ChartOfAccountController::class, 'store']);
        Route::put('/accounting/coa/{id}',            [ChartOfAccountController::class, 'update']);
        Route::delete('/accounting/coa/{id}',         [ChartOfAccountController::class, 'destroy']);
        Route::put('/accounting/mappings',            [AccountMappingController::class, 'update']);
        Route::post('/accounting/journals',           [JournalEntryController::class, 'store']);
        Route::post('/accounting/journals/{id}/post', [JournalEntryController::class, 'post']);
        Route::delete('/accounting/journals/{id}',    [JournalEntryController::class, 'destroy']);
    });

    // ── Reads: 120 req/min per user ───────────────────────────────────────────
    Route::middleware('throttle:api-reads')->group(function () {
        Route::get('/sales',                [SaleController::class,    'index']);
        Route::get('/sales/returnable',     [SaleController::class,    'returnable']);
        Route::get('/sale-returns',         [SaleController::class,    'indexReturns']);
        Route::get('/purchases',            [PurchaseController::class, 'index']);
        Route::get('/purchases/returnable', [PurchaseController::class, 'returnable']);
        Route::get('/purchase-returns',     [PurchaseController::class, 'indexReturns']);
        Route::get('/payments',          [PaymentController::class,  'index']);
        Route::get('/inventory-ledger',       [ProductController::class,  'getLedger']);
        Route::get('/dashboard',                [DashboardController::class, 'index']);
        Route::get('/outstanding',              [PartyController::class, 'outstanding']);
        Route::get('/parties/{id}/references',  [PartyController::class, 'references']);
        Route::get('/parties/{id}/ledger',      [PartyController::class, 'ledger']);

        Route::get('/products/barcode',              [ProductController::class, 'findByBarcode']);
        Route::get('/products/{id}/uom-conversions', [ProductController::class, 'listUomConversions']);

        Route::get('/job-cards',         [JobCardController::class, 'index']);
        Route::get('/job-cards/history', [JobCardController::class, 'history']);
        Route::get('/job-cards/{id}',    [JobCardController::class, 'show']);

        Route::get('/settings/document-sequences', [SettingsController::class, 'getDocumentSequences']);
        Route::get('/field-settings',              [FieldSettingController::class, 'index']);

        Route::get('/accounting/coa',           [ChartOfAccountController::class, 'index']);
        Route::get('/accounting/mappings',      [AccountMappingController::class, 'index']);
        Route::get('/accounting/journals',      [JournalEntryController::class, 'index']);
        Route::get('/accounting/journals/{id}', [JournalEntryController::class, 'show']);

        Route::get('/reports/profit-loss',   [ReportController::class, 'profitLoss']);
        Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet']);

        Route::get('/report-builder/{type}', [ReportBuilderController::class, 'index']);
        Route::put('/report-builder/{type}', [ReportBuilderController::class, 'update']);

        Route::get('/reports/detailed-sales',    [ReportDataController::class, 'detailedSales']);
        Route::get('/reports/detailed-purchase', [ReportDataController::class, 'detailedPurchase']);
        Route::get('/reports/sales-returns',      [ReportDataController::class, 'salesReturns']);
        Route::get('/reports/purchase-returns',   [ReportDataController::class, 'purchaseReturns']);
        Route::get('/reports/sales-by-customer',  [ReportDataController::class, 'salesByCustomer']);
        Route::get('/reports/purchase-by-vendor', [ReportDataController::class, 'purchaseByVendor']);
    });
});
