@extends('layouts.app')
@section('page-title', 'Product Master - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title">Product Master</h2>
        <p class="mb-0 inv-subtitle">Configure global catalog, pricing strategies, and stock thresholds.</p>
      </div>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-light shadow-sm" id="inv-sel-toggle-btn" onclick="toggleInvSelectMode()" title="Multi-select mode">
          <i class="ti ti-checkbox me-1"></i>Select
        </button>
        <button class="btn btn-light shadow-sm" onclick="openProductModal('add')">
          <i class="ti ti-plus me-1"></i>Add Product
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card">
  <div class="card-body p-0">
    <div class="row g-0" id="inv-stats-row">
      <div class="col-6 col-md-3">
        <div class="inv-stat-cell inv-stat-blue">
          <div class="inv-stat-icon-sm"><i class="ti ti-package"></i></div>
          <div>
            <div class="inv-stat-label">Total Products</div>
            <div class="inv-stat-value" id="stat-total">0</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="inv-stat-cell inv-stat-green">
          <div class="inv-stat-icon-sm"><i class="ti ti-circle-check"></i></div>
          <div>
            <div class="inv-stat-label">In Stock</div>
            <div class="inv-stat-value text-success" id="stat-instock">0</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="inv-stat-cell inv-stat-red">
          <div class="inv-stat-icon-sm"><i class="ti ti-alert-circle"></i></div>
          <div>
            <div class="inv-stat-label">Out of Stock</div>
            <div class="inv-stat-value text-danger" id="stat-outstock">0</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="inv-stat-cell inv-stat-orange">
          <div class="inv-stat-icon-sm"><i class="ti ti-alert-triangle"></i></div>
          <div>
            <div class="inv-stat-label">Low Stock</div>
            <div class="inv-stat-value text-warning" id="stat-lowstock">0</div>
          </div>
        </div>
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
          <input type="text" class="form-control inv-input ps-5" id="inv-search" placeholder="Search by Item No., barcode, or name..." oninput="currentPage=1;renderPage();">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select inv-input" id="inv-category" onchange="currentPage=1;renderPage();"><option value="all">All Categories</option></select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select inv-input" id="inv-type" onchange="currentPage=1;renderPage();">
          <option value="all">All Types</option><option value="Product">Product</option><option value="Service">Service</option>
        </select>
      </div>
      <div class="col-auto ms-md-auto">
        <label class="d-flex align-items-center gap-2 mb-0 cursor-pointer">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input inv-toggle" type="checkbox" role="switch" id="inv-lowstock" onchange="currentPage=1;renderPage();">
          </div>
          <span class="inv-toggle-label">Low Stock Only</span>
        </label>
      </div>
    </div>
  </div>
</div>

<div id="inv-bulk-bar" class="inv-bulk-bar d-none">
  <div class="d-flex align-items-center gap-3">
    <span class="inv-bulk-count"><i class="ti ti-checkbox me-1"></i><span id="inv-sel-count">0</span> product(s) selected</span>
    <button class="inv-bulk-del-btn" onclick="confirmDeleteSelectedProducts()"><i class="ti ti-trash me-1"></i>Delete Selected</button>
    <button class="inv-bulk-clear-btn" onclick="clearProductSelection()"><i class="ti ti-x me-1"></i>Clear Selection</button>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th inv-chk-col" class="col-erp-checkbox"><input type="checkbox" class="inv-chk" id="inv-select-all" onclick="toggleSelectAllProducts(this)" title="Select all"></th>
          <th class="cursor-pointer inv-th" onclick="toggleSort('itemNumber')">Item No. <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="cursor-pointer inv-th" onclick="toggleSort('name')">Name <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th">Category</th>
          <th class="inv-th">Type</th>
          <th class="inv-th">UOM</th>
          <th class="text-end cursor-pointer inv-th" onclick="toggleSort('unitCost')">Cost <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="text-end cursor-pointer inv-th" onclick="toggleSort('unitPrice')">Price <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="text-center cursor-pointer inv-th" onclick="toggleSort('currentStock')">Stock <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="text-center inv-th">Reorder</th>
          <th class="inv-th">Status</th>
          <th class="text-center inv-th">Actions</th>
        </tr>
      </thead>
      <tbody id="inv-tbody"></tbody>
    </table>
  </div>
  <div class="card-footer inv-table-footer d-flex align-items-center justify-content-between">
    <div class="text-muted" id="inv-info" erp-text-sm"></div>
    <ul class="pagination mb-0" id="inv-pagination"></ul>
  </div>
</div>

</div>

{{-- Delete Confirm Overlay --}}
<div class="ms-overlay d-none" id="invDeleteConfirm">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon" class="ms-icon-danger"><i class="ti ti-trash"></i></div>
      <div class="ms-title" id="invDeleteConfirmTitle">Delete Product?</div>
      <p class="ms-sub" id="invDeleteConfirmSub">Are you sure you want to delete this product? This cannot be undone.</p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="document.getElementById('invDeleteConfirm').classList.add('d-none')">Cancel</button>
      <button class="ms-btn-confirm" class="btn-erp-danger-gradient" onclick="doDeleteProduct()">Yes, Delete</button>
    </div>
  </div>
</div>

{{-- Delete Error Overlay --}}
<div class="ms-overlay d-none" id="invDeleteError">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon" class="ms-icon-warning"><i class="ti ti-alert-triangle"></i></div>
      <div class="ms-title">Cannot Delete</div>
      <p class="ms-sub" id="invDeleteErrorMsg"></p>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" class="btn-erp-danger-gradient" onclick="document.getElementById('invDeleteError').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Delete Success Overlay --}}
<div class="ms-overlay d-none" id="invDeleteSuccess">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Deleted!</div>
      <p class="ms-sub" id="invDeleteSuccessMsg">Product has been removed from the system.</p>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" onclick="document.getElementById('invDeleteSuccess').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Confirm Save Overlay --}}
<div class="ms-overlay d-none" id="invSaveConfirm">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm"><i class="ti ti-edit"></i></div>
      <div class="ms-title">Save Product?</div>
      <p class="ms-sub">Are you sure you want to save this product?</p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="document.getElementById('invSaveConfirm').classList.add('d-none')">Cancel</button>
      <button class="ms-btn-confirm" onclick="doSaveProduct()"><i class="ti ti-device-floppy me-1"></i>Yes, Save</button>
    </div>
  </div>
</div>

{{-- Success Overlay --}}
<div class="ms-overlay d-none" id="invSaveSuccess">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Saved!</div>
      <p class="ms-sub">Product saved successfully.</p>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" onclick="document.getElementById('invSaveSuccess').classList.add('d-none')"><i class="ti ti-check me-1"></i>OK</button>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-620">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="productModalTitle"><i class="ti ti-package me-2"></i>Add Product</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <form id="productForm">
          <input type="hidden" id="pf-id">
          <div class="row pm-field-row">
            <div class="col-8"><label class="pm-label">Product Name <span class="text-danger">*</span></label><input type="text" class="form-control pm-input" id="pf-name" placeholder="Enter product name" required></div>
            <div class="col-4"><label class="pm-label">Barcode</label><div class="d-flex gap-1"><input type="text" class="form-control pm-input" id="pf-barcode" placeholder="Optional"><button type="button" class="bc-cam-btn" onclick="openBarcodeScanner(function(c){document.getElementById('pf-barcode').value=c;})" title="Scan with camera"><i class="ti ti-camera" class="erp-icon-md"></i></button></div></div>
          </div>
          <div class="row pm-field-row">
            <div class="col-4"><label class="pm-label">Category</label><select class="form-select pm-input" id="pf-category"></select></div>
            <div class="col-4"><label class="pm-label">UOM</label><select class="form-select pm-input" id="pf-uom"></select></div>
            <div class="col-4"><label class="pm-label">Item Type</label>
              <select class="form-select pm-input" id="pf-type"><option value="Product">Product</option><option value="Service">Service</option></select>
            </div>
          </div>
          <div class="row pm-field-row">
            <div class="col-4">
              <label class="pm-label">Unit Cost</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Rs.</span>
                <input type="number" step="0.01" class="form-control pm-input" id="pf-cost" value="0">
              </div>
            </div>
            <div class="col-4">
              <label class="pm-label">Unit Price</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Rs.</span>
                <input type="number" step="0.01" class="form-control pm-input" id="pf-price" value="0">
              </div>
            </div>
            <div class="col-4">
              <label class="pm-label">Reorder Level</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Qty</span>
                <input type="number" class="form-control pm-input" id="pf-reorder" value="0">
              </div>
            </div>
          </div>
          <div class="row pm-field-row" id="pf-opening-row">
            <div class="col-4">
              <label class="pm-label">Opening Stock</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Qty</span>
                <input type="number" min="0" class="form-control pm-input" id="pf-opening" value="0" placeholder="0">
              </div>
              <div class="erp-info-hint"><i class="ti ti-info-circle me-1"></i>Initial stock on hand when adding new product</div>
            </div>
          </div>

          {{-- Accounting Mappings (collapsible) --}}
          <div class="pm-acct-wrap">
            <button type="button" class="pm-acct-toggle" onclick="toggleProductAccounting()">
              <span><i class="ti ti-book-2 me-2"></i>Accounting Accounts</span>
              <i class="ti ti-chevron-down" id="pfAcctChevron"></i>
            </button>
            <div id="pfAcctSection" class="pm-acct-body" style="display:none;">
              <div class="row g-2">
                <div class="col-12 col-md-4">
                  <label class="pm-label">Sales Revenue Account</label>
                  <select class="form-select pm-input" id="pf-acct-sales-revenue">
                    <option value="">— Not set —</option>
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="pm-label">Cost of Goods Sold Account</label>
                  <select class="form-select pm-input" id="pf-acct-cogs">
                    <option value="">— Not set —</option>
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="pm-label">Inventory Asset Account</label>
                  <select class="form-select pm-input" id="pf-acct-inventory">
                    <option value="">— Not set —</option>
                  </select>
                </div>
              </div>
              <div class="erp-info-hint mt-2"><i class="ti ti-info-circle me-1"></i>Company-wide defaults used for journal posting on sales &amp; purchases.</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" id="pf-submit" onclick="confirmSaveProduct()"><i class="ti ti-device-floppy me-1"></i>Save Product</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-440">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-adjustments me-2"></i>Stock Adjustment</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="pm-field-row">
          <label class="pm-label">Product</label>
          <div class="erp-adj-product-name" id="adj-product-name"></div>
        </div>
        <div class="pm-field-row">
          <label class="pm-label">Quantity Change (+/-)</label>
          <div class="input-group">
            <span class="input-group-text pm-prefix">Qty</span>
            <input type="number" class="form-control pm-input" id="adj-qty" value="0">
          </div>
        </div>
        <div class="pm-field-row" class="mb-0">
          <label class="pm-label">Reason</label>
          <select class="form-select pm-input" id="adj-type">
            <option value="Adjustment_Damage">Damage / Spoilage</option>
            <option value="Adjustment_Loss">Loss / Theft</option>
            <option value="Adjustment_Found">Found / Returned</option>
            <option value="Adjustment_Internal">Internal Use</option>
          </select>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="submitAdjustment()"><i class="ti ti-check me-1"></i>Commit</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --inv-primary: #3B4FE4;
  --inv-primary-end: #5B6CF9;
  --inv-font: 'Inter', sans-serif;
}
.page-body, .page-wrapper {
  font-family: var(--inv-font);
  font-size: 14px;
  background: #F5F6FA !important;
}

.inv-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.inv-header-card {
  background: linear-gradient(135deg, #3B4FE4 0%, #5B6CF9 100%);
  border: none;
  border-radius: 10px;
  overflow: hidden;
  position: relative;
}
.inv-header-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
  background-size: 16px 16px;
  opacity: 0.5;
  pointer-events: none;
}
.inv-header-card::after {
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
.inv-header-body {
  padding: 20px 28px !important;
  position: relative;
  z-index: 1;
}
.inv-header-card .inv-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #fff;
}
.inv-header-card .inv-subtitle {
  font-size: 0.82rem;
  font-weight: 400;
  color: rgba(255,255,255,0.82);
}
.inv-header-card .btn {
  font-size: 0.82rem;
  font-weight: 600;
  padding: 8px 18px;
}

.inv-section-card {
  border: 1px solid #E8EAF0;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  background: #fff;
}

.inv-stat-cell {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-right: 1px solid #F0F2F8;
  border-bottom: 1px solid #F0F2F8;
  position: relative;
}
.inv-stat-cell::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
}
.inv-stat-blue::before { background: #3B4FE4; }
.inv-stat-green::before { background: #10B981; }
.inv-stat-red::before { background: #EF4444; }
.inv-stat-orange::before { background: #F59E0B; }

.inv-stat-icon-sm {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 18px;
}
.inv-stat-blue .inv-stat-icon-sm { background: rgba(59,79,228,0.08); color: #3B4FE4; }
.inv-stat-green .inv-stat-icon-sm { background: rgba(16,185,129,0.08); color: #10B981; }
.inv-stat-red .inv-stat-icon-sm { background: rgba(239,68,68,0.08); color: #EF4444; }
.inv-stat-orange .inv-stat-icon-sm { background: rgba(245,158,11,0.08); color: #F59E0B; }

.inv-stat-label {
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: 2px;
}
.inv-stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.1;
  color: #1e293b;
}

.inv-filter-body {
  padding: 12px 16px !important;
}
.inv-input {
  height: 36px !important;
  font-size: 0.85rem !important;
  border: 1px solid #DDE1EC !important;
  border-radius: 6px !important;
  transition: all 0.2s ease;
}
.inv-input:focus {
  border-color: var(--inv-primary) !important;
  box-shadow: 0 0 0 3px rgba(59,79,228,0.08) !important;
}
.inv-toggle-label {
  font-size: 0.82rem;
  font-weight: 500;
  color: #64748b;
}
.inv-toggle {
  width: 2.2em !important;
  height: 1.2em !important;
  cursor: pointer;
}
.inv-toggle:checked {
  background-color: #f59e0b;
  border-color: #f59e0b;
}

.inv-table-card { overflow: hidden; }
.inv-table thead {
  background: #F8F9FC;
}
.inv-th {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #64748b;
  border-bottom: 2px solid #E8EAF0 !important;
  white-space: nowrap;
  padding: 10px 14px !important;
}
.inv-table tbody tr {
  transition: background-color 0.15s ease;
}
.inv-table tbody tr:hover {
  background-color: #F5F7FF !important;
}
.inv-table tbody td {
  padding: 10px 14px !important;
  vertical-align: middle;
  border-bottom: 1px solid #F0F2F8 !important;
  border-top: none !important;
}

.inv-row-outofstock {
  background-color: #FFF8F8 !important;
  border-left: 3px solid #FF4D4F;
}
.inv-row-outofstock:hover {
  background-color: #FFF0F0 !important;
}
.inv-row-lowstock {
  background-color: #FFFDF5 !important;
  border-left: 3px solid #fcd34d;
}
.inv-row-lowstock:hover {
  background-color: #FFF9E6 !important;
}

.inv-sku {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  font-size: 0.75rem;
  font-weight: 500;
  color: #5B6CF9;
  background: #EEF0F8;
  padding: 2px 8px;
  border-radius: 4px;
  display: inline-block;
}
.inv-product-name {
  font-size: 0.88rem;
  font-weight: 400;
  color: #1e293b;
}
.inv-category-text {
  font-size: 0.82rem;
  font-weight: 400;
  color: #64748b;
}
.inv-cost {
  font-size: 0.82rem;
  font-weight: 400;
  color: #475569;
}
.inv-price {
  font-size: 0.85rem;
  font-weight: 400;
  color: #1e293b;
}
.inv-stock-num {
  font-size: 0.88rem;
  font-weight: 400;
}
.inv-reorder-num {
  font-size: 0.85rem;
  font-weight: 400;
  color: #64748b;
}

.badge-type-product {
  background: transparent;
  color: #2563eb;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  border: 1px solid #2563eb;
}
.badge-type-service {
  background: transparent;
  color: #7c3aed;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  border: 1px solid #7c3aed;
}
.badge-status {
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.72rem;
  letter-spacing: 0.03em;
}
.badge-status-instock { background: rgba(16,185,129,0.1); color: #059669; }
.badge-status-outofstock { background: rgba(239,68,68,0.1); color: #dc2626; }
.badge-status-lowstock { background: rgba(245,158,11,0.1); color: #d97706; }
.badge-status-service { background: rgba(100,116,139,0.1); color: #64748b; }

.inv-action-btn {
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
.inv-action-btn:hover {
  color: #3B4FE4;
  background: #EEF0F8;
}
.inv-action-btn.inv-action-warn:hover {
  color: #f59e0b;
  background: rgba(245,158,11,0.08);
}
.inv-action-btn.inv-action-danger:hover {
  color: #dc2626;
  background: rgba(220,38,38,0.08);
}
/* ── Bulk Select ── */
.inv-chk-col { display: none; }
.inv-select-active .inv-chk-col { display: table-cell; }
.inv-chk { width: 15px; height: 15px; cursor: pointer; accent-color: #3B4FE4; vertical-align: middle; }
#inv-sel-toggle-btn { transition: all 0.15s; }
#inv-sel-toggle-btn.active { background: #3B4FE4 !important; color: #fff !important; border-color: #3B4FE4 !important; }
.inv-bulk-bar { background: #EEF0FF; border: 1px solid #C5CAE9; border-radius: 8px; padding: 10px 18px; display: flex; align-items: center; }
.inv-bulk-count { font-size: 0.85rem; font-weight: 600; color: #3B4FE4; }
.inv-bulk-del-btn { background: linear-gradient(135deg,#dc2626,#ef4444); border: none; border-radius: 7px; padding: 7px 16px; font-size: 0.82rem; font-weight: 600; color: #fff; cursor: pointer; transition: opacity 0.15s; }
.inv-bulk-del-btn:hover { opacity: 0.88; }
.inv-bulk-clear-btn { background: none; border: 1px solid #DDE1EC; border-radius: 7px; padding: 7px 14px; font-size: 0.82rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.15s; }
.inv-bulk-clear-btn:hover { border-color: #94a3b8; color: #1e293b; }

.inv-table-footer {
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
  text-align: center;
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
.cursor-pointer { cursor: pointer; }

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
<script src="{{ asset('js/pages/inventory.js') }}?v={{ filemtime(public_path('js/pages/inventory.js')) }}"></script>
@endpush
