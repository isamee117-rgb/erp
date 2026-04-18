# Price Tier Category Dropdown Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the free-text input in Customer Category Pricing with a dropdown linked to `businessCategories`, and restyle the section as an accordion matching UOM Conversions.

**Architecture:** All changes are confined to `renderPriceTiersSection()` and related helper functions in `public/js/pages/inventory.js`. The accordion reuses existing `pm-acct-*` CSS classes already used by the Accounting Accounts and UOM Conversions sections. No backend, API, or CSS changes are needed.

**Tech Stack:** Vanilla JavaScript, Bootstrap 5, Tabler CSS, Laravel Blade (no build step)

---

### Task 1: Rewrite `renderPriceTiersSection()` — accordion + table + dropdown add card

**Files:**
- Modify: `public/js/pages/inventory.js:507-545`

This task replaces the entire `renderPriceTiersSection()` function body with:
1. An accordion wrapper (same `pm-acct-wrap` / `pm-acct-toggle` / `pm-acct-body` pattern as UOM Conversions)
2. A table inside showing existing tiers (or empty-state row)
3. An always-visible add card at the bottom with a `<select>` dropdown (filtered to exclude already-used categories)
4. If all business categories are already used, the add card is hidden

- [ ] **Step 1: Replace `renderPriceTiersSection()` in `inventory.js`**

Find and replace the entire function (lines 507–545) with:

```js
function renderPriceTiersSection(product) {
  var section = document.getElementById('pf-price-tiers-section');
  section.style.display = '';
  var tiers = product.priceTiers || [];
  var allCats = window.ERP.state.businessCategories || [];
  var usedCats = tiers.map(function(t) { return t.category; });
  var availableCats = allCats.filter(function(c) { return usedCats.indexOf(c.name) === -1; });
  var pid = product.id;

  // Table rows
  var rowsHtml = '';
  if (tiers.length === 0) {
    rowsHtml = '<tr><td colspan="3" class="text-center text-muted uom-conv-empty">No price tiers — all customers pay the base unit price.</td></tr>';
  } else {
    tiers.forEach(function(t) {
      rowsHtml +=
        '<tr>' +
          '<td class="uom-conv-td">' + escHtml(t.category) + '</td>' +
          '<td class="uom-conv-td text-end">' + ERP.formatCurrency(t.price) + '</td>' +
          '<td class="uom-conv-td text-center">' +
            '<button type="button" class="uom-conv-del-btn" onclick="confirmDeletePriceTier(\'' + escHtml(t.id) + '\',\'' + escHtml(t.category) + '\')" title="Remove tier">' +
              '<i class="ti ti-trash"></i>' +
            '</button>' +
          '</td>' +
        '</tr>';
    });
  }

  // Dropdown options (available categories only)
  var catOptHtml = '<option value="">Select category...</option>';
  availableCats.forEach(function(c) {
    catOptHtml += '<option value="' + escHtml(c.name) + '">' + escHtml(c.name) + '</option>';
  });

  // Add card — hidden when no categories left to assign
  var addCardHtml = '';
  if (availableCats.length > 0) {
    addCardHtml =
      '<div class="uom-add-card" id="pm-tier-add-card">' +
        '<div class="uom-add-grid">' +
          '<div>' +
            '<div class="pm-label mb-1">Customer Category</div>' +
            '<select class="form-select pm-input" id="pm-tier-cat">' + catOptHtml + '</select>' +
          '</div>' +
          '<div>' +
            '<div class="pm-label mb-1">Price</div>' +
            '<div class="input-group">' +
              '<span class="input-group-text pm-prefix">Rs.</span>' +
              '<input type="number" step="0.01" min="0" class="form-control pm-input" id="pm-tier-price" placeholder="0.00">' +
            '</div>' +
          '</div>' +
          '<div class="uom-add-btn-wrap">' +
            '<button type="button" class="pm-btn-save uom-conv-add-btn" onclick="savePriceTierRow()"><i class="ti ti-plus me-1"></i>Add</button>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  section.innerHTML =
    '<div class="pm-acct-wrap mt-1">' +
      '<button type="button" class="pm-acct-toggle" onclick="togglePriceTierSection()">' +
        '<span><i class="ti ti-tag me-2"></i>Customer Category Pricing</span>' +
        '<i class="ti ti-chevron-down" id="priceTierChevron"></i>' +
      '</button>' +
      '<div id="priceTierBody" class="pm-acct-body" style="display:none;">' +
        '<table class="table table-sm uom-conv-table mb-2">' +
          '<thead><tr>' +
            '<th class="uom-conv-th">Customer Category</th>' +
            '<th class="uom-conv-th text-end">Price</th>' +
            '<th class="uom-conv-th"></th>' +
          '</tr></thead>' +
          '<tbody>' + rowsHtml + '</tbody>' +
        '</table>' +
        addCardHtml +
      '</div>' +
    '</div>';
}
```

- [ ] **Step 2: Verify in browser**

Open product modal (Edit any product). Scroll to "Customer Category Pricing" accordion. Confirm:
- It is collapsed by default (chevron down, body hidden)
- No free-text input visible

---

### Task 2: Add `togglePriceTierSection()` and update `savePriceTierRow()` / `deletePriceTierById()`

**Files:**
- Modify: `public/js/pages/inventory.js:548-586`

- [ ] **Step 1: Delete `openAddPriceTierRow()` (lines 548–553) — it is no longer needed**

Remove the entire function:
```js
// DELETE THIS ENTIRE FUNCTION:
function openAddPriceTierRow() {
  document.getElementById('pm-tier-add-row').style.display = '';
  document.getElementById('pm-tier-cat').value = '';
  document.getElementById('pm-tier-price').value = '';
  document.getElementById('pm-tier-cat').focus();
}
```

- [ ] **Step 2: Add `togglePriceTierSection()` immediately after `renderPriceTiersSection()`**

Insert right after the closing `}` of `renderPriceTiersSection()`:

```js
function togglePriceTierSection() {
  var body = document.getElementById('priceTierBody');
  var chevron = document.getElementById('priceTierChevron');
  if (!body) return;
  var open = body.style.display !== 'none';
  body.style.display = open ? 'none' : 'block';
  if (chevron) {
    chevron.classList.toggle('ti-chevron-down', open);
    chevron.classList.toggle('ti-chevron-up', !open);
  }
}
```

- [ ] **Step 3: Update `savePriceTierRow()` — keep accordion open after save**

Replace the existing `savePriceTierRow()` (currently lines 555–569) with:

```js
async function savePriceTierRow() {
  var productId = document.getElementById('pf-id').value;
  var category  = (document.getElementById('pm-tier-cat').value || '').trim();
  var price     = parseFloat(document.getElementById('pm-tier-price').value);
  if (!category) { alert('Customer category name is required'); return; }
  if (isNaN(price) || price < 0) { alert('Price must be 0 or greater'); return; }
  try {
    await ERP.api.savePriceTier(productId, { category: category, price: price });
    await ERP.sync();
    var product = (window.ERP.state.products || []).find(function(p) { return p.id === productId; });
    if (product) { renderPriceTiersSection(product); }
    var body = document.getElementById('priceTierBody');
    if (body) body.style.display = 'block';
  } catch(e) {
    alert('Error: ' + e.message);
  }
}
```

- [ ] **Step 4: Update `deletePriceTierById()` — keep accordion open after delete**

Replace the existing `deletePriceTierById()` (currently lines 576–586) with:

```js
async function deletePriceTierById(tierId) {
  var productId = document.getElementById('pf-id').value;
  try {
    await ERP.api.deletePriceTier(productId, tierId);
    await ERP.sync();
    var product = (window.ERP.state.products || []).find(function(p) { return p.id === productId; });
    if (product) { renderPriceTiersSection(product); }
    var body = document.getElementById('priceTierBody');
    if (body) body.style.display = 'block';
  } catch(e) {
    alert('Error: ' + e.message);
  }
}
```

- [ ] **Step 5: Verify full flow in browser**

1. Open Edit Product modal
2. Click "Customer Category Pricing" accordion header → body expands
3. Add card shows dropdown with `businessCategories` values (e.g. Wholesale, Retail, Corporate)
4. Select a category, enter price, click "+ Add" → tier appears in table, accordion stays open
5. Verify selected category no longer appears in dropdown after being added
6. Delete a tier → deleted category reappears in dropdown
7. Add all available categories → add card disappears

- [ ] **Step 6: Commit**

```bash
git add public/js/pages/inventory.js
git commit -m "feat: replace price tier text input with business category dropdown + accordion UI"
```

---

## Self-Review

**Spec coverage:**
- [x] Text input → `<select>` dropdown ✓ (Task 1)
- [x] Dropdown populated from `businessCategories` ✓ (Task 1)
- [x] Duplicate prevention (used cats filtered out) ✓ (Task 1)
- [x] All categories used → add card hidden ✓ (Task 1)
- [x] Accordion structure matching UOM Conversions ✓ (Task 1)
- [x] Table with empty-state row ✓ (Task 1)
- [x] `togglePriceTierSection()` ✓ (Task 2)
- [x] Accordion stays open after add/delete ✓ (Task 2)
- [x] `openAddPriceTierRow()` removed ✓ (Task 2)
- [x] No backend/API changes needed ✓

**Placeholder scan:** None found.

**Type consistency:** `escHtml`, `ERP.formatCurrency`, `ERP.api.savePriceTier`, `ERP.api.deletePriceTier` — all used in original code, no renames introduced.
