@extends('layouts.app')
@section('page-title', ($type ?? 'Party') . ' Master - LeanERP')
@section('content')
<div id="party-type" data-type="{{ $type ?? 'Customer' }}"></div>

<div class="pty-page-wrap">

<div class="card pty-header-card">
  <div class="card-body pty-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 pty-title"><span id="pageTitle">{{ $type ?? 'Party' }} Master</span></h2>
        <p class="mb-0 pty-subtitle">Manage contacts, credit limits, and payment terms.</p>
      </div>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-light shadow-sm" id="pty-sel-toggle-btn" onclick="togglePtySelectMode()" title="Multi-select mode">
          <i class="ti ti-checkbox me-1"></i>Select
        </button>
        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#partyModal" onclick="openAddModal()">
          <i class="ti ti-plus me-1"></i><span id="addBtnLabel">Add {{ $type ?? 'Party' }}</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card pty-section-card pty-filter-bar">
  <div class="card-body pty-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search" class="erp-icon-sm"></i></span>
          <input type="text" class="form-control pty-input ps-5" id="searchInput" placeholder="Search by name, code, phone...">
        </div>
      </div>
      <div class="col-6 col-md-3">
        <select class="form-select pty-input" id="entityTypeFilter"><option value="">All Entity Types</option></select>
      </div>
      <div class="col-auto">
        <button class="btn btn-sm" class="btn btn-sm btn-erp-clear" onclick="clearFilters()"><i class="ti ti-x me-1"></i>Clear</button>
      </div>
    </div>
  </div>
</div>

<div id="pty-bulk-bar" class="pty-bulk-bar d-none">
  <div class="d-flex align-items-center gap-3">
    <span class="pty-bulk-count"><i class="ti ti-checkbox me-1"></i><span id="pty-sel-count">0</span> record(s) selected</span>
    <button class="pty-bulk-del-btn" onclick="confirmDeleteSelectedParties()"><i class="ti ti-trash me-1"></i>Delete Selected</button>
    <button class="pty-bulk-clear-btn" onclick="clearPartySelection()"><i class="ti ti-x me-1"></i>Clear Selection</button>
  </div>
</div>

<div class="card pty-section-card pty-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter pty-table mb-0">
      <thead>
        <tr>
          <th class="pty-th pty-chk-col" class="col-erp-checkbox"><input type="checkbox" class="pty-chk" id="pty-select-all" onclick="toggleSelectAllParties(this)" title="Select all"></th>
          <th class="pty-th">Code</th>
          <th class="pty-th">Name</th>
          <th class="pty-th">Phone</th>
          <th class="pty-th">Email</th>
          <th class="pty-th">Entity Type</th>
          <th class="pty-th">Category</th>
          <th class="text-end pty-th">Balance</th>
          <th class="text-end pty-th">Credit Limit</th>
          <th class="text-center pty-th">Actions</th>
        </tr>
      </thead>
      <tbody id="partiesBody"></tbody>
    </table>
  </div>
  <div class="card-footer pty-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted" id="paginationInfo" erp-text-sm"></div>
    <ul class="pagination mb-0" id="pagination"></ul>
  </div>
</div>

</div>

{{-- Delete Confirm Overlay --}}
<div class="ms-overlay d-none" id="ptyDeleteConfirm">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon" class="ms-icon-danger"><i class="ti ti-trash"></i></div>
      <div class="ms-title" id="ptyDeleteConfirmTitle">Delete Record?</div>
      <p class="ms-sub" id="ptyDeleteConfirmSub">Are you sure you want to delete this record? This cannot be undone.</p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="document.getElementById('ptyDeleteConfirm').classList.add('d-none')">Cancel</button>
      <button class="ms-btn-confirm" class="btn-erp-danger-gradient" onclick="doPtyDelete()">Yes, Delete</button>
    </div>
  </div>
</div>

{{-- Delete Error Overlay --}}
<div class="ms-overlay d-none" id="ptyDeleteError">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon" class="ms-icon-warning"><i class="ti ti-alert-triangle"></i></div>
      <div class="ms-title">Cannot Delete</div>
      <p class="ms-sub" id="ptyDeleteErrorMsg"></p>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" class="btn-erp-danger-gradient" onclick="document.getElementById('ptyDeleteError').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Delete Success Overlay --}}
<div class="ms-overlay d-none" id="ptyDeleteSuccess">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Deleted!</div>
      <p class="ms-sub" id="ptyDeleteSuccessMsg">Record has been removed from the system.</p>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" onclick="document.getElementById('ptyDeleteSuccess').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Confirm Save Overlay --}}
<div class="ms-overlay d-none" id="ptySaveConfirm">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm"><i class="ti ti-edit"></i></div>
      <div class="ms-title">Save Record?</div>
      <p class="ms-sub">Are you sure you want to save this record?</p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="document.getElementById('ptySaveConfirm').classList.add('d-none')">Cancel</button>
      <button class="ms-btn-confirm" onclick="doSaveParty()"><i class="ti ti-device-floppy me-1"></i>Yes, Save</button>
    </div>
  </div>
</div>

{{-- Success Overlay --}}
<div class="ms-overlay d-none" id="ptySaveSuccess">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Saved!</div>
      <p class="ms-sub">Record saved successfully.</p>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" onclick="document.getElementById('ptySaveSuccess').classList.add('d-none')"><i class="ti ti-check me-1"></i>OK</button>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="partyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-640">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="modalTitle"><i class="ti ti-users me-2"></i>Add Party</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <input type="hidden" id="editId">
        <div class="row pm-field-row">
          <div class="col-4"><label class="pm-label">Code</label><input type="text" class="form-control pm-input" id="pCode" placeholder="Auto or manual"></div>
          <div class="col-8"><label class="pm-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control pm-input" id="pName" placeholder="Full name" required></div>
        </div>
        <div class="row pm-field-row">
          <div class="col-6"><label class="pm-label">Phone</label><input type="text" class="form-control pm-input" id="pPhone" placeholder="Phone number"></div>
          <div class="col-6"><label class="pm-label">Email</label><input type="email" class="form-control pm-input" id="pEmail" placeholder="Email address"></div>
        </div>
        <div class="pm-field-row">
          <label class="pm-label">Address</label><textarea class="form-control pm-input" id="pAddress" rows="2" placeholder="Street address" class="erp-textarea-auto"></textarea>
        </div>
        <div class="row pm-field-row">
          <div class="col-6"><label class="pm-label">Entity Sub Type</label><select class="form-select pm-input" id="pSubType"><option value="">Select...</option></select></div>
          <div class="col-6"><label class="pm-label">Business Category</label><select class="form-select pm-input" id="pBizCat"><option value="">Select...</option></select></div>
        </div>
        <div class="row pm-field-row">
          <div class="col-4">
            <label class="pm-label">Payment Terms</label>
            <div class="input-group">
              <input type="number" class="form-control pm-input" id="pTerms" value="0">
              <span class="input-group-text pm-prefix" class="erp-input-group-suffix">days</span>
            </div>
          </div>
          <div class="col-4">
            <label class="pm-label">Credit Limit</label>
            <div class="input-group">
              <span class="input-group-text pm-prefix">Rs.</span>
              <input type="number" class="form-control pm-input" id="pCreditLimit" value="0">
            </div>
          </div>
          <div class="col-4">
            <label class="pm-label">Opening Balance</label>
            <div class="input-group">
              <span class="input-group-text pm-prefix">Rs.</span>
              <input type="number" class="form-control pm-input" id="pOpenBal" value="0">
            </div>
          </div>
        </div>
        <div class="pm-field-row" class="mb-0">
          <label class="pm-label">Bank Details</label><input type="text" class="form-control pm-input" id="pBank" placeholder="Bank name, account no., IFSC">
        </div>

        {{-- Accounting Mappings (collapsible) --}}
        <div class="pm-acct-wrap">
          <button type="button" class="pm-acct-toggle" onclick="togglePartyAccounting()">
            <span><i class="ti ti-book-2 me-2"></i>Accounting Accounts</span>
            <i class="ti ti-chevron-down" id="ptyAcctChevron"></i>
          </button>
          <div id="ptyAcctSection" class="pm-acct-body" style="display:none;">
            {{-- Customer fields --}}
            <div id="pty-acct-customer" class="row g-2">
              <div class="col-12 col-md-6">
                <label class="pm-label">Accounts Receivable</label>
                <select class="form-select pm-input" id="pf-acct-ar">
                  <option value="">— Not set —</option>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Cash / Bank Account</label>
                <select class="form-select pm-input" id="pf-acct-cash">
                  <option value="">— Not set —</option>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Discount Allowed</label>
                <select class="form-select pm-input" id="pf-acct-disc-allowed">
                  <option value="">— Not set —</option>
                </select>
              </div>
            </div>
            {{-- Vendor fields --}}
            <div id="pty-acct-vendor" class="row g-2" style="display:none;">
              <div class="col-12 col-md-6">
                <label class="pm-label">Accounts Payable</label>
                <select class="form-select pm-input" id="pf-acct-ap">
                  <option value="">— Not set —</option>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Cash / Bank Account</label>
                <select class="form-select pm-input" id="pf-acct-cash-vendor">
                  <option value="">— Not set —</option>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Discount Received</label>
                <select class="form-select pm-input" id="pf-acct-disc-received">
                  <option value="">— Not set —</option>
                </select>
              </div>
            </div>
            <div class="erp-info-hint mt-2"><i class="ti ti-info-circle me-1"></i>Company-wide defaults used for journal posting.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="confirmSaveParty()"><i class="ti ti-device-floppy me-1"></i>Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --pty-primary: #3B4FE4;
  --pty-primary-end: #5B6CF9;
  --pty-font: 'Inter', sans-serif;
}
.page-body, .page-wrapper {
  font-family: var(--pty-font);
  font-size: 14px;
  background: #F5F6FA !important;
}

.pty-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pty-header-card {
  background: linear-gradient(135deg, #3B4FE4 0%, #5B6CF9 100%);
  border: none;
  border-radius: 10px;
  overflow: hidden;
  position: relative;
}
.pty-header-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
  background-size: 16px 16px;
  opacity: 0.5;
  pointer-events: none;
}
.pty-header-card::after {
  content: '';
  position: absolute;
  top: -40%;
  right: -8%;
  width: 260px;
  height: 260px;
  background: rgba(255,255,255,0.06);
  border-radius: 50%;
  pointer-events: none;
}
.pty-header-body {
  padding: 20px 28px !important;
  position: relative;
  z-index: 1;
}
.pty-header-card .pty-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #fff;
}
.pty-header-card .pty-subtitle {
  font-size: 0.82rem;
  font-weight: 400;
  color: rgba(255,255,255,0.82);
}
.pty-header-card .btn {
  font-size: 0.82rem;
  font-weight: 600;
  padding: 8px 18px;
}

.pty-section-card {
  border: 1px solid #E8EAF0;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  background: #fff;
}

.pty-filter-body {
  padding: 12px 16px !important;
}
.pty-input {
  height: 36px !important;
  font-size: 0.85rem !important;
  border: 1px solid #DDE1EC !important;
  border-radius: 6px !important;
  transition: all 0.2s ease;
}
.pty-input:focus {
  border-color: var(--pty-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,79,228,0.08) !important;
}

.pty-table-card { overflow: hidden; }
.pty-table thead {
  background: #F8F9FC;
}
.pty-th {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #64748b;
  border-bottom: 2px solid #E8EAF0 !important;
  white-space: nowrap;
  padding: 10px 14px !important;
}
.pty-table tbody tr {
  transition: background-color 0.15s ease;
}
.pty-table tbody tr:hover {
  background-color: #F5F7FF !important;
}
.pty-table tbody td {
  padding: 10px 14px !important;
  vertical-align: middle;
  border-bottom: 1px solid #F0F2F8 !important;
  border-top: none !important;
  font-size: 0.85rem;
  color: #1e293b;
}

.pty-code {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  font-size: 0.75rem;
  font-weight: 500;
  color: #5B6CF9;
  background: #EEF0F8;
  padding: 2px 8px;
  border-radius: 4px;
  display: inline-block;
}

.pty-action-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: #64748b;
  transition: all 0.15s ease;
  padding: 0;
  font-size: 15px;
}
.pty-action-btn:hover {
  color: #3B4FE4;
  background: #EEF0F8;
}
.pty-action-btn.pty-action-danger:hover {
  color: #EF4444;
  background: rgba(239,68,68,0.08);
}
/* ── Bulk Select ── */
.pty-chk-col { display: none; }
.pty-select-active .pty-chk-col { display: table-cell; }
.pty-chk { width: 15px; height: 15px; cursor: pointer; accent-color: #3B4FE4; vertical-align: middle; }
#pty-sel-toggle-btn { transition: all 0.15s; }
#pty-sel-toggle-btn.active { background: #3B4FE4 !important; color: #fff !important; border-color: #3B4FE4 !important; }
.pty-bulk-bar { background: #EEF0FF; border: 1px solid #C5CAE9; border-radius: 8px; padding: 10px 18px; display: flex; align-items: center; }
.pty-bulk-count { font-size: 0.85rem; font-weight: 600; color: #3B4FE4; }
.pty-bulk-del-btn { background: linear-gradient(135deg,#dc2626,#ef4444); border: none; border-radius: 7px; padding: 7px 16px; font-size: 0.82rem; font-weight: 600; color: #fff; cursor: pointer; transition: opacity 0.15s; }
.pty-bulk-del-btn:hover { opacity: 0.88; }
.pty-bulk-clear-btn { background: none; border: 1px solid #DDE1EC; border-radius: 7px; padding: 7px 14px; font-size: 0.82rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.15s; }
.pty-bulk-clear-btn:hover { border-color: #94a3b8; color: #1e293b; }

.pty-table-footer {
  background: #fff;
  border-top: 1px solid #E8EAF0;
  padding: 10px 16px;
}

.pagination .page-link {
  border-radius: 6px !important;
  margin: 0 2px;
  border: 1px solid #E8EAF0;
  color: #64748b;
  font-weight: 500;
  font-size: 0.8rem;
  min-width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 8px;
  transition: all 0.15s ease;
}
.pagination .page-item.active .page-link {
  background: #3B4FE4;
  border-color: #3B4FE4;
  color: #fff;
  box-shadow: 0 1px 3px rgba(59,79,228,0.3);
}
.pagination .page-link:hover {
  background: #F5F6FA;
  border-color: #DDE1EC;
  color: #1e293b;
}

.pm-modal-content {
  border-radius: 12px;
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.pm-modal-header {
  background: linear-gradient(135deg, #3B4FE4 0%, #5B6CF9 100%);
  padding: 16px 24px;
  border-bottom: none;
}
.pm-modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
}
.pm-modal-close {
  background: none;
  border: none;
  color: #fff;
  font-size: 1.4rem;
  line-height: 1;
  opacity: 0.8;
  transition: opacity 0.15s ease;
  padding: 0;
  cursor: pointer;
}
.pm-modal-close:hover { opacity: 1; }
.pm-modal-body {
  background: #F8F9FC;
  padding: 24px;
}
.pm-field-row {
  margin-bottom: 16px;
}
.pm-field-row:last-child { margin-bottom: 0; }
.pm-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6B7280;
  margin-bottom: 6px;
}
.pm-input {
  height: 38px !important;
  border-radius: 7px !important;
  border: 1px solid #DDE1EC !important;
  background: #FFFFFF !important;
  font-size: 0.875rem !important;
  font-weight: 500 !important;
  color: #1A1D2E !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.pm-input::placeholder {
  color: #B0B7C9 !important;
  font-weight: 400 !important;
}
.pm-input:focus {
  border-color: #5B6CF9 !important;
  box-shadow: 0 0 0 3px rgba(91,108,249,0.12) !important;
}
.pm-prefix {
  background: #F0F2F8;
  border: 1px solid #DDE1EC;
  border-right: none;
  font-size: 0.8rem;
  color: #6B7280;
  border-radius: 7px 0 0 7px;
  height: 38px;
  display: flex;
  align-items: center;
  padding: 0 10px;
}
.pm-prefix + .pm-input {
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}
.pm-modal-footer {
  background: #FFFFFF;
  border-top: 1px solid #E8EAF0;
  padding: 14px 24px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.pm-btn-cancel {
  background: none;
  border: none;
  color: #6B7280;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 9px 16px;
  cursor: pointer;
  transition: color 0.15s ease;
}
.pm-btn-cancel:hover { color: #1A1D2E; }
.pm-btn-save {
  background: linear-gradient(135deg, #3B4FE4, #5B6CF9);
  border: none;
  border-radius: 7px;
  padding: 9px 22px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.pm-btn-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(91,108,249,0.35);
}
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
/* Accounting collapsible */
.pm-acct-wrap{border-top:1px dashed #DDE1EC;margin-top:14px;padding-top:4px;}
.pm-acct-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;background:none;border:none;padding:8px 2px;font-size:0.8rem;font-weight:600;color:#3B4FE4;cursor:pointer;text-align:left;letter-spacing:0.02em;transition:color 0.15s;}
.pm-acct-toggle:hover{color:#2a3bb0;}
.pm-acct-toggle .ti-chevron-down{transition:transform 0.2s;font-size:0.85rem;}
.pm-acct-toggle.open .ti-chevron-down{transform:rotate(180deg);}
.pm-acct-body{padding:10px 0 4px;}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/parties.js') }}?v={{ filemtime(public_path('js/pages/parties.js')) }}"></script>
@endpush
