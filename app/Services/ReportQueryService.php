<?php

namespace App\Services;

use App\Http\Resources\SaleOrderResource;
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
