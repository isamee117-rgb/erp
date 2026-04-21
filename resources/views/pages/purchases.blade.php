@extends('layouts.app')
@section('page-title', 'Purchases - LeanERP')
@section('content')
<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title">Purchase Orders</h2>
        <p class="mb-0 inv-subtitle">Manage vendor orders and receiving of goods.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" onclick="openNewPOModal()"><i class="ti ti-plus me-1"></i>New Purchase Order</button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="d-flex align-items-center gap-2">
      <div class="flex-grow-1 position-relative">
        <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search"></i></span>
        <input type="text" class="form-control inv-input ps-5" id="po-search" placeholder="Search by PO ID or Vendor...">
      </div>
      <div class="inv-toolbar-group">
        <button class="inv-icon-btn" id="po-filter-toggle-btn" title="Toggle Filters">
          <i class="ti ti-filter"></i>
        </button>
      </div>
    </div>
    <div id="po-filters-panel" class="d-none mt-2">
      <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
          <select class="form-select inv-input" id="po-status">
            <option value="all">All Status</option>
            <option value="Draft">Draft</option>
            <option value="Partially Received">Partially Received</option>
            <option value="Received">Received</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <select class="form-select inv-input" id="po-vendor">
            <option value="all">All Vendors</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <input type="date" class="form-control inv-input" id="po-date-from" title="From Date">
        </div>
        <div class="col-6 col-md-2">
          <input type="date" class="form-control inv-input" id="po-date-to" title="To Date">
        </div>
        <div class="col-auto">
          <button class="inv-icon-btn" id="po-clear-filters-btn" title="Clear Filters"><i class="ti ti-x"></i></button>
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
          <th class="inv-th col-erp-expand"></th>
          <th class="inv-th cursor-pointer" onclick="poSortToggle('id')">PO # <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th cursor-pointer" onclick="poSortToggle('createdAt')">Date <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th">Vendor</th>
          <th class="inv-th text-center">Items</th>
          <th class="inv-th text-end cursor-pointer" onclick="poSortToggle('totalAmount')">Total <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th">Status</th>
          <th class="inv-th text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="po-tbody"></tbody>
    </table>
  </div>
  <div class="card-footer inv-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted erp-text-sm" id="po-info"></div>
    <ul class="pagination mb-0" id="po-pagination"></ul>
  </div>
</div>

</div>

{{-- Confirm Overlay --}}
<div class="ms-overlay d-none" id="poConfirmOverlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm" id="confirmIcon"><i class="ti ti-device-floppy"></i></div>
      <div class="ms-title" id="confirmTitle">Confirm</div>
      <p class="ms-sub" id="confirmMessage"></p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" id="confirmCancelBtn">Cancel</button>
      <button class="ms-btn-confirm" id="confirmOkBtn"><i class="ti ti-device-floppy me-1" id="confirmOkIcon"></i><span id="confirmOkLabel">Yes, Save</span></button>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="newPOModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-680">
    <div class="modal-content pm-modal-content sdd-overflow">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-shopping-cart me-2"></i>New Purchase Order</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="row g-3 mb-3">
          <div class="col-7">
            <label class="pm-label">Vendor <span class="text-danger">*</span></label>
            <div class="sdd-wrap" id="npo-vendor-sdd">
              <div class="sr-sdd-trigger" onclick="sddToggle('npo-vendor-sdd')">
                <span class="sdd-disp erp-dropdown-placeholder" id="npo-vendor-disp">Select Vendor...</span>
                <i class="ti ti-chevron-down sdd-caret"></i>
              </div>
              <div class="sdd-panel">
                <div class="sdd-search-row">
                  <i class="ti ti-search"></i>
                  <input type="text" class="sdd-search-inp" placeholder="Search vendor..." oninput="sddFilterOpts('npo-vendor-sdd',this.value)" onclick="event.stopPropagation()">
                </div>
                <div class="sdd-opts-wrap" id="npo-vendor-opts"></div>
              </div>
              <input type="hidden" id="npo-vendor">
            </div>
            <div class="text-danger small mt-1 d-none" id="npo-vendor-error">Please select a vendor.</div>
          </div>
          <div class="col-5">
            <label class="pm-label"><i class="ti ti-calendar me-1"></i>Order Date</label>
            <input type="date" class="form-control pm-input" id="npo-date">
          </div>
        </div>
        <div class="pb-2 mb-2">
          <span class="erp-table-col-header">Order Items</span>
        </div>
        <div class="mb-3">
          <div class="d-flex gap-2 align-items-center">
            <div class="position-relative flex-fill">
              <span class="position-absolute top-50 translate-middle-y ms-2" data-class="erp-icon-disabled"><i class="ti ti-barcode" class="erp-icon-md"></i></span>
              <input type="text" class="form-control pm-input ps-5" id="npo-barcode" placeholder="Scan barcode or type Item No. and press Enter..." onkeydown="npoScanProduct(event)" autocomplete="off">
            </div>
            <button type="button" class="bc-cam-btn" style="width:38px;height:38px;" onclick="openBarcodeScanner(function(c){document.getElementById('npo-barcode').value=c;npoScanProduct({key:'Enter',preventDefault:function(){}});})" title="Scan with camera"><i class="ti ti-camera" class="erp-icon-md"></i></button>
          </div>
          <div id="npo-scan-feedback" class="erp-feedback-inline"></div>
        </div>
        <div class="text-end mb-2">
          <button type="button" class="inv-action-btn" style="width:auto;padding:0 10px;font-size:0.78rem;color:#CD0000" onclick="addPOItemRow()"><i class="ti ti-plus me-1"></i>Add Row</button>
        </div>
        <table class="table table-sm mb-0" style="table-layout:fixed;">
          <thead><tr>
            <th class="po-th-col" style="width:36px;">#</th>
            <th class="po-th-col">Product</th>
            <th class="po-th-col" style="width:90px;">UOM</th>
            <th class="po-th-col" style="width:80px;">Qty</th>
            <th class="po-th-col" style="width:110px;">Unit Cost</th>
            <th class="po-th-col" style="width:110px;">Line Total</th>
            <th class="po-th-act" style="width:36px;"></th>
          </tr></thead>
          <tbody id="npo-items"></tbody>
        </table>
        <div class="text-danger small mt-1 d-none" id="npo-items-error">Add at least one product.</div>
        <div class="text-end mt-3"><span class="erp-text-82 text-muted">Estimated Total: </span><span class="fs-5 fw-bold text-erp-primary" id="npo-total">0.00</span></div>
      </div>
      <div class="px-3 pb-2 d-none" id="npo-save-error">
        <div class="alert alert-danger py-2 mb-0 small" id="npo-save-error-msg"></div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="createPO()"><i class="ti ti-device-floppy me-1"></i>Save Purchase Order</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="receiveModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-680">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <div>
          <h5 class="modal-title pm-modal-title"><i class="ti ti-package-import me-2"></i>Receive Goods</h5>
          <div class="recv-po-sub" id="recv-po-id"></div>
        </div>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="row mb-3">
          <div class="col-5">
            <label class="pm-label">Receive Date</label>
            <input type="date" class="form-control pm-input" id="recv-date">
          </div>
        </div>
        <table class="table table-sm mb-0">
          <thead><tr>
            <th class="po-th-col" style="width:36px;">#</th>
            <th class="po-th-col">Product</th>
            <th class="po-th-col text-center">Ordered</th>
            <th class="po-th-col text-center">Received</th>
            <th class="po-th-col text-center">Remaining</th>
            <th class="po-th-col" style="width:120px;">Receive Qty</th>
          </tr></thead>
          <tbody id="recv-items"></tbody>
        </table>
        <div class="pm-field-row mt-3 mb-0">
          <label class="pm-label">Notes (optional)</label>
          <textarea class="form-control pm-input pm-textarea" id="recv-notes" rows="2" placeholder="Add any notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" id="recv-submit" onclick="submitReceive()"><i class="ti ti-check me-1"></i>Receive Goods</button>
      </div>
    </div>
  </div>
</div>
{{-- Receipts Panel Overlay --}}
<div class="ms-overlay d-none" id="poReceiptsOverlay" onclick="if(event.target===this)closeReceiptsPanel()">
  <div class="ms-box" style="max-width:680px;width:100%;">
    <div class="ms-body" style="text-align:left;padding:0;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 12px;">
        <div>
          <div class="ms-title" style="margin-bottom:2px;">Goods Receipts</div>
          <div style="font-size:0.78rem;color:#6B7280;" id="receipts-po-sub"></div>
        </div>
        <button onclick="closeReceiptsPanel()" style="background:none;border:none;font-size:1.3rem;color:#6B7280;cursor:pointer;line-height:1;">&times;</button>
      </div>
      <div style="max-height:420px;overflow-y:auto;padding:0 24px 20px;" id="receipts-list"></div>
    </div>
  </div>
</div>

@endsection


@push('scripts')
<script src="{{ asset('js/pages/purchases.js') }}?v={{ filemtime(public_path('js/pages/purchases.js')) }}"></script>
@endpush
