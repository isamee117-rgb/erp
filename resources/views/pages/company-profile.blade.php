@extends('layouts.app')
@section('page-title', 'Company Profile - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-building me-2"></i>Company Profile</h2>
        <p class="mb-0 inv-subtitle">Manage your company's information and contact details.</p>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card inv-section-card">
      <div class="cp-form-header">
        <i class="ti ti-building-store me-2"></i>Company Information
      </div>
      <div class="cp-form-body">

        {{-- Logo Uploader --}}
        <div class="cp-logo-section mb-4">
          <label class="pm-label mb-2">Company Logo</label>
          <div class="cp-logo-wrap">
            <div class="cp-logo-preview" id="logoPreview">
              <img id="logoImg" src="" alt="Company Logo" class="cp-logo-img d-none">
              <div id="logoPlaceholder" class="cp-logo-placeholder">
                <i class="ti ti-building-store"></i>
                <span>No logo</span>
              </div>
            </div>
            <div class="cp-logo-actions">
              <p class="cp-logo-hint">Upload your company logo. Recommended size: 200×200px.<br>Supported formats: JPG, PNG, GIF, WebP. Max size: 2MB.</p>
              <input type="file" id="logoFileInput" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
              <button class="pm-btn-upload" id="uploadLogoBtn" onclick="document.getElementById('logoFileInput').click()">
                <i class="ti ti-upload me-1"></i>Upload Logo
              </button>
              <button class="pm-btn-remove d-none" id="removeLogoBtn" onclick="removeLogo()">
                <i class="ti ti-trash me-1"></i>Remove
              </button>
              <span id="logoUploadStatus" class="cp-upload-status d-none"></span>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-12">
            <label class="pm-label">Company Name</label>
            <input type="text" class="form-control pm-input" id="cpName" placeholder="Enter company name">
          </div>
          <div class="col-md-12">
            <label class="pm-label">Address</label>
            <textarea class="form-control pm-textarea" id="cpAddress" rows="3" placeholder="Enter full address"></textarea>
          </div>
          <div class="col-md-12">
            <label class="pm-label">Tagline</label>
            <input type="text" class="form-control pm-input" id="cpTagline" placeholder="Your company tagline">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Phone</label>
            <input type="text" class="form-control pm-input" id="cpPhone" placeholder="+1 234 567 8900">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Email</label>
            <input type="email" class="form-control pm-input" id="cpEmail" placeholder="info@company.com">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Website</label>
            <input type="url" class="form-control pm-input" id="cpWebsite" placeholder="https://company.com">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Tax ID / VAT Number</label>
            <input type="text" class="form-control pm-input" id="cpTaxId" placeholder="Tax / VAT number">
          </div>
        </div>
      </div>
      <div class="cp-form-footer">
        <button class="pm-btn-save" onclick="saveProfile()"><i class="ti ti-device-floppy me-1"></i>Save Changes</button>
      </div>
    </div>
  </div>
</div>

</div>

{{-- Confirm Save Modal --}}
<div class="cp-modal-backdrop d-none" id="cpConfirmModal">
  <div class="cp-modal-box">
    <div class="cp-modal-body">
      <div class="cp-modal-icon confirm"><i class="ti ti-edit"></i></div>
      <div class="cp-modal-title">Save Changes?</div>
      <p class="cp-modal-sub">Are you sure you want to update your company profile? This will overwrite existing information.</p>
    </div>
    <div class="cp-modal-footer">
      <button class="cp-btn-cancel" onclick="document.getElementById('cpConfirmModal').classList.add('d-none')">Cancel</button>
      <button class="cp-btn-confirm" onclick="doSaveProfile()"><i class="ti ti-device-floppy me-1"></i>Yes, Save</button>
    </div>
  </div>
</div>

{{-- Success Modal --}}
<div class="cp-modal-backdrop d-none" id="cpSuccessModal">
  <div class="cp-modal-box">
    <div class="cp-modal-body">
      <div class="cp-modal-icon success"><i class="ti ti-circle-check"></i></div>
      <div class="cp-modal-title">Profile Saved!</div>
      <p class="cp-modal-sub">Your company profile has been updated successfully.</p>
    </div>
    <div class="cp-modal-footer" class="justify-center">
      <button class="cp-btn-ok" onclick="document.getElementById('cpSuccessModal').classList.add('d-none')"><i class="ti ti-check me-1"></i>OK</button>
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
.inv-header-card::after{content:'';position:absolute;top:-40%;right:-8%;width:260px;height:260px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;overflow:hidden;}
.cp-form-header{padding:16px 24px;background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);font-size:0.9rem;font-weight:600;color:#fff;}
.cp-form-body{padding:24px;background:#F8F9FC;}
.cp-form-footer{padding:14px 24px;background:#fff;border-top:1px solid #E8EAF0;display:flex;justify-content:flex-end;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.pm-textarea{font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-textarea:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.pm-btn-save{height:38px;padding:0 22px;border:none;border-radius:6px;background:linear-gradient(135deg,#CD0000,#e53333);color:#fff;font-size:0.85rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.pm-btn-save:hover{opacity:0.92;}

/* Logo uploader */
.cp-logo-section{background:#fff;border:1px solid #E8EAF0;border-radius:8px;padding:16px;}
.cp-logo-wrap{display:flex;align-items:center;gap:20px;}
.cp-logo-preview{width:96px;height:96px;border-radius:10px;border:2px dashed #DDE1EC;background:#F8F9FC;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;}
.cp-logo-img{width:100%;height:100%;object-fit:contain;}
.cp-logo-placeholder{display:flex;flex-direction:column;align-items:center;gap:4px;color:#9CA3AF;}
.cp-logo-placeholder i{font-size:1.6rem;}
.cp-logo-placeholder span{font-size:0.7rem;}
.cp-logo-actions{display:flex;flex-direction:column;gap:8px;}
.cp-logo-hint{font-size:0.75rem;color:#6B7280;margin:0;line-height:1.5;}
.pm-btn-upload{height:34px;padding:0 16px;border:1px solid var(--inv-primary);border-radius:6px;background:#fff;color:var(--inv-primary);font-size:0.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
.pm-btn-upload:hover{background:rgba(205,0,0,0.06);}
.pm-btn-remove{height:34px;padding:0 16px;border:1px solid #EF4444;border-radius:6px;background:#fff;color:#EF4444;font-size:0.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
.pm-btn-remove:hover{background:#FEF2F2;}
.cp-upload-status{font-size:0.78rem;font-weight:500;}
.cp-upload-status.success{color:#10B981;}
.cp-upload-status.error{color:#EF4444;}

/* Confirm / Success modals */
.cp-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;}
.cp-modal-box{background:#fff;border-radius:14px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,0.18);overflow:hidden;animation:cpModalIn .18s ease;}
@keyframes cpModalIn{from{transform:scale(0.92);opacity:0}to{transform:scale(1);opacity:1}}
.cp-modal-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:1.6rem;}
.cp-modal-icon.confirm{background:#EEF2FF;color:#CD0000;}
.cp-modal-icon.success{background:#ECFDF5;color:#10B981;}
.cp-modal-body{padding:28px 28px 20px;text-align:center;}
.cp-modal-title{font-size:1rem;font-weight:700;color:#111827;margin:14px 0 6px;}
.cp-modal-sub{font-size:0.83rem;color:#6B7280;margin:0;}
.cp-modal-footer{padding:16px 24px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #F3F4F6;}
.cp-btn-cancel{height:36px;padding:0 18px;border:1px solid #DDE1EC;border-radius:7px;background:#fff;color:#374151;font-size:0.83rem;font-weight:600;cursor:pointer;}
.cp-btn-confirm{height:36px;padding:0 18px;border:none;border-radius:7px;background:linear-gradient(135deg,#CD0000,#e53333);color:#fff;font-size:0.83rem;font-weight:600;cursor:pointer;}
.cp-btn-ok{height:36px;padding:0 28px;border:none;border-radius:7px;background:linear-gradient(135deg,#10B981,#34D399);color:#fff;font-size:0.83rem;font-weight:600;cursor:pointer;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/company-profile.js') }}?v={{ filemtime(public_path('js/pages/company-profile.js')) }}"></script>
@endpush
