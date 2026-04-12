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
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\AccountMappingController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Middleware\ApiTokenAuth;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(ApiTokenAuth::class)->group(function () {
    Route::get('/sync', [AuthController::class, 'sync']);
    Route::get('/sync/core',         [AuthController::class, 'syncCore']);
    Route::get('/sync/master',       [AuthController::class, 'syncMaster']);
    Route::get('/sync/transactions', [AuthController::class, 'syncTransactions']);

    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/companies/{id}/status', [CompanyController::class, 'updateStatus']);
    Route::put('/companies/{id}/limit', [CompanyController::class, 'updateLimit']);
    Route::put('/companies/{id}/admin-password', [CompanyController::class, 'updateAdminPassword']);
    Route::put('/companies/{id}/details', [CompanyController::class, 'updateDetails']);
    Route::put('/company-info', [CompanyController::class, 'updateInfo']);
    Route::post('/company-logo', [CompanyController::class, 'uploadLogo']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/products/barcode', [ProductController::class, 'findByBarcode']);
    Route::post('/products/adjust-stock', [ProductController::class, 'adjustStock']);

    // UOM conversions per product
    Route::get('/products/{id}/uom-conversions', [ProductController::class, 'listUomConversions']);
    Route::post('/products/{id}/uom-conversions', [ProductController::class, 'storeUomConversion']);
    Route::put('/products/{id}/uom-conversions/{cid}', [ProductController::class, 'updateUomConversion']);
    Route::delete('/products/{id}/uom-conversions/{cid}', [ProductController::class, 'destroyUomConversion']);

    // Price tiers per product
    Route::post('/products/{id}/price-tiers', [ProductController::class, 'storePriceTier']);
    Route::put('/products/{id}/price-tiers/{tid}', [ProductController::class, 'updatePriceTier']);
    Route::delete('/products/{id}/price-tiers/{tid}', [ProductController::class, 'destroyPriceTier']);

    Route::post('/parties', [PartyController::class, 'store']);
    Route::put('/parties/{id}', [PartyController::class, 'update']);
    Route::delete('/parties/{id}', [PartyController::class, 'destroy']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/return', [SaleController::class, 'createReturn']);

    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::put('/purchases/{id}/receive', [PurchaseController::class, 'receive']);
    Route::post('/purchases/{id}/partial-receive', [PurchaseController::class, 'receive']);
    Route::post('/purchases/return', [PurchaseController::class, 'createReturn']);

    Route::post('/payments', [PaymentController::class, 'store']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);

    Route::put('/settings/currency', [SettingsController::class, 'updateCurrency']);
    Route::put('/settings/invoice-format', [SettingsController::class, 'updateInvoiceFormat']);
    Route::put('/settings/costing-method', [SettingsController::class, 'updateCostingMethod']);
    Route::get('/settings/document-sequences', [SettingsController::class, 'getDocumentSequences']);
    Route::put('/settings/document-sequences', [SettingsController::class, 'updateDocumentSequence']);
    Route::post('/categories', [SettingsController::class, 'createCategory']);
    Route::delete('/categories/{id}', [SettingsController::class, 'deleteCategory']);
    Route::post('/uoms', [SettingsController::class, 'createUOM']);
    Route::delete('/uoms/{id}', [SettingsController::class, 'deleteUOM']);
    Route::post('/entity-types', [SettingsController::class, 'createEntityType']);
    Route::delete('/entity-types/{id}', [SettingsController::class, 'deleteEntityType']);
    Route::post('/business-categories', [SettingsController::class, 'createBusinessCategory']);
    Route::delete('/business-categories/{id}', [SettingsController::class, 'deleteBusinessCategory']);

    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/status', [UserController::class, 'setStatus']);
    Route::put('/users/{id}/password', [UserController::class, 'updatePassword']);

    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Accounting — Chart of Accounts
    Route::get('/accounting/coa', [ChartOfAccountController::class, 'index']);
    Route::post('/accounting/coa', [ChartOfAccountController::class, 'store']);
    Route::put('/accounting/coa/{id}', [ChartOfAccountController::class, 'update']);
    Route::delete('/accounting/coa/{id}', [ChartOfAccountController::class, 'destroy']);

    // Accounting — Account Mappings
    Route::get('/accounting/mappings', [AccountMappingController::class, 'index']);
    Route::put('/accounting/mappings', [AccountMappingController::class, 'update']);

    // Accounting — Journal Entries
    Route::get('/accounting/journals', [JournalEntryController::class, 'index']);
    Route::post('/accounting/journals', [JournalEntryController::class, 'store']);
    Route::get('/accounting/journals/{id}', [JournalEntryController::class, 'show']);
    Route::post('/accounting/journals/{id}/post', [JournalEntryController::class, 'post']);
    Route::delete('/accounting/journals/{id}', [JournalEntryController::class, 'destroy']);

    // Reports
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet']);
});
