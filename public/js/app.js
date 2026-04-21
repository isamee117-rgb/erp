(function() {
    'use strict';

    var baseMeta = document.querySelector('meta[name="base-url"]');
    var BASE_URL = baseMeta ? baseMeta.getAttribute('content') : '';
    var BASE_PATH = BASE_URL.replace(/^https?:\/\/[^\/]+/, '');

    window.ERP = window.ERP || {};

    window.ERP.state = {
        companies: [],
        customRoles: [],
        users: [],
        currentUser: null,
        products: [],
        categories: [],
        uoms: [],
        entityTypes: [],
        businessCategories: [],
        parties: [],
        purchaseOrders: [],
        sales: [],
        salesReturns: [],
        purchaseReturns: [],
        payments: [],
        ledger: [],
        costLayers: [],
        jobCards: [],
        jobCardHistory: [],
        currency: 'Rs.',
        invoiceFormat: 'A4',
        costingMethod: 'moving_average',
        jobCardMode: false,
        documentSequences: [],
        chartOfAccounts: [],
        accountMappings: {}
    };

    function mergeState(data) {
        if (data && typeof data === 'object') {
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    window.ERP.state[key] = data[key];
                }
            }
        }
    }
    window.ERP.mergeState = mergeState;

    // Legacy full sync (still works)
    window.ERP.sync = function() {
        return window.ERP.api.sync().then(function(data) {
            mergeState(data);
            updateUI();
            applyPermissions();
            return data;
        });
    };

    // Progressive sync: core → page render → master + transactions background mein
    window.ERP.syncProgressive = function(onCoreReady) {
        showSyncSpinner();

        return window.ERP.api.syncCore()
            .then(function(coreData) {
                mergeState(coreData);
                updateUI();
                applyPermissions();
                hideSyncSpinner();

                if (typeof onCoreReady === 'function') {
                    onCoreReady();
                }

                return Promise.all([
                    window.ERP.api.syncMaster().then(function(masterData) {
                        mergeState(masterData);
                        if (typeof window.ERP.onReady === 'function') {
                            window.ERP.onReady();
                        }
                    }).catch(function(err) {
                        console.warn('syncMaster failed after retries:', err.message);
                    }),
                    window.ERP.api.syncTransactions().then(function(txData) {
                        mergeState(txData);
                        if (txData.loadedFrom) {
                            window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
                        }
                        if (typeof window.ERP.onReady === 'function') {
                            window.ERP.onReady();
                        }
                    }).catch(function(err) {
                        console.warn('syncTransactions failed after retries:', err.message);
                    })
                ]);
            })
            .catch(function(err) {
                if (err.message && err.message.indexOf('401') !== -1) {
                    window.ERP.logout();
                    return;
                }
                showSyncError();
                console.error('syncCore failed after all retries:', err.message);
            });
    };

    var SYNC_OVERLAY_ID = 'erp-sync-overlay';

    function showSyncSpinner() {
        if (document.getElementById(SYNC_OVERLAY_ID)) return;
        var overlay = document.createElement('div');
        overlay.id = SYNC_OVERLAY_ID;
        overlay.className = 'erp-sync-overlay';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"></div>' +
            '<p class="text-muted mb-0">Loading, please wait...</p>';
        document.body.appendChild(overlay);
    }

    function hideSyncSpinner() {
        var overlay = document.getElementById(SYNC_OVERLAY_ID);
        if (overlay) overlay.parentNode.removeChild(overlay);
    }

    function showSyncError() {
        var overlay = document.getElementById(SYNC_OVERLAY_ID);
        if (!overlay) { return; }
        overlay.className = 'erp-sync-overlay';
        overlay.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
            '<line x1="1" y1="1" x2="23" y2="23"></line>' +
            '<path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>' +
            '<path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>' +
            '<path d="M10.71 5.05A16 16 0 0 1 22.56 9"></path>' +
            '<path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>' +
            '<path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>' +
            '<line x1="12" y1="20" x2="12.01" y2="20"></line>' +
            '</svg>' +
            '<h3 class="text-danger mb-1">No Internet Connection</h3>' +
            '<p class="text-muted mb-3">Please check your connection and try again.</p>' +
            '<button class="btn btn-primary" id="erp-retry-btn">Retry</button>';
        document.getElementById('erp-retry-btn').addEventListener('click', function() {
            hideSyncSpinner();
            window.ERP.init();
        });
    }

    window.ERP.init = function() {
        var isLoginPage = window.location.pathname === BASE_PATH + '/login';
        var token = localStorage.getItem('leanerp_token');

        if (!token && !isLoginPage) {
            window.location.href = BASE_URL + '/login';
            return;
        }

        if (isLoginPage) return;

        var savedUser = localStorage.getItem('leanerp_user');
        if (savedUser && savedUser !== 'undefined') {
            try {
                window.ERP.state.currentUser = JSON.parse(savedUser);
                updateUI();
            } catch(e) {}
        }

        window.ERP.syncProgressive(function() {
            // Core ready — page turant render karo
            if (typeof window.ERP.onReady === 'function') {
                window.ERP.onReady();
            }
        }).catch(function(err) {
            console.error('Sync failed:', err);
            if (err.message && err.message.indexOf('401') !== -1) {
                window.ERP.logout();
            }
        });
    };

    window.ERP.hasPermission = function(module, action) {
        var user = window.ERP.state.currentUser;
        if (!user) return false;
        if (user.systemRole === 'Super Admin' || user.systemRole === 'Company Admin') return true;
        var roles = window.ERP.state.customRoles || [];
        var role = null;
        for (var i = 0; i < roles.length; i++) {
            if (roles[i].id === user.roleId) { role = roles[i]; break; }
        }
        if (!role || !role.permissions || !role.permissions[module]) return false;
        return !!role.permissions[module][action];
    };

    window.ERP.logout = function() {
        localStorage.removeItem('leanerp_token');
        localStorage.removeItem('leanerp_user');
        // Clear auth cookie
        document.cookie = 'leanerp_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax';
        window.location.href = BASE_URL + '/login';
    };

    window.ERP.formatCurrency = function(amount) {
        var currency = window.ERP.state.currency || 'Rs.';
        var num = parseFloat(amount) || 0;
        return currency + ' ' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    function updateUI() {
        var user = window.ERP.state.currentUser;
        if (!user) return;

        var initial = user.username ? user.username.charAt(0).toUpperCase() : '?';

        var sidebarUsername = document.getElementById('sidebar-username');
        var sidebarRole = document.getElementById('sidebar-role');
        var sidebarAvatar = document.getElementById('sidebar-avatar');
        var mobileAvatar = document.getElementById('mobile-avatar');

        if (sidebarUsername) sidebarUsername.textContent = user.username;
        if (sidebarRole) sidebarRole.textContent = user.systemRole || '';
        if (sidebarAvatar) sidebarAvatar.textContent = initial;
        if (mobileAvatar) mobileAvatar.textContent = initial;

        applySidebarMode();
    }

    function applySidebarMode() {
        var jobCardMode = window.ERP.state.jobCardMode;
        var jcNav       = document.querySelector('[data-nav-mode="job-card"]');
        if (jcNav) jcNav.style.display = jobCardMode ? '' : 'none';
    }

    function applyPermissions() {
        var user = window.ERP.state.currentUser;
        if (!user) return;

        var navItems = document.querySelectorAll('[data-module]');
        for (var i = 0; i < navItems.length; i++) {
            var module = navItems[i].getAttribute('data-module');
            if (!window.ERP.hasPermission(module, 'view')) {
                navItems[i].style.display = 'none';
            } else {
                navItems[i].style.display = '';
            }
        }

        var superItems = document.querySelectorAll('[data-super-only]');
        for (var j = 0; j < superItems.length; j++) {
            if (user.systemRole !== 'Super Admin') {
                superItems[j].style.display = 'none';
            } else {
                superItems[j].style.display = '';
            }
        }
    }

    function highlightActiveNav() {
        var path = window.location.pathname.replace(BASE_PATH, '') || '/';
        var links = document.querySelectorAll('[data-nav-path]');
        for (var i = 0; i < links.length; i++) {
            var navPath = links[i].getAttribute('data-nav-path');
            if (path === navPath || (navPath !== '/' && path.indexOf(navPath) === 0)) {
                links[i].classList.add('active');
            } else {
                links[i].classList.remove('active');
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            highlightActiveNav();
            window.ERP.init();
        });
    } else {
        highlightActiveNav();
        window.ERP.init();
    }
})();
