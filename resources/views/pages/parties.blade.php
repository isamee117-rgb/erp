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
        <p class="mb-0 pty-subtitle">Manage contacts, credit limits, and payment terms for all business partners.</p>
      </div>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#partyModal" onclick="openAddModal()">
          <i class="ti ti-plus me-1"></i><span id="addBtnLabel">Add {{ $type ?? 'Party' }}</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card pty-section-card pty-filter-bar">
  <div class="card-body pty-filter-body">
    {{-- Row 1: Search + icon toolbar --}}
    <div class="d-flex align-items-center gap-2">
      <div class="flex-grow-1 position-relative">
        <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search"></i></span>
        <input type="text" class="form-control pty-input ps-5" id="searchInput" placeholder="Search by name, code, phone...">
      </div>
      <div class="inv-toolbar-group">
        <button class="inv-icon-btn" id="pty-filter-toggle-btn" title="Toggle Filters">
          <i class="ti ti-filter"></i>
        </button>
        <div class="dropdown">
          <button class="inv-icon-btn" id="ptyColsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Columns">
            <i class="ti ti-layout-columns"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end inv-cols-menu" id="ptyColsMenu"></ul>
        </div>
        <button class="inv-icon-btn" id="pty-sel-toggle-btn" onclick="togglePtySelectMode()" title="Multi-select">
          <i class="ti ti-checkbox"></i>
        </button>
      </div>
    </div>
    {{-- Row 2: Collapsible filters --}}
    <div id="pty-filters-panel" class="d-none mt-2">
      <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
          <select class="form-select pty-input" id="entityTypeFilter"><option value="">All Entity Types</option></select>
        </div>
        <div class="col-auto">
          <button class="inv-icon-btn" onclick="clearFilters()" title="Clear Filters"><i class="ti ti-x"></i></button>
        </div>
      </div>
    </div>
    <div id="pty-dyn-filters" class="d-flex flex-wrap gap-2 mt-2"></div>
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
        <tr id="pty-thead-row">
          <th class="pty-th pty-chk-col" class="col-erp-checkbox"><input type="checkbox" class="pty-chk" id="pty-select-all" onclick="toggleSelectAllParties(this)" title="Select all"></th>
          <th class="pty-th cursor-pointer" onclick="togglePtySort('code')">Code <i class="ti ti-arrows-sort ms-1"></i></th>
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
  <div class="modal-dialog modal-dialog-centered modal-dialog-680">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="modalTitle"><i class="ti ti-users me-2"></i>Add Party</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <input type="hidden" id="editId">
        <div class="row pm-field-row">
          <div class="col-4"><label class="pm-label">Code</label><input type="text" class="form-control pm-input" id="pCode" placeholder="Auto or manual"></div>
          <div class="col-8"><label class="pm-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control pm-input" id="pName" placeholder="Full name" required><div class="text-danger small mt-1 d-none" id="pName-error">Name is required.</div></div>
        </div>
        <div class="row pm-field-row">
          <div class="col-6"><label class="pm-label">Phone</label><input type="text" class="form-control pm-input" id="pPhone" placeholder="Phone number"><div class="text-danger small mt-1 d-none" id="pPhone-error">Enter a valid phone number (digits, +, -, spaces only).</div></div>
          <div class="col-6"><label class="pm-label">Email</label><input type="email" class="form-control pm-input" id="pEmail" placeholder="Email address"><div class="text-danger small mt-1 d-none" id="pEmail-error">Enter a valid email address.</div></div>
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
            <div class="text-danger small mt-1 d-none" id="pTerms-error">Payment terms cannot be negative.</div>
          </div>
          <div class="col-4">
            <label class="pm-label">Credit Limit</label>
            <div class="input-group">
              <span class="input-group-text pm-prefix">Rs.</span>
              <input type="number" class="form-control pm-input" id="pCreditLimit" value="0">
            </div>
            <div class="text-danger small mt-1 d-none" id="pCreditLimit-error">Credit limit cannot be negative.</div>
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

        <div id="pty-dynamic-fields" class="row pm-field-row g-3 mt-1"></div>

        {{-- Accounting Mappings (collapsible) --}}
        <div class="pm-acct-wrap">
          <button type="button" class="pm-acct-toggle" onclick="togglePartyAccounting()">
            <span><i class="ti ti-book-2 me-2"></i>Posting Accounts</span>
            <i class="ti ti-chevron-down" id="ptyAcctChevron"></i>
          </button>
          <div id="ptyAcctSection" class="pm-acct-body" style="display:none;">
            {{-- Customer fields --}}
            <div id="pty-acct-customer" class="row g-2">
              <div class="col-12 col-md-6">
                <label class="pm-label">Accounts Receivable</label>
                <div class="sdd-wrap" id="sdd-pty-acct-ar">
                  <div class="sr-sdd-trigger" onclick="ptyAcctSddToggle('sdd-pty-acct-ar')">
                    <span class="sdd-disp" id="sdd-pty-acct-ar-disp" style="color:#B0B7C9">— Not set —</span>
                    <i class="ti ti-chevron-down sdd-caret"></i>
                  </div>
                  <div class="sdd-panel">
                    <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="ptyAcctSddFilter('sdd-pty-acct-ar',this.value)" onclick="event.stopPropagation()"></div>
                    <div class="sdd-opts-wrap" id="sdd-pty-acct-ar-opts"></div>
                  </div>
                  <input type="hidden" id="pf-acct-ar">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Cash / Bank</label>
                <div class="sdd-wrap" id="sdd-pty-acct-cash">
                  <div class="sr-sdd-trigger" onclick="ptyAcctSddToggle('sdd-pty-acct-cash')">
                    <span class="sdd-disp" id="sdd-pty-acct-cash-disp" style="color:#B0B7C9">— Not set —</span>
                    <i class="ti ti-chevron-down sdd-caret"></i>
                  </div>
                  <div class="sdd-panel">
                    <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="ptyAcctSddFilter('sdd-pty-acct-cash',this.value)" onclick="event.stopPropagation()"></div>
                    <div class="sdd-opts-wrap" id="sdd-pty-acct-cash-opts"></div>
                  </div>
                  <input type="hidden" id="pf-acct-cash">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Discount Allowed</label>
                <div class="sdd-wrap" id="sdd-pty-acct-disc-allowed">
                  <div class="sr-sdd-trigger" onclick="ptyAcctSddToggle('sdd-pty-acct-disc-allowed')">
                    <span class="sdd-disp" id="sdd-pty-acct-disc-allowed-disp" style="color:#B0B7C9">— Not set —</span>
                    <i class="ti ti-chevron-down sdd-caret"></i>
                  </div>
                  <div class="sdd-panel">
                    <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="ptyAcctSddFilter('sdd-pty-acct-disc-allowed',this.value)" onclick="event.stopPropagation()"></div>
                    <div class="sdd-opts-wrap" id="sdd-pty-acct-disc-allowed-opts"></div>
                  </div>
                  <input type="hidden" id="pf-acct-disc-allowed">
                </div>
              </div>
            </div>
            {{-- Vendor fields --}}
            <div id="pty-acct-vendor" class="row g-2" style="display:none;">
              <div class="col-12 col-md-6">
                <label class="pm-label">Accounts Payable</label>
                <div class="sdd-wrap" id="sdd-pty-acct-ap">
                  <div class="sr-sdd-trigger" onclick="ptyAcctSddToggle('sdd-pty-acct-ap')">
                    <span class="sdd-disp" id="sdd-pty-acct-ap-disp" style="color:#B0B7C9">— Not set —</span>
                    <i class="ti ti-chevron-down sdd-caret"></i>
                  </div>
                  <div class="sdd-panel">
                    <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="ptyAcctSddFilter('sdd-pty-acct-ap',this.value)" onclick="event.stopPropagation()"></div>
                    <div class="sdd-opts-wrap" id="sdd-pty-acct-ap-opts"></div>
                  </div>
                  <input type="hidden" id="pf-acct-ap">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Cash / Bank</label>
                <div class="sdd-wrap" id="sdd-pty-acct-cash-vendor">
                  <div class="sr-sdd-trigger" onclick="ptyAcctSddToggle('sdd-pty-acct-cash-vendor')">
                    <span class="sdd-disp" id="sdd-pty-acct-cash-vendor-disp" style="color:#B0B7C9">— Not set —</span>
                    <i class="ti ti-chevron-down sdd-caret"></i>
                  </div>
                  <div class="sdd-panel">
                    <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="ptyAcctSddFilter('sdd-pty-acct-cash-vendor',this.value)" onclick="event.stopPropagation()"></div>
                    <div class="sdd-opts-wrap" id="sdd-pty-acct-cash-vendor-opts"></div>
                  </div>
                  <input type="hidden" id="pf-acct-cash-vendor">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="pm-label">Discount Received</label>
                <div class="sdd-wrap" id="sdd-pty-acct-disc-received">
                  <div class="sr-sdd-trigger" onclick="ptyAcctSddToggle('sdd-pty-acct-disc-received')">
                    <span class="sdd-disp" id="sdd-pty-acct-disc-received-disp" style="color:#B0B7C9">— Not set —</span>
                    <i class="ti ti-chevron-down sdd-caret"></i>
                  </div>
                  <div class="sdd-panel">
                    <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="ptyAcctSddFilter('sdd-pty-acct-disc-received',this.value)" onclick="event.stopPropagation()"></div>
                    <div class="sdd-opts-wrap" id="sdd-pty-acct-disc-received-opts"></div>
                  </div>
                  <input type="hidden" id="pf-acct-disc-received">
                </div>
              </div>
            </div>
            <div class="erp-info-hint mt-2"><i class="ti ti-info-circle me-1"></i>Company-wide defaults used for journal posting.</div>
          </div>
        </div>
      </div>
      <div class="px-3 pb-2 d-none" id="pty-save-error">
        <div class="alert alert-danger py-2 mb-0 small" id="pty-save-error-msg"></div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="confirmSaveParty()"><i class="ti ti-device-floppy me-1"></i>Save</button>
      </div>
    </div>
  </div>
</div>
@endsection


@push('scripts')
<script src="{{ asset('js/pages/parties.js') }}?v={{ filemtime(public_path('js/pages/parties.js')) }}"></script>
@endpush
