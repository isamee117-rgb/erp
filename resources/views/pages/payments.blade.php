@extends('layouts.app')
@section('page-title', 'Payments - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-wallet me-2"></i>Payments</h2>
        <p class="mb-0 inv-subtitle">Record and track receipts and payments with parties.</p>
      </div>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-light shadow-sm" onclick="openReconLog()"><i class="ti ti-history me-1"></i>Reconciliation Log</button>
        <button class="btn btn-light shadow-sm" onclick="openCashRecon()"><i class="ti ti-calculator me-1"></i>Cash Reconciliation</button>
        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="openAddPayment()"><i class="ti ti-plus me-1"></i>Add Payment</button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-3">
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search" class="erp-icon-sm"></i></span>
          <input type="text" class="form-control inv-input ps-5" id="searchInput" placeholder="Search payments...">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select inv-input" id="typeFilter">
          <option value="">All Types</option>
          <option value="Payment Received">Received</option>
          <option value="Payment Made">Made</option>
        </select>
      </div>
      <div class="col-6 col-md-3"><input type="date" class="form-control inv-input" id="dateFrom"></div>
      <div class="col-6 col-md-3"><input type="date" class="form-control inv-input" id="dateTo"></div>
      <div class="col-auto ms-md-auto">
        <button class="btn btn-light inv-input px-3" onclick="clearFilters()"><i class="ti ti-x"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th">Date</th>
          <th class="inv-th">Party</th>
          <th class="inv-th">Type</th>
          <th class="inv-th">Amount</th>
          <th class="inv-th">Reference</th>
          <th class="inv-th">Notes</th>
        </tr>
      </thead>
      <tbody id="paymentsBody"></tbody>
    </table>
  </div>
  <div class="card-footer inv-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted" id="paginationInfo" erp-text-sm"></div>
    <ul class="pagination mb-0" id="pagination"></ul>
  </div>
</div>

</div>

<div class="modal modal-blur fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-wallet me-2"></i>Add Payment</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="pm-field-row">
          <label class="pm-label">Party</label>
          <div class="sdd-wrap" id="pmParty-sdd">
            <div class="sdd-trigger pm-sdd-trigger" id="pmParty-trigger" onclick="sddToggle('pmParty-sdd')">
              <span class="sdd-disp" id="pmParty-disp" class="erp-dropdown-placeholder">Select Party</span>
              <i class="ti ti-chevron-down sdd-caret"></i>
            </div>
            <div class="sdd-panel">
              <div class="sdd-search-row">
                <i class="ti ti-search"></i>
                <input type="text" class="sdd-search-inp" placeholder="Search party..." oninput="sddFilterOpts('pmParty-sdd',this.value)" onclick="event.stopPropagation()">
              </div>
              <div class="sdd-opts-wrap" id="pmParty-opts"></div>
            </div>
            <input type="hidden" id="pmParty">
          </div>
        </div>
        <div class="pm-field-row">
          <label class="pm-label">Type</label>
          <select class="form-select pm-input" id="pmType" onchange="updateRefDropdown()">
            <option value="Payment Received">Payment Received</option>
            <option value="Payment Made">Payment Made</option>
          </select>
        </div>
        <div class="pm-field-row">
          <label class="pm-label">G/L Account</label>
          <div class="sdd-wrap" id="pmAcct-sdd">
            <div class="sdd-trigger pm-sdd-trigger" id="pmAcct-trigger" onclick="sddToggle('pmAcct-sdd')">
              <span class="sdd-disp" id="pmAcct-disp" style="color:#B0B7C9;">— Select Account —</span>
              <i class="ti ti-chevron-down sdd-caret"></i>
            </div>
            <div class="sdd-panel">
              <div class="sdd-search-row">
                <i class="ti ti-search"></i>
                <input type="text" class="sdd-search-inp" placeholder="Search account..." oninput="sddFilterOpts('pmAcct-sdd',this.value)" onclick="event.stopPropagation()">
              </div>
              <div class="sdd-opts-wrap" id="pmAcct-opts"></div>
            </div>
            <input type="hidden" id="pmAcct">
          </div>
        </div>
        <div class="pm-field-row">
          <label class="pm-label">Amount</label>
          <input type="number" class="form-control pm-input" id="pmAmount" step="0.01" min="0">
        </div>
        <div class="pm-field-row">
          <label class="pm-label">Reference</label>
          <div class="sdd-wrap" id="pmRef-sdd">
            <div class="sdd-trigger pm-sdd-trigger" id="pmRef-trigger" onclick="sddToggle('pmRef-sdd')">
              <span class="sdd-disp" id="pmRef-disp" class="erp-dropdown-placeholder">-- Select Reference --</span>
              <i class="ti ti-chevron-down sdd-caret"></i>
            </div>
            <div class="sdd-panel">
              <div class="sdd-search-row">
                <i class="ti ti-search"></i>
                <input type="text" class="sdd-search-inp" placeholder="Search reference..." oninput="sddFilterOpts('pmRef-sdd',this.value)" onclick="event.stopPropagation()">
              </div>
              <div class="sdd-opts-wrap" id="pmRef-opts"></div>
            </div>
            <input type="hidden" id="pmRef">
          </div>
        </div>
        <div class="pm-field-row" class="mb-0">
          <label class="pm-label">Notes</label>
          <textarea class="form-control pm-textarea" id="pmNotes" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="savePayment()"><i class="ti ti-device-floppy me-1"></i>Save Payment</button>
      </div>
    </div>
  </div>
</div>

{{-- Cash Reconciliation Modal --}}
<div class="modal modal-blur fade" id="cashReconModal" tabindex="-1" style="z-index:1060;">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-520">
    <div class="modal-content cr-modal-content">

      {{-- Header --}}
      <div class="cr-modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="cr-header-icon"><i class="ti ti-calculator" class="fs-3" style="color:#14b8a6;"></i></div>
          <div>
            <div class="cr-header-title">DAILY CASH RECONCILIATION</div>
            <div class="cr-header-date" id="crHeaderDate"></div>
          </div>
        </div>
        <button type="button" class="cr-close-btn" onclick="closeCashRecon()">&times;</button>
      </div>

      <div class="modal-body cr-modal-body">

        {{-- Date From / Date To / Cashier --}}
        <div class="row g-3 mb-2">
          <div class="col-4">
            <label class="cr-label">Date From</label>
            <input type="date" class="form-control cr-input" id="crDateFrom" onchange="refreshCrSystemData()">
          </div>
          <div class="col-4">
            <label class="cr-label">Date To</label>
            <input type="date" class="form-control cr-input" id="crDateTo" onchange="refreshCrSystemData()">
          </div>
          <div class="col-4">
            <label class="cr-label">Cashier</label>
            <select class="form-select cr-input" id="crPharmacist">
              <option value="">Select Cashier</option>
            </select>
          </div>
        </div>
        {{-- Time From / Time To --}}
        <div class="row g-3 mb-3">
          <div class="col-4">
            <label class="cr-label">Time From</label>
            <input type="time" class="form-control cr-input" id="crTimeFrom" onchange="refreshCrSystemData()">
          </div>
          <div class="col-4">
            <label class="cr-label">Time To</label>
            <input type="time" class="form-control cr-input" id="crTimeTo" onchange="refreshCrSystemData()">
          </div>
        </div>

        {{-- System Records --}}
        <div class="cr-section mb-3">
          <div class="cr-section-title">SYSTEM RECORDS</div>
          <div class="row g-3">
            <div class="col-6">
              <label class="cr-label">Opening Balance</label>
              <input type="number" class="form-control cr-input" id="crOpeningBalance" value="0" oninput="calcCrExpected()">
            </div>
            <div class="col-6">
              <label class="cr-label">Cash Sales</label>
              <input type="number" class="form-control cr-input" id="crCashSales" value="0" oninput="calcCrExpected()" readonly>
            </div>
            <div class="col-6">
              <label class="cr-label">Payments Received</label>
              <input type="number" class="form-control cr-input" id="crPaymentsReceived" value="0" oninput="calcCrExpected()" readonly>
            </div>
            <div class="col-6">
              <label class="cr-label">Returns/Refunds</label>
              <input type="number" class="form-control cr-input" id="crReturns" value="0" oninput="calcCrExpected()">
            </div>
          </div>
          <div class="cr-expected-row mt-3">
            <span class="fw-600">Expected Closing:</span>
            <span class="cr-expected-val" id="crExpectedClosing">PKR 0</span>
          </div>
        </div>

        {{-- Physical Count (Denominations) - Optional --}}
        <div class="cr-section mb-3">
          <div class="d-flex align-items-center justify-content-between mb-2 erp-cursor-pointer" onclick="toggleDenominations()">
            <div class="cr-section-title mb-0">PHYSICAL COUNT (DENOMINATIONS) <span class="cr-denom-label">— Optional</span></div>
            <i class="ti ti-chevron-down cr-denom-chevron" id="crDenomChevron"></i>
          </div>
          <div id="crDenomFields" class="d-none">
            <div class="row g-2 mb-3">
              <div class="col-3"><label class="cr-label">5000 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d5000" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">1000 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d1000" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">500 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d500" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">100 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d100" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">50 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d50" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">20 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d20" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">10 x</label><input type="number" class="form-control cr-input cr-denom text-end" id="d10" value="0" min="0" oninput="calcCrTotal()"></div>
              <div class="col-3"><label class="cr-label">Coins</label><input type="number" class="form-control cr-input cr-denom text-end" id="dCoins" value="0" min="0" oninput="calcCrTotal()"></div>
            </div>
            <div class="cr-total-row">
              <span class="fw-600">Denomination Total:</span>
              <span class="cr-total-val" id="crTotalCash">PKR 0</span>
            </div>
          </div>
          {{-- Manual total cash entry (always visible) --}}
          <div class="mt-2">
            <label class="cr-label">Total Cash in Hand</label>
            <input type="number" class="form-control cr-input" id="crManualTotalCash" value="0" min="0" placeholder="Enter total cash amount" oninput="calcCrVariance()">
          </div>
        </div>

        {{-- Variance --}}
        <div class="cr-section cr-variance-section mb-3">
          <div class="cr-section-title">VARIANCE</div>
          <div class="d-flex align-items-center gap-4 flex-wrap mb-3">
            <div>Expected: <strong id="crVarExpected">PKR 0</strong></div>
            <div>Actual: <strong id="crVarActual">PKR 0</strong></div>
            <div>Difference: <strong id="crVarDiff" class="cr-diff-ok">PKR 0</strong></div>
          </div>
          <div class="mb-3">
            <label class="cr-label">Reason for Variance</label>
            <input type="text" class="form-control cr-input" id="crVarianceReason" placeholder="e.g. Small change given, not recorded">
          </div>
          <div>
            <label class="cr-label">Authorized By</label>
            <input type="text" class="form-control cr-input" id="crAuthorizedBy" placeholder="Manager name">
          </div>
        </div>


      </div>{{-- /modal-body --}}

      {{-- Footer --}}
      <div class="cr-modal-footer">
        <button class="cr-btn-cancel" onclick="closeCashRecon()">Cancel</button>
        <button class="cr-btn-draft"><i class="ti ti-device-floppy me-1"></i>Save Draft</button>
        <button class="cr-btn-submit"><i class="ti ti-check me-1"></i>Submit</button>
      </div>

    </div>
  </div>
</div>

{{-- Reconciliation Log Modal --}}
<div class="modal modal-blur fade" id="reconLogModal" tabindex="-1" style="z-index:1070;">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content cr-modal-content">
      <div class="cr-modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="cr-header-icon"><i class="ti ti-history" class="fs-3" style="color:#14b8a6;"></i></div>
          <div>
            <div class="cr-header-title">RECONCILIATION LOG</div>
            <div class="cr-header-date">All submitted cash reconciliations</div>
          </div>
        </div>
        <button type="button" class="cr-close-btn" onclick="closeReconLog()">&times;</button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" class="erp-text-82">
            <thead class="erp-thead-light">
              <tr>
                <th class="cr-log-th">#</th>
                <th class="cr-log-th">Date From</th>
                <th class="cr-log-th">Time From</th>
                <th class="cr-log-th">Date To</th>
                <th class="cr-log-th">Time To</th>
                <th class="cr-log-th">Cashier</th>
                <th class="cr-log-th text-end">Expected</th>
                <th class="cr-log-th text-end">Actual</th>
                <th class="cr-log-th text-end">Variance</th>
                <th class="cr-log-th">Status</th>
                <th class="cr-log-th">Action</th>
              </tr>
            </thead>
            <tbody id="reconLogBody">
              <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-inbox d-block mb-2" class="fs-2"></i>No reconciliations submitted yet</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="cr-modal-footer">
        <button class="cr-btn-cancel" onclick="closeReconLog()">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Reconciliation Detail Overlay --}}
<div id="crDetailOverlay" class="cr-detail-overlay" style="display:none;">
  <div class="cr-detail-panel">
    {{-- Header --}}
    <div class="cr-detail-header">
      <div class="cr-detail-header-left">
        <div class="cr-detail-icon-box">
          <i class="ti ti-file-description cr-detail-icon"></i>
        </div>
        <div>
          <div class="cr-detail-title">RECONCILIATION DETAIL</div>
          <div class="cr-detail-sub">Full breakdown of this entry</div>
        </div>
      </div>
      <button onclick="document.getElementById('crDetailOverlay').style.display='none'" class="cr-detail-close-btn">&times;</button>
    </div>
    {{-- Body --}}
    <div id="crDetailContent" class="cr-detail-body"></div>
    {{-- Footer --}}
    <div class="cr-detail-footer">
      <button onclick="document.getElementById('crDetailOverlay').style.display='none'" class="cr-detail-close-solid">Close</button>
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('js/pages/payments.js') }}?v={{ filemtime(public_path('js/pages/payments.js')) }}"></script>
@endpush
