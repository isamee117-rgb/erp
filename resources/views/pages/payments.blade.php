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
.inv-action-btn{width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;border:none;background:transparent;color:#64748b;transition:all 0.15s ease;padding:0;font-size:15px;}
.inv-action-btn.inv-action-danger:hover{color:#EF4444;background:rgba(239,68,68,0.08);}
.inv-table-footer{background:#fff;border-top:1px solid #E8EAF0;padding:10px 16px;}
.pagination .page-link{border-radius:6px!important;margin:0 2px;border:1px solid #E8EAF0;color:#64748b;font-weight:500;font-size:0.8rem;min-width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;transition:all 0.15s ease;}
.pagination .page-item.active .page-link{background:#3B4FE4;border-color:#3B4FE4;color:#fff;}
.pagination .page-link:hover{background:#F5F6FA;border-color:#DDE1EC;color:#1e293b;}
.badge-pill{font-weight:600;padding:3px 10px;border-radius:20px;font-size:0.72rem;}
.badge-green{background:rgba(16,185,129,0.1);color:#059669;}
.badge-red{background:rgba(239,68,68,0.1);color:#dc2626;}
.pm-modal-content{border-radius:12px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);}
.pm-modal-header{background:linear-gradient(135deg,#3B4FE4 0%,#5B6CF9 100%);padding:16px 24px;border-bottom:none;}
.pm-modal-title{font-size:1rem;font-weight:700;color:#fff;}
.pm-modal-close{background:none;border:none;color:#fff;font-size:1.4rem;line-height:1;opacity:0.8;padding:0;cursor:pointer;transition:opacity 0.15s ease;}
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

/* ── Cash Reconciliation Modal ─────────────────────────────── */
.cr-modal-content{border-radius:14px;overflow:hidden;border:none;box-shadow:0 24px 64px rgba(0,0,0,0.18);}
.cr-modal-header{background:#fff;padding:18px 22px;border-bottom:1px solid #E8EAF0;display:flex;align-items:center;justify-content:space-between;}
.cr-header-icon{width:48px;height:48px;border-radius:12px;background:rgba(20,184,166,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.cr-header-title{font-size:0.95rem;font-weight:700;color:#14b8a6;letter-spacing:0.04em;}
.cr-header-date{font-size:0.78rem;color:#64748b;margin-top:2px;}
.cr-close-btn{background:none;border:none;font-size:1.5rem;color:#94a3b8;line-height:1;cursor:pointer;padding:0;transition:color 0.15s ease;}
.cr-close-btn:hover{color:#374151;}
.cr-modal-body{background:#F8F9FC;padding:20px;}
.cr-section{background:#fff;border:1px solid #E8EAF0;border-radius:10px;padding:16px;}
.cr-section-title{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;margin-bottom:12px;}
.cr-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#6B7280;margin-bottom:5px;}
.cr-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:7px!important;background:#fff!important;color:#1A1D2E!important;}
.cr-input:focus{border-color:#14b8a6!important;box-shadow:0 0 0 3px rgba(20,184,166,0.12)!important;}
.cr-input[readonly]{background:#F8F9FC!important;color:#64748b!important;}
.cr-expected-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F0FDF9;border-radius:7px;border:1px solid #99f6e4;}
.cr-expected-val{font-size:1rem;font-weight:700;color:#14b8a6;}
.cr-total-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F8F9FC;border-radius:7px;border:1px solid #E8EAF0;}
.cr-total-val{font-size:1rem;font-weight:700;color:#1A1D2E;}
.cr-variance-section{background:#FFF5F5!important;border-color:#FED7D7!important;}
.cr-variance-section .cr-section-title{color:#DC2626;}
.cr-diff-ok{color:#059669;}
.cr-diff-short{color:#DC2626;}
.cr-diff-over{color:#D97706;}
.cr-modal-footer{background:#fff;border-top:1px solid #E8EAF0;padding:14px 20px;display:flex;justify-content:flex-end;gap:10px;}
.cr-btn-cancel{background:none;border:1px solid #DDE1EC;border-radius:7px;color:#6B7280;font-size:0.875rem;font-weight:500;padding:9px 18px;cursor:pointer;transition:all 0.15s ease;}
.cr-btn-cancel:hover{border-color:#94a3b8;color:#374151;}
.cr-btn-draft{background:#F8F9FC;border:1px solid #DDE1EC;border-radius:7px;color:#374151;font-size:0.875rem;font-weight:600;padding:9px 18px;cursor:pointer;transition:all 0.15s ease;}
.cr-btn-draft:hover{background:#E8EAF0;}
.cr-btn-submit{background:linear-gradient(135deg,#14b8a6,#0d9488);border:none;border-radius:7px;padding:9px 22px;font-size:0.875rem;font-weight:600;color:#fff;cursor:pointer;transition:transform 0.15s ease,box-shadow 0.15s ease;}
.cr-btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(20,184,166,0.4);}
.fw-600{font-weight:600;}

/* ── Payment modal overflow fix for SDD panels ── */
#paymentModal .pm-modal-content { overflow: visible !important; }
#paymentModal .pm-modal-header  { border-radius: 12px 12px 0 0; overflow: hidden; }

/* ── Searchable Dropdown (SDD) ──────────────────────────── */
.sdd-wrap { position: relative; }
.pm-sdd-trigger {
  height: 38px; font-size: 0.875rem; font-weight: 500;
  border: 1px solid #DDE1EC; border-radius: 7px; background: #fff;
  width: 100%; padding: 0 10px; cursor: pointer;
  display: flex; align-items: center; justify-content: space-between;
  transition: border-color 0.15s ease, box-shadow 0.15s ease; color: #1A1D2E;
}
.pm-sdd-trigger:hover { border-color: #B0B7C9; }
.sdd-wrap.open .pm-sdd-trigger { border-color: #5B6CF9; box-shadow: 0 0 0 3px rgba(91,108,249,0.12); }
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
<script src="{{ asset('js/pages/payments.js') }}?v={{ filemtime(public_path('js/pages/payments.js')) }}"></script>
@endpush
