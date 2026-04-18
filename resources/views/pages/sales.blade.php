@extends('layouts.app')
@section('page-title', 'Sales History - LeanERP')
@section('content')
<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title">Sales History</h2>
        <p class="mb-0 inv-subtitle">Review and manage all recorded point-of-sale transactions.</p>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-4">
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search erp-icon-sm"></i></span>
          <input type="text" class="form-control inv-input ps-5" id="sale-search" placeholder="Search by Invoice ID or Customer..." oninput="sCurrentPage=1;renderPage();">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select inv-input" id="sale-payment" onchange="sCurrentPage=1;renderPage();">
          <option value="all">All Methods</option><option value="Cash">Cash</option><option value="Credit">Credit</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input type="date" class="form-control inv-input" id="sale-date-from" title="From Date" onchange="sCurrentPage=1;renderPage();">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" class="form-control inv-input" id="sale-date-to" title="To Date" onchange="sCurrentPage=1;renderPage();">
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th col-erp-expand"></th>
          <th class="inv-th cursor-pointer" onclick="sSortToggle('id')">Invoice # <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th cursor-pointer" onclick="sSortToggle('createdAt')">Date <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th">Customer</th>
          <th class="inv-th text-center">Items</th>
          <th class="inv-th text-end cursor-pointer" onclick="sSortToggle('totalAmount')">Total <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th">Payment</th>
          <th class="inv-th">Status</th>
          <th class="inv-th text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="sale-tbody"></tbody>
    </table>
  </div>
  <div class="card-footer inv-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted erp-text-sm" id="sale-info"></div>
    <ul class="pagination mb-0" id="sale-pagination"></ul>
  </div>
</div>

</div>
@endsection


@push('scripts')
<script src="{{ asset('js/pages/sales.js') }}?v={{ filemtime(public_path('js/pages/sales.js')) }}"></script>
@endpush
