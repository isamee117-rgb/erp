@extends('layouts.auth')
@section('page-title', 'Sign In - LeanERP')
@section('content')

<div class="login-wrap">
  <div class="login-card">

    <div class="login-header">
      <div class="login-logo-circle">
        <i class="ti ti-building-factory-2"></i>
      </div>
      <h1 class="login-brand">LeanERP</h1>
      <p class="login-tagline">Enterprise Resource Planning</p>
    </div>

    <div class="login-body">
      <div class="login-welcome">
        <h2 class="login-heading">Welcome back</h2>
        <p class="login-subheading">Sign in to access your workspace</p>
      </div>

      <div class="login-error-box d-none" id="login-error" role="alert">
        <i class="ti ti-alert-circle me-2"></i><span id="login-error-text"></span>
      </div>

      <form id="login-form" autocomplete="off" novalidate>
        <div class="login-field">
          <label class="login-label">Username</label>
          <div class="login-input-wrap">
            <span class="login-input-icon"><i class="ti ti-user"></i></span>
            <input type="text" class="login-input" id="login-username" placeholder="Enter your username" autocomplete="off" required>
          </div>
        </div>

        <div class="login-field">
          <label class="login-label">Password</label>
          <div class="login-input-wrap">
            <span class="login-input-icon"><i class="ti ti-lock"></i></span>
            <input type="password" class="login-input" id="login-password" placeholder="Enter your password" autocomplete="off" required>
            <button type="button" class="login-toggle-pass" id="toggle-password" tabindex="-1">
              <i class="ti ti-eye" id="toggle-password-icon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="login-btn" id="login-btn">
          <span id="login-btn-text"><i class="ti ti-arrow-right me-2"></i>Sign In</span>
          <span id="login-btn-spinner" class="d-none">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>Signing in...
          </span>
        </button>
      </form>
    </div>

    <div class="login-footer">
      Powered by <strong>Syncstack Solutions</strong> &mdash; All rights reserved
    </div>

  </div>
</div>

@endsection
@push('scripts')
<script src="{{ url('js/pages/login.js') }}"></script>
@endpush
