@extends('layouts.app')
@section('page-title', 'Company Management - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-briefcase me-2"></i>Company Management</h2>
        <p class="mb-0 inv-subtitle">Super Admin — Tenant provisioning and company configuration.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" class="btn-erp-sm" data-bs-toggle="modal" data-bs-target="#addCompanyModal" onclick="openAddCompany()">
          <i class="ti ti-plus me-1"></i>Add Company
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <label class="pm-label">Search Companies</label>
        <input type="text" class="form-control inv-input" id="searchInput" placeholder="Search by name or ID...">
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th">Company Name</th>
          <th class="inv-th">Status</th>
          <th class="inv-th">User Limit</th>
          <th class="inv-th">SaaS Plan</th>
          <th class="inv-th text-end">Registration Payment</th>
          <th class="inv-th">Actions</th>
        </tr>
      </thead>
      <tbody id="companiesBody"></tbody>
    </table>
  </div>
</div>

</div>

{{-- Add Company Modal --}}
<div class="modal modal-blur fade" id="addCompanyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-building-plus me-2"></i>Add Company</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="pm-label">Company Name</label>
            <input type="text" class="form-control pm-input" id="coName" placeholder="Enter company name" required>
          </div>
          <div class="col-md-6">
            <label class="pm-label">Admin Username</label>
            <input type="text" class="form-control pm-input" id="coAdminUser" placeholder="Admin username" required>
          </div>
          <div class="col-md-6">
            <label class="pm-label">Admin Password</label>
            <div class="input-group">
              <input type="password" class="form-control pm-input" id="coAdminPass" placeholder="Admin password" required class="erp-input-no-right-border">
              <button type="button" class="pm-eye-btn" onclick="togglePwdVis('coAdminPass',this)" tabindex="-1"><i class="ti ti-eye"></i></button>
            </div>
          </div>
          <div class="col-md-4">
            <label class="pm-label">User Limit</label>
            <input type="number" class="form-control pm-input" id="coLimit" value="10" min="1">
          </div>
          <div class="col-md-4">
            <label class="pm-label">Registration Payment</label>
            <input type="number" class="form-control pm-input" id="coPayment" value="0" min="0">
          </div>
          <div class="col-md-4">
            <label class="pm-label">SaaS Plan</label>
            <select class="form-select pm-input" id="coPlan">
              <option value="Monthly">Monthly</option>
              <option value="Annually">Annually</option>
              <option value="One-Time">One-Time</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="createCompany()"><i class="ti ti-building-plus me-1"></i>Create Company</button>
      </div>
    </div>
  </div>
</div>

{{-- View / Edit Company Details Modal --}}
<div class="modal modal-blur fade" id="companyDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-edit me-2"></i>Company Details</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <div class="row g-3">
          <div class="col-12">
            <small class="text-uppercase text-muted fw-bold" class="erp-section-title-upper">Account Settings</small>
            <hr class="mt-1 mb-2">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Company Name</label>
            <input type="text" class="form-control pm-input" id="cdName" placeholder="Company name">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Status</label>
            <input type="text" class="form-control pm-input" id="cdStatus" readonly class="erp-readonly-field">
          </div>
          <div class="col-md-4">
            <label class="pm-label">SaaS Plan</label>
            <select class="form-select pm-input" id="cdPlan">
              <option value="Monthly">Monthly</option>
              <option value="Annually">Annually</option>
              <option value="One-Time">One-Time</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="pm-label">User Limit</label>
            <input type="number" class="form-control pm-input" id="cdLimit" min="1">
          </div>
          <div class="col-md-4">
            <label class="pm-label">Registration Payment</label>
            <input type="number" class="form-control pm-input" id="cdPayment" min="0" step="0.01">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Admin Username</label>
            <input type="text" class="form-control pm-input" id="cdAdminUser" readonly class="erp-readonly-field">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Admin Password (Current)</label>
            <input type="text" class="form-control pm-input" id="cdAdminPass" readonly class="erp-readonly-field">
          </div>
          <div class="col-12 mt-2">
            <small class="text-uppercase text-muted fw-bold" class="erp-section-title-upper">Company Info (Printed on Invoices)</small>
            <hr class="mt-1 mb-2">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Display Name</label>
            <input type="text" class="form-control pm-input" id="cdInfoName" placeholder="Name shown on invoices">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Tagline</label>
            <input type="text" class="form-control pm-input" id="cdTagline" placeholder="Tagline">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Phone</label>
            <input type="text" class="form-control pm-input" id="cdPhone" placeholder="Phone number">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Email</label>
            <input type="email" class="form-control pm-input" id="cdEmail" placeholder="Email address">
          </div>
          <div class="col-12">
            <label class="pm-label">Address</label>
            <textarea class="form-control pm-input" id="cdAddress" rows="2" placeholder="Full address" class="erp-textarea-auto"></textarea>
          </div>
          <div class="col-md-6">
            <label class="pm-label">Website</label>
            <input type="text" class="form-control pm-input" id="cdWebsite" placeholder="https://...">
          </div>
          <div class="col-md-6">
            <label class="pm-label">Tax ID / NTN</label>
            <input type="text" class="form-control pm-input" id="cdTaxId" placeholder="Tax identification number">
          </div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="saveCompanyDetails()"><i class="ti ti-device-floppy me-1"></i>Save Changes</button>
      </div>
    </div>
  </div>
</div>

{{-- Reset Password Modal --}}
<div class="modal modal-blur fade" id="resetPwdModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-key me-2"></i>Reset Admin Password</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <p class="text-muted mb-3" id="resetPwdLabel" class="erp-text-85">Company: </p>
        <div>
          <label class="pm-label">New Password</label>
          <div class="input-group">
            <input type="password" class="form-control pm-input" id="resetPwdInput" placeholder="Enter new password" class="erp-input-no-right-border">
            <button type="button" class="pm-eye-btn" onclick="togglePwdVis('resetPwdInput',this)" tabindex="-1"><i class="ti ti-eye"></i></button>
          </div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="doResetPassword()"><i class="ti ti-check me-1"></i>Reset Password</button>
      </div>
    </div>
  </div>
</div>

{{-- Update Limit Modal --}}
<div class="modal modal-blur fade" id="updateLimitModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-users me-2"></i>Update User Limit</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <p class="text-muted mb-3" id="updateLimitLabel" class="erp-text-85">Company: </p>
        <div>
          <label class="pm-label">New User Limit</label>
          <input type="number" class="form-control pm-input" id="updateLimitInput" min="1" placeholder="Enter user limit">
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="doUpdateLimit()"><i class="ti ti-check me-1"></i>Update Limit</button>
      </div>
    </div>
  </div>
</div>

@endsection
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{--inv-primary:#CD0000;--inv-font:'Inter',sans-serif;}
.pm-eye-btn{border:1px solid #DDE1EC;border-left:none;border-radius:0 8px 8px 0;background:#fff;color:#6B7280;padding:0 12px;cursor:pointer;transition:color .15s;}
.pm-eye-btn:hover{color:#CD0000;}
.input-group .pm-input{border-radius:8px 0 0 8px!important;}
.page-body,.page-wrapper{font-family:var(--inv-font);font-size:14px;background:#F5F6FA!important;}
.inv-page-wrap{display:flex;flex-direction:column;gap:16px;}
.inv-header-card{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);border:none;border-radius:10px;overflow:hidden;position:relative;}
.inv-header-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.5;pointer-events:none;}
.inv-header-card::after{content:'';position:absolute;top:-40%;right:-8%;width:260px;height:260px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;}
.inv-filter-body{padding:12px 16px!important;}
.inv-input{height:36px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;transition:all 0.2s ease;}
.inv-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.inv-table-card{overflow:hidden;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;}
.badge-pill{font-weight:600;padding:3px 10px;border-radius:20px;font-size:0.72rem;}
.badge-blue{background:rgba(205,0,0,0.1);color:#CD0000;}
.badge-green{background:rgba(16,185,129,0.1);color:#059669;}
.badge-red{background:rgba(239,68,68,0.1);color:#dc2626;}
.badge-orange{background:rgba(249,115,22,0.1);color:#ea580c;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(205,0,0,0.08)!important;}
.pm-modal-content{border:none!important;border-radius:12px!important;overflow:hidden;}
.pm-modal-header{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);padding:16px 24px!important;border-bottom:none!important;}
.pm-modal-title{color:#fff!important;font-size:1rem;font-weight:600;}
.pm-modal-close{filter:invert(1);opacity:0.8;}
.pm-modal-body{padding:24px!important;background:#F8F9FC;}
.pm-modal-footer{padding:12px 24px!important;background:#fff;border-top:1px solid #E8EAF0!important;display:flex;justify-content:flex-end;gap:8px;}
.pm-btn-cancel{height:38px;padding:0 18px;border:1px solid #DDE1EC;border-radius:6px;background:#fff;color:#374151;font-size:0.85rem;font-weight:500;cursor:pointer;}
.pm-btn-save{height:38px;padding:0 22px;border:none;border-radius:6px;background:linear-gradient(135deg,#CD0000,#e53333);color:#fff;font-size:0.85rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;}
.inv-action-btn{width:30px;height:30px;border:1px solid #E8EAF0;border-radius:6px;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.8rem;color:#64748b;transition:all 0.15s;margin:0 2px;}
.inv-action-btn:hover{background:#F5F7FF;border-color:#CD0000;color:#CD0000;}
.company-id-badge{font-size:0.72rem;color:#94a3b8;font-family:monospace;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/company-management.js') }}?v={{ filemtime(public_path('js/pages/company-management.js')) }}"></script>
@endpush
