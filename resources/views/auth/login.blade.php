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
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
.page.page-center{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1e1b4b 0%,#3B4FE4 50%,#5B6CF9 100%);position:relative;overflow:hidden;}
.page.page-center::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.08) 1px,transparent 1px);background-size:24px 24px;pointer-events:none;}
.page.page-center::after{content:'';position:absolute;top:-15%;right:-8%;width:480px;height:480px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;}
.login-wrap{width:100%;max-width:420px;padding:20px;position:relative;z-index:1;}
.login-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.35),0 0 0 1px rgba(255,255,255,0.08);}
.login-header{background:linear-gradient(135deg,#3B4FE4 0%,#5B6CF9 100%);padding:36px 32px 30px;text-align:center;position:relative;overflow:hidden;}
.login-header::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.6;pointer-events:none;}
.login-header::after{content:'';position:absolute;bottom:-50px;right:-30px;width:160px;height:160px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.login-logo-circle{width:68px;height:68px;border-radius:18px;background:rgba(255,255,255,0.18);border:2px solid rgba(255,255,255,0.28);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;position:relative;z-index:1;}
.login-logo-circle i{font-size:1.9rem;color:#fff;}
.login-brand{font-size:1.75rem;font-weight:800;color:#fff;margin:0 0 6px;letter-spacing:-0.5px;position:relative;z-index:1;font-family:'Inter',sans-serif;}
.login-tagline{font-size:0.72rem;color:rgba(255,255,255,0.72);margin:0;text-transform:uppercase;letter-spacing:0.14em;position:relative;z-index:1;}
.login-body{padding:28px 32px 24px;background:#fff;}
.login-welcome{margin-bottom:22px;}
.login-heading{font-size:1.1rem;font-weight:700;color:#1e293b;margin:0 0 4px;font-family:'Inter',sans-serif;}
.login-subheading{font-size:0.82rem;color:#94a3b8;margin:0;}
.login-error-box{border-radius:8px;font-size:0.82rem;padding:10px 14px;margin-bottom:18px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#dc2626;display:flex;align-items:center;}
.login-field{margin-bottom:16px;}
.login-label{display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-bottom:7px;}
.login-input-wrap{position:relative;display:flex;align-items:center;}
.login-input-icon{position:absolute;left:13px;color:#94a3b8;font-size:1rem;pointer-events:none;display:flex;align-items:center;z-index:1;}
.login-input{width:100%;height:44px;padding:0 44px 0 40px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:0.88rem;color:#1e293b;background:#F8FAFC;transition:all 0.2s;outline:none;font-family:'Inter',sans-serif;}
.login-input:focus{border-color:#3B4FE4;background:#fff;box-shadow:0 0 0 3px rgba(59,79,228,0.1);}
.login-input::placeholder{color:#CBD5E1;}
.login-toggle-pass{position:absolute;right:12px;background:none;border:none;color:#94a3b8;cursor:pointer;padding:4px;display:flex;align-items:center;font-size:1rem;transition:color 0.15s;}
.login-toggle-pass:hover{color:#3B4FE4;}
.login-btn{width:100%;height:46px;border:none;border-radius:8px;background:linear-gradient(135deg,#3B4FE4 0%,#5B6CF9 100%);color:#fff;font-size:0.9rem;font-weight:700;cursor:pointer;margin-top:6px;display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif;letter-spacing:0.02em;transition:transform 0.15s,box-shadow 0.15s;box-shadow:0 4px 15px rgba(59,79,228,0.35);}
.login-btn:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(59,79,228,0.45);}
.login-btn:active{transform:translateY(0);}
.login-btn:disabled{opacity:0.75;cursor:not-allowed;transform:none;}
.login-footer{padding:13px 32px;background:#F8FAFC;border-top:1px solid #F1F5F9;text-align:center;font-size:0.72rem;color:#94a3b8;}
.login-footer strong{color:#64748b;}
</style>
@endpush
@push('scripts')
<script>
(function() {
    var baseMeta = document.querySelector('meta[name="base-url"]');
    var BASE_URL = baseMeta ? baseMeta.getAttribute('content') : '';
    var form = document.getElementById('login-form');
    var errorEl = document.getElementById('login-error');
    var errorText = document.getElementById('login-error-text');
    var btn = document.getElementById('login-btn');
    var btnText = document.getElementById('login-btn-text');
    var btnSpinner = document.getElementById('login-btn-spinner');
    var toggleBtn = document.getElementById('toggle-password');
    var passInput = document.getElementById('login-password');
    var toggleIcon = document.getElementById('toggle-password-icon');

    toggleBtn.addEventListener('click', function() {
        if (passInput.type === 'password') {
            passInput.type = 'text';
            toggleIcon.className = 'ti ti-eye-off';
        } else {
            passInput.type = 'password';
            toggleIcon.className = 'ti ti-eye';
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        errorEl.classList.add('d-none');
        btn.disabled = true;
        btnText.classList.add('d-none');
        btnSpinner.classList.remove('d-none');

        var username = document.getElementById('login-username').value.trim();
        var password = passInput.value;

        fetch(BASE_URL + '/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ username: username, password: password })
        })
        .then(function(res) {
            return res.json().then(function(data) { return { ok: res.ok, data: data }; });
        })
        .then(function(result) {
            if (result.ok && result.data.token) {
                localStorage.setItem('leanerp_token', result.data.token);
                localStorage.setItem('leanerp_user', JSON.stringify(result.data.user));
                // Set cookie for server-side auth check (expires in 8 hours)
                var expires = new Date(Date.now() + 8 * 60 * 60 * 1000).toUTCString();
                var cookiePath = BASE_URL ? (new URL(BASE_URL)).pathname : '/';
                if(!cookiePath.endsWith('/')) cookiePath += '/';
                document.cookie = 'leanerp_token=' + encodeURIComponent(result.data.token) + '; expires=' + expires + '; path=' + cookiePath + '; SameSite=Lax';
                window.location.href = BASE_URL + '/dashboard';
            } else {
                throw new Error(result.data.message || result.data.error || 'Authentication failed');
            }
        })
        .catch(function(err) {
            errorText.textContent = err.message || 'Authentication failed';
            errorEl.classList.remove('d-none');
        })
        .finally(function() {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            btnSpinner.classList.add('d-none');
        });
    });

    if (localStorage.getItem('leanerp_token')) {
        // Re-set cookie with correct path before redirecting (prevents infinite loop)
        var existingToken = localStorage.getItem('leanerp_token');
        var cookiePath = BASE_URL ? (new URL(BASE_URL)).pathname : '/';
        if(!cookiePath.endsWith('/')) cookiePath += '/';
        var expires = new Date(Date.now() + 8 * 60 * 60 * 1000).toUTCString();
        document.cookie = 'leanerp_token=' + encodeURIComponent(existingToken) + '; expires=' + expires + '; path=' + cookiePath + '; SameSite=Lax';
        window.location.href = BASE_URL + '/dashboard';
    }
})();
</script>
@endpush
