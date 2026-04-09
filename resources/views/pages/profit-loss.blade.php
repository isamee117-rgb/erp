@extends('layouts.app')
@section('page-title', 'Profit & Loss - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-chart-line me-2"></i>Profit &amp; Loss</h2>
        <p class="mb-0 inv-subtitle">Income statement showing revenues, expenses and net profit for a period.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

{{-- Date Filter --}}
<div class="card inv-section-card no-print">
  <div class="set-card-body d-flex gap-3 flex-wrap align-items-end">
    <div>
      <label class="pm-label">From Date</label>
      <input type="date" class="form-control pm-input" id="plFrom" style="min-width:160px;">
    </div>
    <div>
      <label class="pm-label">To Date</label>
      <input type="date" class="form-control pm-input" id="plTo" style="min-width:160px;">
    </div>
    <div>
      <button class="btn btn-primary" onclick="loadPL()" style="height:38px;"><i class="ti ti-search me-1"></i>Generate Report</button>
    </div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" onclick="setPeriod('month')">This Month</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="setPeriod('quarter')">This Quarter</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="setPeriod('year')">This Year</button>
    </div>
  </div>
</div>

{{-- Report --}}
<div id="plReport" class="card inv-section-card" style="display:none;">
  <div class="set-card-header d-flex justify-content-between">
    <span><i class="ti ti-chart-line me-2 text-green"></i>Profit &amp; Loss Statement</span>
    <span id="plPeriodLabel" class="text-muted" style="font-size:0.8rem;font-weight:400;"></span>
  </div>
  <div class="table-responsive">
    <table class="table table-vcenter inv-table mb-0" id="plTable">
      <thead>
        <tr>
          <th class="inv-th">Account</th>
          <th class="inv-th text-end">Amount</th>
        </tr>
      </thead>
      <tbody id="plBody"></tbody>
    </table>
  </div>
</div>

<div id="plLoading" class="text-center py-5" style="display:none;">
  <div class="spinner-border text-primary"></div>
  <div class="mt-2 text-muted" style="font-size:0.85rem;">Generating report...</div>
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
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;overflow:hidden;}
.set-card-header{padding:14px 20px;font-size:0.85rem;font-weight:700;color:#1e293b;border-bottom:1px solid #E8EAF0;background:#F8F9FC;display:flex;align-items:center;}
.set-card-body{padding:16px 20px;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(59,79,228,0.08)!important;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody td{padding:8px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;font-size:0.85rem;}
.pl-section-row td{background:#3B4FE4;color:#fff;font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;padding:10px 14px!important;}
.pl-subtotal-row td{background:#F0F4FF;font-weight:700;font-size:0.85rem;border-top:1px solid #DDE1EC!important;}
.pl-total-row td{background:#1e293b;color:#fff;font-weight:700;font-size:0.95rem;padding:12px 14px!important;}
.pl-total-row.profit td{background:#059669;}
.pl-total-row.loss td{background:#dc2626;}
.pl-sub-type td{background:#F8F9FC;font-size:0.78rem;font-weight:600;color:#64748b;padding:8px 14px 4px!important;border-bottom:none!important;}
.text-green{color:#059669!important;}
@media print{.no-print{display:none!important;}.inv-header-card{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/profit-loss.js') }}?v={{ filemtime(public_path('js/pages/profit-loss.js')) }}"></script>
@endpush
