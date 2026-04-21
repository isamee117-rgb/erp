# Weak Internet Handling — Design Spec

**Date:** 2026-04-21
**Status:** Approved

---

## Problem

When the user has a weak or no internet connection:
- Sync requests hang for up to 120 seconds (browser default timeout)
- The app shows a blank screen with no feedback
- Requests silently fail — `.catch` returns `{}` and the user never knows
- No retry happens — one failure = permanent blank state until manual refresh

---

## Solution Overview

Add timeout, retry, and user feedback to the sync layer only. Mutations (createSale, addPayment, etc.) are not affected — retrying mutations risks double-submission.

**Approach:** Patch the existing `request()` function in `api.js` and add a spinner/error UI in `app.js`. No new files, no new dependencies.

---

## Section 1: `api.js` Changes

### Timeout (all requests)

Wrap every `fetch()` call with an `AbortController` that fires after **20 seconds**. If the timer fires before a response arrives, the fetch is aborted and throws a `TimeoutError`.

```
fetch(url, { ...opts, signal: controller.signal })
AbortController timeout: 20,000ms
On abort: throw new Error('Request timed out')
```

This applies to ALL requests (sync and mutations) so the user is never left waiting indefinitely for any operation.

### Retry (sync requests only)

A private `withRetry(fn, maxAttempts, delayMs)` helper:
- Calls `fn()` up to `maxAttempts` times
- On failure, waits `delayMs` milliseconds before next attempt
- Returns the result of the first successful call
- After all attempts exhausted, throws the last error

**Parameters:**
- `maxAttempts`: 3
- `delayMs`: 2000ms (2 seconds between retries)

**Applied to:** `syncCore`, `syncMaster`, `syncTransactions` only.

**NOT applied to:** Any POST/PUT/DELETE mutation (createSale, addPayment, receivePurchaseOrder, etc.)

### Behaviour change for sync `.catch`

Current: `.catch(function() { return {}; })` — silently swallows errors.
New: Remove the `.catch` from sync functions. Let errors propagate up to `app.js` where they are handled with the error screen.

---

## Section 2: `app.js` Changes

### Spinner Overlay

When `syncProgressive()` starts, inject a full-page overlay into `<body>`:

```
Position: fixed, full viewport, z-index: 9999
Background: white (or semi-transparent)
Content: Tabler spinner + "Loading, please wait..."
```

The overlay is removed as soon as `syncCore` succeeds — so the user sees the page as fast as possible. Master and transaction sync continue in background without blocking the UI.

### Error Screen

If `syncCore` fails after all 3 retries, replace the spinner content with:

```
Icon:    Tabler icon-wifi-off (red)
Heading: "No Internet Connection"
Message: "Please check your connection and try again."
Button:  "Retry" → re-runs ERP.init() from scratch
```

The error screen is shown inside the same overlay (spinner content is replaced — no new DOM element needed).

### Implementation points

- `showSyncSpinner()` — creates and appends overlay to `<body>`
- `showSyncError()` — replaces overlay content with error UI
- `hideSyncSpinner()` — removes overlay from DOM
- `syncProgressive()` calls `showSyncSpinner()` at start, `hideSyncSpinner()` after core ready, `showSyncError()` on core failure

---

## Files Changed

| File | Change |
|------|--------|
| `public/js/api.js` | Add AbortController timeout to `request()`, add `withRetry()` helper, apply retry to sync functions, remove silent `.catch` from sync functions |
| `public/js/app.js` | Add `showSyncSpinner()`, `showSyncError()`, `hideSyncSpinner()`, update `syncProgressive()` to call them |

---

## What Is NOT Changed

- No changes to any Blade view files
- No changes to any backend PHP files
- No changes to any other JS page files
- Mutations (POST/PUT/DELETE) do not retry — only timeout after 20s
- No Service Worker, no localStorage cache of API responses

---

## Success Criteria

1. On weak internet, user sees a spinner instead of blank screen
2. Sync retries up to 3 times automatically before showing error
3. After 20 seconds with no response, any request fails cleanly (not after 120s)
4. Error screen shows with a working Retry button
5. Retry button successfully re-initialises the app when connection is restored
6. No mutation (sale/payment/purchase) ever retries automatically
