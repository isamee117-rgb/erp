@extends('layouts.app')
@section('page-title', 'Journal Entries - LeanERP')
@section('content')

{{-- Delete Confirm Overlay --}}
<div id="jeDeleteConfirm" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm"><i class="ti ti-trash"></i></div>
      <div class="ms-title">Delete Journal Entry?</div>
      <div class="ms-sub">Only draft entries can be deleted. This action cannot be undone.</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="cancelJeDelete()">Cancel</button>
      <button class="ms-btn-confirm" onclick="doJeDelete()">Yes, Delete</button>
    </div>
  </div>
</div>

{{-- View Lines Modal --}}
<div class="modal modal-blur fade" id="jeViewModal" tabindex="-1" aria-labelledby="jeViewModalLabel" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="jeViewModalLabel"><i class="ti ti-notebook me-2"></i>Journal Entry</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body" id="jeViewBody"></div>
      <div class="modal-footer pm-modal-footer">
        <button type="button" class="pm-btn-cancel" data-bs-dismiss="modal">Close</button>
        <button type="button" class="pm-btn-save" id="jePostBtn" onclick="postCurrentJe()" style="display:none;"><i class="ti ti-check me-1"></i>Post Entry</button>
      </div>
    </div>
  </div>
</div>

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-notebook me-2"></i>Journal Entries</h2>
        <p class="mb-0 inv-subtitle">Double-entry ledger — all financial transactions recorded here.</p>
      </div>
      <div class="col-auto">
        <a href="{{ rtrim(url('/'), '/') }}/accounting/journals/create" class="btn btn-light shadow-sm"><i class="ti ti-plus me-1"></i>New Entry</a>
      </div>
    </div>
  </div>
</div>

{{-- Filters --}}
<div class="card inv-section-card">
  <div class="set-card-body d-flex gap-3 flex-wrap align-items-end">
    <div>
      <label class="pm-label">From Date</label>
      <input type="date" class="form-control pm-input" id="jeFrom" style="min-width:150px;">
    </div>
    <div>
      <label class="pm-label">To Date</label>
      <input type="date" class="form-control pm-input" id="jeTo" style="min-width:150px;">
    </div>
    <div>
      <label class="pm-label">Type</label>
      <select class="form-select pm-input" id="jeType" style="min-width:140px;">
        <option value="">All Types</option>
        <option value="sale">Sale</option>
        <option value="sale_return">Sale Return</option>
        <option value="purchase">Purchase</option>
        <option value="purchase_return">Purchase Return</option>
        <option value="payment">Payment</option>
        <option value="manual">Manual</option>
      </select>
    </div>
    <div>
      <label class="pm-label">Status</label>
      <select class="form-select pm-input" id="jeStatus" style="min-width:120px;">
        <option value="">All</option>
        <option value="posted">Posted</option>
        <option value="draft">Draft</option>
      </select>
    </div>
    <div>
      <button class="btn btn-light shadow-sm" onclick="loadJournals()" style="height:38px;"><i class="ti ti-search me-1"></i>Search</button>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th inv-th-sort" id="jeThEntryNo" onclick="toggleJeSort('entry_no')" style="cursor:pointer;user-select:none;">Entry No. <i class="ti ti-arrows-sort" style="font-size:0.7rem;opacity:0.4;"></i></th>
          <th class="inv-th">Date</th>
          <th class="inv-th">Description</th>
          <th class="inv-th">Type</th>
          <th class="inv-th">Debit</th>
          <th class="inv-th">Credit</th>
          <th class="inv-th">Status</th>
          <th class="inv-th">Actions</th>
        </tr>
      </thead>
      <tbody id="jeBody"></tbody>
    </table>
  </div>
  <div class="card-footer pty-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted" id="jePaginationInfo" style="font-size:0.82rem;"></div>
    <ul class="pagination mb-0" id="jePagination"></ul>
  </div>
</div>

</div>
@endsection
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{--inv-primary:#CD0000;--inv-font:'Inter',sans-serif;}
.page-body,.page-wrapper{font-family:var(--inv-font);font-size:14px;background:#F5F6FA!important;}
.inv-page-wrap{display:flex;flex-direction:column;gap:16px;}
.inv-header-card{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);border:none;border-radius:10px;overflow:hidden;position:relative;}
.inv-header-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.5;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;overflow:hidden;}
.inv-table-card{overflow:hidden;}
.set-card-body{padding:16px 20px;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-th-sort:hover{color:#CD0000;background:#FFF5F5;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;font-size:0.85rem;}
.badge-pill{font-weight:600;padding:3px 10px;border-radius:20px;font-size:0.72rem;}
.badge-green{background:rgba(16,185,129,0.1);color:#059669;}
.badge-gray{background:rgba(100,116,139,0.1);color:#64748b;}
.badge-blue{background:rgba(205,0,0,0.1);color:#CD0000;}
.badge-orange{background:rgba(249,115,22,0.1);color:#ea580c;}
.btn-icon-sm{width:28px;height:28px;padding:0;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;border:1px solid #E8EAF0;background:#fff;cursor:pointer;transition:all 0.15s;}
.btn-icon-sm:hover{background:#F5F7FF;border-color:#CD0000;color:#CD0000;}
.btn-icon-sm.danger:hover{background:#FFF5F5;border-color:#dc2626;color:#dc2626;}
.je-lines-table{width:100%;border-collapse:collapse;font-size:0.82rem;}
.je-lines-table th{background:#F8F9FC;padding:8px 12px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;border-bottom:1px solid #E8EAF0;}
.je-lines-table td{padding:8px 12px;border-bottom:1px solid #F0F2F8;vertical-align:middle;}
.je-lines-table tfoot td{padding:8px 12px;font-weight:700;background:#F8F9FC;border-top:2px solid #E8EAF0;}
.ms-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9999;display:flex;align-items:center;justify-content:center;}
.ms-overlay.d-none{display:none!important;}
.ms-box{background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.18);width:100%;max-width:380px;overflow:hidden;animation:msIn 0.18s ease;}
@keyframes msIn{from{opacity:0;transform:scale(0.93);}to{opacity:1;transform:scale(1);}}
.ms-body{padding:32px 28px 20px;text-align:center;}
.ms-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;}
.ms-icon-confirm{background:rgba(220,38,38,0.1);color:#dc2626;}
.ms-title{font-size:1.05rem;font-weight:700;color:#1e293b;margin-bottom:8px;}
.ms-sub{font-size:0.82rem;color:#64748b;line-height:1.5;}
.ms-footer{padding:0 28px 24px;display:flex;gap:10px;justify-content:center;}
.ms-btn-cancel{flex:1;padding:9px 0;border:1px solid #DDE1EC;border-radius:8px;background:#fff;font-size:0.85rem;font-weight:600;color:#64748b;cursor:pointer;}
.ms-btn-confirm{flex:1;padding:9px 0;border:none;border-radius:8px;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;}
.pm-modal-content{border-radius:12px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);}
.pm-modal-header{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);padding:16px 24px;border-bottom:none;}
.pm-modal-title{font-size:1rem;font-weight:700;color:#fff;}
.pm-modal-close{background:none;border:none;color:#fff;font-size:1.4rem;line-height:1;opacity:0.8;transition:opacity 0.15s ease;padding:0;cursor:pointer;}
.pm-modal-close:hover{opacity:1;}
.pm-modal-body{background:#F8F9FC;padding:24px;}
.pm-modal-footer{background:#fff;border-top:1px solid #E8EAF0;padding:14px 24px;display:flex;justify-content:flex-end;gap:10px;}
.pm-btn-cancel{background:none;border:none;color:#6B7280;font-size:0.875rem;font-weight:500;padding:8px 16px;border-radius:7px;cursor:pointer;transition:background 0.15s;}
.pm-btn-cancel:hover{background:#F1F3F9;}
.pm-btn-save{background:linear-gradient(135deg,#CD0000,#e53333);border:none;color:#fff;font-size:0.875rem;font-weight:600;padding:8px 20px;border-radius:7px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:opacity 0.15s;}
.pm-btn-save:hover{opacity:0.9;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/journal-entries.js') }}?v={{ filemtime(public_path('js/pages/journal-entries.js')) }}"></script>
@endpush
