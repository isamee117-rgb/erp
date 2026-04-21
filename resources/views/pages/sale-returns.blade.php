@extends('layouts.app')
@section('page-title', 'Sales Returns - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-receipt-refund me-2"></i>Sales Returns</h2>
        <p class="mb-0 inv-subtitle">Credit memos and customer return management.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#newSReturnModal">
          <i class="ti ti-plus me-1"></i>New Return
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="d-flex align-items-center gap-2">
      <div class="flex-grow-1 position-relative">
        <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search"></i></span>
        <input type="text" class="form-control inv-input ps-5" id="searchInput" placeholder="Search by Return # or Customer...">
      </div>
      <div class="inv-toolbar-group">
        <button class="inv-icon-btn" id="sret-filter-toggle-btn" title="Toggle Filters">
          <i class="ti ti-filter"></i>
        </button>
      </div>
    </div>
    <div id="sret-filters-panel" class="d-none mt-2">
      <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
          <input type="date" class="form-control inv-input" id="dateFrom" title="From Date">
        </div>
        <div class="col-6 col-md-3">
          <input type="date" class="form-control inv-input" id="dateTo" title="To Date">
        </div>
        <div class="col-auto">
          <button class="inv-icon-btn" id="sret-clear-filters-btn" title="Clear Filters"><i class="ti ti-x"></i></button>
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
          <th class="inv-th col-erp-action"></th>
          <th class="inv-th">Return #</th>
          <th class="inv-th">Date</th>
          <th class="inv-th">Invoice #</th>
          <th class="inv-th">Customer</th>
          <th class="inv-th">Items</th>
          <th class="inv-th text-end">Total</th>
          <th class="inv-th">Reason</th>
        </tr>
      </thead>
      <tbody id="returnsBody"></tbody>
    </table>
  </div>
  <div class="card-footer inv-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted erp-text-sm" id="paginationInfo"></div>
    <ul class="pagination mb-0" id="pagination"></ul>
  </div>
</div>

</div>

<div class="modal modal-blur fade" id="newSReturnModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content pm-modal-content sdd-overflow">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-receipt-refund me-2"></i>Create Sale Return</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="pm-field-row">
          <label class="pm-label">Select Sales Invoice</label>
          <div class="sdd-wrap" id="saleSelect-sdd">
            <div class="sr-sdd-trigger" id="saleSelect-trigger" onclick="sddToggle('saleSelect-sdd')">
              <span class="sdd-disp erp-dropdown-placeholder" id="saleSelect-disp">-- Select an Invoice --</span>
              <i class="ti ti-chevron-down sdd-caret"></i>
            </div>
            <div class="sdd-panel">
              <div class="sdd-search-row">
                <i class="ti ti-search"></i>
                <input type="text" class="sdd-search-inp" placeholder="Search by invoice no. or customer..." oninput="sddFilterOpts('saleSelect-sdd',this.value)" onclick="event.stopPropagation()">
              </div>
              <div class="sdd-opts-wrap" id="saleSelect-opts"></div>
            </div>
            <input type="hidden" id="saleSelect">
          </div>
        </div>
        <div id="saleItemsContainer" class="d-none">
          <label class="pm-label mb-2">Items to Return</label>
          <div id="saleItemsGrouped"></div>
        </div>
        <div class="pm-field-row mb-0">
          <label class="pm-label">Reason</label>
          <textarea class="form-control pm-textarea" id="returnReason" rows="2" placeholder="Reason for return..."></textarea>
        </div>
      </div>
      <div class="px-3 pb-2 d-none" id="sret-save-error">
        <div class="alert alert-danger py-2 mb-0 small" id="sret-save-error-msg"></div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="submitReturn()"><i class="ti ti-check me-1"></i>Create Return</button>
      </div>
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('js/pages/sale-returns.js') }}?v={{ filemtime(public_path('js/pages/sale-returns.js')) }}"></script>
@endpush
