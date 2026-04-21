# Weak Internet Handling Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 20-second timeout, 3-attempt retry on sync requests, and a spinner/error screen so users on weak internet never see a blank screen.

**Architecture:** Patch `request()` in `api.js` with AbortController timeout; add a private `withRetry()` helper and apply it to the three sync functions; add spinner/error overlay functions to `app.js` and wire them into `syncProgressive()`.

**Tech Stack:** Vanilla JS, Tabler CSS (Bootstrap 5), existing IIFE pattern — no new dependencies.

---

## File Map

| File | What changes |
|------|-------------|
| `public/js/api.js` | Add AbortController timeout to `request()`; add `withRetry()` helper; apply retry to `syncCore`, `syncMaster`, `syncTransactions`; remove silent `.catch` from all three |
| `public/js/app.js` | Add `showSyncSpinner()`, `showSyncError()`, `hideSyncSpinner()`; update `syncProgressive()` to call them |

---

## Task 1: Add AbortController timeout to `request()` in `api.js`

**Files:**
- Modify: `public/js/api.js:19-39`

- [ ] **Step 1: Open `public/js/api.js` and replace the `request()` function**

Replace the current `request` function (lines 19–39) with this version that adds a 20-second AbortController timeout:

```js
function request(method, url, body) {
    var controller = new AbortController();
    var timeoutId = setTimeout(function() { controller.abort(); }, 20000);

    var opts = { method: method, headers: buildHeaders(), signal: controller.signal };
    if (body !== undefined) { opts.body = JSON.stringify(body); }

    return fetch(API_BASE + url, opts)
        .then(function(res) {
            clearTimeout(timeoutId);
            if (!res.ok) {
                return res.json().catch(function() { return { message: 'Request failed' }; }).then(function(err) {
                    if (err.errors && typeof err.errors === 'object') {
                        var msgs = [];
                        Object.keys(err.errors).forEach(function(field) {
                            var fieldErrors = Array.isArray(err.errors[field]) ? err.errors[field] : [err.errors[field]];
                            fieldErrors.forEach(function(msg) { msgs.push(msg); });
                        });
                        if (msgs.length) { throw new Error(msgs.join('; ')); }
                    }
                    throw new Error(err.message || err.error || 'Request failed');
                });
            }
            return res.json();
        })
        .catch(function(err) {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                throw new Error('Request timed out. Please check your internet connection.');
            }
            throw err;
        });
}
```

- [ ] **Step 2: Manually test in browser (no automated test for fetch)**

Open the browser console on any ERP page. Run:
```js
// Simulate by temporarily setting a very short timeout — just verify no syntax errors
ERP.api.syncCore().then(function(d){ console.log('ok', d); }).catch(function(e){ console.error(e.message); });
```
Expected: Either `ok {...}` (if server is up) or a clean error message — no uncaught exceptions.

- [ ] **Step 3: Commit**

```bash
git add public/js/api.js
git commit -m "feat: add 20s AbortController timeout to all fetch requests"
```

---

## Task 2: Add `withRetry()` helper to `api.js`

**Files:**
- Modify: `public/js/api.js` — add helper after `buildHeaders()` function (before `request()`)

- [ ] **Step 1: Add `withRetry` function after `buildHeaders` (around line 18, before `function request`)**

Insert this block between `buildHeaders` and `request`:

```js
function withRetry(fn, maxAttempts, delayMs) {
    return fn().catch(function(err) {
        if (maxAttempts <= 1) { throw err; }
        return new Promise(function(resolve) {
            setTimeout(resolve, delayMs);
        }).then(function() {
            return withRetry(fn, maxAttempts - 1, delayMs);
        });
    });
}
```

- [ ] **Step 2: Verify no syntax errors**

Open browser console on any ERP page. Type:
```js
typeof withRetry
```
Expected: This is a private IIFE variable so it won't be accessible from console — that's correct. Just check no page errors appear in the console on load.

- [ ] **Step 3: Commit**

```bash
git add public/js/api.js
git commit -m "feat: add withRetry() helper for resilient sync requests"
```

---

## Task 3: Apply retry to sync functions and remove silent `.catch`

**Files:**
- Modify: `public/js/api.js` — `syncCore`, `syncMaster`, `syncTransactions` (lines 52–73)

- [ ] **Step 1: Replace `syncCore`, `syncMaster`, `syncTransactions` with retry-wrapped versions**

Find and replace the three sync functions. Current code:

```js
syncCore: function() {
    var token = getToken();
    if (!token) return Promise.resolve({});
    return request('GET', '/sync/core').catch(function() { return {}; });
},
syncMaster: function() {
    var token = getToken();
    if (!token) return Promise.resolve({});
    return request('GET', '/sync/master').catch(function() { return {}; });
},
syncTransactions: function(params) {
    var token = getToken();
    if (!token) return Promise.resolve({});
    var qs = '';
    if (params && (params.from || params.to)) {
        var parts = [];
        if (params.from) parts.push('from=' + encodeURIComponent(params.from));
        if (params.to)   parts.push('to='   + encodeURIComponent(params.to));
        qs = '?' + parts.join('&');
    }
    return request('GET', '/sync/transactions' + qs).catch(function() { return {}; });
},
```

Replace with:

```js
syncCore: function() {
    var token = getToken();
    if (!token) return Promise.resolve({});
    return withRetry(function() { return request('GET', '/sync/core'); }, 3, 2000);
},
syncMaster: function() {
    var token = getToken();
    if (!token) return Promise.resolve({});
    return withRetry(function() { return request('GET', '/sync/master'); }, 3, 2000);
},
syncTransactions: function(params) {
    var token = getToken();
    if (!token) return Promise.resolve({});
    var qs = '';
    if (params && (params.from || params.to)) {
        var parts = [];
        if (params.from) parts.push('from=' + encodeURIComponent(params.from));
        if (params.to)   parts.push('to='   + encodeURIComponent(params.to));
        qs = '?' + parts.join('&');
    }
    return withRetry(function() { return request('GET', '/sync/transactions' + qs); }, 3, 2000);
},
```

- [ ] **Step 2: Also remove the silent `.catch` from the legacy `sync` function (line 50–51)**

Find:
```js
sync: function() {
    var token = getToken();
    if (!token) return Promise.resolve({});
    return request('GET', '/sync').catch(function() { return {}; });
},
```

Replace with:
```js
sync: function() {
    var token = getToken();
    if (!token) return Promise.resolve({});
    return withRetry(function() { return request('GET', '/sync'); }, 3, 2000);
},
```

- [ ] **Step 3: Verify in browser console**

On any ERP page with server running:
```js
ERP.api.syncCore().then(function(d){ console.log('syncCore ok', Object.keys(d)); }).catch(function(e){ console.error('syncCore fail:', e.message); });
```
Expected: `syncCore ok [...]` with state keys listed.

- [ ] **Step 4: Commit**

```bash
git add public/js/api.js
git commit -m "feat: apply withRetry (3 attempts, 2s delay) to all sync functions"
```

---

## Task 4: Add spinner overlay functions to `app.js`

**Files:**
- Modify: `public/js/app.js` — add three private functions before `window.ERP.init`

- [ ] **Step 1: Add `showSyncSpinner`, `hideSyncSpinner`, `showSyncError` functions**

In `public/js/app.js`, insert these three functions just before the `window.ERP.init = function()` line:

```js
var SYNC_OVERLAY_ID = 'erp-sync-overlay';

function showSyncSpinner() {
    if (document.getElementById(SYNC_OVERLAY_ID)) return;
    var overlay = document.createElement('div');
    overlay.id = SYNC_OVERLAY_ID;
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;';
    overlay.innerHTML = '<div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;"></div>' +
        '<p class="text-muted mb-0">Loading, please wait...</p>';
    document.body.appendChild(overlay);
}

function hideSyncSpinner() {
    var overlay = document.getElementById(SYNC_OVERLAY_ID);
    if (overlay) overlay.parentNode.removeChild(overlay);
}

function showSyncError() {
    var overlay = document.getElementById(SYNC_OVERLAY_ID);
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = SYNC_OVERLAY_ID;
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;';
        document.body.appendChild(overlay);
    }
    overlay.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
        '<line x1="1" y1="1" x2="23" y2="23"></line>' +
        '<path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>' +
        '<path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>' +
        '<path d="M10.71 5.05A16 16 0 0 1 22.56 9"></path>' +
        '<path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>' +
        '<path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>' +
        '<line x1="12" y1="20" x2="12.01" y2="20"></line>' +
        '</svg>' +
        '<h3 class="mb-1" style="color:#d63939;">No Internet Connection</h3>' +
        '<p class="text-muted mb-3">Please check your connection and try again.</p>' +
        '<button class="btn btn-primary" id="erp-retry-btn">Retry</button>';
    document.getElementById('erp-retry-btn').addEventListener('click', function() {
        hideSyncSpinner();
        window.ERP.init();
    });
}
```

- [ ] **Step 2: Verify no syntax errors**

Open any ERP page in browser. Check console for errors on load. No new errors should appear.

- [ ] **Step 3: Commit**

```bash
git add public/js/app.js
git commit -m "feat: add showSyncSpinner, hideSyncSpinner, showSyncError to app.js"
```

---

## Task 5: Wire spinner/error into `syncProgressive()` in `app.js`

**Files:**
- Modify: `public/js/app.js` — `syncProgressive` function (lines 61–93)

- [ ] **Step 1: Replace `syncProgressive` with the wired-up version**

Find the current `window.ERP.syncProgressive` function and replace it entirely:

```js
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
            showSyncError();
            console.error('syncCore failed after all retries:', err.message);
        });
};
```

> **Note:** `syncMaster` and `syncTransactions` failures after core success are non-fatal — they log a warning but don't show the error screen, because the user already sees the page with core data. Only `syncCore` failure triggers the error screen.

- [ ] **Step 2: End-to-end test in browser (server running)**

1. Open any ERP page. Expected: spinner appears briefly, then page loads normally.
2. Open DevTools → Network tab → set throttling to "Offline".
3. Refresh the page. Expected: spinner appears, then after ~60 seconds (3 retries × 20s timeout) the error screen appears with "No Internet Connection" and a Retry button.
4. Re-enable network. Click "Retry". Expected: spinner appears again, page loads successfully.

- [ ] **Step 3: Test that mutations are unaffected**

With network online, create a sale or add a payment. Expected: works as before — no change in behaviour for mutations.

- [ ] **Step 4: Commit**

```bash
git add public/js/app.js
git commit -m "feat: show spinner on sync start, error screen if syncCore fails after retries"
```

---

## Self-Review

**Spec coverage check:**
- ✓ 20-second timeout on all requests → Task 1
- ✓ `withRetry()` helper with 3 attempts, 2s delay → Task 2
- ✓ Applied to `syncCore`, `syncMaster`, `syncTransactions` (and legacy `sync`) → Task 3
- ✓ Silent `.catch` removed from all sync functions → Task 3
- ✓ Spinner overlay on sync start → Task 4 + 5
- ✓ Error screen with Retry button on syncCore failure → Task 4 + 5
- ✓ Retry button calls `ERP.init()` → Task 4
- ✓ `hideSyncSpinner()` called after syncCore success → Task 5
- ✓ Mutations not retried → enforced by only wrapping sync functions in Task 3
- ✓ No Blade/PHP/other JS file changes → confirmed by file map

**Placeholder scan:** No TBDs, no vague steps. All code blocks are complete. ✓

**Type consistency:**
- `SYNC_OVERLAY_ID` used in all three overlay functions consistently ✓
- `showSyncSpinner` / `hideSyncSpinner` / `showSyncError` names match across Task 4 and Task 5 ✓
- `withRetry(fn, 3, 2000)` signature matches definition ✓
