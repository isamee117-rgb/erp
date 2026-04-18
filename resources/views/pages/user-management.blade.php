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
        <button class="btn btn-light shadow-sm" id="addUserBtn" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openAddUser()">
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
              <input type="password" class="form-control pm-input" id="uPassword" placeholder="Enter password">
              <button type="button" class="btn btn-outline-secondary pm-eye-btn" onclick="togglePwdVisibility()" tabindex="-1" id="uPwdEye" title="Show/Hide password">
                <i class="ti ti-eye" id="uPwdEyeIcon"></i>
              </button>
            </div>
            <small class="erp-text-xs text-muted d-none" id="uPasswordHint">Leave blank to keep current password</small>
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
        <p class="erp-text-85 text-muted mb-3" id="pwdUserLabel">For user: </p>
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
@push('scripts')
<script src="{{ asset('js/pages/user-management.js') }}?v={{ filemtime(public_path('js/pages/user-management.js')) }}"></script>
@endpush
