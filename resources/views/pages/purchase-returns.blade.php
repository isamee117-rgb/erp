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
          <th class="inv-th" class="col-erp-action"></th>
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
    <div class="text-muted" id="paginationInfo" erp-text-sm"></div>
    <ul class="pagination mb-0" id="pagination"></ul>
  </div>
</div>

</div>

<div class="modal modal-blur fade" id="newPReturnModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-repeat me-2"></i>Create Purchase Return</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="pm-field-row">
          <label class="pm-label">Select Purchase Order</label>
          <div class="sdd-wrap" id="poSelect-sdd">
            <div class="sdd-trigger pr-sdd-trigger" id="poSelect-trigger" onclick="sddToggle('poSelect-sdd')">
              <span class="sdd-disp" id="poSelect-disp" class="erp-dropdown-placeholder">-- Select a PO --</span>
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
        <div class="pm-field-row" class="mb-0">
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
.inv-header-card .btn{font-size:0.82rem;font-weight:600;padding:8px 18px;}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;}
.inv-filter-body{padding:12px 16px!important;}
.inv-input{height:36px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;transition:all 0.2s ease;}
.inv-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(59,79,228,0.08)!important;}
.inv-table-card{overflow:hidden;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;}
.inv-table-footer{background:#fff;border-top:1px solid #E8EAF0;padding:10px 16px;}
.pagination .page-link{border-radius:6px!important;margin:0 2px;border:1px solid #E8EAF0;color:#64748b;font-weight:500;font-size:0.8rem;min-width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;transition:all 0.15s ease;}
.pagination .page-item.active .page-link{background:#3B4FE4;border-color:#3B4FE4;color:#fff;}
.pagination .page-link:hover{background:#F5F6FA;border-color:#DDE1EC;color:#1e293b;}
.cursor-pointer{cursor:pointer;}
.badge-pill{font-weight:600;padding:3px 10px;border-radius:20px;font-size:0.72rem;}
.badge-orange{background:rgba(245,158,11,0.1);color:#d97706;}
.pm-modal-content{border-radius:12px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);}
.pm-modal-header{background:linear-gradient(135deg,#3B4FE4 0%,#5B6CF9 100%);padding:16px 24px;border-bottom:none;}
.pm-modal-title{font-size:1rem;font-weight:700;color:#fff;}
.pm-modal-close{background:none;border:none;color:#fff;font-size:1.4rem;line-height:1;opacity:0.8;transition:opacity 0.15s ease;padding:0;cursor:pointer;}
.pm-modal-close:hover{opacity:1;}
.pm-modal-body{background:#F8F9FC;padding:24px;}
.pm-field-row{margin-bottom:16px;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;border-radius:7px!important;border:1px solid #DDE1EC!important;background:#FFFFFF!important;font-size:0.875rem!important;font-weight:500!important;color:#1A1D2E!important;transition:border-color 0.15s ease,box-shadow 0.15s ease;}
.pm-input:focus{border-color:#5B6CF9!important;box-shadow:0 0 0 3px rgba(91,108,249,0.12)!important;}
.pm-textarea{border-radius:7px!important;border:1px solid #DDE1EC!important;background:#FFFFFF!important;font-size:0.875rem!important;color:#1A1D2E!important;}
.pm-textarea:focus{border-color:#5B6CF9!important;box-shadow:0 0 0 3px rgba(91,108,249,0.12)!important;}
.pm-modal-footer{background:#FFFFFF;border-top:1px solid #E8EAF0;padding:14px 24px;display:flex;justify-content:flex-end;gap:10px;}
.pm-btn-cancel{background:none;border:none;color:#6B7280;font-size:0.875rem;font-weight:500;padding:9px 16px;cursor:pointer;transition:color 0.15s ease;}
.pm-btn-cancel:hover{color:#1A1D2E;}
.pm-btn-save{background:linear-gradient(135deg,#3B4FE4,#5B6CF9);border:none;border-radius:7px;padding:9px 22px;font-size:0.875rem;font-weight:600;color:#fff;cursor:pointer;transition:transform 0.15s ease,box-shadow 0.15s ease;}
.pm-btn-save:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(91,108,249,0.35);}

/* ── Modal overflow fix so SDD panel isn't clipped ── */
#newPReturnModal .pm-modal-content { overflow: visible !important; }
#newPReturnModal .pm-modal-header  { border-radius: 12px 12px 0 0; overflow: hidden; }

/* ── Searchable Dropdown (SDD) ──────────────────────────── */
.sdd-wrap { position: relative; }
.pr-sdd-trigger {
  height: 38px; font-size: 0.875rem; font-weight: 500;
  border: 1px solid #DDE1EC; border-radius: 7px; background: #fff;
  width: 100%; padding: 0 10px; cursor: pointer;
  display: flex; align-items: center; justify-content: space-between;
  transition: border-color 0.2s ease, box-shadow 0.2s ease; color: #1A1D2E;
}
.pr-sdd-trigger:hover { border-color: #B0B7C9; }
.sdd-wrap.open .pr-sdd-trigger { border-color: #5B6CF9; box-shadow: 0 0 0 3px rgba(91,108,249,0.12); }
.sdd-caret { font-size: 14px; color: #6B7280; flex-shrink: 0; transition: transform 0.2s ease; }
.sdd-wrap.open .sdd-caret { transform: rotate(180deg); }
.sdd-disp { font-size: 0.875rem; color: #1A1D2E; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sdd-panel {
  display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
  background: #fff; border: 1px solid #DDE1EC; border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 3000; overflow: hidden;
}
.sdd-wrap.open .sdd-panel { display: block; }
.sdd-search-row { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-bottom: 1px solid #F0F2F8; }
.sdd-search-row .ti-search { font-size: 13px; color: #9CA3AF; flex-shrink: 0; }
.sdd-search-inp { border: none; outline: none; font-size: 0.82rem; color: #1A1D2E; flex: 1; background: transparent; }
.sdd-opts-wrap { max-height: 200px; overflow-y: auto; }
.sdd-opt { padding: 8px 12px; font-size: 0.83rem; color: #374151; cursor: pointer; transition: background 0.12s; }
.sdd-opt:hover { background: #F0F4FF; color: #3B4FE4; }
.sdd-no-res { padding: 10px 12px; font-size: 0.82rem; color: #9CA3AF; text-align: center; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/purchase-returns.js') }}?v={{ filemtime(public_path('js/pages/purchase-returns.js')) }}"></script>
@endpush
