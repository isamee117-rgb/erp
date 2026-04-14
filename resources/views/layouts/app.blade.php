@php $base = rtrim(url('/'), '/'); @endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="Expires" content="0"/>
    <meta name="base-url" content="{{ $base }}">
    <title>@yield('page-title', 'LeanERP')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet"/>
    <style>
        :root { --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { font-family: var(--tblr-font-sans-serif); }
        .navbar-vertical .navbar-nav .nav-link { font-size: 0.8125rem; }
        .nav-link-title { margin-left: 0.5rem; }
    </style>
    @stack('styles')
</head>
<body class="layout-fluid">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <div id="app-data" data-user="{{ json_encode($currentUser ?? null) }}" data-currency="{{ $currency ?? 'Rs.' }}"></div>
    <div class="page">
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="{{ $base }}/dashboard">
                        <span class="text-white fw-bold erp-brand-name">LeanERP</span>
                    </a>
                </h1>
                <div class="navbar-nav flex-row d-lg-none">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" id="mobile-avatar">?</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0)" onclick="ERP.logout()" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $base }}/dashboard" data-nav-path="/dashboard">
                                <span class="nav-link-icon"><i class="ti ti-home"></i></span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item pt-2"><small class="nav-link-title text-uppercase text-muted fw-bold ps-3" class="erp-nav-section-label">Master Data</small></li>
                        <li class="nav-item" data-module="Inventory">
                            <a class="nav-link" href="{{ $base }}/inventory" data-nav-path="/inventory">
                                <span class="nav-link-icon"><i class="ti ti-package"></i></span>
                                <span class="nav-link-title">Product Master</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Parties">
                            <a class="nav-link" href="{{ $base }}/customers" data-nav-path="/customers">
                                <span class="nav-link-icon"><i class="ti ti-users"></i></span>
                                <span class="nav-link-title">Customer Master</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Parties">
                            <a class="nav-link" href="{{ $base }}/vendors" data-nav-path="/vendors">
                                <span class="nav-link-icon"><i class="ti ti-truck"></i></span>
                                <span class="nav-link-title">Vendor Master</span>
                            </a>
                        </li>
                        <li class="nav-item pt-2"><small class="nav-link-title text-uppercase text-muted fw-bold ps-3" class="erp-nav-section-label">Supply Chain &amp; Operations</small></li>
                        <li class="nav-item" data-module="POS" data-nav-mode="pos">
                            <a class="nav-link" href="{{ $base }}/pos" data-nav-path="/pos">
                                <span class="nav-link-icon"><i class="ti ti-shopping-bag"></i></span>
                                <span class="nav-link-title">POS Terminal</span>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-mode="job-card">
                            <a class="nav-link" href="{{ $base }}/job-card" data-nav-path="/job-card">
                                <span class="nav-link-icon"><i class="ti ti-clipboard-list"></i></span>
                                <span class="nav-link-title">Job Card</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Sales">
                            <a class="nav-link" href="{{ $base }}/sales" data-nav-path="/sales">
                                <span class="nav-link-icon"><i class="ti ti-history"></i></span>
                                <span class="nav-link-title">Sales History</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Purchases">
                            <a class="nav-link" href="{{ $base }}/purchases" data-nav-path="/purchases">
                                <span class="nav-link-icon"><i class="ti ti-shopping-cart"></i></span>
                                <span class="nav-link-title">Purchases</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Returns">
                            <a class="nav-link" href="{{ $base }}/sales-returns" data-nav-path="/sales-returns">
                                <span class="nav-link-icon"><i class="ti ti-rotate-clockwise"></i></span>
                                <span class="nav-link-title">Sales Returns</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Returns">
                            <a class="nav-link" href="{{ $base }}/purchase-returns" data-nav-path="/purchase-returns">
                                <span class="nav-link-icon"><i class="ti ti-repeat"></i></span>
                                <span class="nav-link-title">Purchase Returns</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Inventory">
                            <a class="nav-link" href="{{ $base }}/inventory-ledger" data-nav-path="/inventory-ledger">
                                <span class="nav-link-icon"><i class="ti ti-receipt"></i></span>
                                <span class="nav-link-title">Inventory Ledger</span>
                            </a>
                        </li>
                        <li class="nav-item pt-2"><small class="nav-link-title text-uppercase text-muted fw-bold ps-3" class="erp-nav-section-label">Finance</small></li>
                        <li class="nav-item" data-module="Finance">
                            <a class="nav-link" href="{{ $base }}/payments" data-nav-path="/payments">
                                <span class="nav-link-icon"><i class="ti ti-wallet"></i></span>
                                <span class="nav-link-title">Payments</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Finance">
                            <a class="nav-link" href="{{ $base }}/ledger" data-nav-path="/ledger">
                                <span class="nav-link-icon"><i class="ti ti-file-text"></i></span>
                                <span class="nav-link-title">Ledgers</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Finance">
                            <a class="nav-link" href="{{ $base }}/outstanding" data-nav-path="/outstanding">
                                <span class="nav-link-icon"><i class="ti ti-building-bank"></i></span>
                                <span class="nav-link-title">Outstanding</span>
                            </a>
                        </li>
                        <li class="nav-item pt-2" data-module="Accounting"><small class="nav-link-title text-uppercase text-muted fw-bold ps-3" class="erp-nav-section-label">Accounting</small></li>
                        <li class="nav-item" data-module="Accounting">
                            <a class="nav-link" href="{{ $base }}/accounting/coa" data-nav-path="/accounting/coa">
                                <span class="nav-link-icon"><i class="ti ti-list-numbers"></i></span>
                                <span class="nav-link-title">Chart of Accounts</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Accounting">
                            <a class="nav-link" href="{{ $base }}/accounting/journals" data-nav-path="/accounting/journals">
                                <span class="nav-link-icon"><i class="ti ti-notebook"></i></span>
                                <span class="nav-link-title">Journal Entries</span>
                            </a>
                        </li>
                        <li class="nav-item pt-2"><small class="nav-link-title text-uppercase text-muted fw-bold ps-3" class="erp-nav-section-label">System Administration</small></li>
                        <li class="nav-item" data-module="Settings">
                            <a class="nav-link" href="{{ $base }}/company" data-nav-path="/company">
                                <span class="nav-link-icon"><i class="ti ti-building"></i></span>
                                <span class="nav-link-title">Company Profile</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Reports">
                            <a class="nav-link" href="{{ $base }}/reports" data-nav-path="/reports">
                                <span class="nav-link-icon"><i class="ti ti-chart-bar"></i></span>
                                <span class="nav-link-title">Reports</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Users">
                            <a class="nav-link" href="{{ $base }}/admin/users" data-nav-path="/admin/users">
                                <span class="nav-link-icon"><i class="ti ti-user-cog"></i></span>
                                <span class="nav-link-title">Users</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Roles">
                            <a class="nav-link" href="{{ $base }}/admin/roles" data-nav-path="/admin/roles">
                                <span class="nav-link-icon"><i class="ti ti-shield"></i></span>
                                <span class="nav-link-title">Roles</span>
                            </a>
                        </li>
                        <li class="nav-item" data-module="Settings">
                            <a class="nav-link" href="{{ $base }}/settings" data-nav-path="/settings">
                                <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
                                <span class="nav-link-title">System Setup</span>
                            </a>
                        </li>
                        <li class="nav-item" data-super-only="true">
                            <a class="nav-link" href="{{ $base }}/admin/companies" data-nav-path="/admin/companies">
                                <span class="nav-link-icon"><i class="ti ti-briefcase"></i></span>
                                <span class="nav-link-title">Company Master</span>
                            </a>
                        </li>
                    </ul>
                    <div class="mt-auto p-3 border-top border-secondary">
                        <div class="d-flex align-items-center text-white mb-2" id="sidebar-user-info">
                            <span class="avatar avatar-sm me-2" id="sidebar-avatar">?</span>
                            <div class="flex-fill">
                                <div class="fw-bold small" id="sidebar-username">—</div>
                                <div class="text-muted small" id="sidebar-role">—</div>
                            </div>
                        </div>
                        <a href="javascript:void(0)" onclick="ERP.logout()" class="btn btn-sm btn-outline-danger w-100">
                            <i class="ti ti-logout me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </aside>
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/api.js') }}?v={{ filemtime(public_path('js/api.js')) }}"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')

    {{-- ── Shared Camera Barcode Scanner ─────────────────────────────────── --}}
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    {{-- Scanner overlay --}}
    <div id="bc-scanner-overlay" class="erp-scanner-overlay">
      <div class="erp-scanner-container">
        <div class="erp-scanner-title">
          <i class="ti ti-scan me-2 erp-icon-inline"></i>Point camera at barcode
        </div>
        <div class="erp-scanner-reader-box">
          <div id="bc-reader"></div>
          {{-- Viewfinder corners --}}
          <div class="erp-scanner-viewfinder">
            <div class="erp-scanner-corner erp-scanner-corner-tl"></div>
            <div class="erp-scanner-corner erp-scanner-corner-tr"></div>
            <div class="erp-scanner-corner erp-scanner-corner-bl"></div>
            <div class="erp-scanner-corner erp-scanner-corner-br"></div>
            <div id="bc-scanline" class="erp-scanner-scanline"></div>
          </div>
        </div>
        <div id="bc-feedback" class="erp-feedback-scanner"></div>
        <button onclick="closeBarcodeScanner()" class="erp-scanner-cancel-btn">
          <i class="ti ti-x me-1"></i>Cancel
        </button>
        <div class="text-white-50 erp-text-xxs mt-2">Supports 1D barcodes, QR codes &amp; more</div>
      </div>
    </div>
    <script>
    var _bcCallback = null, _bcScanner = null, _bcScanned = false;
    function openBarcodeScanner(callback) {
      _bcCallback = callback;
      _bcScanned = false;
      document.getElementById('bc-feedback').textContent = 'Initializing camera...';
      document.getElementById('bc-feedback').style.color = 'rgba(255,255,255,0.5)';
      document.getElementById('bc-scanner-overlay').style.display = 'flex';
      _bcScanner = new Html5Qrcode('bc-reader');
      _bcScanner.start(
        { facingMode: 'environment' },
        { fps: 12, qrbox: { width: 300, height: 130 }, aspectRatio: 1.6 },
        function(code) {
          if (_bcScanned) return;
          _bcScanned = true;
          document.getElementById('bc-feedback').textContent = '\u2713 Detected: ' + code;
          document.getElementById('bc-feedback').style.color = '#10B981';
          setTimeout(function() {
            closeBarcodeScanner();
            if (_bcCallback) _bcCallback(code);
          }, 400);
        },
        function() {}
      ).catch(function(err) {
        document.getElementById('bc-feedback').textContent = 'Camera error: ' + (err.message || err);
        document.getElementById('bc-feedback').style.color = '#EF4444';
      });
      _bcScanner.getRunningTrackCameraCapabilities && setTimeout(function(){
        document.getElementById('bc-feedback').textContent = 'Ready — point at barcode';
        document.getElementById('bc-feedback').style.color = 'rgba(255,255,255,0.5)';
      }, 1200);
    }
    function closeBarcodeScanner() {
      document.getElementById('bc-scanner-overlay').style.display = 'none';
      if (_bcScanner) { _bcScanner.stop().catch(function(){}); _bcScanner = null; }
    }
    // Close on overlay background click
    document.getElementById('bc-scanner-overlay').addEventListener('click', function(e) {
      if (e.target === this) closeBarcodeScanner();
    });
    </script>
</body>
</html>
