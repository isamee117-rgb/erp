@extends('layouts.app')
@section('page-title', 'Outstanding - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-building-bank me-2"></i>Outstanding Balances</h2>
        <p class="mb-0 inv-subtitle">Track receivables from customers and payables to vendors.</p>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-card">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-end">
      <div class="col-auto"><label class="pm-label">From</label><input type="date" class="form-control inv-input" id="dateFrom" onchange="renderPage()"></div>
      <div class="col-auto"><label class="pm-label">To</label><input type="date" class="form-control inv-input" id="dateTo" onchange="renderPage()"></div>
      <div class="col-auto" class="erp-pt-btn"><button class="btn btn-light inv-input px-3" onclick="document.getElementById('dateFrom').value='';document.getElementById('dateTo').value='';renderPage()"><i class="ti ti-x"></i></button></div>
    </div>
  </div>
</div>

<div class="card inv-section-card" class="erp-overflow-hidden">
  <div class="inv-tab-header">
    <ul class="nav inv-nav-tabs" role="tablist">
      <li class="nav-item"><a class="nav-link inv-nav-link active" data-bs-toggle="tab" href="#receivable" onclick="setTimeout(renderPage,100)"><i class="ti ti-trending-up me-1"></i>Receivable (Customers)</a></li>
      <li class="nav-item"><a class="nav-link inv-nav-link" data-bs-toggle="tab" href="#payable" onclick="setTimeout(renderPage,100)"><i class="ti ti-trending-down me-1"></i>Payable (Vendors)</a></li>
    </ul>
  </div>
  <div class="tab-content">
    <div class="tab-pane active" id="receivable">
      <div class="table-responsive">
        <table class="table table-hover table-vcenter inv-table mb-0">
          <thead>
            <tr>
              <th class="inv-th">Party Name</th>
              <th class="inv-th text-end">Total Sales</th>
              <th class="inv-th text-end">Payments Received</th>
              <th class="inv-th text-end">Outstanding Balance</th>
            </tr>
          </thead>
          <tbody id="receivableBody"></tbody>
          <tfoot id="receivableFoot"></tfoot>
        </table>
      </div>
      <div class="inv-footer-bar">
        <div class="text-muted" id="recPagInfo" erp-text-sm"></div>
        <ul class="pagination mb-0" id="recPag"></ul>
      </div>
    </div>
    <div class="tab-pane" id="payable">
      <div class="table-responsive">
        <table class="table table-hover table-vcenter inv-table mb-0">
          <thead>
            <tr>
              <th class="inv-th">Party Name</th>
              <th class="inv-th text-end">Total Purchases</th>
              <th class="inv-th text-end">Payments Made</th>
              <th class="inv-th text-end">Outstanding Balance</th>
            </tr>
          </thead>
          <tbody id="payableBody"></tbody>
          <tfoot id="payableFoot"></tfoot>
        </table>
      </div>
      <div class="inv-footer-bar">
        <div class="text-muted" id="payPagInfo" erp-text-sm"></div>
        <ul class="pagination mb-0" id="payPag"></ul>
      </div>
    </div>
  </div>
</div>

</div>
@endsection
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{--inv-primary:#3B4FE4;--inv-font:'Inter',sans-serif;}
.page-body,.page-wrapper{font-family:var(--inv-font);font-size:14px;background:#F5F6FA!important;}
.inv-page-wrap{display:flex;flex-direction:column;gap:16px;}
.inv-header-card{background:linear-gradient(135deg,#3B4FE4 0%,#5B6CF9 100%);border:none;border-radius:10px;overflow:hidden;position:relative;}
.inv-header-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.5;pointer-events:none;}
.inv-header-card::after{content:'';position:absolute;top:-40%;right:-8%;width:260px;height:260px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;}
.inv-tab-header{padding:0 16px;border-bottom:2px solid #E8EAF0;background:#F8F9FC;}
.inv-nav-tabs{border:none;gap:4px;}
.inv-nav-link{font-size:0.82rem;font-weight:600;color:#64748b;padding:12px 16px!important;border:none!important;border-bottom:2px solid transparent!important;margin-bottom:-2px;border-radius:0!important;background:none!important;transition:all 0.2s;}
.inv-nav-link.active{color:var(--inv-primary)!important;border-bottom-color:var(--inv-primary)!important;}
.inv-nav-link:hover:not(.active){color:#374151;border-bottom-color:#DDE1EC!important;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;}
.inv-table tfoot td{padding:10px 14px!important;font-weight:700;background:#F8F9FC!important;border-top:2px solid #E8EAF0!important;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.inv-filter-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;}
.inv-filter-body{padding:12px 16px!important;}
.inv-input{height:36px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;}
.inv-footer-bar{padding:10px 16px;border-top:1px solid #E8EAF0;display:flex;align-items:center;justify-content:space-between;background:#fff;flex-wrap:wrap;gap:8px;}
.pagination .page-link{border-radius:6px!important;margin:0 2px;border:1px solid #E8EAF0;color:#64748b;font-weight:500;font-size:0.8rem;min-width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;transition:all 0.15s ease;}
.pagination .page-item.active .page-link{background:#3B4FE4;border-color:#3B4FE4;color:#fff;}
.pagination .page-item.disabled .page-link{opacity:0.45;pointer-events:none;}
.pagination .page-link:hover:not(.active){background:#F5F6FA;border-color:#DDE1EC;color:#1e293b;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/outstanding.js') }}?v={{ filemtime(public_path('js/pages/outstanding.js')) }}"></script>
@endpush
