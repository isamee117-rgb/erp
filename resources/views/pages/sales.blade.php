@extends('layouts.app')
@section('page-title', 'Sales History - LeanERP')
@section('content')
<div class="pg-wrap">

<div class="card pg-header-card">
  <div class="card-body pg-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 pg-title">Sales History</h2>
        <p class="mb-0 pg-subtitle">Review and manage all recorded point-of-sale transactions.</p>
      </div>
    </div>
  </div>
</div>

<div class="card pg-section-card pg-filter-bar">
  <div class="card-body pg-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-4">
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search erp-icon-sm"></i></span>
          <input type="text" class="form-control pg-input ps-5" id="sale-search" placeholder="Search by Invoice ID or Customer..." oninput="sCurrentPage=1;renderPage();">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select pg-input" id="sale-payment" onchange="sCurrentPage=1;renderPage();">
          <option value="all">All Methods</option><option value="Cash">Cash</option><option value="Credit">Credit</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input type="date" class="form-control pg-input" id="sale-date-from" title="From Date" onchange="sCurrentPage=1;renderPage();">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" class="form-control pg-input" id="sale-date-to" title="To Date" onchange="sCurrentPage=1;renderPage();">
      </div>
    </div>
  </div>
</div>

<div class="card pg-section-card pg-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter pg-table mb-0">
      <thead>
        <tr>
          <th class="pg-th col-erp-expand"></th>
          <th class="pg-th cursor-pointer" onclick="sSortToggle('id')">Invoice # <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="pg-th cursor-pointer" onclick="sSortToggle('createdAt')">Date <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="pg-th">Customer</th>
          <th class="pg-th text-center">Items</th>
          <th class="pg-th text-end cursor-pointer" onclick="sSortToggle('totalAmount')">Total <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="pg-th">Payment</th>
          <th class="pg-th">Status</th>
          <th class="pg-th text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="sale-tbody"></tbody>
    </table>
  </div>
  <div class="card-footer pg-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted erp-text-sm" id="sale-info"></div>
    <ul class="pagination mb-0" id="sale-pagination"></ul>
  </div>
</div>

</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root { --pg-primary: #3B4FE4; --pg-primary-end: #5B6CF9; --pg-font: 'Inter', sans-serif; }
.page-body, .page-wrapper { font-family: var(--pg-font); font-size: 14px; background: #F5F6FA !important; }
.pg-wrap { display: flex; flex-direction: column; gap: 16px; }
.pg-header-card { background: linear-gradient(135deg, #3B4FE4 0%, #5B6CF9 100%); border: none; border-radius: 10px; overflow: hidden; position: relative; }
.pg-header-card::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 16px 16px; opacity: 0.5; pointer-events: none; }
.pg-header-card::after { content: ''; position: absolute; top: -40%; right: -8%; width: 260px; height: 260px; background: rgba(255,255,255,0.06); border-radius: 50%; pointer-events: none; }
.pg-header-body { padding: 20px 28px !important; position: relative; z-index: 1; }
.pg-header-card .pg-title { font-size: 1.35rem; font-weight: 700; color: #fff; }
.pg-header-card .pg-subtitle { font-size: 0.82rem; font-weight: 400; color: rgba(255,255,255,0.82); }
.pg-header-card .btn { font-size: 0.82rem; font-weight: 600; padding: 8px 18px; }
.pg-section-card { border: 1px solid #E8EAF0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); background: #fff; }
.pg-filter-body { padding: 12px 16px !important; }
.pg-input { height: 36px !important; font-size: 0.85rem !important; border: 1px solid #DDE1EC !important; border-radius: 6px !important; transition: all 0.2s ease; }
.pg-input:focus { border-color: var(--pg-primary) !important; box-shadow: 0 0 0 3px rgba(59,79,228,0.08) !important; }
.pg-table-card { overflow: hidden; }
.pg-table thead { background: #F8F9FC; }
.pg-th { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; border-bottom: 2px solid #E8EAF0 !important; white-space: nowrap; padding: 10px 14px !important; }
.pg-table tbody tr { transition: background-color 0.15s ease; }
.pg-table tbody tr:hover { background-color: #F5F7FF !important; }
.pg-table tbody td { padding: 10px 14px !important; vertical-align: middle; border-bottom: 1px solid #F0F2F8 !important; border-top: none !important; font-size: 0.85rem; color: #1e293b; }
.pg-table-footer { background: #fff; border-top: 1px solid #E8EAF0; padding: 10px 16px; }
.pg-action-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: none; background: transparent; color: #64748b; transition: all 0.15s ease; padding: 0; font-size: 15px; }
.pg-action-btn:hover { color: #3B4FE4; background: #EEF0F8; }
.pg-badge { font-size: 0.72rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; letter-spacing: 0.03em; }
.pg-badge-cash { background: rgba(59,79,228,0.08); color: #3B4FE4; }
.pg-badge-credit { background: rgba(139,92,246,0.08); color: #7c3aed; }
.pg-badge-completed { background: rgba(16,185,129,0.1); color: #059669; }
.pg-badge-returned { background: rgba(239,68,68,0.1); color: #dc2626; }
.pg-id { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 0.75rem; font-weight: 500; color: #5B6CF9; background: #EEF0F8; padding: 2px 8px; border-radius: 4px; }
.expand-row { background: #FAFBFD !important; }
.expand-row:hover { background: #FAFBFD !important; }
.expand-row td { border-bottom: 1px solid #E8EAF0 !important; }
.expand-row .table th { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background: #F8F9FC; padding: 8px 12px; border-bottom: 1px solid #E8EAF0; }
.expand-row .table td { font-size: 0.82rem; padding: 8px 12px; border-bottom: 1px solid #F0F2F8; }
.pagination .page-link { border-radius: 6px !important; margin: 0 2px; border: 1px solid #E8EAF0; color: #64748b; font-weight: 500; font-size: 0.8rem; min-width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; padding: 0 8px; transition: all 0.15s ease; }
.pagination .page-item.active .page-link { background: #3B4FE4; border-color: #3B4FE4; color: #fff; box-shadow: 0 1px 3px rgba(59,79,228,0.3); }
.pagination .page-link:hover { background: #F5F6FA; border-color: #DDE1EC; color: #1e293b; }
.cursor-pointer { cursor: pointer; }
@media print { .no-print { display: none !important; } }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/sales.js') }}?v={{ filemtime(public_path('js/pages/sales.js')) }}"></script>
@endpush
