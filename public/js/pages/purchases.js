var poSortKey = 'createdAt', poSortDir = 'desc', poCurrentPage = 1, poPerPage = 10;

function showConfirm(title, message, okLabel, iconClass) {
  return new Promise(function(resolve) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmOkLabel').textContent = okLabel || 'Yes, Save';
    var iconEl = document.getElementById('confirmOkIcon');
    iconEl.className = 'ti ' + (iconClass || 'ti-device-floppy') + ' me-1';
    var overlay = document.getElementById('poConfirmOverlay');
    overlay.classList.remove('d-none');

    var okBtn = document.getElementById('confirmOkBtn');
    var cancelBtn = document.getElementById('confirmCancelBtn');
    var resolved = false;

    function cleanup() { okBtn.removeEventListener('click', onOk); cancelBtn.removeEventListener('click', onCancel); }
    function onOk()     { if (resolved) return; resolved = true; cleanup(); overlay.classList.add('d-none'); resolve(true); }
    function onCancel() { if (resolved) return; resolved = true; cleanup(); overlay.classList.add('d-none'); resolve(false); }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
  });
}
var poExpandedId = null;
var poItems = [];
var receivePOId = null;

window.ERP.onReady = function() {
  populateVendorFilter();
  renderPage();
};

function getVendors() {
  return (window.ERP.state.parties || []).filter(function(p) { return p.type === 'Vendor'; });
}

function populateVendorFilter() {
  var sel = document.getElementById('po-vendor');
  sel.innerHTML = '<option value="all">All Vendors</option>';
  getVendors().forEach(function(v) {
    sel.innerHTML += '<option value="' + v.id + '">' + v.name + '</option>';
  });
}

function getFilteredPOs() {
  var search = (document.getElementById('po-search').value || '').toLowerCase();
  var statusF = document.getElementById('po-status').value;
  var vendorF = document.getElementById('po-vendor').value;
  var dateFrom = document.getElementById('po-date-from').value;
  var dateTo   = document.getElementById('po-date-to').value;
  var vendors = getVendors();
  var fromTs = dateFrom ? new Date(dateFrom).setHours(0,0,0,0) : null;
  var toTs   = dateTo   ? new Date(dateTo).setHours(23,59,59,999) : null;

  var list = (window.ERP.state.purchaseOrders || []).filter(function(po) {
    var vendor = vendors.find(function(v) { return v.id === po.vendorId; });
    var ms = (po.id || '').toLowerCase().indexOf(search) !== -1 || (vendor && vendor.name.toLowerCase().indexOf(search) !== -1);
    var mst = statusF === 'all' || po.status === statusF;
    var mv = vendorF === 'all' || po.vendorId === vendorF;
    var md = (!fromTs || po.createdAt >= fromTs) && (!toTs || po.createdAt <= toTs);
    return ms && mst && mv && md;
  });

  list.sort(function(a, b) {
    var va = a[poSortKey] || '', vb = b[poSortKey] || '';
    if (va < vb) return poSortDir === 'asc' ? -1 : 1;
    if (va > vb) return poSortDir === 'asc' ? 1 : -1;
    return 0;
  });
  return list;
}

function poSortToggle(key) {
  if (poSortKey === key) poSortDir = poSortDir === 'asc' ? 'desc' : 'asc';
  else { poSortKey = key; poSortDir = 'asc'; }
  renderPage();
}

function renderPage() {
  var filtered = getFilteredPOs();
  var vendors = getVendors();
  var products = window.ERP.state.products || [];
  var totalPages = Math.max(1, Math.ceil(filtered.length / poPerPage));
  if (poCurrentPage > totalPages) poCurrentPage = totalPages;
  var start = (poCurrentPage - 1) * poPerPage;
  var page = filtered.slice(start, start + poPerPage);

  var tbody = document.getElementById('po-tbody');
  var html = '';
  if (page.length === 0) {
    html = '<tr><td colspan="8" class="text-center text-muted py-4">No purchase orders found</td></tr>';
  } else {
    page.forEach(function(po) {
      var vendor = vendors.find(function(v) { return v.id === po.vendorId; });
      var vendorName = vendor ? vendor.name : 'Unknown Vendor';
      var isExpanded = poExpandedId === po.id;

      var statusBadge = 'pg-badge ';
      if (po.status === 'Draft') statusBadge += 'pg-badge-draft';
      else if (po.status === 'Partially Received') statusBadge += 'pg-badge-partial';
      else if (po.status === 'Received') statusBadge += 'pg-badge-received';
      else if (po.status === 'Returned') statusBadge += 'pg-badge-returned';

      html += '<tr class="cursor-pointer" onclick="togglePOExpand(\'' + po.id + '\')">' +
        '<td><i class="ti erp-icon-sm ' + (isExpanded ? 'ti-chevron-down text-erp-primary' : 'ti-chevron-right erp-text-placeholder') + '"></i></td>' +
        '<td><span class="pg-id">' + (po.id || '') + '</span></td>' +
        '<td>' + new Date(po.createdAt).toLocaleDateString() + '</td>' +
        '<td><i class="ti ti-truck me-1" class="erp-dropdown-placeholder"></i>' + vendorName + '</td>' +
        '<td class="text-center">' + (po.items ? po.items.length : 0) + '</td>' +
        '<td class="text-end">' + ERP.formatCurrency(po.totalAmount || 0) + '</td>' +
        '<td><span class="' + statusBadge + '">' + (po.status || '') + '</span></td>' +
        '<td class="text-center">' +
        ((po.status === 'Draft' || po.status === 'Partially Received') ? '<button class="pg-action-btn pg-action-success" onclick="event.stopPropagation();openReceiveModal(\'' + po.id + '\')" title="Receive"><i class="ti ti-package-import"></i></button>' : '') +
        '</td></tr>';

      if (isExpanded) {
        html += '<tr class="expand-row"><td colspan="8"><div class="p-3"><div class="row"><div class="col-md-7">' +
          '<h4 class="mb-3" class="erp-table-section-header">Order Items</h4>' +
          '<table class="table table-sm mb-0"><thead><tr><th>Product</th><th class="text-center">UOM</th><th class="text-center">Ordered</th><th class="text-center">Received</th><th class="text-end">Unit Cost</th><th class="text-end">Line Total</th></tr></thead><tbody>';
        (po.items || []).forEach(function(item) {
          var prod = products.find(function(p) { return p.id === item.productId; });
          var received = item.receivedQuantity || 0;
          var uomLabel = 'Base';
          if (item.uomId && prod) {
            var conv = (prod.uomConversions || []).find(function(c) { return c.uomId === item.uomId; });
            if (conv) uomLabel = conv.uomName || item.uomId;
          }
          html += '<tr><td>' + (prod ? prod.name : 'Deleted Product') + '</td>' +
            '<td class="text-center"><span class="po-uom-badge">' + uomLabel + '</span></td>' +
            '<td class="text-center">' + (item.quantity || 0) + '</td>' +
            '<td class="text-center"><span class="' + (received < item.quantity ? 'text-primary' : 'text-success') + '">' + received + ' / ' + item.quantity + '</span></td>' +
            '<td class="text-end">' + ERP.formatCurrency(item.unitCost || 0) + '</td>' +
            '<td class="text-end" class="fw-semibold">' + ERP.formatCurrency(item.totalLineCost || 0) + '</td></tr>';
        });
        html += '</tbody></table></div><div class="col-md-5">' +
          '<h4 class="mb-3" class="erp-table-section-header">Summary</h4>' +
          '<div class="erp-summary-box">' +
          '<div class="d-flex justify-content-between erp-text-sm"><span class="text-muted">Total Value</span><span class="fw-semibold">' + ERP.formatCurrency(po.totalAmount || 0) + '</span></div>' +
          '</div>' +
          '<div class="d-flex gap-2 mt-3">';
        if (po.status === 'Draft' || po.status === 'Partially Received') {
          html += '<button class="pm-btn-save flex-fill btn-erp-receive" onclick="event.stopPropagation();openReceiveModal(\'' + po.id + '\')"><i class="ti ti-package-import me-1"></i>' + (po.status === 'Partially Received' ? 'Receive More' : 'Receive Goods') + '</button>';
        } else {
          html += '<div class="po-received-status"><i class="ti ti-check me-1"></i>Goods Received</div>';
        }
        html += '</div></div></div></div></td></tr>';
      }
    });
  }
  tbody.innerHTML = html;

  document.getElementById('po-info').textContent = 'Showing ' + (filtered.length ? start + 1 : 0) + ' to ' + Math.min(start + poPerPage, filtered.length) + ' of ' + filtered.length + ' orders';

  var pagHtml = '';
  pagHtml += '<li class="page-item ' + (poCurrentPage <= 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();poCurrentPage--;renderPage();">\u00AB</a></li>';
  var poShown = {}, poLast = 0;
  for (var p = 1; p <= Math.min(2, totalPages); p++) poShown[p] = true;
  for (var p = Math.max(1, poCurrentPage - 2); p <= Math.min(totalPages, poCurrentPage + 2); p++) poShown[p] = true;
  for (var p = Math.max(1, totalPages - 1); p <= totalPages; p++) poShown[p] = true;
  for (var pg = 1; pg <= totalPages; pg++) {
    if (!poShown[pg]) continue;
    if (poLast > 0 && pg - poLast > 1) pagHtml += '<li class="page-item disabled"><a class="page-link">\u2026</a></li>';
    pagHtml += '<li class="page-item ' + (pg === poCurrentPage ? 'active' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();poCurrentPage=' + pg + ';renderPage();">' + pg + '</a></li>';
    poLast = pg;
  }
  pagHtml += '<li class="page-item ' + (poCurrentPage >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();poCurrentPage++;renderPage();">\u00BB</a></li>';
  document.getElementById('po-pagination').innerHTML = pagHtml;
}

function togglePOExpand(id) {
  poExpandedId = poExpandedId === id ? null : id;
  renderPage();
}

// ── Searchable Dropdown helpers ───────────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function sddClose(id) {
  var wrap = document.getElementById(id);
  if (wrap) wrap.classList.remove('open');
}
function sddCloseAll() {
  document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
}
function sddToggle(id) {
  var wrap = document.getElementById(id);
  if (!wrap) return;
  var wasOpen = wrap.classList.contains('open');
  sddCloseAll();
  if (!wasOpen) {
    wrap.classList.add('open');
    var inp = wrap.querySelector('.sdd-search-inp');
    if (inp) { inp.value = ''; sddFilterOpts(id, ''); setTimeout(function(){ inp.focus(); }, 50); }
  }
}
function sddFilterOpts(id, query) {
  var wrap = document.getElementById(id);
  if (!wrap) return;
  var q = query.toLowerCase();
  wrap.querySelectorAll('.sdd-opt').forEach(function(opt) {
    opt.style.display = opt.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
  });
  var noRes = wrap.querySelector('.sdd-no-res');
  if (noRes) {
    var visible = Array.from(wrap.querySelectorAll('.sdd-opt')).some(function(o){ return o.style.display !== 'none'; });
    noRes.style.display = visible ? 'none' : '';
  }
}
function sddSelectVendor(vendorId, vendorName) {
  document.getElementById('npo-vendor').value = vendorId;
  var disp = document.getElementById('npo-vendor-disp');
  disp.textContent = vendorName;
  disp.style.color = '#1A1D2E';
  var wrap = document.getElementById('npo-vendor-sdd');
  if (wrap) {
    wrap.querySelectorAll('.sdd-opt').forEach(function(o){ o.classList.toggle('sdd-selected', o.dataset.val === vendorId); });
  }
  sddClose('npo-vendor-sdd');
}
function sddSelectProd(idx, productId, productName) {
  var wrap = document.getElementById('npo-prod-sdd-' + idx);
  if (wrap) {
    var disp = wrap.querySelector('.sdd-disp');
    if (disp) { disp.textContent = productName; disp.style.color = '#1A1D2E'; }
    wrap.querySelectorAll('.sdd-opt').forEach(function(o){ o.classList.toggle('sdd-selected', o.dataset.val === productId); });
    sddClose('npo-prod-sdd-' + idx);
  }
  updatePOItem(idx, 'productId', productId);
}
function renderProductSDD(idx, selectedId) {
  var products = window.ERP.state.products || [];
  var sel = products.find(function(p){ return p.id === selectedId; });
  var dispTxt = sel ? escHtml(sel.name) : 'Select Product...';
  var dispColor = sel ? '#1A1D2E' : '#B0B7C9';
  var opts = products.map(function(p) {
    return '<div class="sdd-opt' + (p.id === selectedId ? ' sdd-selected' : '') + '" data-val="' + escHtml(p.id) + '" onclick="sddSelectProd(' + idx + ',\'' + escHtml(p.id) + '\',\'' + escHtml(p.name).replace(/'/g,'&#39;') + '\')">' + escHtml(p.name) + '</div>';
  }).join('');
  return '<div class="sdd-wrap" id="npo-prod-sdd-' + idx + '">' +
    '<div class="sdd-trigger pm-input po-input-sm" onclick="sddToggle(\'npo-prod-sdd-' + idx + '\')">' +
      '<span class="sdd-disp" style="color:' + dispColor + '">' + dispTxt + '</span>' +
      '<i class="ti ti-chevron-down sdd-caret"></i>' +
    '</div>' +
    '<div class="sdd-panel">' +
      '<div class="sdd-search-row"><i class="ti ti-search"></i>' +
        '<input type="text" class="sdd-search-inp" placeholder="Search..." oninput="sddFilterOpts(\'npo-prod-sdd-' + idx + '\',this.value)" onclick="event.stopPropagation()">' +
      '</div>' +
      '<div class="sdd-opts-wrap">' + (opts || '<div class="sdd-no-res">No products found</div>') +
        '<div class="sdd-no-res" class="d-none">No results</div>' +
      '</div>' +
    '</div>' +
  '</div>';
}
// Close all SDDs when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.sdd-wrap')) sddCloseAll();
});

function openNewPOModal() {
  poItems = [];
  // Reset vendor SDD
  document.getElementById('npo-vendor').value = '';
  var disp = document.getElementById('npo-vendor-disp');
  disp.textContent = 'Select Vendor...';
  disp.style.color = '#B0B7C9';
  var vendorOpts = document.getElementById('npo-vendor-opts');
  vendorOpts.innerHTML = '';
  getVendors().forEach(function(v) {
    vendorOpts.innerHTML += '<div class="sdd-opt" data-val="' + escHtml(v.id) + '" onclick="sddSelectVendor(\'' + escHtml(v.id) + '\',\'' + escHtml(v.name).replace(/'/g,'&#39;') + '\')">' + escHtml(v.name) + '</div>';
  });
  if (!getVendors().length) vendorOpts.innerHTML = '<div class="sdd-no-res">No vendors found</div>';
  var wrap = document.getElementById('npo-vendor-sdd');
  if (wrap) wrap.querySelectorAll('.sdd-opt').forEach(function(o){ o.classList.remove('sdd-selected'); });
  document.getElementById('npo-items').innerHTML = '';
  document.getElementById('npo-total').textContent = '0.00';
  // Set today's date as default
  var today = new Date();
  var yyyy = today.getFullYear();
  var mm = String(today.getMonth() + 1).padStart(2, '0');
  var dd = String(today.getDate()).padStart(2, '0');
  document.getElementById('npo-date').value = yyyy + '-' + mm + '-' + dd;
  addPOItemRow();
  new bootstrap.Modal(document.getElementById('newPOModal')).show();
}

function addPOItemRow() {
  poItems.push({ productId: '', uomId: null, quantity: 1, unitCost: 0 });
  renderPOItems();
}

function renderUomCell(idx, item) {
  var product = (window.ERP.state.products || []).find(function(p) { return p.id === item.productId; });
  var conversions = (product && product.uomConversions) ? product.uomConversions : [];
  var baseLabel = (product && product.uom) ? escHtml(product.uom) : 'Base';
  if (!conversions.length) return '<td class="po-td-input text-center"><span class="text-muted erp-text-sm">' + baseLabel + '</span></td>';
  var html = '<td class="po-td-input"><select class="form-select pm-input po-input-sm" onchange="updatePOItem(' + idx + ',\'uomId\',this.value)">';
  html += '<option value="">' + baseLabel + '</option>';
  conversions.forEach(function(c) {
    var selected = item.uomId === c.uomId ? ' selected' : '';
    html += '<option value="' + escHtml(c.uomId) + '"' + selected + '>' + escHtml(c.uomName || c.uomId) + '</option>';
  });
  html += '</select></td>';
  return html;
}

function renderPOItems() {
  var tbody = document.getElementById('npo-items');
  var html = '';
  poItems.forEach(function(item, idx) {
    html += '<tr>' +
      '<td class="po-td-input align-middle" style="color:#9CA3AF;font-size:0.78rem;width:32px;">' + (idx + 1) + '</td>' +
      '<td class="po-td-input">' + renderProductSDD(idx, item.productId) + '</td>' +
      renderUomCell(idx, item) +
      '<td class="po-td-input"><input type="number" class="form-control pm-input text-center po-input-sm" value="' + item.quantity + '" onchange="updatePOItem(' + idx + ',\'quantity\',this.value)"></td>' +
      '<td class="po-td-input"><input type="number" step="0.01" class="form-control pm-input po-input-sm" value="' + item.unitCost + '" onchange="updatePOItem(' + idx + ',\'unitCost\',this.value)"></td>' +
      '<td class="po-td-input text-end" style="font-weight:600;color:#1A1D2E;white-space:nowrap;">' + ERP.formatCurrency(item.quantity * item.unitCost) + '</td>' +
      '<td class="po-td-input text-center"><button type="button" class="pg-action-btn text-danger" onclick="removePOItem(' + idx + ')"><i class="ti ti-trash"></i></button></td></tr>';
  });
  tbody.innerHTML = html;
  updatePOTotal();
}

function updatePOItem(idx, field, value) {
  if (field === 'productId') {
    poItems[idx].productId = value;
    poItems[idx].uomId = null;
    var p = (window.ERP.state.products || []).find(function(x) { return x.id === value; });
    if (p) {
      poItems[idx].unitCost = p.unitCost || 0;
      // Auto-select default purchase UOM if defined
      var defConv = (p.uomConversions || []).find(function(c) { return c.isDefaultPurchaseUnit; });
      if (defConv) {
        poItems[idx].uomId = defConv.uomId;
        poItems[idx].unitCost = (p.unitCost || 0) * defConv.multiplier;
      }
    }
    renderPOItems();
  } else if (field === 'uomId') {
    poItems[idx].uomId = value || null;
    // Recalculate unit cost based on selected UOM
    var prod = (window.ERP.state.products || []).find(function(x) { return x.id === poItems[idx].productId; });
    if (prod) {
      var baseUnitCost = prod.unitCost || 0;
      if (value) {
        var conv = (prod.uomConversions || []).find(function(c) { return c.uomId === value; });
        poItems[idx].unitCost = conv ? baseUnitCost * conv.multiplier : baseUnitCost;
      } else {
        poItems[idx].unitCost = baseUnitCost;
      }
    }
    renderPOItems();
  } else if (field === 'quantity') {
    poItems[idx].quantity = parseInt(value) || 1;
    updatePOTotal();
  } else if (field === 'unitCost') {
    poItems[idx].unitCost = parseFloat(value) || 0;
    updatePOTotal();
  }
}

function removePOItem(idx) {
  poItems.splice(idx, 1);
  renderPOItems();
}

function updatePOTotal() {
  var total = poItems.reduce(function(acc, i) { return acc + (i.quantity * i.unitCost); }, 0);
  document.getElementById('npo-total').textContent = ERP.formatCurrency(total);
}

async function createPO() {
  var vendorId = document.getElementById('npo-vendor').value;
  if (!vendorId) { alert('Please select a vendor'); return; }
  var validItems = poItems.filter(function(i) { return i.productId; }).map(function(i) {
    return { productId: i.productId, uomId: i.uomId || null, quantity: i.quantity, unitCost: i.unitCost };
  });
  if (validItems.length === 0) { alert('Add at least one product'); return; }
  if (!await showConfirm('Save Purchase Order', 'Are you sure you want to save this Purchase Order?')) return;

  try {
    var orderDate = document.getElementById('npo-date').value;
    await ERP.api.createPurchaseOrder(vendorId, validItems, orderDate);
    bootstrap.Modal.getInstance(document.getElementById('newPOModal')).hide();
    await ERP.sync();
    renderPage();
  } catch(e) { alert(e.message || 'Failed to create PO'); }
}

function openReceiveModal(poId) {
  receivePOId = poId;
  var po = (window.ERP.state.purchaseOrders || []).find(function(x) { return x.id === poId; });
  if (!po) return;
  var products = window.ERP.state.products || [];

  document.getElementById('recv-po-id').textContent = 'PO: ' + po.id;
  document.getElementById('recv-notes').value = '';

  var tbody = document.getElementById('recv-items');
  var html = '';
  (po.items || []).forEach(function(item) {
    var prod = products.find(function(p) { return p.id === item.productId; });
    var received = item.receivedQuantity || 0;
    var remaining = item.quantity - received;
    html += '<tr>' +
      '<td class="po-td-item">' + (prod ? prod.name : 'Item') + '</td>' +
      '<td class="po-td-center">' + item.quantity + '</td>' +
      '<td class="po-td-center">' + received + '</td>' +
      '<td class="po-td-remain ' + (remaining > 0 ? 'text-primary' : 'text-success') + '">' + remaining + '</td>' +
      '<td class="po-td-input"><input type="number" class="form-control pm-input text-center recv-qty po-input-sm" data-item-id="' + item.id + '" data-product-id="' + item.productId + '" data-unit-cost="' + item.unitCost + '" max="' + remaining + '" value="' + Math.max(0, remaining) + '"></td></tr>';
  });
  tbody.innerHTML = html;
  new bootstrap.Modal(document.getElementById('receiveModal')).show();
}

// ── Barcode Scanner Support (New PO modal) ───────────────────────────────────
function npoScanProduct(e) {
  if (e.key !== 'Enter') return;
  e.preventDefault();
  var val = (document.getElementById('npo-barcode').value || '').trim();
  if (!val) return;
  var lower = val.toLowerCase();
  var products = window.ERP.state.products || [];
  // Exact barcode or SKU first, then partial name match
  var match = products.find(function(p) {
    return (p.barcode || '').toLowerCase() === lower || p.sku.toLowerCase() === lower;
  });
  if (!match) {
    var visible = products.filter(function(p) {
      return p.name.toLowerCase().indexOf(lower) !== -1 ||
             p.sku.toLowerCase().indexOf(lower) !== -1 ||
             (p.barcode || '').toLowerCase().indexOf(lower) !== -1;
    });
    if (visible.length === 1) match = visible[0];
  }
  if (match) {
    // If already in list, increment qty; otherwise add new row
    var existing = poItems.find(function(i) { return i.productId === match.id; });
    if (existing) {
      existing.quantity++;
    } else {
      poItems.push({ productId: match.id, quantity: 1, unitCost: match.unitCost || 0 });
    }
    renderPOItems();
    document.getElementById('npo-barcode').value = '';
    npoScanFeedback('\u2713 Added: ' + match.name, true);
  } else {
    npoScanFeedback('\u2717 Not found: ' + val, false);
  }
}
function npoScanFeedback(msg, ok) {
  var el = document.getElementById('npo-scan-feedback');
  if (!el) return;
  el.textContent = msg;
  el.style.color = ok ? '#059669' : '#EF4444';
  el.style.opacity = '1';
  clearTimeout(el._t);
  el._t = setTimeout(function() { el.style.opacity = '0'; }, 2500);
}
// Also focus the barcode input when the modal opens
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('newPOModal');
  if (modal) {
    modal.addEventListener('shown.bs.modal', function() {
      var inp = document.getElementById('npo-barcode');
      if (inp) inp.focus();
    });
  }
});

async function submitReceive() {
  if (!receivePOId) return;
  if (!await showConfirm('Receive Goods', 'Are you sure you want to receive these goods? This will update your inventory stock.', 'Yes, Receive', 'ti-package-import')) return;
  var inputs = document.querySelectorAll('.recv-qty');
  var items = [];
  inputs.forEach(function(inp) {
    var qty = parseInt(inp.value) || 0;
    if (qty > 0) {
      items.push({
        purchaseItemId: inp.dataset.itemId,
        productId: inp.dataset.productId,
        quantity: qty,
        unitCost: parseFloat(inp.dataset.unitCost) || 0
      });
    }
  });
  if (items.length === 0) { alert('Enter at least one quantity to receive'); return; }
  var notes = document.getElementById('recv-notes').value;

  var btn = document.getElementById('recv-submit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Receiving...';

  try {
    await ERP.api.partialReceivePurchaseOrder(receivePOId, items, notes || undefined);
    bootstrap.Modal.getInstance(document.getElementById('receiveModal')).hide();
    await ERP.sync();
    renderPage();
  } catch(e) {
    alert(e.message || 'Failed to receive goods');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-check me-1"></i>Receive Goods';
  }
}
