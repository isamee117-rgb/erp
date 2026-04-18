@extends('layouts.app')
@section('page-title', 'Role Management - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-shield me-2"></i>Role Management</h2>
        <p class="mb-0 inv-subtitle">Create roles and assign module permissions for your team.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" onclick="openRoleModal()">
          <i class="ti ti-plus me-1"></i>Add Role
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-filter-bar">
  <div class="card-body inv-filter-body">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <label class="pm-label">Search Roles</label>
        <input type="text" class="form-control inv-input" id="searchInput" placeholder="Search by role name...">
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card inv-table-card">
  <div class="table-responsive">
    <table class="table table-hover table-vcenter inv-table mb-0">
      <thead>
        <tr>
          <th class="inv-th">Role Name</th>
          <th class="inv-th">Users</th>
          <th class="inv-th">Permissions Summary</th>
          <th class="inv-th">Actions</th>
        </tr>
      </thead>
      <tbody id="rolesBody"></tbody>
    </table>
  </div>
</div>

</div>

{{-- Role Modal --}}
<div class="modal modal-blur fade" id="roleModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content pm-modal-content">
      <div class="modal-header pm-modal-header">
        <h5 class="modal-title pm-modal-title" id="roleModalTitle"><i class="ti ti-shield-plus me-2"></i>Add Role</h5>
        <button type="button" class="btn-close pm-modal-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pm-modal-body">
        <input type="hidden" id="editRoleId">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="pm-label">Role Name</label>
            <input type="text" class="form-control pm-input" id="roleName" placeholder="Enter role name" required>
          </div>
          <div class="col-md-6">
            <label class="pm-label">Description</label>
            <input type="text" class="form-control pm-input" id="roleDesc" placeholder="Short description (optional)">
          </div>
        </div>
        <div class="perm-section-title">Permission Matrix</div>
        <div class="card erp-permission-table-wrap">
          <div class="table-responsive">
            <table class="table table-vcenter table-sm mb-0 perm-table">
              <thead>
                <tr>
                  <th class="perm-th">Module</th>
                  <th class="perm-th text-center">View</th>
                  <th class="perm-th text-center">Create</th>
                  <th class="perm-th text-center">Edit</th>
                  <th class="perm-th text-center">Delete</th>
                </tr>
              </thead>
              <tbody id="permBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer pm-modal-footer">
        <button class="pm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="pm-btn-save" onclick="saveRole()"><i class="ti ti-device-floppy me-1"></i>Save Role</button>
      </div>
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('js/pages/role-management.js') }}?v={{ filemtime(public_path('js/pages/role-management.js')) }}"></script>
@endpush
