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
        <button class="btn btn-primary" onclick="saveMappings()"><i class="ti ti-device-floppy me-1"></i>Save Mappings</button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card">
  <div class="set-card-header"><i class="ti ti-info-circle me-2 text-blue"></i>How it works</div>
  <div class="set-card-body">
    <p class="mb-0" style="font-size:0.82rem;color:#64748b;line-height:1.7;">
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
          <th class="inv-th" style="min-width:280px;">GL Account</th>
        </tr>
      </thead>
      <tbody id="mapBody"></tbody>
    </table>
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
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;overflow:hidden;}
.inv-table-card{overflow:hidden;}
.set-card-header{padding:14px 20px;font-size:0.85rem;font-weight:700;color:#1e293b;border-bottom:1px solid #E8EAF0;background:#F8F9FC;display:flex;align-items:center;}
.set-card-body{padding:16px 20px;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(59,79,228,0.08)!important;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;font-size:0.85rem;}
.map-key{font-size:0.78rem;font-weight:700;color:#3B4FE4;font-family:monospace;}
.map-desc{font-size:0.8rem;color:#64748b;}
.map-section-row td{background:#F8F9FC;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;padding:8px 14px!important;}
.text-blue{color:#3B4FE4!important;}
.text-purple{color:#7c3aed!important;}
.ms-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9999;display:flex;align-items:center;justify-content:center;}
.ms-overlay.d-none{display:none!important;}
.ms-box{background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.18);width:100%;max-width:380px;overflow:hidden;animation:msIn 0.18s ease;}
@keyframes msIn{from{opacity:0;transform:scale(0.93);}to{opacity:1;transform:scale(1);}}
.ms-body{padding:32px 28px 20px;text-align:center;}
.ms-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;}
.ms-icon-success{background:rgba(5,150,105,0.1);color:#059669;}
.ms-title{font-size:1.05rem;font-weight:700;color:#1e293b;margin-bottom:8px;}
.ms-sub{font-size:0.82rem;color:#64748b;line-height:1.5;}
.ms-footer{padding:0 28px 24px;display:flex;gap:10px;justify-content:center;}
.ms-btn-ok{padding:9px 40px;border:none;border-radius:8px;background:linear-gradient(135deg,#059669,#10B981);color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/account-mappings.js') }}?v={{ filemtime(public_path('js/pages/account-mappings.js')) }}"></script>
@endpush
