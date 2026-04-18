@extends('layouts.app')
@section('page-title', 'Purchase Returns - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-repeat me-2"></i>Purchase Returns</h2>
        <p class="mb-0 inv-subtitle">Debit memos and vendor return management.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#newPReturnModal"><i class="ti ti-plus me-1"></i>New Return</button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-4">
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search" class="erp-icon-sm"></i></span>
          <input type="text" class="form-control inv-input ps-5" id="searchInput" placeholder="Search returns...">
        </div>
      </div>
      <div class="col-6 col-md-3"><input type="date" class="form-control inv-input" id="dateFrom"></div>
      <div class="col-6 col-md-3"><input type="date" class="form-control inv-input" id="dateTo"></div>
      <div class="col-auto ms-md-auto">
        <button class="btn btn-light inv-input px-3" onclick="clearFilters()"><i class="ti ti-x me-1"></i>Clear</button>
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
          <th class="inv-th">Original PO #</th>
          <th class="inv-th">Vendor</th>
          <th class="inv-th">Items</th>
          <th class="inv-th">Total</th>
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

<div class="modal modal-blur fade" id="newPReturnModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content pm-modal-content sdd-overflow">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-repeat me-2"></i>Create Purchase Return</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="pm-field-row">
          <label class="pm-label">Select Purchase Order</label>
          <div class="sdd-wrap" id="poSelect-sdd">
            <div class="sr-sdd-trigger" id="poSelect-trigger" onclick="sddToggle('poSelect-sdd')">
              <span class="sdd-disp erp-dropdown-placeholder" id="poSelect-disp">-- Select a PO --</span>
              <i class="ti ti-chevron-down sdd-caret"></i>
            </div>
            <div class="sdd-panel">
              <div class="sdd-search-row">
                <i class="ti ti-search"></i>
                <input type="text" class="sdd-search-inp" placeholder="Search by PO no. or vendor..." oninput="sddFilterOpts('poSelect-sdd',this.value)" onclick="event.stopPropagation()">
              </div>
              <div class="sdd-opts-wrap" id="poSelect-opts"></div>
            </div>
            <input type="hidden" id="poSelect">
          </div>
        </div>
        <div id="poItemsContainer" class="d-none">
          <div class="pm-field-row">
            <label class="pm-label">Items to Return</label>
            <div class="table-responsive">
              <table class="table table-sm inv-table mb-0">
                <thead><tr><th class="inv-th">Product</th><th class="inv-th">Received Qty</th><th class="inv-th">Return Qty</th><th class="inv-th">Cost</th></tr></thead>
                <tbody id="poItemsBody"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="pm-field-row mb-0">
          <label class="pm-label">Reason</label>
          <textarea class="form-control pm-textarea" id="returnReason" rows="2"></textarea>
        </div>
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
<script src="{{ asset('js/pages/purchase-returns.js') }}?v={{ filemtime(public_path('js/pages/purchase-returns.js')) }}"></script>
@endpush
