@extends('layouts.app')
@section('page-title', 'Inventory Ledger - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-receipt me-2"></i>Inventory Ledger</h2>
        <p class="mb-0 inv-subtitle">Track every stock movement by product and date range.</p>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="d-flex align-items-center gap-2">
      <div class="flex-grow-1">
        <div class="sdd-wrap" id="productSelect-sdd">
          <div class="il-sdd-trigger" onclick="sddToggle('productSelect-sdd')">
            <span class="sdd-disp erp-dropdown-placeholder" id="productSelect-disp">-- Select a Product --</span>
            <i class="ti ti-chevron-down sdd-caret"></i>
          </div>
          <div class="sdd-panel">
            <div class="sdd-search-row">
              <i class="ti ti-search"></i>
              <input type="text" class="sdd-search-inp" placeholder="Search by name or SKU..." oninput="sddFilterOpts('productSelect-sdd',this.value)" onclick="event.stopPropagation()">
            </div>
            <div class="sdd-opts-wrap" id="productSelect-opts"></div>
          </div>
          <input type="hidden" id="productSelect">
        </div>
      </div>
      <div class="inv-toolbar-group">
        <button class="inv-icon-btn" id="il-filter-toggle-btn" title="Toggle Filters">
          <i class="ti ti-filter"></i>
        </button>
      </div>
    </div>
    <div id="il-filters-panel" class="d-none mt-2">
      <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
          <input type="date" class="form-control inv-input" id="dateFrom" title="From Date">
        </div>
        <div class="col-6 col-md-3">
          <input type="date" class="form-control inv-input" id="dateTo" title="To Date">
        </div>
        <div class="col-auto">
          <button class="inv-icon-btn" id="il-clear-filters-btn" title="Clear Filters"><i class="ti ti-x"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th">Date</th>
          <th class="inv-th">Type</th>
          <th class="inv-th">Reference #</th>
          <th class="inv-th text-end">Qty In</th>
          <th class="inv-th text-end">Qty Out</th>
          <th class="inv-th text-end">Balance</th>
        </tr>
      </thead>
      <tbody id="ledgerBody"></tbody>
    </table>
  </div>
  <div class="inv-footer-bar">
    <div class="text-muted" id="ilPagInfo" erp-text-sm"></div>
    <ul class="pagination mb-0" id="ilPag"></ul>
  </div>
</div>

</div>
@endsection
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{--inv-primary:#CD0000;--inv-font:'Inter',sans-serif;}
.page-body,.page-wrapper{font-family:var(--inv-font);font-size:14px;background:#F5F6FA!important;}
.inv-page-wrap{display:flex;flex-direction:column;gap:16px;}
.inv-header-card{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);border:none;border-radius:10px;overflow:hidden;position:relative;}
.inv-header-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.5;pointer-events:none;}
.inv-header-card::after{content:'';position:absolute;top:-40%;right:-8%;width:260px;height:260px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;}
.inv-filter-body{padding:12px 16px!important;}
.inv-input{height:36px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;transition:all 0.2s ease;}
.inv-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.inv-table-card{overflow:hidden;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;}
.badge-pill{font-weight:600;padding:3px 10px;border-radius:20px;font-size:0.72rem;}
.badge-blue{background:rgba(205,0,0,0.1);color:#CD0000;}
.badge-green{background:rgba(16,185,129,0.1);color:#059669;}
.badge-red{background:rgba(239,68,68,0.1);color:#dc2626;}
.badge-gray{background:rgba(100,116,139,0.1);color:#64748b;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.inv-footer-bar{padding:10px 16px;border-top:1px solid #E8EAF0;display:flex;align-items:center;justify-content:space-between;background:#fff;flex-wrap:wrap;gap:8px;}
.pagination .page-link{border-radius:6px!important;margin:0 2px;border:1px solid #E8EAF0;color:#64748b;font-weight:500;font-size:0.8rem;min-width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;transition:all 0.15s ease;}
.pagination .page-item.active .page-link{background:#CD0000;border-color:#CD0000;color:#fff;}
.pagination .page-item.disabled .page-link{opacity:0.45;pointer-events:none;}
.pagination .page-link:hover:not(.active){background:#F5F6FA;border-color:#DDE1EC;color:#1e293b;}

/* ── Searchable Dropdown (SDD) ──────────────────────────── */
.sdd-wrap{position:relative;}
.il-sdd-trigger{
  height:36px;font-size:0.85rem;
  border:1px solid #DDE1EC;border-radius:6px;background:#fff;
  width:100%;padding:0 10px;cursor:pointer;
  display:flex;align-items:center;justify-content:space-between;
  transition:border-color 0.2s ease,box-shadow 0.2s ease;color:#1A1D2E;
}
.il-sdd-trigger:hover{border-color:#B0B7C9;}
.sdd-wrap.open .il-sdd-trigger{border-color:#CD0000;box-shadow:0 0 0 3px rgba(205,0,0,0.08);}
.sdd-caret{font-size:14px;color:#6B7280;flex-shrink:0;transition:transform 0.2s ease;}
.sdd-wrap.open .sdd-caret{transform:rotate(180deg);}
.sdd-disp{font-size:0.85rem;color:#1A1D2E;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sdd-panel{
  display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
  background:#fff;border:1px solid #DDE1EC;border-radius:8px;
  box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:3000;overflow:hidden;
}
.sdd-wrap.open .sdd-panel{display:block;}
.sdd-search-row{display:flex;align-items:center;gap:8px;padding:8px 10px;border-bottom:1px solid #F0F2F8;}
.sdd-search-row .ti-search{font-size:13px;color:#9CA3AF;flex-shrink:0;}
.sdd-search-inp{border:none;outline:none;font-size:0.82rem;color:#1A1D2E;flex:1;background:transparent;}
.sdd-opts-wrap{max-height:220px;overflow-y:auto;}
.sdd-opt{padding:8px 12px;font-size:0.83rem;color:#374151;cursor:pointer;transition:background 0.12s;}
.sdd-opt:hover{background:#F0F4FF;color:#CD0000;}
.sdd-no-res{padding:10px 12px;font-size:0.82rem;color:#9CA3AF;text-align:center;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/inventory-ledger.js') }}?v={{ filemtime(public_path('js/pages/inventory-ledger.js')) }}"></script>
@endpush
