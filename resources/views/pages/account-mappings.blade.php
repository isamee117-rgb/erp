@extends('layouts.app')
@section('page-title', 'Account Mappings - LeanERP')
@section('content')

{{-- Save Success Overlay --}}
<div id="mapSaveSuccess" class="d-none ms-overlay">
  <div class="ms-box">
    <div class="ms-body">
      <div class="ms-icon ms-icon-success"><i class="ti ti-circle-check"></i></div>
      <div class="ms-title">Mappings Saved!</div>
      <div class="ms-sub">Account mappings updated successfully. Auto-posting is now active.</div>
    </div>
    <div class="ms-footer">
      <button class="ms-btn-ok" onclick="document.getElementById('mapSaveSuccess').classList.add('d-none')">OK</button>
    </div>
  </div>
</div>

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-arrows-exchange me-2"></i>Account Mappings</h2>
        <p class="mb-0 inv-subtitle">Link system events to GL accounts for automatic journal posting.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" onclick="saveMappings()"><i class="ti ti-device-floppy me-1"></i>Save Mappings</button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card">
  <div class="set-card-header"><i class="ti ti-info-circle me-2 text-blue"></i>How it works</div>
  <div class="set-card-body">
    <p class="mb-0 erp-text-82 text-muted lh-relaxed">
      When a sale, purchase, payment or return is recorded, the system will automatically create a journal entry using the accounts mapped below.
      Map all required accounts before your first transaction, or leave blank to skip auto-posting.
    </p>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="set-card-header"><i class="ti ti-arrows-exchange me-2 text-purple"></i>Mapping Configuration</div>
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th">Event / Mapping Key</th>
          <th class="inv-th">Description</th>
          <th class="inv-th map-th-account">GL Account</th>
        </tr>
      </thead>
      <tbody id="mapBody"></tbody>
    </table>
  </div>
</div>

</div>
@endsection
@push('scripts')
<script src="{{ asset('js/pages/account-mappings.js') }}?v={{ filemtime(public_path('js/pages/account-mappings.js')) }}"></script>
@endpush
