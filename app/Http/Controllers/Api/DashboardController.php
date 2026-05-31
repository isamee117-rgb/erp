<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->get('auth_user');
        $coId   = $user->company_id;
        $filter = $request->query('filter', 'month');

        [$from, $to] = $this->dateRange($filter);

        $grossSales  = (float) SaleOrder::where('company_id', $coId)->whereBetween('created_at', [$from, $to])->sum('total_amount');
        $saleReturns = (float) SaleReturn::where('company_id', $coId)->whereBetween('created_at', [$from, $to])->sum('total_amount');
        $salesCount  = SaleOrder::where('company_id', $coId)->whereBetween('created_at', [$from, $to])->count();
        $returnsCount= SaleReturn::where('company_id', $coId)->whereBetween('created_at', [$from, $to])->count();

        $products       = Product::where('company_id', $coId)->get(['current_stock','unit_cost','reorder_level']);
        $inventoryValue = (float) $products->sum(fn($p) => ($p->current_stock ?? 0) * ($p->unit_cost ?? 0));
        $lowStockCount  = $products->filter(fn($p) => ($p->current_stock ?? 0) <= ($p->reorder_level ?? 0))->count();
        $pendingPOs     = PurchaseOrder::where('company_id', $coId)->where('status', 'Draft')->count();

        $recent = SaleOrder::where('company_id', $coId)
            ->orderByDesc('created_at')->limit(10)
            ->get(['id','invoice_no','total_amount','created_at'])
            ->map(fn($s) => [
                'id'        => $s->invoice_no ?? $s->id,
                'amount'    => (float) $s->total_amount,
                'createdAt' => strtotime($s->created_at) * 1000,
            ]);

        return response()->json([
            'netSales'           => round($grossSales - $saleReturns, 2),
            'salesCount'         => $salesCount,
            'returnsCount'       => $returnsCount,
            'inventoryValue'     => round($inventoryValue, 2),
            'productCount'       => $products->count(),
            'lowStockCount'      => $lowStockCount,
            'pendingPOCount'     => $pendingPOs,
            'recentTransactions' => $recent,
            'trend'              => $this->buildTrend($coId, $filter, $from, $to),
        ]);
    }

    private function dateRange(string $filter): array
    {
        $now = now();
        return match ($filter) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'year'  => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function buildTrend(string $coId, string $filter, $from, $to): array
    {
        $now = now();

        if ($filter === 'today') {
            $sales   = $this->aggByHour($coId, 'sale_orders',   'total_amount', $from, $to);
            $returns = $this->aggByHour($coId, 'sale_returns',  'total_amount', $from, $to);
            return array_map(function ($h) use ($sales, $returns) {
                $label = ($h < 10 ? '0' : '') . $h . ':00';
                $val   = max(0, ($sales[$h] ?? 0) - ($returns[$h] ?? 0));
                return ['label' => $label, 'value' => $val];
            }, range(0, 23));
        }

        if ($filter === 'year') {
            $sales   = $this->aggByMonth($coId, 'sale_orders',  'total_amount', $from, $to);
            $returns = $this->aggByMonth($coId, 'sale_returns', 'total_amount', $from, $to);
            $months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return array_map(function ($m) use ($sales, $returns, $months) {
                $val = max(0, ($sales[$m] ?? 0) - ($returns[$m] ?? 0));
                return ['label' => $months[$m - 1], 'value' => $val];
            }, range(1, 12));
        }

        // month (default)
        $daysInMonth = (int) $now->format('t');
        $sales   = $this->aggByDay($coId, 'sale_orders',  'total_amount', $from, $to);
        $returns = $this->aggByDay($coId, 'sale_returns', 'total_amount', $from, $to);
        return array_map(function ($d) use ($sales, $returns) {
            $val = max(0, ($sales[$d] ?? 0) - ($returns[$d] ?? 0));
            return ['label' => (string) $d, 'value' => $val];
        }, range(1, $daysInMonth));
    }

    private function aggByHour(string $coId, string $table, string $col, $from, $to): array
    {
        return DB::table($table)
            ->where('company_id', $coId)->whereBetween('created_at', [$from, $to])
            ->selectRaw('HOUR(created_at) as period, SUM(' . $col . ') as total')
            ->groupBy('period')->pluck('total', 'period')
            ->map(fn($v) => (float) $v)->all();
    }

    private function aggByDay(string $coId, string $table, string $col, $from, $to): array
    {
        return DB::table($table)
            ->where('company_id', $coId)->whereBetween('created_at', [$from, $to])
            ->selectRaw('DAY(created_at) as period, SUM(' . $col . ') as total')
            ->groupBy('period')->pluck('total', 'period')
            ->map(fn($v) => (float) $v)->all();
    }

    private function aggByMonth(string $coId, string $table, string $col, $from, $to): array
    {
        return DB::table($table)
            ->where('company_id', $coId)->whereBetween('created_at', [$from, $to])
            ->selectRaw('MONTH(created_at) as period, SUM(' . $col . ') as total')
            ->groupBy('period')->pluck('total', 'period')
            ->map(fn($v) => (float) $v)->all();
    }
}
