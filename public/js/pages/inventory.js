var sortKey = 'name', sortDir = 'asc', currentPage = 1, itemsPerPage = 10;
var editingProductId = null, adjustProductId = null;

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

window.ERP.onReady = function() { renderPage(); };

function computeStats() {
  var products = window.ERP.state.products || [];
  var total = products.length;
  var inStock = 0, outStock = 0, lowStock = 0;
  products.forEach(function(p) {
    if (p.type === 'Service') return;
    if (p.currentStock <= 0) outStock++;
    else if (p.currentStock <= p.reorderLevel) lowStock++;
    else inStock++;
  });
  document.getElementById('stat-total').textContent = total;
  document.getElementById('stat-instock').textContent = inStock;
  document.getElementById('stat-outstock').textContent = outStock;
  document.getElementById('stat-lowstock').textContent = lowStock;
}

function getFilteredProducts() {
  var state = window.ERP.state;
  var search = (document.getElementById('inv-search').value || '').toLowerCase();
  var cat = document.getElementById('inv-category').value;
  var type = document.getElementById('inv-type').value;
  var lowOnly = document.getElementById('inv-lowstock').checked;

  var catSel = document.getElementById('inv-category');
  if (catSel.options.length <= 1) {
    (state.categories || []).forEach(function(c) {
      var o = document.createElement('option'); o.value = c.id; o.textContent = c.name; catSel.appendChild(o);
    });
  }

  var list = (state.products || []).filter(function(p) {
    var ms = p.name.toLowerCase().indexOf(search) !== -1 || (p.itemNumber||'').toLowerCase().indexOf(search) !== -1 || (p.barcode || '').toLowerCase().indexOf(search) !== -1;
    var mc = cat === 'all' || p.categoryId === cat;
    var mt = type === 'all' || p.type === type;
    var ml = !lowOnly || (p.currentStock <= p.reorderLevel);
    return ms && mc && mt && ml;
  });

  list.sort(function(a, b) {
    var va = a[sortKey], vb = b[sortKey];
    if (va === undefined) va = '';
    if (vb === undefined) vb = '';
    if (va < vb) return sortDir === 'asc' ? -1 : 1;
    if (va > vb) return sortDir === 'asc' ? 1 : -1;
    return 0;
  });
  return list;
}

function toggleSort(key) {
  if (sortKey === key) { sortDir = sortDir === 'asc' ? 'desc' : 'asc'; }
  else { sortKey = key; sortDir = 'asc'; }
  renderPage();
}

function renderPage() {
  var state = window.ERP.state;
  computeStats();
  var filtered = getFilteredProducts();
  var totalPages = Math.max(1, Math.ceil(filtered.length / itemsPerPage));
  if (currentPage > totalPages) currentPage = totalPages;
  var start = (currentPage - 1) * itemsPerPage;
  var page = filtered.slice(start, start + itemsPerPage);
  var cats = state.categories || [];

  var tbody = document.getElementById('inv-tbody');
  var html = '';
  if (page.length === 0) {
    html = '<tr><td colspan="12" class="text-center text-muted py-5"><div class="py-3"><i class="ti ti-package-off fs-1 d-block mb-2 text-muted"></i>No matching products found</div></td></tr>';
  } else {
    page.forEach(function(p) {
      var catName = '';
      for (var i = 0; i < cats.length; i++) { if (cats[i].id === p.categoryId) { catName = cats[i].name; break; } }
      var isLow = p.type === 'Product' && p.currentStock <= p.reorderLevel && p.currentStock > 0;
      var isOut = p.type === 'Product' && p.currentStock <= 0;

      var statusBadge = '';
      var rowClass = '';
      if (p.type === 'Service') {
        statusBadge = '<span class="badge badge-status badge-status-service">Service</span>';
      } else if (isOut) {
        statusBadge = '<span class="badge badge-status badge-status-outofstock">Out of Stock</span>';
        rowClass = 'inv-row-outofstock';
      } else if (isLow) {
        statusBadge = '<span class="badge badge-status badge-status-lowstock">Low Stock</span>';
        rowClass = 'inv-row-lowstock';
      } else {
        statusBadge = '<span class="badge badge-status badge-status-instock">In Stock</span>';
      }

      var typeBadge = p.type === 'Service'
        ? '<span class="badge-type-service">Service</span>'
        : '<span class="badge-type-product">Product</span>';

      html += '<tr class="' + rowClass + '">' +
        '<td class="inv-chk-col" class="erp-col-chk-pad"><input type="checkbox" class="inv-chk inv-row-chk" data-id="' + p.id + '" ' + (selectedProducts.has(p.id) ? 'checked' : '') + ' onclick="toggleSelectProduct(\'' + p.id + '\',this)"></td>' +
        '<td><span class="inv-sku">' + (p.itemNumber || '') + '</span></td>' +
        '<td class="inv-product-name">' + (p.name || '') + '</td>' +
        '<td><span class="inv-category-text">' + (catName || 'General') + '</span></td>' +
        '<td>' + typeBadge + '</td>' +
        '<td><span class="inv-category-text">' + (p.uom || '') + '</span></td>' +
        '<td class="text-end inv-cost">' + ERP.formatCurrency(p.unitCost || 0) + '</td>' +
        '<td class="text-end inv-price">' + ERP.formatCurrency(p.unitPrice || 0) + '</td>' +
        '<td class="text-center inv-stock-num">' + (p.type === 'Service' ? '<span class="text-muted">\u2014</span>' : (p.currentStock || 0)) + '</td>' +
        '<td class="text-center inv-reorder-num">' + (p.reorderLevel || 0) + '</td>' +
        '<td>' + statusBadge + '</td>' +
        '<td class="text-center"><div class="d-flex gap-1 justify-content-center">' +
        '<button class="inv-action-btn" onclick="openProductModal(\'edit\',\'' + p.id + '\')" title="Edit Product"><i class="ti ti-pencil"></i></button>' +
        (p.type !== 'Service' ? '<button class="inv-action-btn inv-action-warn" onclick="openAdjustModal(\'' + p.id + '\')" title="Adjust Stock"><i class="ti ti-adjustments"></i></button>' : '') +
        '<button class="inv-action-btn inv-action-danger" onclick="confirmDeleteProduct(\'' + p.id + '\')" title="Delete Product"><i class="ti ti-trash"></i></button>' +
        '</div></td></tr>';
    });
  }
  tbody.innerHTML = html;
  updateInvBulkBar();
  // sync select-all state
  var allChks = document.querySelectorAll('.inv-row-chk');
  var checkedChks = Array.from(allChks).filter(function(c){ return selectedProducts.has(c.dataset.id); });
  var sa = document.getElementById('inv-select-all');
  if (sa) { sa.checked = allChks.length > 0 && checkedChks.length === allChks.length; sa.indeterminate = checkedChks.length > 0 && checkedChks.length < allChks.length; }

  var showingStart = filtered.length === 0 ? 0 : start + 1;
  document.getElementById('inv-info').textContent = 'Showing ' + showingStart + ' to ' + Math.min(start + itemsPerPage, filtered.length) + ' of ' + filtered.length + ' products';

  var pagHtml = '';
  pagHtml += '<li class="page-item ' + (currentPage <= 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();currentPage--;renderPage();">\u00AB</a></li>';
  var invShown = {}, invLast = 0;
  for (var p = 1; p <= Math.min(2, totalPages); p++) invShown[p] = true;
  for (var p = Math.max(1, currentPage - 2); p <= Math.min(totalPages, currentPage + 2); p++) invShown[p] = true;
  for (var p = Math.max(1, totalPages - 1); p <= totalPages; p++) invShown[p] = true;
  for (var pg = 1; pg <= totalPages; pg++) {
    if (!invShown[pg]) continue;
    if (invLast > 0 && pg - invLast > 1) pagHtml += '<li class="page-item disabled"><a class="page-link">\u2026</a></li>';
    pagHtml += '<li class="page-item ' + (pg === currentPage ? 'active' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();currentPage=' + pg + ';renderPage();">' + pg + '</a></li>';
    invLast = pg;
  }
  pagHtml += '<li class="page-item ' + (currentPage >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();currentPage++;renderPage();">\u00BB</a></li>';
  document.getElementById('inv-pagination').innerHTML = pagHtml;
}

function openProductModal(mode, productId) {
  editingProductId = mode === 'edit' ? productId : null;
  document.getElementById('productModalTitle').innerHTML = mode === 'edit' ? '<i class="ti ti-pencil me-2"></i>Edit Product' : '<i class="ti ti-package me-2"></i>Add Product';

  // Reset accounting section collapsed state
  var acctSection = document.getElementById('pfAcctSection');
  acctSection.style.display = 'none';
  acctSection.previousElementSibling.classList.remove('open');

  populateProductAccountingDropdowns();

  var cats = window.ERP.state.categories || [];
  var uoms = window.ERP.state.uoms || [];
  var catSel = document.getElementById('pf-category');
  var uomSel = document.getElementById('pf-uom');
  catSel.innerHTML = '<option value="">Select Category</option>';
  uomSel.innerHTML = '<option value="">Select UOM</option>';
  cats.forEach(function(c) { var o = document.createElement('option'); o.value = c.id; o.textContent = c.name; catSel.appendChild(o); });
  uoms.forEach(function(u) {
    var o1 = document.createElement('option'); o1.value = u.name; o1.textContent = u.name; uomSel.appendChild(o1);
  });

  var uomSection = document.getElementById('pf-uom-section');

  if (mode === 'edit' && productId) {
    var p = (window.ERP.state.products || []).find(function(x) { return x.id === productId; });
    if (p) {
      document.getElementById('pf-id').value = p.id;
      document.getElementById('pf-name').value = p.name || '';
      document.getElementById('pf-barcode').value = p.barcode || '';
      document.getElementById('pf-category').value = p.categoryId || '';
      document.getElementById('pf-uom').value = p.uom || '';
      document.getElementById('pf-type').value = p.type || 'Product';
      document.getElementById('pf-cost').value = p.unitCost || 0;
      document.getElementById('pf-price').value = p.unitPrice || 0;
      document.getElementById('pf-reorder').value = p.reorderLevel || 0;
      document.getElementById('pf-opening-row').style.display = 'none';
      uomSection.style.display = '';
      renderUomConversionsSection(p);
      renderPriceTiersSection(p);
    }
  } else {
    document.getElementById('pf-id').value = '';
    document.getElementById('pf-name').value = '';
    document.getElementById('pf-barcode').value = '';
    document.getElementById('pf-category').value = '';
    document.getElementById('pf-uom').value = '';
    document.getElementById('pf-type').value = 'Product';
    document.getElementById('pf-cost').value = '0';
    document.getElementById('pf-price').value = '0';
    document.getElementById('pf-reorder').value = '0';
    document.getElementById('pf-opening').value = '0';
    document.getElementById('pf-opening-row').style.display = '';
    uomSection.style.display = 'none';
    uomSection.innerHTML = '';
    var tierSection = document.getElementById('pf-price-tiers-section');
    tierSection.style.display = 'none';
    tierSection.innerHTML = '';
  }
  new bootstrap.Modal(document.getElementById('productModal')).show();
}

function confirmSaveProduct() {
  if (!document.getElementById('pf-name').value) { alert('Product name is required'); return; }
  document.getElementById('invSaveConfirm').classList.remove('d-none');
}
async function doSaveProduct() {
  document.getElementById('invSaveConfirm').classList.add('d-none');
  var data = {
    name: document.getElementById('pf-name').value,
    barcode: document.getElementById('pf-barcode').value || undefined,
    categoryId: document.getElementById('pf-category').value || undefined,
    uom: document.getElementById('pf-uom').value,
    type: document.getElementById('pf-type').value,
    unitCost: parseFloat(document.getElementById('pf-cost').value) || 0,
    unitPrice: parseFloat(document.getElementById('pf-price').value) || 0,
    reorderLevel: parseInt(document.getElementById('pf-reorder').value) || 0
  };
  try {
    if (editingProductId) {
      data.id = editingProductId;
      await ERP.api.updateProduct(data);
    } else {
      data.initialStock = parseInt(document.getElementById('pf-opening').value) || 0;
      await ERP.api.createProduct(data);
    }
    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
    // Save accounting mappings (non-blocking — failure shown separately)
    await saveProductAccountingMappings();
    await ERP.sync();
    renderPage();
    document.getElementById('invSaveSuccess').classList.remove('d-none');
  } catch(e) { alert(e.message || 'Failed to save product'); }
}

// ── Product Accounting Helpers ────────────────────────────────────────────────
function populateProductAccountingDropdowns() {
  var accounts = (window.ERP.state.chartOfAccounts || []).filter(function(a) { return a.isActive; });
  var mappings = window.ERP.state.accountMappings || {};

  var fields = [
    { elId: 'pf-acct-sales-revenue', key: 'sales_revenue' },
    { elId: 'pf-acct-cogs',          key: 'cost_of_goods_sold' },
    { elId: 'pf-acct-inventory',     key: 'inventory_asset' },
  ];

  fields.forEach(function(f) {
    var sel = document.getElementById(f.elId);
    sel.innerHTML = '<option value="">— Not set —</option>';
    accounts.forEach(function(a) {
      var opt = document.createElement('option');
      opt.value = a.id;
      opt.textContent = a.code + ' — ' + a.name;
      sel.appendChild(opt);
    });
    // Pre-select current mapping
    var current = mappings[f.key];
    if (current && current.accountId) sel.value = current.accountId;
  });
}

function toggleProductAccounting() {
  var section = document.getElementById('pfAcctSection');
  var btn = document.querySelector('.pm-acct-wrap .pm-acct-toggle');
  var open = section.style.display !== 'none';
  section.style.display = open ? 'none' : 'block';
  btn.classList.toggle('open', !open);
}

async function saveProductAccountingMappings() {
  var fields = [
    { elId: 'pf-acct-sales-revenue', key: 'sales_revenue' },
    { elId: 'pf-acct-cogs',          key: 'cost_of_goods_sold' },
    { elId: 'pf-acct-inventory',     key: 'inventory_asset' },
  ];
  var mappings = [];
  fields.forEach(function(f) {
    var val = document.getElementById(f.elId).value;
    if (val) mappings.push({ mappingKey: f.key, accountId: val });
  });
  if (!mappings.length) return;
  try {
    await ERP.api.saveMappings(mappings);
  } catch(e) {
    // Non-blocking — product saved, only mappings failed
    console.warn('Accounting mapping save failed:', e.message);
  }
}

function openAdjustModal(productId) {
  adjustProductId = productId;
  var p = (window.ERP.state.products || []).find(function(x) { return x.id === productId; });
  document.getElementById('adj-product-name').textContent = p ? p.name : '';
  document.getElementById('adj-qty').value = '0';
  document.getElementById('adj-type').value = 'Adjustment_Damage';
  new bootstrap.Modal(document.getElementById('adjustModal')).show();
}

async function submitAdjustment() {
  var qty = parseInt(document.getElementById('adj-qty').value);
  var type = document.getElementById('adj-type').value;
  if (!qty || qty === 0) { alert('Enter a non-zero quantity'); return; }
  try {
    await ERP.api.adjustStock(adjustProductId, qty, type);
    bootstrap.Modal.getInstance(document.getElementById('adjustModal')).hide();
    await ERP.sync();
    renderPage();
  } catch(e) { alert(e.message || 'Adjustment failed'); }
}

// ── Multi-select ──
var selectedProducts = new Set();
var invSelectMode = false;
function toggleInvSelectMode() {
  invSelectMode = !invSelectMode;
  var wrap = document.querySelector('.inv-page-wrap');
  var btn = document.getElementById('inv-sel-toggle-btn');
  if (invSelectMode) {
    wrap.classList.add('inv-select-active');
    btn.classList.add('active');
  } else {
    wrap.classList.remove('inv-select-active');
    btn.classList.remove('active');
    clearProductSelection();
  }
}
function toggleSelectAllProducts(chk) {
  var boxes = document.querySelectorAll('.inv-row-chk');
  boxes.forEach(function(cb) {
    cb.checked = chk.checked;
    if (chk.checked) selectedProducts.add(cb.dataset.id);
    else selectedProducts.delete(cb.dataset.id);
  });
  updateInvBulkBar();
}
function toggleSelectProduct(id, chk) {
  if (chk.checked) selectedProducts.add(id); else selectedProducts.delete(id);
  updateInvBulkBar();
  var all = document.querySelectorAll('.inv-row-chk');
  var checked = Array.from(all).filter(function(c){ return c.checked; });
  var sa = document.getElementById('inv-select-all');
  if (sa) { sa.checked = all.length > 0 && checked.length === all.length; sa.indeterminate = checked.length > 0 && checked.length < all.length; }
}
function updateInvBulkBar() {
  var bar = document.getElementById('inv-bulk-bar');
  document.getElementById('inv-sel-count').textContent = selectedProducts.size;
  if (selectedProducts.size > 0) bar.classList.remove('d-none'); else bar.classList.add('d-none');
}
function clearProductSelection() {
  selectedProducts.clear();
  document.querySelectorAll('.inv-row-chk').forEach(function(cb){ cb.checked = false; });
  var sa = document.getElementById('inv-select-all'); if(sa){ sa.checked = false; sa.indeterminate = false; }
  updateInvBulkBar();
  // exit select mode
  invSelectMode = false;
  var wrap = document.querySelector('.inv-page-wrap');
  if(wrap) wrap.classList.remove('inv-select-active');
  var btn = document.getElementById('inv-sel-toggle-btn');
  if(btn) btn.classList.remove('active');
}

// ── Delete (single + bulk) ──
var _invDeleteId = null, _invBulkDelete = false;
function confirmDeleteProduct(id) {
  _invDeleteId = id; _invBulkDelete = false;
  document.getElementById('invDeleteConfirmTitle').textContent = 'Delete Product?';
  document.getElementById('invDeleteConfirmSub').textContent = 'Are you sure you want to delete this product? This cannot be undone.';
  document.getElementById('invDeleteConfirm').classList.remove('d-none');
}
function confirmDeleteSelectedProducts() {
  var n = selectedProducts.size; if (!n) return;
  _invBulkDelete = true; _invDeleteId = null;
  document.getElementById('invDeleteConfirmTitle').textContent = 'Delete ' + n + ' Product' + (n > 1 ? 's' : '') + '?';
  document.getElementById('invDeleteConfirmSub').textContent = 'All ' + n + ' selected product' + (n > 1 ? 's' : '') + ' will be permanently removed. This cannot be undone.';
  document.getElementById('invDeleteConfirm').classList.remove('d-none');
}
async function doDeleteProduct() {
  document.getElementById('invDeleteConfirm').classList.add('d-none');
  if (_invBulkDelete) { await _doBulkDeleteProducts(); return; }
  if (!_invDeleteId) return;
  try {
    await ERP.api.deleteProduct(_invDeleteId); _invDeleteId = null;
    await ERP.sync(); renderPage();
    document.getElementById('invDeleteSuccessMsg').textContent = 'Product has been removed from the system.';
    document.getElementById('invDeleteSuccess').classList.remove('d-none');
  } catch(e) {
    _invDeleteId = null;
    document.getElementById('invDeleteErrorMsg').textContent = e.message || 'An error occurred.';
    document.getElementById('invDeleteError').classList.remove('d-none');
  }
}
async function _doBulkDeleteProducts() {
  var ids = Array.from(selectedProducts), errors = [], ok = 0;
  for (var i = 0; i < ids.length; i++) {
    try { await ERP.api.deleteProduct(ids[i]); ok++; }
    catch(e) {
      var pr = (window.ERP.state.products || []).find(function(x){ return x.id === ids[i]; });
      errors.push((pr ? pr.name : ids[i]) + ': ' + (e.message || 'Error'));
    }
  }
  clearProductSelection();
  await ERP.sync(); renderPage();
  if (errors.length === 0) {
    document.getElementById('invDeleteSuccessMsg').textContent = ok + ' product' + (ok > 1 ? 's' : '') + ' deleted successfully.';
    document.getElementById('invDeleteSuccess').classList.remove('d-none');
  } else {
    var msg = (ok > 0 ? ok + ' deleted. ' : '') + errors.length + ' could not be deleted: ' + errors.join('; ');
    document.getElementById('invDeleteErrorMsg').textContent = msg;
    document.getElementById('invDeleteError').classList.remove('d-none');
  }
}

// ── UOM Conversion Management ─────────────────────────────────────────────────

function renderUomConversionsSection(product) {
  var conversions = product.uomConversions || [];
  var uoms = window.ERP.state.uoms || [];
  var section = document.getElementById('pf-uom-section');
  if (!section) return;

  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

  var pid = product.id;

  var uomOptHtml = '<option value="">Select UOM...</option>';
  uoms.forEach(function(u) {
    uomOptHtml += '<option value="' + esc(u.id) + '">' + esc(u.name) + '</option>';
  });

  var rowsHtml = '';
  if (conversions.length === 0) {
    rowsHtml = '<tr><td colspan="5" class="text-center text-muted uom-conv-empty">No conversions defined</td></tr>';
  } else {
    conversions.forEach(function(conv) {
      var cid = conv.id;
      var multCell =
        '<span id="uom-mult-display-' + esc(cid) + '" class="uom-mult-display">× ' + conv.multiplier + '</span>' +
        '<button type="button" id="uom-edit-btn-' + esc(cid) + '" class="uom-conv-edit-btn ms-1" onclick="startUomConvEdit(\'' + esc(cid) + '\',' + conv.multiplier + ')" title="Edit multiplier"><i class="ti ti-pencil"></i></button>' +
        '<span id="uom-mult-edit-' + esc(cid) + '" class="uom-mult-edit-wrap" style="display:none;">' +
          '<input type="number" id="uom-mult-inp-' + esc(cid) + '" class="uom-mult-edit-inp" step="0.001" min="0.001">' +
          '<button type="button" class="uom-conv-save-btn" onclick="saveUomConvMult(\'' + esc(pid) + '\',\'' + esc(cid) + '\')"><i class="ti ti-check"></i></button>' +
          '<button type="button" class="uom-conv-cancel-btn" onclick="cancelUomConvEdit(\'' + esc(cid) + '\')"><i class="ti ti-x"></i></button>' +
        '</span>';

      var defPurchCb = '<input type="checkbox" class="uom-conv-cb"' + (conv.isDefaultPurchaseUnit ? ' checked' : '') +
        ' onchange="setUomConvDefault(\'' + esc(pid) + '\',\'' + esc(cid) + '\',\'purchase\',this.checked)" title="Default purchase unit">';
      var defSaleCb = '<input type="checkbox" class="uom-conv-cb"' + (conv.isDefaultSalesUnit ? ' checked' : '') +
        ' onchange="setUomConvDefault(\'' + esc(pid) + '\',\'' + esc(cid) + '\',\'sales\',this.checked)" title="Default sales unit">';

      rowsHtml += '<tr>' +
        '<td class="uom-conv-td">' + esc(conv.uomName || conv.uomId) + '</td>' +
        '<td class="uom-conv-td text-center">' + multCell + '</td>' +
        '<td class="uom-conv-td text-center">' + defPurchCb + '</td>' +
        '<td class="uom-conv-td text-center">' + defSaleCb + '</td>' +
        '<td class="uom-conv-td text-center"><button type="button" class="uom-conv-del-btn" onclick="doDeleteUomConv(\'' + esc(pid) + '\',\'' + esc(cid) + '\')"><i class="ti ti-trash"></i></button></td>' +
        '</tr>';
    });
  }

  section.innerHTML =
    '<div class="pm-acct-wrap mt-1">' +
      '<button type="button" class="pm-acct-toggle" onclick="toggleUomSection()">' +
        '<span><i class="ti ti-arrows-exchange me-2"></i>UOM Conversions</span>' +
        '<i class="ti ti-chevron-down" id="uomConvChevron"></i>' +
      '</button>' +
      '<div id="uomConvBody" class="pm-acct-body" style="display:none;">' +
        '<table class="table table-sm uom-conv-table mb-2">' +
          '<thead><tr>' +
            '<th class="uom-conv-th">Unit</th>' +
            '<th class="uom-conv-th text-center">Multiplier</th>' +
            '<th class="uom-conv-th text-center">Def. Purchase</th>' +
            '<th class="uom-conv-th text-center">Def. Sales</th>' +
            '<th class="uom-conv-th"></th>' +
          '</tr></thead>' +
          '<tbody id="uomConvRows">' + rowsHtml + '</tbody>' +
        '</table>' +
        '<div class="uom-add-card">' +
          '<div class="uom-add-grid">' +
            '<div>' +
              '<div class="pm-label mb-1">Unit</div>' +
              '<select class="form-select pm-input" id="uom-add-uomid">' + uomOptHtml + '</select>' +
            '</div>' +
            '<div>' +
              '<div class="pm-label mb-1">Multiplier</div>' +
              '<div class="input-group">' +
                '<span class="input-group-text pm-prefix">×</span>' +
                '<input type="number" step="0.001" min="0.001" class="form-control pm-input" id="uom-add-mult" placeholder="e.g. 12">' +
              '</div>' +
            '</div>' +
            '<div class="uom-add-btn-wrap">' +
              '<button type="button" class="pm-btn-save uom-conv-add-btn" onclick="doAddUomConv(\'' + esc(pid) + '\')"><i class="ti ti-plus me-1"></i>Add</button>' +
            '</div>' +
          '</div>' +
          '<div class="erp-info-hint mt-2"><i class="ti ti-info-circle me-1"></i>Multiplier = base units per 1 of this unit. E.g. 1 Box = 12 Pieces → multiplier 12.</div>' +
        '</div>' +
      '</div>' +
    '</div>';
}

function renderPriceTiersSection(product) {
  var section = document.getElementById('pf-price-tiers-section');
  section.style.display = '';
  var tiers = product.priceTiers || [];

  var html = '<div class="pm-tier-wrap">' +
    '<div class="pm-tier-header">' +
      '<span><i class="ti ti-tag me-1"></i>Customer Category Pricing</span>' +
      '<button type="button" class="pm-tier-add-btn" onclick="openAddPriceTierRow()"><i class="ti ti-plus me-1"></i>Add Tier</button>' +
    '</div>';

  if (tiers.length === 0) {
    html += '<div class="pm-tier-empty">No price tiers — all customers pay the base unit price.</div>';
  } else {
    html += '<table class="pm-tier-table"><thead><tr><th>Customer Category</th><th class="text-end">Price</th><th></th></tr></thead><tbody>';
    tiers.forEach(function(t) {
      html += '<tr>' +
        '<td>' + escHtml(t.category) + '</td>' +
        '<td class="text-end">' + ERP.formatCurrency(t.price) + '</td>' +
        '<td class="text-center">' +
          '<button class="inv-action-btn inv-action-danger" onclick="confirmDeletePriceTier(\'' + escHtml(t.id) + '\',\'' + escHtml(t.category) + '\')" title="Remove tier"><i class="ti ti-trash"></i></button>' +
        '</td></tr>';
    });
    html += '</tbody></table>';
  }

  html += '<div id="pm-tier-add-row" style="display:none;">' +
    '<div class="pm-tier-add-form row g-2 mt-1">' +
      '<div class="col-5"><input type="text" class="form-control pm-input" id="pm-tier-cat" placeholder="e.g. Wholesale, VIP, Retail"></div>' +
      '<div class="col-4"><div class="input-group"><span class="input-group-text pm-prefix">Rs.</span><input type="number" step="0.01" min="0" class="form-control pm-input" id="pm-tier-price" placeholder="0.00"></div></div>' +
      '<div class="col-3 d-flex gap-1">' +
        '<button type="button" class="pm-btn-save" onclick="savePriceTierRow()"><i class="ti ti-check me-1"></i>Add</button>' +
        '<button type="button" class="pm-btn-cancel" onclick="document.getElementById(\'pm-tier-add-row\').style.display=\'none\'">Cancel</button>' +
      '</div>' +
    '</div>' +
  '</div>' +
  '</div>';

  section.innerHTML = html;
}

function openAddPriceTierRow() {
  document.getElementById('pm-tier-add-row').style.display = '';
  document.getElementById('pm-tier-cat').value = '';
  document.getElementById('pm-tier-price').value = '';
  document.getElementById('pm-tier-cat').focus();
}

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
  } catch(e) {
    alert('Error: ' + e.message);
  }
}

function confirmDeletePriceTier(tierId, categoryName) {
  if (!confirm('Remove price tier for "' + categoryName + '"?')) return;
  deletePriceTierById(tierId);
}

async function deletePriceTierById(tierId) {
  var productId = document.getElementById('pf-id').value;
  try {
    await ERP.api.deletePriceTier(productId, tierId);
    await ERP.sync();
    var product = (window.ERP.state.products || []).find(function(p) { return p.id === productId; });
    if (product) { renderPriceTiersSection(product); }
  } catch(e) {
    alert('Error: ' + e.message);
  }
}

function toggleUomSection() {
  var body = document.getElementById('uomConvBody');
  var chevron = document.getElementById('uomConvChevron');
  if (!body) return;
  var open = body.style.display !== 'none';
  body.style.display = open ? 'none' : 'block';
  if (chevron) {
    chevron.classList.toggle('ti-chevron-down', open);
    chevron.classList.toggle('ti-chevron-up', !open);
  }
}

async function doAddUomConv(productId) {
  var uomId = document.getElementById('uom-add-uomid').value;
  var mult  = parseFloat(document.getElementById('uom-add-mult').value);
  if (!uomId) { alert('Select a UOM'); return; }
  if (!mult || mult <= 0) { alert('Enter a positive multiplier'); return; }
  try {
    await ERP.api.addUomConversion(productId, { uomId: uomId, multiplier: mult });
    await ERP.api.syncMaster().then(function(d) { window.ERP.state.products = d.products || window.ERP.state.products; });
    var p = (window.ERP.state.products || []).find(function(x) { return x.id === productId; });
    if (p) renderUomConversionsSection(p);
    // Keep section open
    var body = document.getElementById('uomConvBody');
    if (body) body.style.display = 'block';
  } catch(e) { alert(e.message || 'Failed to add conversion'); }
}

async function doDeleteUomConv(productId, cid) {
  try {
    await ERP.api.deleteUomConversion(productId, cid);
    await ERP.api.syncMaster().then(function(d) { window.ERP.state.products = d.products || window.ERP.state.products; });
    var p = (window.ERP.state.products || []).find(function(x) { return x.id === productId; });
    if (p) renderUomConversionsSection(p);
    var body = document.getElementById('uomConvBody');
    if (body) body.style.display = 'block';
  } catch(e) { alert(e.message || 'Failed to delete conversion'); }
}

async function setUomConvDefault(productId, cid, type, value) {
  var payload = type === 'purchase'
    ? { isDefaultPurchaseUnit: value }
    : { isDefaultSalesUnit: value };
  try {
    await ERP.api.updateUomConversion(productId, cid, payload);
    await ERP.api.syncMaster().then(function(d) { window.ERP.state.products = d.products || window.ERP.state.products; });
    var p = (window.ERP.state.products || []).find(function(x) { return x.id === productId; });
    if (p) renderUomConversionsSection(p);
    var body = document.getElementById('uomConvBody');
    if (body) body.style.display = 'block';
  } catch(e) { alert(e.message || 'Failed to update default'); }
}

function startUomConvEdit(cid, currentMult) {
  document.getElementById('uom-mult-display-' + cid).style.display = 'none';
  document.getElementById('uom-edit-btn-' + cid).style.display = 'none';
  var editWrap = document.getElementById('uom-mult-edit-' + cid);
  editWrap.style.display = 'inline-flex';
  var inp = document.getElementById('uom-mult-inp-' + cid);
  inp.value = currentMult;
  inp.focus();
  inp.select();
}

function cancelUomConvEdit(cid) {
  document.getElementById('uom-mult-display-' + cid).style.display = '';
  document.getElementById('uom-edit-btn-' + cid).style.display = '';
  document.getElementById('uom-mult-edit-' + cid).style.display = 'none';
}

async function saveUomConvMult(productId, cid) {
  var val = parseFloat(document.getElementById('uom-mult-inp-' + cid).value);
  if (!val || val <= 0) { alert('Enter a positive multiplier'); return; }
  try {
    await ERP.api.updateUomConversion(productId, cid, { multiplier: val });
    await ERP.api.syncMaster().then(function(d) { window.ERP.state.products = d.products || window.ERP.state.products; });
    var p = (window.ERP.state.products || []).find(function(x) { return x.id === productId; });
    if (p) renderUomConversionsSection(p);
    var body = document.getElementById('uomConvBody');
    if (body) body.style.display = 'block';
  } catch(e) { alert(e.message || 'Failed to update multiplier'); }
}
