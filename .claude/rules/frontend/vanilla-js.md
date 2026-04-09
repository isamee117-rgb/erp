# Frontend JS Rules — LeanERP

> This project uses **Vanilla JavaScript** (no React/Vue/Angular).

## Global State

- All state lives in `window.ERP.state` (defined in `public/js/app.js`)
- Never store state in DOM attributes — read from `window.ERP.state`
- After any API mutation, call `ERP.sync()` or targeted state merge to refresh

## API Calls

- All API functions are in `window.ERP.api` (`public/js/api.js`)
- Always use `ERP.api.*` methods — never write raw `fetch()` calls in page JS
- Handle errors with `.catch(function(e){ alert('Error: ' + e.message); })`

## Page JS Files (`public/js/pages/`)

- One JS file per page
- Export functions via `window.*` or plain `function` declarations (no modules)
- `window.ERP.onReady = function(){ renderPage(); }` — called after sync completes
- DOM manipulation: use `innerHTML` for list rendering, `textContent` for single values

## Patterns

```js
// Render pattern
function renderPage() {
    var items = window.ERP.state.someList || [];
    var html = '';
    items.forEach(function(item) {
        html += '<tr>...</tr>';
    });
    document.getElementById('tableBody').innerHTML = html || '<tr><td>No data</td></tr>';
}

// Save pattern
async function saveItem() {
    try {
        await ERP.api.someAction(data);
        bootstrap.Modal.getInstance(document.getElementById('myModal')).hide();
        await ERP.sync();
        renderPage();
    } catch(e) {
        alert('Error: ' + e.message);
    }
}
```

## Rules

- No ES6 modules (`import`/`export`) — use IIFE or plain functions
- No arrow functions in older code sections (keep consistency with existing code)
- Currency display: always use `ERP.formatCurrency(amount)`
- Permissions check: always use `ERP.hasPermission(module, action)` before showing actions
