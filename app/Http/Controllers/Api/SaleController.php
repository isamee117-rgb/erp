<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleOrderResource;
use App\Http\Resources\SaleReturnResource;
use App\Models\SaleOrder;
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

    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $coId = $user->company_id;

        $query = SaleOrder::with(['items', 'customer', 'returns'])
            ->where('company_id', $coId)
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if (($payment = $request->query('payment')) && $payment !== 'all') {
            $query->where('payment_method', $payment);
        }

        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return SaleOrderResource::collection($query->paginate(50));
    }

    public function store(StoreSaleRequest $request)
    {
        $user = $request->get('auth_user');
        $sale = $this->saleService->createSale($user, $request->validated());

        $journalWarning = null;
        try {
            $this->journalService->postSaleInvoice($sale);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for sale', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
            $journalWarning = $e->getMessage();
        }

        return (new SaleOrderResource($sale))->additional(array_filter(['warning' => $journalWarning]));
    }

    public function createReturn(Request $request)
    {
        $user = $request->get('auth_user');

        try {
            $saleReturn = $this->saleService->createReturn($user, $request->all());
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Sale not found' ? 404 : 422;
            Log::warning('Sale return rejected', ['user_id' => $user->id, 'company_id' => $user->company_id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], $status);
        }

        $journalWarning = null;
        try {
            $this->journalService->postSaleReturn($saleReturn, $user->id);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for sale return', ['return_id' => $saleReturn->id, 'error' => $e->getMessage()]);
            $journalWarning = $e->getMessage();
        }

        return (new SaleReturnResource($saleReturn))->additional(array_filter(['warning' => $journalWarning]));
    }
}
