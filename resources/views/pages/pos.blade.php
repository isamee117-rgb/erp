@extends('layouts.app')
@section('page-title', 'POS Terminal - LeanERP')
@section('content')
<div class="pos-page-wrap">

<div class="card pos-header-card">
  <div class="card-body pos-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 pos-title">POS Terminal</h2>
        <p class="mb-0 pos-subtitle">Quick sales, real-time inventory, instant invoicing.</p>
      </div>
    </div>
  </div>
</div>

<div class="row g-3" class="erp-pos-products">
  <div class="col-lg-8 d-flex flex-column" class="erp-min-h-0">
    <div class="card pos-section-card mb-3" class="erp-flex-shrink-0">
      <div class="card-body pos-filter-body">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="d-flex gap-2 align-items-center">
              <div class="position-relative flex-fill">
                <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search" class="erp-icon-sm"></i></span>
                <input type="text" class="form-control pos-input ps-5" id="pos-search" placeholder="Search or scan barcode — press Enter to add..." oninput="renderProducts();" onkeydown="posScannerEnter(event)" autofocus>
              </div>
              <button class="bc-cam-btn" class="btn-erp-scanner-lg" onclick="openBarcodeScanner(function(c){document.getElementById('pos-search').value=c;renderProducts();posScannerEnter({key:'Enter',preventDefault:function(){}});})" title="Scan with camera"><i class="ti ti-camera" class="erp-icon-lg"></i></button>
            </div>
            <div id="pos-scan-feedback" class="erp-feedback"></div>
          </div>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2" id="pos-categories"></div>
      </div>
    </div>
    <div class="card pos-section-card flex-fill d-flex flex-column" class="erp-min-h-0">
      <div class="card-body p-3 overflow-auto flex-fill" id="pos-product-grid" class="erp-min-h-0"></div>
    </div>
  </div>

  <div class="col-lg-4" style="align-self:flex-start;">
    <div class="card pos-section-card d-flex flex-column" id="pos-right-card" class="erp-overflow-visible">
      <div class="pos-cart-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold" class="erp-pos-order-title">Current Order</h5>
        <button class="pos-clear-btn" onclick="clearCart()"><i class="ti ti-trash me-1"></i>Clear</button>
      </div>
      <div class="p-3" id="pos-cart"></div>
      <div class="pos-checkout-panel" class="erp-flex-shrink-0">
        <div class="mb-2">
          <label class="pm-label"><i class="ti ti-user me-1"></i>Customer</label>
          <div class="sdd-wrap" id="pos-customer-sdd">
            <div class="sdd-trigger pos-sdd-trigger" id="pos-customer-trigger" onclick="sddToggle('pos-customer-sdd')">
              <span class="sdd-disp" id="pos-customer-disp" class="erp-dropdown-placeholder">— Select Customer —</span>
              <i class="ti ti-chevron-down sdd-caret"></i>
            </div>
            <div class="sdd-panel">
              <div class="sdd-search-row">
                <i class="ti ti-search"></i>
                <input type="text" class="sdd-search-inp" placeholder="Search customer..." oninput="sddFilterOpts('pos-customer-sdd',this.value)" onclick="event.stopPropagation()">
              </div>
              <div class="sdd-opts-wrap" id="pos-customer-opts"></div>
            </div>
            <input type="hidden" id="pos-customer">
          </div>
        </div>
        <div class="d-flex justify-content-between erp-text-sm mb-1"><span class="text-muted">Subtotal</span><span id="pos-subtotal" class="pos-subtotal-val">0.00</span></div>
        <div class="d-flex justify-content-between erp-text-sm mb-2"><span class="text-muted">Discount</span><span id="pos-discount" class="pos-discount-val">0.00</span></div>
        <div class="d-flex justify-content-between mb-2 pt-1" class="rpt-sales-item-row"><span class="fw-bold" class="erp-text-85">Grand Total</span><span id="pos-total" class="fw-bold pos-total-val">0.00</span></div>
        <div class="row g-2 mb-2">
          <div class="col-6"><button class="btn w-100 pos-pay-btn" id="btn-cash" onclick="setPayment('Cash')"><i class="ti ti-cash me-1"></i>Cash</button></div>
          <div class="col-6"><button class="btn w-100 pos-pay-btn" id="btn-credit" onclick="setPayment('Credit')"><i class="ti ti-credit-card me-1"></i>Credit</button></div>
        </div>
        <button class="pos-complete-btn w-100" id="pos-checkout" onclick="completeSale()" disabled>
          <i class="ti ti-check me-1"></i>Complete Sale
        </button>
      </div>
    </div>
  </div>
</div>

</div>

{{-- Customer Required Error Overlay --}}
<div class="ms-overlay d-none" id="posCustomerError">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon" class="ms-icon-warning-amber"><i class="ti ti-user-exclamation"></i></div>
      <div class="ms-title">Customer Required</div>
      <p class="ms-sub">Please select a customer before completing the sale.</p>
    </div>
    <div class="ms-footer-center">
      <button class="ms-btn-ok-warn" onclick="closePosCustomerError()"><i class="ti ti-user me-1"></i>Select Customer</button>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="saleSuccessModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-380">
    <div class="modal-content pm-modal-content">
      <div class="modal-body py-4 text-center erp-bg-light">
        <div class="mb-3"><div class="pos-success-icon-wrap"><i class="ti ti-check" class="fs-3 text-success"></i></div></div>
        <h4 class="pos-success-title">Sale Complete!</h4>
        <p class="pos-success-sub">Invoice <strong id="sale-success-id" class="text-erp-primary"></strong> generated.</p>
        <div class="pos-success-box">
          <div class="pos-success-lbl">Total Paid</div>
          <div class="pos-success-amt" id="sale-success-amount"></div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer flex-column gap-2">
        <button class="pm-btn-save w-100" onclick="printLastSale()"><i class="ti ti-printer me-1"></i>Print Invoice</button>
        <button class="pm-btn-cancel w-100" data-bs-dismiss="modal" class="text-center">Done</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --pos-primary: #3B4FE4;
  --pos-primary-end: #5B6CF9;
  --pos-font: 'Inter', sans-serif;
}
.page-body, .page-wrapper {
  font-family: var(--pos-font);
  font-size: 14px;
  background: #F5F6FA !important;
}

.pos-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pos-header-card {
  background: linear-gradient(135deg, #3B4FE4 0%, #5B6CF9 100%);
  border: none;
  border-radius: 10px;
  overflow: hidden;
  position: relative;
}
.pos-header-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
  background-size: 16px 16px;
  opacity: 0.5;
  pointer-events: none;
}
.pos-header-card::after {
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
.pos-header-body {
  padding: 20px 28px !important;
  position: relative;
  z-index: 1;
}
.pos-header-card .pos-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #fff;
}
.pos-header-card .pos-subtitle {
  font-size: 0.82rem;
  font-weight: 400;
  color: rgba(255,255,255,0.82);
}

.pos-section-card {
  border: 1px solid #E8EAF0;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  background: #fff;
  overflow: hidden;
}

.pos-filter-body {
  padding: 12px 16px !important;
}
.pos-input {
  height: 36px !important;
  font-size: 0.85rem !important;
  border: 1px solid #DDE1EC !important;
  border-radius: 6px !important;
  transition: all 0.2s ease;
}
.pos-input:focus {
  border-color: var(--pos-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,79,228,0.08) !important;
}

.pos-product-card {
  cursor: pointer;
  border: 1px solid #E8EAF0;
  border-radius: 10px;
  background: #fff;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.pos-product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  border-color: #5B6CF9;
}
.pos-product-card.disabled {
  opacity: 0.45;
  cursor: not-allowed;
  pointer-events: none;
}
.pos-product-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #1A1D2E;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pos-product-sku {
  font-size: 0.68rem;
  color: #6B7280;
}
.pos-product-price {
  font-size: 0.85rem;
  font-weight: 700;
  color: #3B4FE4;
}
.pos-product-icon {
  height: 52px;
  background: #F8F9FC;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.pos-cart-header {
  padding: 14px 16px;
  border-bottom: 1px solid #E8EAF0;
}
.pos-clear-btn {
  background: none;
  border: none;
  font-size: 0.78rem;
  font-weight: 500;
  color: #EF4444;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background 0.15s ease;
}
.pos-clear-btn:hover { background: rgba(239,68,68,0.06); }

.pos-cart-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid #F0F2F8;
}
.pos-cart-item:last-child { border-bottom: none; }

.pos-checkout-panel {
  padding: 10px 14px;
  border-top: 1px solid #E8EAF0;
  background: #FAFBFD;
}

.pos-pay-btn {
  height: 32px;
  font-size: 0.78rem;
  font-weight: 600;
  border-radius: 7px;
  border: 1px solid #DDE1EC;
  color: #6B7280;
  background: #fff;
  transition: all 0.15s ease;
}
.pos-pay-btn.active {
  background: linear-gradient(135deg, #3B4FE4, #5B6CF9);
  border-color: #3B4FE4;
  color: #fff;
  box-shadow: 0 2px 6px rgba(59,79,228,0.25);
}

.pos-complete-btn {
  height: 38px;
  font-size: 0.85rem;
  font-weight: 700;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #10B981, #059669);
  color: #fff;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.pos-complete-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(16,185,129,0.35);
}
.pos-complete-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pos-qty-group {
  display: inline-flex;
  border: 1px solid #DDE1EC;
  border-radius: 6px;
  overflow: hidden;
}
.pos-qty-btn {
  width: 26px;
  height: 26px;
  border: none;
  background: #F8F9FC;
  color: #6B7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
  transition: background 0.15s ease;
}
.pos-qty-btn:hover { background: #EEF0F8; color: #3B4FE4; }
.pos-qty-input {
  width: 40px;
  height: 26px;
  border: none;
  border-left: 1px solid #DDE1EC;
  border-right: 1px solid #DDE1EC;
  text-align: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #1A1D2E;
  background: #fff;
  padding: 0;
  outline: none;
  -moz-appearance: textfield;
}
.pos-qty-input::-webkit-outer-spin-button,
.pos-qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.pos-qty-input:focus { background: #F0F4FF; border-left-color: #3B4FE4; border-right-color: #3B4FE4; }

.pos-cat-btn {
  height: 30px;
  padding: 0 12px;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 6px;
  border: 1px solid #DDE1EC;
  background: #fff;
  color: #6B7280;
  cursor: pointer;
  transition: all 0.15s ease;
}
.pos-cat-btn:hover { border-color: #5B6CF9; color: #5B6CF9; }
.pos-cat-btn.active {
  background: #3B4FE4;
  border-color: #3B4FE4;
  color: #fff;
}

.pm-modal-content {
  border-radius: 12px;
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.pm-modal-footer {
  background: #FFFFFF;
  border-top: 1px solid #E8EAF0;
  padding: 14px 24px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.pm-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6B7280;
  margin-bottom: 6px;
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
/* ── Editable Line Price ───────────────────────────────── */
.pos-line-price-wrap {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  border: 1px dashed #DDE1EC;
  border-radius: 6px;
  background: #F8F9FC;
  padding: 0 5px 0 6px;
  height: 28px;
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
  cursor: text;
}
.pos-line-price-wrap:hover {
  border-color: #5B6CF9;
  background: #fff;
  box-shadow: 0 0 0 2px rgba(91,108,249,0.08);
}
.pos-line-price-wrap:focus-within {
  border-color: #3B4FE4;
  background: #fff;
  box-shadow: 0 0 0 2px rgba(59,79,228,0.12);
  border-style: solid;
}
.pos-line-price-prefix {
  font-size: 0.72rem;
  font-weight: 600;
  color: #6B7280;
  white-space: nowrap;
}
.pos-line-price {
  width: 52px;
  border: none !important;
  background: transparent !important;
  font-size: 0.82rem !important;
  font-weight: 700 !important;
  color: #1A1D2E !important;
  text-align: right;
  padding: 0 !important;
  outline: none;
  box-shadow: none !important;
  -moz-appearance: textfield;
}
.pos-line-price::-webkit-outer-spin-button,
.pos-line-price::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.pos-line-price-icon {
  font-size: 11px;
  color: #B0B7C9;
  flex-shrink: 0;
  transition: color 0.15s ease;
}
.pos-line-price-wrap:hover .pos-line-price-icon,
.pos-line-price-wrap:focus-within .pos-line-price-icon { color: #5B6CF9; }

/* ── POS right card: allow SDD panel to overflow card boundary ── */
#pos-right-card { overflow: visible !important; border-radius: 10px; }
#pos-right-card .pos-cart-header { border-radius: 10px 10px 0 0; overflow: hidden; }

/* ── POS Customer SDD trigger (mirrors the old select appearance) ── */
.pos-sdd-trigger {
  height: 36px;
  font-size: 0.85rem;
  border: 1px solid #DDE1EC;
  border-radius: 6px;
  background: #fff;
  width: 100%;
  padding: 0 10px 0 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.pos-sdd-trigger:hover { border-color: #B0B7C9; }
.pos-sdd-trigger.sdd-open {
  border-color: #3B4FE4;
  box-shadow: 0 0 0 3px rgba(59,79,228,0.08);
}
.pos-sdd-trigger.is-invalid {
  border-color: #EF4444 !important;
  box-shadow: 0 0 0 3px rgba(239,68,68,0.1) !important;
}

/* ── Searchable Dropdown (SDD) ─────────────────────────── */
.sdd-wrap { position: relative; }
.sdd-caret { font-size: 14px; color: #6B7280; flex-shrink: 0; transition: transform 0.2s ease; }
.sdd-wrap.open .sdd-caret { transform: rotate(180deg); }
.sdd-disp { font-size: 0.85rem; color: #1A1D2E; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sdd-panel {
  display: none;
  position: absolute;
  top: calc(100% + 4px);
  left: 0; right: 0;
  background: #fff;
  border: 1px solid #DDE1EC;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  z-index: 3000;
  overflow: hidden;
}
.sdd-wrap.open .sdd-panel { display: block; }
.sdd-search-row { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-bottom: 1px solid #F0F2F8; }
.sdd-search-row .ti-search { font-size: 13px; color: #9CA3AF; flex-shrink: 0; }
.sdd-search-inp { border: none; outline: none; font-size: 0.82rem; color: #1A1D2E; flex: 1; background: transparent; }
.sdd-opts-wrap { max-height: 180px; overflow-y: auto; }
.sdd-opt { padding: 8px 12px; font-size: 0.83rem; color: #374151; cursor: pointer; transition: background 0.12s; }
.sdd-opt:hover { background: #F0F4FF; color: #3B4FE4; }
.sdd-no-res { padding: 10px 12px; font-size: 0.82rem; color: #9CA3AF; text-align: center; }
/* ── Overlays ── */
.ms-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;}
.ms-overlay.d-none{display:none!important;}
.ms-box{background:#fff;border-radius:14px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.18);overflow:hidden;animation:msIn .18s ease;}
@keyframes msIn{from{transform:scale(0.92);opacity:0}to{transform:scale(1);opacity:1}}
.ms-body{padding:28px 28px 20px;text-align:center;}
.ms-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:1.6rem;}
.ms-title{font-size:1rem;font-weight:700;color:#111827;margin:14px 0 6px;}
.ms-sub{font-size:0.83rem;color:#6B7280;margin:0;}
.ms-footer-center{padding:16px 24px;display:flex;gap:10px;justify-content:center;border-top:1px solid #F3F4F6;}
.ms-btn-ok-warn{height:36px;padding:0 28px;border:none;border-radius:7px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;font-size:0.83rem;font-weight:600;cursor:pointer;}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/pos.js') }}?v={{ filemtime(public_path('js/pages/pos.js')) }}"></script>
@endpush
