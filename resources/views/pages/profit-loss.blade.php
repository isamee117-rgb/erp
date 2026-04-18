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
@push('scripts')
<script src="{{ asset('js/pages/profit-loss.js') }}?v={{ filemtime(public_path('js/pages/profit-loss.js')) }}"></script>
@endpush
