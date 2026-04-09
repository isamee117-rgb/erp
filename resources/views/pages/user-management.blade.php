@extends('layouts.app')
@section('page-title', 'User Management - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-user-cog me-2"></i>User Management</h2>
        <p class="mb-0 inv-subtitle" id="userLimitInfo">Manage users and their access roles within your company.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" id="addUserBtn" class="btn-erp-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openAddUser()">
          <i class="ti ti-plus me-1"></i>Add User
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <label class="pm-label">Search Users</label>
        <input type="text" class="form-control inv-input" id="searchInput" placeholder="Search by username...">
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th">Username</th>
          <th class="inv-th">Name</th>
          <th class="inv-th">Role</th>
          <th class="inv-th">Status</th>
          <th class="inv-th">Actions</th>
        </tr>
      </thead>
      <tbody id="usersBody"></tbody>
    </table>
  </div>
</div>

</div>

{{-- Add / Edit User Modal --}}
<div class="modal modal-blur fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="userModalTitle"><i class="ti ti-user-plus me-2"></i>Add User</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <div id="userError" class="alert alert-danger d-none mb-3"></div>
        <div class="row g-3">
          <div class="col-12">
            <label class="pm-label">Name</label>
            <input type="text" class="form-control pm-input" id="uName" placeholder="Enter full name">
          </div>
          <div class="col-12">
            <label class="pm-label">Username</label>
            <input type="text" class="form-control pm-input" id="uUsername" placeholder="Enter username" required>
          </div>
          <div class="col-12" id="uPasswordRow">
            <label class="pm-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control pm-input" id="uPassword" placeholder="Enter password" class="erp-input-no-right-border">
              <button type="button" class="btn btn-outline-secondary pm-eye-btn" onclick="togglePwdVisibility()" tabindex="-1" id="uPwdEye" title="Show/Hide password">
                <i class="ti ti-eye" id="uPwdEyeIcon"></i>
              </button>
            </div>
            <small class="text-muted" id="uPasswordHint" class="erp-text-xs d-none">Leave blank to keep current password</small>
          </div>
          <div class="col-12">
            <label class="pm-label">Role</label>
            <select class="form-select pm-input" id="uRole"><option value="">Select Role...</option></select>
          </div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="saveUser()" id="saveUserBtn"><i class="ti ti-device-floppy me-1"></i>Save User</button>
      </div>
    </div>
  </div>
</div>

{{-- Reset Password Modal --}}
<div class="modal modal-blur fade" id="passwordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title"><i class="ti ti-key me-2"></i>Reset Password</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <p class="text-muted mb-3" id="pwdUserLabel" class="erp-text-85">For user: </p>
        <div>
          <label class="pm-label">New Password</label>
          <input type="text" class="form-control pm-input" id="newPwd" placeholder="Enter new password">
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="resetPassword()"><i class="ti ti-check me-1"></i>Update Password</button>
      </div>
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
.inv-header-card::after{content:'';position:absolute;top:-40%;right:-8%;width:260px;height:260px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.inv-header-body{padding:20px 28px!important;position:relative;z-index:1;}
.inv-header-card .inv-title{font-size:1.35rem;font-weight:700;color:#fff;}
.inv-header-card .inv-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.82);}
.inv-section-card{border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.06);background:#fff;}
.inv-filter-body{padding:12px 16px!important;}
.inv-input{height:36px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;transition:all 0.2s ease;}
.inv-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(59,79,228,0.08)!important;}
.inv-table-card{overflow:hidden;}
.inv-table thead{background:#F8F9FC;}
.inv-th{font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;border-bottom:2px solid #E8EAF0!important;white-space:nowrap;padding:10px 14px!important;}
.inv-table tbody tr{transition:background-color 0.15s ease;}
.inv-table tbody tr:hover{background-color:#F5F7FF!important;}
.inv-table tbody td{padding:10px 14px!important;vertical-align:middle;border-bottom:1px solid #F0F2F8!important;border-top:none!important;}
.badge-pill{font-weight:600;padding:3px 10px;border-radius:20px;font-size:0.72rem;}
.badge-green{background:rgba(16,185,129,0.1);color:#059669;}
.badge-red{background:rgba(239,68,68,0.1);color:#dc2626;}
.badge-blue{background:rgba(59,79,228,0.1);color:#3B4FE4;}
.badge-gray{background:rgba(100,116,139,0.1);color:#64748b;}
.pm-label{display:block;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;margin-bottom:6px;}
.pm-input{height:38px!important;font-size:0.85rem!important;border:1px solid #DDE1EC!important;border-radius:6px!important;background:#fff!important;}
.pm-input:focus{border-color:var(--inv-primary)!important;box-shadow:0 0 0 3px rgba(59,79,228,0.08)!important;}
.pm-modal-content{border:none!important;border-radius:12px!important;overflow:hidden;}
.pm-modal-header{background:linear-gradient(135deg,#3B4FE4 0%,#5B6CF9 100%);padding:16px 24px!important;border-bottom:none!important;}
.pm-modal-title{color:#fff!important;font-size:1rem;font-weight:600;}
.pm-modal-close{filter:invert(1);opacity:0.8;}
.pm-modal-body{padding:24px!important;background:#F8F9FC;}
.pm-modal-footer{padding:12px 24px!important;background:#fff;border-top:1px solid #E8EAF0!important;display:flex;justify-content:flex-end;gap:8px;}
.pm-btn-cancel{height:38px;padding:0 18px;border:1px solid #DDE1EC;border-radius:6px;background:#fff;color:#374151;font-size:0.85rem;font-weight:500;cursor:pointer;}
.pm-btn-save{height:38px;padding:0 22px;border:none;border-radius:6px;background:linear-gradient(135deg,#3B4FE4,#5B6CF9);color:#fff;font-size:0.85rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;}
.inv-action-btn{width:30px;height:30px;border:1px solid #E8EAF0;border-radius:6px;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.8rem;color:#64748b;transition:all 0.15s;margin:0 2px;}
.inv-action-btn:hover{background:#F5F7FF;border-color:#3B4FE4;color:#3B4FE4;}
.user-avatar{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#3B4FE4,#5B6CF9);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;margin-right:10px;}
.pm-eye-btn{height:38px!important;width:40px;border:1px solid #DDE1EC!important;border-left:none!important;border-radius:0 6px 6px 0!important;background:#fff!important;color:#6B7280;padding:0;display:flex;align-items:center;justify-content:center;transition:color 0.15s;}
.pm-eye-btn:hover{color:#3B4FE4!important;background:#F5F7FF!important;}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/user-management.js') }}?v={{ filemtime(public_path('js/pages/user-management.js')) }}"></script>
@endpush
