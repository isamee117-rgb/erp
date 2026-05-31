<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
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

    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $coId = $user->company_id;

        $query = PurchaseOrder::with(['items', 'receives.items', 'vendor'])
            ->where('company_id', $coId)
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('po_no', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if (($status = $request->query('status')) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (($vendor = $request->query('vendor')) && $vendor !== 'all') {
            $query->where('vendor_id', $vendor);
        }

        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return PurchaseOrderResource::collection($query->paginate(50));
    }

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
            Log::warning('Purchase receive rejected', ['user_id' => $user->id, 'company_id' => $user->company_id, 'error' => $e->getMessage()]);
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

    public function returnable(Request $request)
    {
        $user   = $request->get('auth_user');
        $coId   = $user->company_id;
        $search = $request->query('search', '');

        $query = PurchaseOrder::with(['items', 'receives.items', 'vendor'])
            ->where('company_id', $coId)
            ->whereIn('status', ['Received', 'Partially Received'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('po_no', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        return PurchaseOrderResource::collection($query->limit(25)->get());
    }

    public function indexReturns(Request $request)
    {
        $user = $request->get('auth_user');
        $coId = $user->company_id;

        $query = PurchaseReturn::with(['items', 'vendor', 'originalPurchase'])
            ->where('company_id', $coId)
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('return_no', 'like', "%{$search}%")
                  ->orWhere('original_purchase_id', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return PurchaseReturnResource::collection($query->paginate(50));
    }

    public function createReturn(Request $request)
    {
        $user = $request->get('auth_user');

        try {
            $purchaseReturn = $this->purchaseService->createReturn($user, $request->all());
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Purchase order not found' ? 404 : 422;
            Log::warning('Purchase return rejected', ['user_id' => $user->id, 'company_id' => $user->company_id, 'error' => $e->getMessage()]);
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
