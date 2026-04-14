<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\WebAuth;

// Public routes
Route::get('/', function () {
    return redirect(url('/login'));
});

Route::get('/login', function () {
    return view('auth.login');
});

// Protected routes — require leanerp_token cookie
Route::middleware([WebAuth::class])->group(function () {

    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    });

    Route::get('/inventory', function () {
        return view('pages.inventory');
    });

    Route::get('/customers', function () {
        return view('pages.parties', ['type' => 'Customer']);
    });

    Route::get('/vendors', function () {
        return view('pages.parties', ['type' => 'Vendor']);
    });

    Route::get('/pos', function () {
        return view('pages.pos');
    });

    Route::get('/job-card', function () {
        return view('pages.job-card');
    });

    Route::get('/sales', function () {
        return view('pages.sales');
    });

    Route::get('/purchases', function () {
        return view('pages.purchases');
    });

    Route::get('/sales-returns', function () {
        return view('pages.sale-returns');
    });

    Route::get('/purchase-returns', function () {
        return view('pages.purchase-returns');
    });

    Route::get('/inventory-ledger', function () {
        return view('pages.inventory-ledger');
    });

    Route::get('/payments', function () {
        return view('pages.payments');
    });

    Route::get('/ledger', function () {
        return view('pages.party-ledger');
    });

    Route::get('/outstanding', function () {
        return view('pages.outstanding');
    });

    Route::get('/company', function () {
        return view('pages.company-profile');
    });

    Route::get('/reports', function () {
        return view('pages.reports');
    });

    Route::get('/settings', function () {
        return view('pages.settings');
    });

    Route::get('/admin/users', function () {
        return view('pages.user-management');
    });

    Route::get('/admin/roles', function () {
        return view('pages.role-management');
    });

    Route::get('/admin/companies', function () {
        return view('pages.company-management');
    });

    Route::get('/adjustments', function () {
        return view('pages.adjustments');
    });

    // Accounting
    Route::get('/accounting/coa', function () {
        return view('pages.coa');
    });
    Route::get('/accounting/mappings', function () {
        return view('pages.account-mappings');
    });
    Route::get('/accounting/journals', function () {
        return view('pages.journal-entries');
    });
    Route::get('/accounting/journals/create', function () {
        return view('pages.journal-entry-create');
    });
    Route::get('/accounting/profit-loss', function () {
        return view('pages.profit-loss');
    });
    Route::get('/accounting/balance-sheet', function () {
        return view('pages.balance-sheet');
    });

});
