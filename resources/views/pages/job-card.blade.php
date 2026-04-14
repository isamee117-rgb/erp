@extends('layouts.app')
@section('page-title', 'Job Card - LeanERP')
@section('content')
<div class="jc-page-wrap" id="jc-page-wrap">

    {{-- Print Header (hidden on screen, shown on print) --}}
    <div class="jc-print-header" id="jc-print-header"></div>

    {{-- Page Header --}}
    <div class="card inv-header-card mb-0">
        <div class="card-body inv-header-body py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="inv-title"><i class="ti ti-clipboard-list me-2"></i>Job Cards</div>
                    <div class="inv-subtitle">Workshop job management &amp; billing</div>
                </div>
                <button class="btn btn-sm btn-white jc-new-tab-btn" id="jc-new-tab-btn">
                    <i class="ti ti-plus me-1"></i> New Job Card
                </button>
            </div>
        </div>
    </div>

    {{-- Tab Bar --}}
    <div class="jc-tab-bar" id="jc-tab-bar"></div>

    {{-- Active Card Panel --}}
    <div id="jc-panel-wrap"></div>

</div>

{{-- ── New Job Card Modal ──────────────────────────────────────────────── --}}
<div class="modal modal-blur fade" id="jcNewCardModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-clipboard-plus me-2"></i>New Job Card</h5>
        <button type="button" class="pm-modal-close" data-bs-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body pm-modal-body">

        {{-- Customer Search --}}
        <div class="mb-3 position-relative">
          <label class="pm-label"><i class="ti ti-search me-1"></i>Customer Search</label>
          <input type="text" id="jcm-search" class="form-control pm-input" placeholder="Type name, phone or vehicle reg to search existing customers...">
          <div id="jcm-suggestions" class="list-group position-absolute w-100" style="z-index:9999;top:100%;left:0;display:none;max-height:180px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,0.12)"></div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="pm-label">Customer Name</label>
            <input type="text" id="jcm-name" class="form-control pm-input" placeholder="Full name">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Phone</label>
            <input type="text" id="jcm-phone" class="form-control pm-input" placeholder="e.g. 03001234567">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Vehicle Reg No</label>
            <input type="text" id="jcm-vreg" class="form-control pm-input" placeholder="e.g. ABC-123">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Make / Model / Year</label>
            <input type="text" id="jcm-make" class="form-control pm-input" placeholder="e.g. Toyota Corolla 2020">
          </div>
          <div class="col-md-6">
            <label class="pm-label">VIN / Chassis No</label>
            <input type="text" id="jcm-vin" class="form-control pm-input" placeholder="Chassis number">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Engine No</label>
            <input type="text" id="jcm-engine" class="form-control pm-input" placeholder="Engine number">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Current Odometer (km)</label>
            <input type="number" id="jcm-odometer" class="form-control pm-input" placeholder="e.g. 85000">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Lift No</label>
            <input type="text" id="jcm-lift" class="form-control pm-input" placeholder="e.g. Lift-3">
          </div>
        </div>

        {{-- Hidden customer id --}}
        <input type="hidden" id="jcm-customer-id">

      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" id="jcm-save-btn"><i class="ti ti-circle-check me-1"></i>Create Job Card</button>
      </div>
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('js/pages/job-card.js') }}"></script>
@endpush
