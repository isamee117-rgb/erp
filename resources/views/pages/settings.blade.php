@extends('layouts.app')
@section('page-title', 'System Setup - LeanERP')
@section('content')

{{-- Delete Confirm Overlay --}}
<div id="stgDeleteConfirm" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm"><i class="ti ti-trash"></i></div>
      <div class="ms-title">Confirm Delete</div>
      <div class="ms-sub">Are you sure you want to delete this item? This action cannot be undone.</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="cancelStgDelete()">Cancel</button>
      <button class="ms-btn-confirm" onclick="doStgDelete()">Yes, Delete</button>
    </div>
  </div>
</div>

{{-- Delete Error Overlay --}}
<div id="stgDeleteError" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-error"><i class="ti ti-alert-triangle"></i></div>
      <div class="ms-title">Cannot Delete</div>
      <div class="ms-sub" id="stgDeleteErrorMsg"></div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" class="btn-erp-danger-gradient" onclick="document.getElementById('stgDeleteError').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Delete Success Overlay --}}
<div id="stgDeleteSuccess" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Deleted Successfully</div>
      <div class="ms-sub">The item has been removed from the system.</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" onclick="document.getElementById('stgDeleteSuccess').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Sequence Save Confirm Overlay --}}
<div id="stgSeqConfirm" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-confirm"><i class="ti ti-device-floppy"></i></div>
      <div class="ms-title">Save Sequence?</div>
      <div class="ms-sub" id="stgSeqConfirmMsg">Are you sure you want to save this document sequence?</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="document.getElementById('stgSeqConfirm').classList.add('d-none')">Cancel</button>
      <button class="ms-btn-confirm" onclick="doSaveSeq()"><i class="ti ti-device-floppy me-1"></i>Yes, Save</button>
    </div>
  </div>
</div>

{{-- Sequence Save Success Overlay --}}
<div id="stgSeqSuccess" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Sequence Saved!</div>
      <div class="ms-sub" id="stgSeqSuccessMsg">Document sequence has been updated successfully.</div>
    </div>
    <div class="ms-footer" class="justify-center">
      <button class="ms-btn-ok" onclick="document.getElementById('stgSeqSuccess').classList.add('d-none')"><i class="ti ti-check me-1"></i>OK</button>
    </div>
  </div>
</div>

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-settings me-2"></i>System Setup</h2>
        <p class="mb-0 inv-subtitle">Configure currency, invoice format, costing method and more.</p>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">

  {{-- Currency --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-coin me-2 text-warning"></i>Currency</div>
      <div class="set-card-body">
        <label class="pm-label">Select Currency</label>
        <select class="form-select pm-input" id="currencySelect">
          <option value="$">US Dollar ($)</option>
          <option value="€">Euro (€)</option>
          <option value="£">British Pound (£)</option>
          <option value="Rs.">Pakistani Rupee (Rs.)</option>
          <option value="₹">Indian Rupee (₹)</option>
          <option value="¥">Japanese Yen (¥)</option>
          <option value="A$">Australian Dollar (A$)</option>
          <option value="C$">Canadian Dollar (C$)</option>
        </select>
        <div id="currencyActions" class="mt-2"></div>
      </div>
    </div>
  </div>

  {{-- Invoice Format --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-file-text me-2 text-blue"></i>Invoice Format</div>
      <div class="set-card-body">
        <label class="pm-label">Select Format</label>
        <select class="form-select pm-input" id="invoiceFormatSelect" onchange="saveInvoiceFormat()">
          <option value="A4">A4 Standard Professional</option>
          <option value="Thermal">Thermal Receipt (80mm)</option>
        </select>
      </div>
    </div>
  </div>

  {{-- Costing Method --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-calculator me-2 text-green"></i>Costing Method</div>
      <div class="set-card-body">
        <label class="pm-label">Select Method</label>
        <select class="form-select pm-input" id="costingMethodSelect" onchange="saveCostingMethod()">
          <option value="moving_average">Moving Average</option>
          <option value="fifo">FIFO (First In, First Out)</option>
        </select>
        <p class="set-desc" id="costingDesc"></p>
      </div>
    </div>
  </div>

  {{-- Active Account --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-user me-2 text-purple"></i>Active Account</div>
      <div class="set-card-body">
        <div class="d-flex align-items-center gap-3">
          <div class="set-avatar" id="settingsAvatar">?</div>
          <div>
            <div class="fw-bold" id="settingsUsername" style="font-size:0.9rem;">—</div>
            <span class="badge-pill badge-blue mt-1" id="settingsRole">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Product Categories --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-category me-2 text-orange"></i>Product Categories</div>
      <div class="set-card-body">
        <div class="input-group mb-3">
          <input type="text" class="form-control pm-input" id="newCategory" placeholder="Add category...">
          <button class="btn btn-primary set-add-btn" onclick="addCategory()"><i class="ti ti-plus"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-2" id="categoriesList"></div>
      </div>
    </div>
  </div>

  {{-- Units of Measure --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-ruler-measure me-2 text-teal"></i>Units of Measure</div>
      <div class="set-card-body">
        <div class="input-group mb-3">
          <input type="text" class="form-control pm-input" id="newUOM" placeholder="Add unit...">
          <button class="btn btn-primary set-add-btn" onclick="addUOM()"><i class="ti ti-plus"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-2" id="uomsList"></div>
      </div>
    </div>
  </div>

  {{-- Entity Types --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-briefcase me-2 text-pink"></i>Entity Types</div>
      <div class="set-card-body">
        <div class="input-group mb-3">
          <input type="text" class="form-control pm-input" id="newEntityType" placeholder="Add type...">
          <button class="btn btn-primary set-add-btn" onclick="addEntityType()"><i class="ti ti-plus"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-2" id="entityTypesList"></div>
      </div>
    </div>
  </div>

  {{-- Business Categories --}}
  <div class="col-lg-6">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-list me-2 text-indigo"></i>Business Categories</div>
      <div class="set-card-body">
        <div class="input-group mb-3">
          <input type="text" class="form-control pm-input" id="newBizCat" placeholder="Add category...">
          <button class="btn btn-primary set-add-btn" onclick="addBizCat()"><i class="ti ti-plus"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-2" id="bizCatsList"></div>
      </div>
    </div>
  </div>

  {{-- Bulk Data Upload --}}
  <div class="col-12">
    <div class="card inv-section-card">
      <div class="set-card-header">
        <i class="ti ti-table-import me-2 text-blue"></i>Bulk Data Upload
        <span class="set-import-subtitle">Import products, customers or vendors from CSV / Excel</span>
      </div>
      <div class="set-card-body" style="padding:28px;">

        {{-- Step bar --}}
        <div class="d-flex align-items-center mb-5" id="bulk-step-bar">
          <div class="bk-dot active" id="bkd1"><span>1</span><div class="bk-dot-lbl">Choose Type</div></div>
          <div class="bk-line" id="bkl1"></div>
          <div class="bk-dot" id="bkd2"><span>2</span><div class="bk-dot-lbl">Upload File</div></div>
          <div class="bk-line" id="bkl2"></div>
          <div class="bk-dot" id="bkd3"><span>3</span><div class="bk-dot-lbl">Map Columns</div></div>
          <div class="bk-line" id="bkl3"></div>
          <div class="bk-dot" id="bkd4"><span>4</span><div class="bk-dot-lbl">Import</div></div>
        </div>

        {{-- Step 1: Choose Type --}}
        <div id="bk-s1">
          <p class="set-desc mb-4">Select the master data type to bulk import:</p>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="bk-type-card" onclick="bkSelectType('product')">
                <i class="ti ti-package bk-type-icon" class="text-erp-primary"></i>
                <div class="bk-type-title">Product Master</div>
                <div class="bk-type-desc">Upload products with Item No., price, stock &amp; barcode</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="bk-type-card" onclick="bkSelectType('customer')">
                <i class="ti ti-users bk-type-icon" class="text-success"></i>
                <div class="bk-type-title">Customer Master</div>
                <div class="bk-type-desc">Import customer names, contacts &amp; opening balances</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="bk-type-card" onclick="bkSelectType('vendor')">
                <i class="ti ti-truck bk-type-icon" class="rpt-icon-sales"></i>
                <div class="bk-type-title">Vendor Master</div>
                <div class="bk-type-desc">Import vendor names, contacts &amp; opening balances</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Step 2: Upload File --}}
        <div id="bk-s2" class="d-none">
          <div class="d-flex align-items-center gap-3 mb-4">
            <button class="bk-back-btn" onclick="bkGoStep(1)"><i class="ti ti-arrow-left me-1"></i>Back</button>
            <span class="set-desc mb-0">Uploading <strong id="bk-type-label" class="text-dark"></strong></span>
          </div>
          <div class="bk-drop-zone" id="bk-drop-zone"
            onclick="document.getElementById('bk-file-inp').click()"
            ondragover="event.preventDefault();this.classList.add('bk-drag-over');"
            ondragleave="this.classList.remove('bk-drag-over');"
            ondrop="event.preventDefault();this.classList.remove('bk-drag-over');bkHandleFile(event.dataTransfer.files[0]);">
            <i class="ti ti-cloud-upload set-upload-icon"></i>
            <div class="set-upload-title">Drag &amp; drop your file here</div>
            <div class="set-desc mb-0" style="margin-bottom:4px!important;">or click to browse</div>
            <div class="set-upload-hint"><i class="ti ti-file-spreadsheet me-1"></i>.csv &nbsp;·&nbsp; .xlsx &nbsp;·&nbsp; .xls</div>
          </div>
          <input type="file" id="bk-file-inp" accept=".csv,.xlsx,.xls" class="d-none" onchange="bkHandleFile(this.files[0])">
          <div class="mt-3">
            <a href="javascript:void(0)" onclick="bkDownloadTemplate()" class="set-download-link"><i class="ti ti-download me-1"></i>Download Sample Template</a>
          </div>
        </div>

        {{-- Step 3: Map Columns --}}
        <div id="bk-s3" class="d-none">
          <div class="d-flex align-items-center gap-3 mb-4">
            <button class="bk-back-btn" onclick="bkGoStep(2)"><i class="ti ti-arrow-left me-1"></i>Back</button>
            <span class="set-desc mb-0">Map your file's columns to system fields, then validate</span>
            <div id="bk-valid-badge" class="set-valid-badge"><i class="ti ti-circle-check"></i>Ready to Import</div>
          </div>
          <div class="set-import-label">File Preview (first 3 rows)</div>
          <div class="table-responsive mb-4" class="set-preview-box">
            <table class="table table-sm mb-0" id="bk-preview-table" style="font-size:0.78rem;"></table>
          </div>
          <div class="set-import-label">Column Mapping</div>
          <div class="table-responsive mb-4" class="erp-permission-table-wrap">
            <table class="table table-sm mb-0" class="erp-text-82">
              <thead class="erp-thead-light">
                <tr>
                  <th class="bk-th">System Field</th>
                  <th class="bk-th" style="width:80px;">Required</th>
                  <th class="bk-th">Map to File Column</th>
                  <th class="bk-th" style="width:90px;">Status</th>
                </tr>
              </thead>
              <tbody id="bk-map-body"></tbody>
            </table>
          </div>
          <div class="d-flex gap-3 align-items-center flex-wrap">
            <button class="btn btn-primary set-action-btn" onclick="bkValidate()"><i class="ti ti-check me-1"></i>Validate Mapping</button>
            <button id="bk-import-btn" class="btn set-import-btn" style="display:none;" onclick="bkStartImport()"><i class="ti ti-upload me-1"></i>Start Import &nbsp;<span class="bk-row-badge" id="bk-row-cnt">0</span></button>
          </div>
        </div>

        {{-- Step 4: Import Progress --}}
        <div id="bk-s4" class="d-none">
          <div class="set-import-label mb-4">Import Progress</div>
          <div class="set-import-prog-box">
            <div class="d-flex justify-content-between mb-2">
              <span class="set-prog-lbl" id="bk-prog-lbl">Processing...</span>
              <span class="set-prog-pct" id="bk-prog-pct">0%</span>
            </div>
            <div class="set-prog-track">
              <div id="bk-prog-bar" style="width:0%;"></div>
            </div>
            <div class="d-flex gap-5 mt-3">
              <div class="text-center"><div class="set-stat-ok" id="bk-ok-n">0</div><div class="set-desc mb-0">Imported</div></div>
              <div class="text-center"><div class="set-stat-fail" id="bk-err-n">0</div><div class="set-desc mb-0">Failed</div></div>
              <div class="text-center"><div class="set-stat-skip" id="bk-skip-n">0</div><div class="set-desc mb-0">Skipped</div></div>
            </div>
          </div>
          <div id="bk-err-list" class="set-err-list"></div>
          <div class="d-flex gap-3" id="bk-done-btns" style="display:none!important;">
            <button class="btn btn-primary set-action-btn-sm" onclick="bkGoStep(1)"><i class="ti ti-upload me-1"></i>Upload More</button>
            <button class="btn set-action-btn-sm" onclick="ERP.sync().then(renderPage)" style="border:1px solid #DDE1EC;font-weight:600;"><i class="ti ti-refresh me-1"></i>Refresh Data</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- Document Sequences --}}
  <div class="col-12">
    <div class="card inv-section-card inv-table-card">
      <div class="set-card-header"><i class="ti ti-hash me-2 text-blue"></i>Document Sequences</div>
      <div class="table-responsive">
        <table class="table table-hover table-vcenter inv-table mb-0">
          <thead>
            <tr>
              <th class="inv-th">Document Type</th>
              <th class="inv-th">Prefix</th>
              <th class="inv-th">Next Number</th>
              <th class="inv-th">Preview</th>
              <th class="inv-th">Status</th>
              <th class="inv-th">Actions</th>
            </tr>
          </thead>
          <tbody id="seqBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Module Settings --}}
  <div class="col-12">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-layout-grid me-2 text-teal"></i>Module Settings</div>
      <div class="set-card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-semibold" style="font-size:0.9rem">Job Card Mode</div>
            <div class="text-muted" style="font-size:0.82rem">Replace POS Terminal with Job Card in the sidebar</div>
          </div>
          <div class="form-check form-switch ms-3">
            <input class="form-check-input" type="checkbox" id="setting-job-card-mode" role="switch" style="width:2.5em;height:1.4em;cursor:pointer">
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Dynamic Fields --}}
  <div class="col-12">
    <div class="card inv-section-card">
      <div class="set-card-header d-flex align-items-center justify-content-between">
        <span><i class="ti ti-layout-columns me-2 text-indigo"></i>Dynamic Fields</span>
        <button id="dynSaveBtn" class="dynf-save-btn d-none" onclick="saveDynamicFields()">
          <i class="ti ti-device-floppy me-1"></i>Save Changes
          <span id="dynSaveBadge" class="dynf-save-badge">0</span>
        </button>
      </div>
      <div class="set-card-body">
        <p class="set-desc mb-3">Enable fields to appear in Product and Customer forms. Fields with data cannot be disabled. Toggle changes are pending until you click <strong>Save Changes</strong>.</p>

        {{-- Product Fields --}}
        <div class="mb-4">
          <div class="dynf-section-title"><i class="ti ti-box me-1"></i>Product Fields</div>
          <div id="dynfields-product" class="dynf-grid"></div>
        </div>

        {{-- Customer Fields --}}
        <div>
          <div class="dynf-section-title"><i class="ti ti-users me-1"></i>Customer Fields</div>
          <div id="dynfields-customer" class="dynf-grid"></div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

{{-- Dynamic Fields Disable Error Overlay --}}
<div id="dynFieldDisableError" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-error"><i class="ti ti-alert-triangle"></i></div>
      <div class="ms-title">Cannot Disable Field</div>
      <div class="ms-sub" id="dynFieldDisableErrorMsg"></div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" onclick="document.getElementById('dynFieldDisableError').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

{{-- Dynamic Fields Save Confirm Overlay --}}
<div id="dynSaveConfirm" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-primary"><i class="ti ti-device-floppy"></i></div>
      <div class="ms-title">Save Field Changes?</div>
      <div class="ms-sub" id="dynSaveConfirmMsg">You are about to update field settings.</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-cancel" onclick="document.getElementById('dynSaveConfirm').classList.add('d-none')">Cancel</button>
      <button class="ms-btn-confirm" onclick="confirmSaveDynamicFields()">Yes, Save</button>
    </div>
  </div>
</div>

{{-- Dynamic Fields Save Success Overlay --}}
<div id="dynSaveSuccess" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Saved Successfully</div>
      <div class="ms-sub" id="dynSaveSuccessMsg">Field settings have been updated.</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" onclick="document.getElementById('dynSaveSuccess').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="{{ asset('js/pages/settings.js') }}?v={{ filemtime(public_path('js/pages/settings.js')) }}"></script>
@endpush
