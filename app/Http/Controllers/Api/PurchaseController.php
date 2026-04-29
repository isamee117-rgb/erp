<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseReturnResource;
use App\Services\JournalPostingService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected JournalPostingService $journalService,
    ) {}

    public function store(StorePurchaseOrderRequest $request)
    {
        $po = $this->purchaseService->createOrder($request->get('auth_user'), $request->validated());
        return new PurchaseOrderResource($po);
    }

    public function receive(ReceivePurchaseOrderRequest $request, $id)
    {
        $user = $request->get('auth_user');

        try {
            $validated = $request->validated();
            $po = $this->purchaseService->receiveOrder(
                $user,
                $id,
                $validated['items'] ?? [],
                $validated['notes'] ?? '',
                $validated['receiveDate'] ?? null
            );
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Purchase order not found' ? 404 : 400;
            return response()->json(['error' => $e->getMessage()], $status);
        }

        $journalWarning = null;
        try {
            $latestReceive = $po->receives()->latest()->first();
            if ($latestReceive) {
                $this->journalService->postPurchaseReceive($latestReceive, $user->id);
            }
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for purchase receive', ['po_id' => $po->id, 'error' => $e->getMessage()]);
            $journalWarning = $e->getMessage();
        }

        return (new PurchaseOrderResource($po))->additional(array_filter(['warning' => $journalWarning]));
    }

    public function createReturn(Request $request)
    {
        $user = $request->get('auth_user');

        try {
            $purchaseReturn = $this->purchaseService->createReturn($user, $request->all());
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Purchase order not found' ? 404 : 422;
            return response()->json(['error' => $e->getMessage()], $status);
        }

        $journalWarning = null;
        try {
            $this->journalService->postPurchaseReturn($purchaseReturn, $user->id);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for purchase return', ['return_id' => $purchaseReturn->id, 'error' => $e->getMessage()]);
            $journalWarning = $e->getMessage();
        }

        return (new PurchaseReturnResource($purchaseReturn))->additional(array_filter(['warning' => $journalWarning]));
    }
}
