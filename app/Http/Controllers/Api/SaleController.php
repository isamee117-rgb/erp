<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleOrderResource;
use App\Http\Resources\SaleReturnResource;
use App\Services\JournalPostingService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected JournalPostingService $journalService,
    ) {}

    public function store(StoreSaleRequest $request)
    {
        $user = $request->get('auth_user');
        $sale = $this->saleService->createSale($user, $request->validated());

        try {
            $this->journalService->postSaleInvoice($sale);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for sale', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
        }

        return new SaleOrderResource($sale);
    }

    public function createReturn(Request $request)
    {
        $user = $request->get('auth_user');

        try {
            $saleReturn = $this->saleService->createReturn($user, $request->all());
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Sale not found' ? 404 : 422;
            return response()->json(['error' => $e->getMessage()], $status);
        }

        try {
            $this->journalService->postSaleReturn($saleReturn, $user->id);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for sale return', ['return_id' => $saleReturn->id, 'error' => $e->getMessage()]);
        }

        return new SaleReturnResource($saleReturn);
    }
}
