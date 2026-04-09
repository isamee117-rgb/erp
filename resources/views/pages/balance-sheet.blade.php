@extends('layouts.app')
@section('page-title', 'Balance Sheet - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-scale me-2"></i>Balance Sheet</h2>
        <p class="mb-0 inv-subtitle">Financial position — Assets, Liabilities and Equity at a point in time.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

{{-- Date Filter --}}
<div class="card inv-section-card no-print">
  <div class="set-card-body d-flex gap-3 align-items-end flex-wrap">
    <div>
      <label class="pm-label">As of Date</label>
      <input type="date" class="form-control pm-input" id="bsAsOf" style="min-width:180px;">
    </div>
    <div>
      <button class="btn btn-primary" onclick="loadBS()" style="height:38px;"><i class="ti ti-search me-1"></i>Generate Report</button>
    </div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" onclick="setToday()">Today</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="setMonthEnd()">Month End</button>
      <button class="btn btn-sm btn-outline-secondary" onclick="setYearEnd()">Year End</button>
    </div>
  </div>
</div>

<div id="bsLoading" class="text-center py-5" style="display:none;">
  <div class="spinner-border text-primary"></div>
  <div class="mt-2 text-muted" style="font-size:0.85rem;">Generating report...</div>
</div>

{{-- Report --}}
<div id="bsReport" style="display:none;">
  <div class="row g-3">

    {{-- Assets --}}
    <div class="col-lg-6">
      <div class="card inv-section-card">
        <div class="set-card-header"><i class="ti ti-building-bank me-2 text-blue"></i>Assets</div>
        <table class="table table-vcenter inv-table mb-0">
          <tbody id="bsAssetsBody"></tbody>
          <tfoot>
            <tr class="bs-total-row">
              <td>Total Assets</td>
              <td class="text-end" id="bsTotalAssets">—</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- Liabilities + Equity --}}
    <div class="col-lg-6">
      <div class="card inv-section-card">
        <div class="set-card-header"><i class="ti ti-receipt me-2 text-orange"></i>Liabilities &amp; Equity</div>
        <table class="table table-vcenter inv-table mb-0">
          <tbody id="bsLiabEquityBody"></tbody>
          <tfoot>
            <tr class="bs-total-row">
              <td>Total Liabilities &amp; Equity</td>
              <td class="text-end" id="bsTotalLiabEquity">—</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>

  {{-- Balance Check --}}
  <div id="bsBalanceCheck" class="card inv-section-card">
    <div class="set-card-body"></div>
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
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;overflow:hidden;}
.set-card-header{padding:14px 20px;font-size:0.85rem;font-weight:700;color:#1e293b;border-bottom:1px solid #E8EAF0;background:#F8F9FC;display:flex;align-items:center;}
.set-card-body{padding:16px 20px;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(59,79,228,0.08)!important;}
.inv-table tbody td{padding:8px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;font-size:0.85rem;}
.bs-section-row td{background:#F0F4FF;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#3B4FE4;padding:8px 14px!important;}
.bs-sub-type td{background:#F8F9FC;font-size:0.75rem;font-weight:600;color:#64748b;padding:6px 14px 3px!important;border-bottom:none!important;}
.bs-subtotal-row td{background:#F8F9FC;font-weight:700;font-size:0.82rem;border-top:1px solid #DDE1EC!important;padding:8px 14px!important;}
.bs-total-row td{background:#1e293b;color:#fff;font-weight:700;font-size:0.9rem;padding:10px 14px!important;}
.text-blue{color:#3B4FE4!important;}.text-orange{color:#ea580c!important;}
.bs-balanced{background:rgba(5,150,105,0.08);color:#059669;padding:10px 14px;font-size:0.85rem;font-weight:600;border-radius:6px;}
.bs-unbalanced{background:rgba(220,38,38,0.08);color:#dc2626;padding:10px 14px;font-size:0.85rem;font-weight:600;border-radius:6px;}
@media print{.no-print{display:none!important;}.inv-header-card{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/balance-sheet.js') }}?v={{ filemtime(public_path('js/pages/balance-sheet.js')) }}"></script>
@endpush
