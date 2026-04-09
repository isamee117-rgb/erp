@extends('layouts.app')
@section('page-title', 'Purchases - LeanERP')
@section('content')
<div class="pg-wrap">

<div class="card pg-header-card">
  <div class="card-body pg-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 pg-title">Purchase Orders</h2>
        <p class="mb-0 pg-subtitle">Manage vendor orders and receiving of goods.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" onclick="openNewPOModal()"><i class="ti ti-plus me-1"></i>New Purchase Order</button>
      </div>
    </div>
  </div>
</div>

<div class="card pg-section-card pg-filter-bar">
  <div class="card-body pg-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-4">
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search" class="erp-icon-sm"></i></span>
          <input type="text" class="form-control pg-input ps-5" id="po-search" placeholder="Search by PO ID or Vendor..." oninput="poCurrentPage=1;renderPage();">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select pg-input" id="po-status" onchange="poCurrentPage=1;renderPage();">
          <option value="all">All Status</option><option value="Draft">Draft</option><option value="Partially Received">Partially Received</option><option value="Received">Received</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select pg-input" id="po-vendor" onchange="poCurrentPage=1;renderPage();">
          <option value="all">All Vendors</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input type="date" class="form-control pg-input" id="po-date-from" title="From Date" onchange="poCurrentPage=1;renderPage();">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" class="form-control pg-input" id="po-date-to" title="To Date" onchange="poCurrentPage=1;renderPage();">
      </div>
    </div>
  </div>
</div>

<div class="card pg-section-card pg-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter pg-table mb-0">
      <thead>
        <tr>
          <th class="pg-th" class="col-erp-expand"></th>
          <th class="pg-th cursor-pointer" onclick="poSortToggle('id')">PO # <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="pg-th cursor-pointer" onclick="poSortToggle('createdAt')">Date <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="pg-th">Vendor</th>
          <th class="pg-th text-center">Items</th>
          <th class="pg-th text-end cursor-pointer" onclick="poSortToggle('totalAmount')">Total <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="pg-th">Status</th>
          <th class="pg-th text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="po-tbody"></tbody>
    </table>
  </div>
  <div class="card-footer pg-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted" id="po-info" erp-text-sm"></div>
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
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-shopping-cart me-2"></i>New Purchase Order</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="row g-3 mb-3">
          <div class="col-7">
            <label class="pm-label">Vendor <span class="text-danger">*</span></label>
            <div class="sdd-wrap" id="npo-vendor-sdd">
              <div class="sdd-trigger pm-input" onclick="sddToggle('npo-vendor-sdd')">
                <span class="sdd-disp" id="npo-vendor-disp" class="erp-dropdown-placeholder">Select Vendor...</span>
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
          </div>
          <div class="col-5">
            <label class="pm-label"><i class="ti ti-calendar me-1"></i>Order Date</label>
            <input type="date" class="form-control pm-input" id="npo-date">
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center pb-2 mb-2" class="erp-divider-bottom">
          <span class="erp-table-col-header">Order Items</span>
          <button class="pg-action-btn" style="width:auto;padding:0 8px;font-size:0.78rem;color:#3B4FE4" onclick="addPOItemRow()"><i class="ti ti-plus me-1"></i>Add Row</button>
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
        <table class="table table-sm mb-0">
          <thead><tr>
            <th class="erp-table-col-header">Product</th>
            <th class="po-th-col" style="width:90px;">Qty</th>
            <th class="po-th-col" style="width:120px;">Unit Cost</th>
            <th class="po-th-act" style="width:36px;"></th>
          </tr></thead>
          <tbody id="npo-items"></tbody>
        </table>
        <div class="text-end mt-3"><span class="erp-text-82 text-muted">Estimated Total: </span><span class="fs-5 fw-bold text-erp-primary" id="npo-total">0.00</span></div>
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
        <table class="table table-sm mb-0">
          <thead><tr>
            <th class="erp-table-col-header">Product</th>
            <th class="text-center" class="erp-table-col-header">Ordered</th>
            <th class="text-center" class="erp-table-col-header">Received</th>
            <th class="text-center" class="erp-table-col-header">Remaining</th>
            <th class="po-th-col" style="width:110px;">Receive Qty</th>
          </tr></thead>
          <tbody id="recv-items"></tbody>
        </table>
        <div class="pm-field-row mt-3" class="mb-0">
          <label class="pm-label">Notes (optional)</label>
          <textarea class="form-control pm-input" id="recv-notes" rows="2" class="erp-textarea-auto" placeholder="Add any notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" id="recv-submit" onclick="submitReceive()" class="btn-erp-success-gradient"><i class="ti ti-check me-1"></i>Receive Goods</button>
      </div>
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
.pg-action-btn.pg-action-success:hover { color: #10B981; background: rgba(16,185,129,0.08); }
.pg-badge { font-size: 0.72rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; letter-spacing: 0.03em; }
.pg-badge-draft { background: rgba(245,158,11,0.1); color: #D97706; }
.pg-badge-partial { background: rgba(59,130,246,0.1); color: #2563EB; }
.pg-badge-received { background: rgba(16,185,129,0.1); color: #059669; }
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

.pm-modal-content { border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.pm-modal-header { background: linear-gradient(135deg, #3B4FE4 0%, #5B6CF9 100%); padding: 16px 24px; border-bottom: none; }
.pm-modal-title { font-size: 1rem; font-weight: 700; color: #fff; }
.pm-modal-close { background: none; border: none; color: #fff; font-size: 1.4rem; line-height: 1; opacity: 0.8; transition: opacity 0.15s ease; padding: 0; cursor: pointer; }
.pm-modal-close:hover { opacity: 1; }
.pm-modal-body { background: #F8F9FC; padding: 24px; }
.pm-field-row { margin-bottom: 16px; }
.pm-label { display: block; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; margin-bottom: 6px; }
.pm-input { height: 38px !important; border-radius: 7px !important; border: 1px solid #DDE1EC !important; background: #FFFFFF !important; font-size: 0.875rem !important; font-weight: 500 !important; color: #1A1D2E !important; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
.pm-input::placeholder { color: #B0B7C9 !important; font-weight: 400 !important; }
.pm-input:focus { border-color: #5B6CF9 !important; box-shadow: 0 0 0 3px rgba(91,108,249,0.12) !important; }
.pm-modal-footer { background: #FFFFFF; border-top: 1px solid #E8EAF0; padding: 14px 24px; display: flex; justify-content: flex-end; gap: 10px; }
.pm-btn-cancel { background: none; border: none; color: #6B7280; font-size: 0.875rem; font-weight: 500; padding: 9px 16px; cursor: pointer; transition: color 0.15s ease; }
.pm-btn-cancel:hover { color: #1A1D2E; }
.pm-btn-save { background: linear-gradient(135deg, #3B4FE4, #5B6CF9); border: none; border-radius: 7px; padding: 9px 22px; font-size: 0.875rem; font-weight: 600; color: #fff; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
.pm-btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(91,108,249,0.35); }

/* ── Confirm Overlay ───────────────────────────────────── */
.ms-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;}
.ms-box{background:#fff;border-radius:14px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.18);overflow:hidden;animation:msIn .18s ease;}
@keyframes msIn{from{transform:scale(0.92);opacity:0}to{transform:scale(1);opacity:1}}
.ms-body{padding:28px 28px 20px;text-align:center;}
.ms-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:1.6rem;}
.ms-icon-confirm{background:#EEF2FF;color:#3B4FE4;}
.ms-icon-success{background:#ECFDF5;color:#10B981;}
.ms-title{font-size:1rem;font-weight:700;color:#111827;margin:14px 0 6px;}
.ms-sub{font-size:0.83rem;color:#6B7280;margin:0;}
.ms-footer{padding:16px 24px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #F3F4F6;}
.ms-btn-cancel{height:36px;padding:0 18px;border:1px solid #DDE1EC;border-radius:7px;background:#fff;color:#374151;font-size:0.83rem;font-weight:600;cursor:pointer;}
.ms-btn-confirm{height:36px;padding:0 18px;border:none;border-radius:7px;background:linear-gradient(135deg,#3B4FE4,#5B6CF9);color:#fff;font-size:0.83rem;font-weight:600;cursor:pointer;}
.ms-btn-ok{height:36px;padding:0 28px;border:none;border-radius:7px;background:linear-gradient(135deg,#10B981,#34D399);color:#fff;font-size:0.83rem;font-weight:600;cursor:pointer;}

/* ── Searchable Dropdown (SDD) ─────────────────────────── */
#newPOModal .pm-modal-content { overflow: visible; }
#newPOModal .pm-modal-header  { border-radius: 12px 12px 0 0; overflow: hidden; }
.sdd-wrap    { position: relative; }
.sdd-trigger { display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 0 10px !important; height: 38px !important; user-select: none; }
.sdd-caret   { color: #B0B7C9; font-size: 14px; flex-shrink: 0; transition: transform 0.18s; }
.sdd-wrap.open .sdd-caret { transform: rotate(180deg); }
.sdd-panel   { display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 2000;
               background: #fff; border: 1px solid #DDE1EC; border-radius: 8px;
               box-shadow: 0 8px 24px rgba(0,0,0,0.12); overflow: hidden; }
.sdd-wrap.open .sdd-panel { display: block; }
.sdd-search-row { display: flex; align-items: center; gap: 6px; padding: 8px 10px; border-bottom: 1px solid #F0F2F8; color: #B0B7C9; }
.sdd-search-inp { border: none; outline: none; flex: 1; font-size: 0.85rem; background: transparent; color: #1A1D2E; }
.sdd-opts-wrap  { max-height: 180px; overflow-y: auto; }
.sdd-opt { padding: 8px 12px; font-size: 0.85rem; color: #374151; cursor: pointer; transition: background 0.12s; }
.sdd-opt:hover  { background: #F5F7FF; color: #3B4FE4; }
.sdd-opt.sdd-selected { background: #EEF0F8; color: #3B4FE4; font-weight: 600; }
.sdd-no-res { padding: 10px 12px; font-size: 0.83rem; color: #9CA3AF; text-align: center; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/purchases.js') }}?v={{ filemtime(public_path('js/pages/purchases.js')) }}"></script>
@endpush
