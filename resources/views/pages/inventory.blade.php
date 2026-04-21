@extends('layouts.app')
@section('page-title', 'Product Master - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title">Product Master</h2>
        <p class="mb-0 inv-subtitle">Manage your products,set prices,and control stock levels.</p>
      </div>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-light shadow-sm" id="inv-add-product-btn">
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
    {{-- Row 1: Search + icon toolbar --}}
    <div class="d-flex align-items-center gap-2">
      <div class="flex-grow-1 position-relative">
        <span class="position-absolute top-50 translate-middle-y ms-3 text-muted"><i class="ti ti-search"></i></span>
        <input type="text" class="form-control inv-input ps-5" id="inv-search" placeholder="Search by Item No., barcode, or name...">
      </div>
      <div class="inv-toolbar-group">
        <button class="inv-icon-btn" id="inv-filter-toggle-btn" title="Toggle Filters">
          <i class="ti ti-filter"></i>
        </button>
        <div class="dropdown">
          <button class="inv-icon-btn" id="invColsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Columns">
            <i class="ti ti-layout-columns"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end inv-cols-menu" id="invColsMenu"></ul>
        </div>
        <button class="inv-icon-btn" id="inv-sel-toggle-btn" title="Multi-select">
          <i class="ti ti-checkbox"></i>
        </button>
      </div>
    </div>
    {{-- Row 2: Collapsible filters --}}
    <div id="inv-filters-panel" class="d-none mt-2">
      <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
          <select class="form-select inv-input" id="inv-category"><option value="all">All Categories</option></select>
        </div>
        <div class="col-6 col-md-3">
          <select class="form-select inv-input" id="inv-type">
            <option value="all">All Types</option><option value="Product">Product</option><option value="Service">Service</option><option value="Non-inventory">Non-inventory</option>
          </select>
        </div>
        <div class="col-auto ms-md-auto">
          <label class="d-flex align-items-center gap-2 mb-0 cursor-pointer">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input inv-toggle" type="checkbox" role="switch" id="inv-lowstock">
            </div>
            <span class="inv-toggle-label">Low Stock Only</span>
          </label>
        </div>
      </div>
    </div>
    <div id="inv-dyn-filters" class="d-flex flex-wrap gap-2 mt-2"></div>
  </div>
</div>

<div id="inv-bulk-bar" class="inv-bulk-bar d-none">
  <div class="d-flex align-items-center gap-3">
    <span class="inv-bulk-count"><i class="ti ti-checkbox me-1"></i><span id="inv-sel-count">0</span> product(s) selected</span>
    <button class="inv-bulk-del-btn" id="inv-bulk-del-btn"><i class="ti ti-trash me-1"></i>Delete Selected</button>
    <button class="inv-bulk-clear-btn" id="inv-bulk-clear-btn"><i class="ti ti-x me-1"></i>Clear Selection</button>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr id="inv-thead-row">
          <th class="inv-th inv-chk-col"><input type="checkbox" class="inv-chk" id="inv-select-all" title="Select all"></th>
          <th class="cursor-pointer inv-th" data-sort="itemNumber">Item No. <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="cursor-pointer inv-th" data-sort="name">Name <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="inv-th">Category</th>
          <th class="inv-th">Type</th>
          <th class="inv-th">UOM</th>
          <th class="text-end cursor-pointer inv-th" data-sort="unitCost">Cost <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="text-end cursor-pointer inv-th" data-sort="unitPrice">Price <i class="ti ti-arrows-sort ms-1"></i></th>
          <th class="text-center cursor-pointer inv-th" data-sort="currentStock">Stock <i class="ti ti-arrows-sort ms-1"></i></th>
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
      <button class="ms-btn-cancel" id="invDeleteConfirmCancel">Cancel</button>
      <button class="ms-btn-confirm" id="invDeleteConfirmOk">Yes, Delete</button>
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
      <button class="ms-btn-ok" id="invDeleteErrorOk">OK</button>
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
      <button class="ms-btn-ok" id="invDeleteSuccessOk">OK</button>
    </div>
  </div>
</div>

{{-- Adj Confirm Overlay --}}
<div class="ms-overlay d-none" id="invAdjConfirm">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm"><i class="ti ti-adjustments"></i></div>
      <div class="ms-title">Commit Adjustment?</div>
      <p class="ms-sub" id="invAdjConfirmSub">Are you sure you want to commit this stock adjustment?</p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" id="invAdjConfirmCancel">Cancel</button>
      <button class="ms-btn-confirm" id="invAdjConfirmOk"><i class="ti ti-check me-1"></i>Yes, Commit</button>
    </div>
  </div>
</div>

{{-- Adj Success Overlay --}}
<div class="ms-overlay d-none" id="invAdjSuccess">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Adjustment Committed!</div>
      <p class="ms-sub">Stock has been updated successfully.</p>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" id="invAdjSuccessOk"><i class="ti ti-check me-1"></i>OK</button>
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
      <button class="ms-btn-cancel" id="invSaveConfirmCancel">Cancel</button>
      <button class="ms-btn-confirm" id="invSaveConfirmOk"><i class="ti ti-device-floppy me-1"></i>Yes, Save</button>
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
      <button class="ms-btn-ok" id="invSaveSuccessOk"><i class="ti ti-check me-1"></i>OK</button>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-680">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="productModalTitle"><i class="ti ti-package me-2"></i>Add Product</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">
        <form id="productForm">
          <input type="hidden" id="pf-id">
          <div class="row pm-field-row">
            <div class="col-8"><label class="pm-label">Product Name <span class="text-danger">*</span></label><input type="text" class="form-control pm-input" id="pf-name" placeholder="Enter product name" maxlength="32" required><div class="invalid-feedback" id="pf-name-error">Product name is required.</div></div>
            <div class="col-4"><label class="pm-label">Barcode</label><div class="d-flex gap-1"><input type="text" class="form-control pm-input" id="pf-barcode" placeholder="Optional"><button type="button" class="bc-cam-btn" id="pf-barcode-scan-btn" title="Scan with camera"><i class="ti ti-camera" class="erp-icon-md"></i></button></div></div>
          </div>
          <div class="row pm-field-row">
            <div class="col-4"><label class="pm-label">Category</label><select class="form-select pm-input" id="pf-category"></select></div>
            <div class="col-4"><label class="pm-label">UOM</label><select class="form-select pm-input" id="pf-uom"></select></div>
            <div class="col-4"><label class="pm-label">Item Type</label><select class="form-select pm-input" id="pf-type"><option value="Product">Product</option><option value="Service">Service</option><option value="Non-inventory">Non-inventory</option></select></div>
          </div>
          <div class="row pm-field-row">
            <div class="col-4">
              <label class="pm-label">Unit Cost</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Rs.</span>
                <input type="number" step="0.01" class="form-control pm-input" id="pf-cost" value="0">
              </div>
              <div class="text-danger small mt-1 d-none" id="pf-cost-error">Unit cost cannot be negative.</div>
            </div>
            <div class="col-4">
              <label class="pm-label">Unit Price</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Rs.</span>
                <input type="number" step="0.01" class="form-control pm-input" id="pf-price" value="0">
              </div>
              <div class="text-danger small mt-1 d-none" id="pf-price-error">Unit price cannot be negative.</div>
            </div>
            <div class="col-4">
              <label class="pm-label">Reorder Level</label>
              <div class="input-group">
                <span class="input-group-text pm-prefix">Qty</span>
                <input type="number" class="form-control pm-input" id="pf-reorder" value="0">
              </div>
              <div class="text-danger small mt-1 d-none" id="pf-reorder-error">Reorder level cannot be negative.</div>
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

          {{-- Dynamic Fields (enabled product fields appear here) --}}
          <div id="pf-dynamic-fields" class="row pm-field-row g-3"></div>

          {{-- Accounting Mappings (collapsible) --}}
          <div class="pm-acct-wrap">
            <button type="button" class="pm-acct-toggle" id="pfAcctToggle">
              <span><i class="ti ti-book-2 me-2"></i>Posting Accounts</span>
              <i class="ti ti-chevron-down" id="pfAcctChevron"></i>
            </button>
            <div id="pfAcctSection" class="pm-acct-body d-none">
              <div class="row g-2">
                <div class="col-12 col-md-4">
                  <label class="pm-label">Sales Revenue</label>
                  <div class="sdd-wrap" id="sdd-acct-sales-revenue">
                    <div class="sr-sdd-trigger" onclick="acctSddToggle('sdd-acct-sales-revenue')">
                      <span class="sdd-disp" id="sdd-acct-sales-revenue-disp" style="color:#B0B7C9">— Not set —</span>
                      <i class="ti ti-chevron-down sdd-caret"></i>
                    </div>
                    <div class="sdd-panel">
                      <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="acctSddFilter('sdd-acct-sales-revenue',this.value)" onclick="event.stopPropagation()"></div>
                      <div class="sdd-opts-wrap" id="sdd-acct-sales-revenue-opts"></div>
                    </div>
                    <input type="hidden" id="pf-acct-sales-revenue">
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="pm-label">Cost of Goods Sold</label>
                  <div class="sdd-wrap" id="sdd-acct-cogs">
                    <div class="sr-sdd-trigger" onclick="acctSddToggle('sdd-acct-cogs')">
                      <span class="sdd-disp" id="sdd-acct-cogs-disp" style="color:#B0B7C9">— Not set —</span>
                      <i class="ti ti-chevron-down sdd-caret"></i>
                    </div>
                    <div class="sdd-panel">
                      <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="acctSddFilter('sdd-acct-cogs',this.value)" onclick="event.stopPropagation()"></div>
                      <div class="sdd-opts-wrap" id="sdd-acct-cogs-opts"></div>
                    </div>
                    <input type="hidden" id="pf-acct-cogs">
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="pm-label">Inventory Asset</label>
                  <div class="sdd-wrap" id="sdd-acct-inventory">
                    <div class="sr-sdd-trigger" onclick="acctSddToggle('sdd-acct-inventory')">
                      <span class="sdd-disp" id="sdd-acct-inventory-disp" style="color:#B0B7C9">— Not set —</span>
                      <i class="ti ti-chevron-down sdd-caret"></i>
                    </div>
                    <div class="sdd-panel">
                      <div class="sdd-search-row"><i class="ti ti-search"></i><input type="text" class="sdd-search-inp" placeholder="Search..." oninput="acctSddFilter('sdd-acct-inventory',this.value)" onclick="event.stopPropagation()"></div>
                      <div class="sdd-opts-wrap" id="sdd-acct-inventory-opts"></div>
                    </div>
                    <input type="hidden" id="pf-acct-inventory">
                  </div>
                </div>
              </div>
              <div class="erp-info-hint mt-2"><i class="ti ti-info-circle me-1"></i>Set default accounts for recording sales and purchase transactions.</div>
            </div>
          </div>
          {{-- UOM Conversions (edit mode only, populated by JS) --}}
          <div id="pf-uom-section" class="d-none"></div>
          {{-- Price Tiers (edit mode only, populated by JS) --}}
          <div id="pf-price-tiers-section" class="d-none"></div>
        </form>
      </div>
      <div class="px-3 pb-2 d-none" id="pf-save-error">
        <div class="alert alert-danger py-2 mb-0 small" id="pf-save-error-msg"></div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" id="pf-submit"><i class="ti ti-device-floppy me-1"></i>Save Product</button>
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
          <div class="invalid-feedback d-block d-none" id="adj-qty-error"></div>
        </div>
        <div class="pm-field-row">
          <label class="pm-label">Reason</label>
          <select class="form-select pm-input" id="adj-type">
            <option value="Adjustment_Damage">Damage / Spoilage</option>
            <option value="Adjustment_Loss">Loss / Theft</option>
            <option value="Adjustment_Found">Found / Returned</option>
            <option value="Adjustment_Internal">Internal Use</option>
          </select>
        </div>
        <div class="pm-field-row mb-0">
          <label class="pm-label">Further Details <span class="text-muted fw-normal">(optional)</span></label>
          <textarea class="form-control pm-input" id="adj-notes" rows="5" placeholder="Add any additional notes..." style="min-height:120px;"></textarea>
        </div>
      </div>
      <div class="px-3 pb-2 d-none" id="adj-save-error">
        <div class="alert alert-danger py-2 mb-0 small" id="adj-save-error-msg"></div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" id="adj-commit-btn"><i class="ti ti-check me-1"></i>Commit</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/inventory.js') }}?v={{ filemtime(public_path('js/pages/inventory.js')) }}"></script>
@endpush
