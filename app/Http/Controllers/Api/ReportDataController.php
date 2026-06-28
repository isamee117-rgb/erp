<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportQueryRequest;
use App\Services\ReportQueryService;
use Carbon\Carbon;

class ReportDataController extends Controller
{
    public function __construct(private ReportQueryService $service) {}

    public function detailedSales(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->detailedSales($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }

    public function detailedPurchase(ReportQueryRequest $request)
    {
        return $this->run($request, fn($coId, $from, $to, $filters, $page, $perPage, $export) =>
            $this->service->detailedPurchase($coId, $from, $to, $filters, $page, $perPage, $export)
        );
    }

    /**
     * Shared request handling: Super-Admin guard, date parsing, paging defaults,
     * filter extraction, and RuntimeException -> 422 mapping.
     */
    private function run(ReportQueryRequest $request, callable $call)
    {
        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        $from    = Carbon::parse($request->input('from'))->startOfDay();
        $to      = Carbon::parse($request->input('to'))->endOfDay();
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('perPage', config('reports.default_per_page'));
        $export  = $request->boolean('export');

        $filters = $request->only(['customerId', 'vendorId', 'paymentMethod', 'status', 'search']);

        try {
            return response()->json(
                $call($user->company_id, $from, $to, $filters, $page, $perPage, $export)
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
