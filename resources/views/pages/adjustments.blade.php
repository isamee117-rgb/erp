@extends('layouts.app')
@section('page-title', 'Adjustment Entries - LeanERP')
@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title"><i class="ti ti-adjustments me-2"></i>Inventory Adjustments</h2></div>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-5"><input type="text" class="form-control" id="searchInput" placeholder="Search by product, SKU, or reference..."></div>
            <div class="col-md-3">
                <select class="form-select" id="typeFilter">
                    <option value="">All Adjustment Types</option>
                    <option value="Adjustment_Damage">Damage</option>
                    <option value="Adjustment_Theft">Theft</option>
                    <option value="Adjustment_Internal">Internal Use</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="dateFilter">
                    <option value="all">Any Time</option>
                    <option value="today">Today</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
            </div>
            <div class="col-md-1"><button class="btn btn-outline-secondary w-100" onclick="clearFilters()"><i class="ti ti-x"></i></button></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-end">Qty Change</th><th>Reference</th></tr></thead>
            <tbody id="adjustmentsBody"></tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center justify-content-between">
        <p class="m-0 text-muted" id="paginationInfo"></p>
        <ul class="pagination m-0" id="pagination"></ul>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/pages/adjustments.js') }}?v={{ filemtime(public_path('js/pages/adjustments.js')) }}"></script>
@endpush
