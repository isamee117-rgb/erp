@extends('layouts.app')
@section('page-title', 'Report Builder - LeanERP')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-xl">
  <div class="page-header mb-3">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="page-title">Report Builder</h2>
        <div class="text-muted mt-1">Map your Chart of Accounts to P&amp;L and Balance Sheet report lines</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header p-0 border-bottom">
      <ul class="nav nav-tabs card-header-tabs ms-0" id="rbTabs">
        <li class="nav-item">
          <button class="nav-link active px-4" id="rb-tab-pl" onclick="rbSwitchTab('profit_loss')">
            <i class="ti ti-chart-bar me-1"></i> Profit &amp; Loss
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link px-4" id="rb-tab-bs" onclick="rbSwitchTab('balance_sheet')">
            <i class="ti ti-scale me-1"></i> Balance Sheet
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body">
      <div id="rb-info-banner" class="alert alert-info mb-3" role="alert">
        <i class="ti ti-info-circle me-1"></i>
        Map your Chart of Accounts to each report line. One account can only be assigned to one line per report.
        Save when done — reports will use these mappings immediately.
      </div>

      <div id="rb-loading" class="text-center py-5 d-none">
        <div class="spinner-border text-primary"></div>
        <div class="mt-2 text-muted">Loading configuration…</div>
      </div>

      <div id="rb-content" class="d-none">
        <table class="table table-bordered align-middle mb-3">
          <thead class="table-light">
            <tr>
              <th style="width:28%">Report Line</th>
              <th>Mapped Accounts <span class="text-muted fw-normal fst-italic">(search by code or name)</span></th>
            </tr>
          </thead>
          <tbody id="rb-tbody"></tbody>
        </table>

        <div id="rb-unmapped-warning" class="alert alert-warning d-none mb-3">
          <i class="ti ti-alert-triangle me-1"></i>
          <strong>Unmapped accounts:</strong>
          <span id="rb-unmapped-list"></span>
          <span class="text-muted ms-2">— these will appear as a warning at the bottom of the report</span>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <button class="btn btn-outline-secondary" onclick="rbReset()">
            <i class="ti ti-refresh me-1"></i> Reset
          </button>
          <button class="btn btn-primary" id="rb-save-btn" onclick="rbSave()" disabled>
            <i class="ti ti-device-floppy me-1"></i> Save Mapping
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('dist/libs/tom-select/dist/js/tom-select.complete.min.js') }}"></script>
<script src="{{ asset('js/pages/report-builder.js') }}?v={{ filemtime(public_path('js/pages/report-builder.js')) }}"></script>
@endpush
