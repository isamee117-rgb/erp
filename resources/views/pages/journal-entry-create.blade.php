@extends('layouts.app')
@section('page-title', 'New Journal Entry - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-pencil me-2"></i>New Journal Entry</h2>
        <p class="mb-0 inv-subtitle">Create a manual double-entry journal. Debits must equal credits.</p>
      </div>
      <div class="col-auto">
        <a href="{{ rtrim(url('/'), '/') }}/accounting/journals" class="btn btn-light shadow-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">

    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-info-circle me-2 text-blue"></i>Entry Details</div>
      <div class="set-card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="pm-label">Date</label>
            <input type="date" class="form-control pm-input" id="jeDate">
          </div>
          <div class="col-md-8">
            <label class="pm-label">Description / Narration</label>
            <input type="text" class="form-control pm-input" id="jeDesc" placeholder="e.g. Opening balance entry">
          </div>
        </div>
      </div>
    </div>

    <div class="card inv-section-card mt-3">
      <div class="set-card-header d-flex align-items-center justify-content-between">
        <span><i class="ti ti-table me-2 text-purple"></i>Journal Lines</span>
        <button class="btn btn-sm btn-outline-primary" onclick="addLine()"><i class="ti ti-plus me-1"></i>Add Line</button>
      </div>
      <div class="table-responsive">
        <table class="table table-vcenter inv-table mb-0">
          <thead>
            <tr>
              <th class="inv-th" style="min-width:240px;">Account</th>
              <th class="inv-th">Narration</th>
              <th class="inv-th" style="width:150px;">Debit</th>
              <th class="inv-th" style="width:150px;">Credit</th>
              <th class="inv-th" style="width:50px;"></th>
            </tr>
          </thead>
          <tbody id="jeLinesBody"></tbody>
          <tfoot>
            <tr id="jeTotalsRow">
              <td colspan="2" class="text-end fw-bold" style="padding:10px 14px;font-size:0.85rem;">Total</td>
              <td class="text-end fw-bold" style="padding:10px 14px;font-size:0.85rem;" id="jeTotalDebit">0.00</td>
              <td class="text-end fw-bold" style="padding:10px 14px;font-size:0.85rem;" id="jeTotalCredit">0.00</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="set-card-body border-top" id="jeBalanceCheck"></div>
    </div>

  </div>
  <div class="col-lg-4">
    <div class="card inv-section-card">
      <div class="set-card-header"><i class="ti ti-send me-2 text-green"></i>Save & Post</div>
      <div class="set-card-body">
        <p style="font-size:0.82rem;color:#64748b;line-height:1.6;" class="mb-3">
          <strong>Save as Draft</strong> — saves without posting. You can post it later.<br>
          <strong>Save &amp; Post</strong> — immediately marks as posted (cannot be deleted).
        </p>
        <div class="d-grid gap-2">
          <button class="btn btn-outline-primary" onclick="submitJe(false)"><i class="ti ti-device-floppy me-1"></i>Save as Draft</button>
          <button class="btn btn-primary" onclick="submitJe(true)"><i class="ti ti-check me-1"></i>Save &amp; Post</button>
        </div>
      </div>
    </div>
  </div>
</div>

</div>
@endsection
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{--inv-primary:#CD0000;--inv-font:'Inter',sans-serif;}
.page-body,.page-wrapper{font-family:var(--inv-font);font-size:14px;background:#F5F6FA!important;}
.inv-page-wrap{display:flex;flex-direction:column;gap:16px;}
.inv-header-card{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);border:none;border-radius:10px;overflow:hidden;position:relative;}
.inv-header-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.5;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;overflow:hidden;}
.set-card-header{padding:14px 20px;font-size:0.85rem;font-weight:700;color:#1e293b;border-bottom:1px solid #E8EAF0;background:#F8F9FC;display:flex;align-items:center;}
.set-card-body{padding:16px 20px;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody td{padding:8px 10px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;}
.inv-table tfoot td{background:#F8F9FC;border-top:2px solid #E8EAF0!important;}
.text-blue{color:#CD0000!important;}.text-purple{color:#7c3aed!important;}.text-green{color:#059669!important;}
.btn-icon-sm{width:28px;height:28px;padding:0;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;border:1px solid #E8EAF0;background:#fff;cursor:pointer;transition:all 0.15s;}
.btn-icon-sm.danger:hover{background:#FFF5F5;border-color:#dc2626;color:#dc2626;}
.balance-ok{background:rgba(5,150,105,0.08);color:#059669;padding:8px 12px;border-radius:6px;font-size:0.82rem;font-weight:600;}
.balance-err{background:rgba(220,38,38,0.08);color:#dc2626;padding:8px 12px;border-radius:6px;font-size:0.82rem;font-weight:600;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/journal-entry-create.js') }}?v={{ filemtime(public_path('js/pages/journal-entry-create.js')) }}"></script>
@endpush
