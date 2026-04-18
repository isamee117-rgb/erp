@extends('layouts.app')
@section('page-title', 'POS Terminal - LeanERP')
@section('content')
<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title">POS Terminal</h2>
        <p class="mb-0 inv-subtitle">Quick sales, real-time inventory, instant invoicing.</p>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8 d-flex flex-column">
    <div class="card inv-section-card mb-3">
      <div class="card-body inv-filter-body">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="d-flex gap-2 align-items-center">
              <div class="position-relative flex-fill">
                <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search erp-icon-sm"></i></span>
                <input type="text" class="form-control inv-input ps-5" id="pos-search" placeholder="Search or scan barcode — press Enter to add..." oninput="renderProducts();" onkeydown="posScannerEnter(event)" autofocus>
              </div>
              <button class="bc-cam-btn" onclick="openBarcodeScanner(function(c){document.getElementById('pos-search').value=c;renderProducts();posScannerEnter({key:'Enter',preventDefault:function(){}});})" title="Scan with camera"><i class="ti ti-camera erp-icon-lg"></i></button>
            </div>
            <div id="pos-scan-feedback" class="erp-feedback"></div>
          </div>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2" id="pos-categories"></div>
      </div>
    </div>
    <div class="card inv-section-card flex-fill d-flex flex-column">
      <div class="card-body p-3 overflow-auto flex-fill" id="pos-product-grid"></div>
    </div>
  </div>

  <div class="col-lg-4" style="align-self:flex-start;">
    <div class="card inv-section-card d-flex flex-column" id="pos-right-card">
      <div class="pos-cart-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Current Order</h5>
        <button class="pos-clear-btn" onclick="clearCart()"><i class="ti ti-trash me-1"></i>Clear</button>
      </div>
      <div class="p-3" id="pos-cart"></div>
      <div class="pos-checkout-panel">
        <div class="mb-2">
          <label class="pm-label"><i class="ti ti-user me-1"></i>Customer</label>
          <div class="sdd-wrap" id="pos-customer-sdd">
            <div class="pos-sdd-trigger" id="pos-customer-trigger" onclick="sddToggle('pos-customer-sdd')">
              <span class="sdd-disp erp-dropdown-placeholder" id="pos-customer-disp">— Select Customer —</span>
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
        <div class="d-flex justify-content-between mb-2 pt-1"><span class="fw-bold">Grand Total</span><span id="pos-total" class="fw-bold pos-total-val">0.00</span></div>
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
      <div class="ms-icon ms-icon-warning"><i class="ti ti-user-exclamation"></i></div>
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
        <div class="mb-3"><div class="pos-success-icon-wrap"><i class="ti ti-check fs-3 text-success"></i></div></div>
        <h4 class="pos-success-title">Sale Complete!</h4>
        <p class="pos-success-sub">Invoice <strong id="sale-success-id" class="text-erp-primary"></strong> generated.</p>
        <div class="pos-success-box">
          <div class="pos-success-lbl">Total Paid</div>
          <div class="pos-success-amt" id="sale-success-amount"></div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer flex-column gap-2">
        <button class="pm-btn-save w-100" onclick="printLastSale()"><i class="ti ti-printer me-1"></i>Print Invoice</button>
        <button class="pm-btn-cancel w-100 text-center" data-bs-dismiss="modal">Done</button>
      </div>
    </div>
  </div>
</div>
@endsection


@push('scripts')
<script src="{{ asset('js/pages/pos.js') }}?v={{ filemtime(public_path('js/pages/pos.js')) }}"></script>
@endpush
