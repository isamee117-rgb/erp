<?php

namespace App\Services;

use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseReturnResource;
use App\Http\Resources\SaleOrderResource;
use App\Http\Resources\SaleReturnResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SaleOrder;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportQueryService
{
    public function detailedSales(
        string $companyId,
        Carbon $from,
        Carbon $to,
        array $filters,
        int $page,
        int $perPage,
        bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = SaleOrder::with(['items', 'returns', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);

        if (!empty($filters['customerId']))    $query->where('customer_id', $filters['customerId']);
        if (!empty($filters['paymentMethod'])) $query->where('payment_method', $filters['paymentMethod']);
        if (!empty($filters['search']))        $query->where('invoice_no', 'like', '%' . $filters['search'] . '%');

        $query->orderByDesc('created_at');

        $summary = $this->salesSummary($companyId, $from, $to, $filters);

        $shape = fn($sale) => (new SaleOrderResource($sale))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape,
            $summary,
            $export
        );
    }

    public function detailedPurchase(
        string $companyId,
        Carbon $from,
        Carbon $to,
        array $filters,
        int $page,
        int $perPage,
        bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = PurchaseOrder::with(['items', 'receives.items', 'vendor'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);

        if (!empty($filters['vendorId'])) $query->where('vendor_id', $filters['vendorId']);
        if (!empty($filters['status']))   $query->where('status', $filters['status']);
        if (!empty($filters['search']))   $query->where('po_no', 'like', '%' . $filters['search'] . '%');

        $query->orderByDesc('created_at');

        $base = PurchaseOrder::where('company_id', $companyId)->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $base->where('vendor_id', $filters['vendorId']);
        if (!empty($filters['status']))   $base->where('status', $filters['status']);
        if (!empty($filters['search']))   $base->where('po_no', 'like', '%' . $filters['search'] . '%');

        $summary = [
            'totalOrders' => (clone $base)->count(),
            'grandTotal'  => (float) (clone $base)->sum('total_amount'),
        ];

        $shape = fn($po) => (new PurchaseOrderResource($po))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape,
            $summary,
            $export
        );
    }

    public function salesReturns(
        string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = SaleReturn::with(['items', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId'])) $query->where('customer_id', $filters['customerId']);
        $query->orderByDesc('created_at');

        $base = SaleReturn::where('company_id', $companyId)->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId'])) $base->where('customer_id', $filters['customerId']);

        $summary = [
            'totalReturns' => (clone $base)->count(),
            'grandTotal'   => (float) (clone $base)->sum('total_amount'),
        ];

        $shape = fn($r) => (new SaleReturnResource($r))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape, $summary, $export
        );
    }

    public function purchaseReturns(
        string $companyId, Carbon $from, Carbon $to, array $filters, int $page, int $perPage, bool $export
    ): array {
        $this->assertExportRange($from, $to, $export);

        $query = PurchaseReturn::with(['items', 'vendor'])
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $query->where('vendor_id', $filters['vendorId']);
        $query->orderByDesc('created_at');

        $base = PurchaseReturn::where('company_id', $companyId)->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['vendorId'])) $base->where('vendor_id', $filters['vendorId']);

        $summary = [
            'totalReturns' => (clone $base)->count(),
            'grandTotal'   => (float) (clone $base)->sum('total_amount'),
        ];

        $shape = fn($r) => (new PurchaseReturnResource($r))->resolve();

        return $this->buildEnvelope(
            $export ? $query->get() : $query->paginate($perPage, ['*'], 'page', $page),
            $shape, $summary, $export
        );
    }

    // ── Shared helpers (reused by every report method) ─────────────────────────

    protected function assertExportRange(Carbon $from, Carbon $to, bool $export): void
    {
        if ($export && $from->diffInDays($to) > config('reports.max_export_days')) {
            throw new \RuntimeException(
                'Date range too large for export. Please select a range of up to 1 year.'
            );
        }
    }

    /**
     * Wraps shaped rows into the {data, pagination, summary} envelope.
     * $rows is a paginator (display) or a plain Collection (export).
     */
    protected function buildEnvelope(
        LengthAwarePaginator|Collection $rows,
        callable $shape,
        array $summary,
        bool $export
    ): array {
        if ($export) {
            return [
                'data'       => $rows->map($shape)->values()->all(),
                'pagination' => null,
                'summary'    => $summary,
            ];
        }

        return [
            'data'       => collect($rows->items())->map($shape)->values()->all(),
            'pagination' => [
                'page'     => $rows->currentPage(),
                'perPage'  => $rows->perPage(),
                'total'    => $rows->total(),
                'lastPage' => $rows->lastPage(),
            ],
            'summary'    => $summary,
        ];
    }

    protected function salesSummary(string $companyId, Carbon $from, Carbon $to, array $filters): array
    {
        $base = SaleOrder::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to]);
        if (!empty($filters['customerId']))    $base->where('customer_id', $filters['customerId']);
        if (!empty($filters['paymentMethod'])) $base->where('payment_method', $filters['paymentMethod']);
        if (!empty($filters['search']))        $base->where('invoice_no', 'like', '%' . $filters['search'] . '%');

        $totalInvoices = (clone $base)->count();
        $grandTotal    = (float) (clone $base)->sum('total_amount');

        // Returns recorded within the same range
        $totalReturns = (float) SaleReturn::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        return [
            'totalInvoices' => $totalInvoices,
            'grandTotal'    => $grandTotal,
            'totalReturns'  => $totalReturns,
            'netTotal'      => $grandTotal - $totalReturns,
        ];
    }
}
