var poPOs = [], poMeta = { currentPage: 1, lastPage: 1, total: 0 };
var poSearchTimer = null, poLoading = false;

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

function poGetFilters() {
    return {
        page:   poMeta.currentPage,
        search: (document.getElementById('po-search').value || '').trim(),
        status: document.getElementById('po-status').value || 'all',
        vendor: document.getElementById('po-vendor').value || 'all',
        from:   document.getElementById('po-date-from').value || '',
        to:     document.getElementById('po-date-to').value || '',
    };
}

function loadPOs(page) {
    if (poLoading) return;
    poLoading = true;
    poMeta.currentPage = page || 1;

    var tbody = document.getElementById('po-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

    ERP.api.getPurchases(poGetFilters())
        .then(function(res) {
            poPOs = res.data || [];
            var m = res.meta || {};
            poMeta = {
                currentPage: m.current_page || 1,
                lastPage:    m.last_page    || 1,
                total:       m.total        || 0,
            };
            renderPage();
        })
        .catch(function(e) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Error loading purchases: ' + e.message + '</td></tr>';
        })
        .finally(function() { poLoading = false; });
}

window.ERP.onReady = function() {
    populateVendorFilter();
    loadPOs(1);
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

function renderPage() {
  var products = window.ERP.state.products || [];
  var tbody = document.getElementById('po-tbody');
  var html = '';
  if (poPOs.length === 0) {
    html = '<tr><td colspan="8" class="text-center text-muted py-4">No purchase orders found</td></tr>';
  } else {
    poPOs.forEach(function(po) {
      var vendorName = po.vendorName || 'Unknown Vendor';
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
          '<table class="table table-sm mb-0"><thead><tr><th class="po-th-col" style="width:36px;">#</th><th class="po-th-col">Product</th><th class="po-th-col text-center">UOM</th><th class="po-th-col text-center">Ordered</th><th class="po-th-col text-center">Received</th><th class="po-th-col text-end">Unit Cost</th><th class="po-th-col text-end">Line Total</th></tr></thead><tbody>';
        (po.items || []).forEach(function(item, idx) {
          var prod = products.find(function(p) { return p.id === item.productId; });
          var received = item.receivedQuantity || 0;
          var uomLabel = 'Base';
          if (item.uomId && prod) {
            var conv = (prod.uomConversions || []).find(function(c) { return c.uomId === item.uomId; });
            if (conv) uomLabel = conv.uomName || item.uomId;
          }
          html += '<tr>' +
            '<td class="text-center" style="color:#9CA3AF;font-size:0.78rem;">' + (idx + 1) + '</td>' +
            '<td>' + (prod ? prod.name : 'Deleted Product') + '</td>' +
            '<td class="text-center"><span class="po-uom-badge">' + uomLabel + '</span></td>' +
            '<td class="text-center">' + (item.quantity || 0) + '</td>' +
            '<td class="text-center"><span class="' + (received < item.quantity ? 'text-primary' : 'text-success') + '">' + received + ' / ' + item.quantity + '</span></td>' +
            '<td class="text-end">' + ERP.formatCurrency(item.unitCost || 0) + '</td>' +
            '<td class="text-end fw-semibold">' + ERP.formatCurrency(item.totalLineCost || 0) + '</td></tr>';
        });
        html += '</tbody></table></div><div class="col-md-5">' +
          '<h4 class="mb-3" class="erp-table-section-header">Summary</h4>' +
          '<div class="erp-summary-box">' +
          '<div class="d-flex justify-content-between erp-text-sm"><span class="text-muted">Total Value</span><span class="fw-semibold">' + ERP.formatCurrency(po.totalAmount || 0) + '</span></div>' +
          '</div>' +
          '<div class="d-flex gap-2 mt-3">';
        if (po.status === 'Draft' || po.status === 'Partially Received') {
          html += '<button class="btn-erp-receive flex-fill" onclick="event.stopPropagation();openReceiveModal(\'' + po.id + '\')"><i class="ti ti-package-import me-1"></i>' + (po.status === 'Partially Received' ? 'Receive More' : 'Receive Goods') + '</button>';
        } else {
          html += '<div class="po-received-status"><i class="ti ti-check me-1"></i>Goods Received</div>';
        }
        var receiptCount = (po.receives || []).length;
        html += '<button class="btn-erp-receipts" onclick="event.stopPropagation();openReceiptsPanel(\'' + po.id + '\')"><i class="ti ti-receipt me-1"></i>Receipts' + (receiptCount ? ' (' + receiptCount + ')' : '') + '</button>';
        html += '</div></div></div></div></td></tr>';
      }
    });
  }
  tbody.innerHTML = html;

  var start = (poMeta.currentPage - 1) * 50;
  document.getElementById('po-info').textContent = 'Showing ' + (poMeta.total ? start + 1 : 0) + ' to ' + Math.min(start + 50, poMeta.total) + ' of ' + poMeta.total + ' orders';

  var totalPages = poMeta.lastPage || 1, cur = poMeta.currentPage || 1;
  var pagHtml = '';
  pagHtml += '<li class="page-item ' + (cur <= 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();loadPOs(' + (cur - 1) + ');">\u00AB</a></li>';
  var poShown = {}, poLast = 0;
  for (var p = 1; p <= Math.min(2, totalPages); p++) poShown[p] = true;
  for (var p = Math.max(1, cur - 2); p <= Math.min(totalPages, cur + 2); p++) poShown[p] = true;
  for (var p = Math.max(1, totalPages - 1); p <= totalPages; p++) poShown[p] = true;
  for (var pg = 1; pg <= totalPages; pg++) {
    if (!poShown[pg]) continue;
    if (poLast > 0 && pg - poLast > 1) pagHtml += '<li class="page-item disabled"><a class="page-link">\u2026</a></li>';
    pagHtml += '<li class="page-item ' + (pg === cur ? 'active' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();loadPOs(' + pg + ');">' + pg + '</a></li>';
    poLast = pg;
  }
  pagHtml += '<li class="page-item ' + (cur >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();loadPOs(' + (cur + 1) + ');">\u00BB</a></li>';
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
    '<div class="sr-sdd-trigger" onclick="sddToggle(\'npo-prod-sdd-' + idx + '\')">' +
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
  document.getElementById('npo-vendor-error').classList.add('d-none');
  document.getElementById('npo-items-error').classList.add('d-none');
  document.getElementById('npo-save-error').classList.add('d-none');
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
  var grnOff   = window.ERP.state.grnEnabled === false;
  var expiryOn = grnOff && !!window.ERP.state.expiryDateEnabled;
  var mfgOn    = grnOff && !!window.ERP.state.mfgDateEnabled;
  var batchOn  = expiryOn || mfgOn;

  var head = '<th class="po-th-col" style="width:36px;">#</th>' +
    '<th class="po-th-col">Product</th>' +
    '<th class="po-th-col" style="width:90px;">UOM</th>' +
    '<th class="po-th-col" style="width:80px;">Qty</th>' +
    '<th class="po-th-col" style="width:110px;">Unit Cost</th>';
  if (batchOn)  head += '<th class="po-th-col">Batch No</th>';
  if (mfgOn)    head += '<th class="po-th-col">Mfg Date</th>';
  if (expiryOn) head += '<th class="po-th-col">Expiry Date</th>';
  head += '<th class="po-th-col" style="width:110px;">Line Total</th>' +
    '<th class="po-th-act" style="width:36px;"></th>';
  document.getElementById('npo-head').innerHTML = head;

  var tbody = document.getElementById('npo-items');
  var html = '';
  poItems.forEach(function(item, idx) {
    html += '<tr>' +
      '<td class="po-td-input" style="color:#9CA3AF;font-size:0.78rem;text-align:center;">' + (idx + 1) + '</td>' +
      '<td class="po-td-input">' + renderProductSDD(idx, item.productId) + '</td>' +
      renderUomCell(idx, item) +
      '<td class="po-td-input"><input type="number" class="form-control pm-input text-center po-input-sm" value="' + item.quantity + '" onchange="updatePOItem(' + idx + ',\'quantity\',this.value)"></td>' +
      '<td class="po-td-input"><input type="number" step="0.01" class="form-control pm-input po-input-sm" value="' + item.unitCost + '" onchange="updatePOItem(' + idx + ',\'unitCost\',this.value)"></td>';
    if (batchOn) {
      html += '<td class="po-td-input"><input type="text" class="form-control pm-input po-input-sm" maxlength="255" placeholder="Batch/Lot" value="' + (item.batchNo || '') + '" onchange="updatePOItem(' + idx + ',\'batchNo\',this.value)"></td>';
    }
    if (mfgOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input po-input-sm" value="' + (item.mfgDate || '') + '" onchange="updatePOItem(' + idx + ',\'mfgDate\',this.value)"></td>';
    }
    if (expiryOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input po-input-sm" value="' + (item.expiryDate || '') + '" onchange="updatePOItem(' + idx + ',\'expiryDate\',this.value)"></td>';
    }
    html += '<td class="po-td-input text-end" id="po-line-total-' + idx + '" style="font-weight:600;color:#1A1D2E;white-space:nowrap;">' + ERP.formatCurrency(item.quantity * item.unitCost) + '</td>' +
      '<td class="po-td-input text-center"><button type="button" class="po-del-btn" onclick="removePOItem(' + idx + ')"><i class="ti ti-x"></i></button></td></tr>';
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
    var ltCell = document.getElementById('po-line-total-' + idx);
    if (ltCell) ltCell.textContent = ERP.formatCurrency(poItems[idx].quantity * poItems[idx].unitCost);
    updatePOTotal();
  } else if (field === 'unitCost') {
    poItems[idx].unitCost = parseFloat(value) || 0;
    var ltCell = document.getElementById('po-line-total-' + idx);
    if (ltCell) ltCell.textContent = ERP.formatCurrency(poItems[idx].quantity * poItems[idx].unitCost);
    updatePOTotal();
  } else if (field === 'batchNo') {
    poItems[idx].batchNo = value;
  } else if (field === 'mfgDate') {
    poItems[idx].mfgDate = value;
  } else if (field === 'expiryDate') {
    poItems[idx].expiryDate = value;
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
  var vendorErr  = document.getElementById('npo-vendor-error');
  var itemsErr   = document.getElementById('npo-items-error');
  var saveErrBox = document.getElementById('npo-save-error');
  var saveErrMsg = document.getElementById('npo-save-error-msg');
  vendorErr.classList.add('d-none');
  itemsErr.classList.add('d-none');
  saveErrBox.classList.add('d-none');

  var vendorId = document.getElementById('npo-vendor').value;
  if (!vendorId) { vendorErr.classList.remove('d-none'); return; }

  var validItems = poItems.filter(function(i) { return i.productId; }).map(function(i) {
    var out = { productId: i.productId, uomId: i.uomId || null, quantity: i.quantity, unitCost: i.unitCost };
    if (i.batchNo)    out.batchNo = i.batchNo;
    if (i.mfgDate)    out.mfgDate = i.mfgDate;
    if (i.expiryDate) out.expiryDate = i.expiryDate;
    return out;
  });
  if (validItems.length === 0) { itemsErr.classList.remove('d-none'); return; }

  if (!await showConfirm('Save Purchase Order', 'Are you sure you want to save this Purchase Order?')) return;

  try {
    var orderDate = document.getElementById('npo-date').value;
    await ERP.api.createPurchaseOrder(vendorId, validItems, orderDate);
    bootstrap.Modal.getInstance(document.getElementById('newPOModal')).hide();
    await loadPOs(1);
    document.getElementById('poCreateSuccess').classList.remove('d-none');
  } catch(e) {
    saveErrMsg.textContent = e.message || 'Failed to create purchase order.';
    saveErrBox.classList.remove('d-none');
  }
}

function openReceiveModal(poId) {
  receivePOId = poId;
  var po = poPOs.find(function(x) { return x.id === poId; });
  if (!po) return;
  var products = window.ERP.state.products || [];

  document.getElementById('recv-po-id').textContent = 'PO: ' + po.id;
  document.getElementById('recv-notes').value = '';
  var errBox = document.getElementById('recv-save-error');
  if (errBox) { errBox.textContent = ''; errBox.classList.add('d-none'); }
  var today = new Date();
  document.getElementById('recv-date').value = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');

  var expiryOn = !!window.ERP.state.expiryDateEnabled;
  var mfgOn    = !!window.ERP.state.mfgDateEnabled;
  var batchOn  = expiryOn || mfgOn;

  var dlg = document.querySelector('#receiveModal .modal-dialog');
  if (dlg) dlg.classList.toggle('modal-dialog-recv-wide', batchOn);

  var head = '<th class="po-th-col" style="width:36px;">#</th>' +
    '<th class="po-th-col">Product</th>' +
    '<th class="po-th-col text-center">Ordered</th>' +
    '<th class="po-th-col text-center">Received</th>' +
    '<th class="po-th-col text-center">Remaining</th>' +
    '<th class="po-th-col" style="width:120px;">Receive Qty</th>';
  if (batchOn)  head += '<th class="po-th-col">Batch No</th>';
  if (mfgOn)    head += '<th class="po-th-col">Mfg Date</th>';
  if (expiryOn) head += '<th class="po-th-col">Expiry Date</th>';
  document.getElementById('recv-head').innerHTML = head;

  var tbody = document.getElementById('recv-items');
  var html = '';
  (po.items || []).forEach(function(item, idx) {
    var prod = products.find(function(p) { return p.id === item.productId; });
    var received = item.receivedQuantity || 0;
    var remaining = item.quantity - received;
    html += '<tr>' +
      '<td class="po-td-center" style="color:#9CA3AF;font-size:0.78rem;">' + (idx + 1) + '</td>' +
      '<td class="po-td-item">' + (prod ? prod.name : 'Item') + '</td>' +
      '<td class="po-td-center">' + item.quantity + '</td>' +
      '<td class="po-td-center">' + received + '</td>' +
      '<td class="po-td-center ' + (remaining > 0 ? 'text-primary' : 'text-success') + ' fw-semibold">' + remaining + '</td>' +
      '<td class="po-td-input">' +
        '<input type="number" class="form-control pm-input text-center recv-qty po-input-sm" ' +
          'data-item-id="' + item.id + '" data-product-id="' + item.productId + '" ' +
          'data-unit-cost="' + item.unitCost + '" data-max="' + remaining + '" ' +
          'min="0" max="' + remaining + '" value="' + Math.max(0, remaining) + '" ' +
          'oninput="validateRecvQty(this)">' +
        '<div class="text-danger" style="font-size:0.72rem;min-height:14px;" id="recv-err-' + item.id + '"></div>' +
      '</td>';
    if (batchOn) {
      html += '<td class="po-td-input"><input type="text" class="form-control pm-input recv-batch po-input-sm" ' +
        'data-item-id="' + item.id + '" maxlength="255" placeholder="Batch/Lot"></td>';
    }
    if (mfgOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input recv-mfg po-input-sm" ' +
        'data-item-id="' + item.id + '"></td>';
    }
    if (expiryOn) {
      html += '<td class="po-td-input"><input type="date" class="form-control pm-input recv-expiry po-input-sm" ' +
        'data-item-id="' + item.id + '"></td>';
    }
    html += '</tr>';
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
  var poCreateOk = document.getElementById('poCreateSuccessOk');
  if (poCreateOk) poCreateOk.addEventListener('click', function() { document.getElementById('poCreateSuccess').classList.add('d-none'); });
  var poReceiveOk = document.getElementById('poReceiveSuccessOk');
  if (poReceiveOk) poReceiveOk.addEventListener('click', function() { document.getElementById('poReceiveSuccess').classList.add('d-none'); });

  var modal = document.getElementById('newPOModal');
  if (modal) {
    modal.addEventListener('shown.bs.modal', function() {
      var inp = document.getElementById('npo-barcode');
      if (inp) inp.focus();
    });
  }

  // Filter bar wiring
  var poSearch = document.getElementById('po-search');
  if (poSearch) poSearch.addEventListener('input', function() {
      clearTimeout(poSearchTimer);
      poSearchTimer = setTimeout(function() { loadPOs(1); }, 400);
  });
  var poStatus = document.getElementById('po-status');
  if (poStatus) poStatus.addEventListener('change', function() { loadPOs(1); });
  var poVendor = document.getElementById('po-vendor');
  if (poVendor) poVendor.addEventListener('change', function() { loadPOs(1); });
  var poDateFrom = document.getElementById('po-date-from');
  if (poDateFrom) poDateFrom.addEventListener('change', function() { loadPOs(1); });
  var poDateTo = document.getElementById('po-date-to');
  if (poDateTo) poDateTo.addEventListener('change', function() { loadPOs(1); });

  // Filter toggle
  var filterBtn = document.getElementById('po-filter-toggle-btn');
  if (filterBtn) {
    filterBtn.addEventListener('click', function() {
      var panel = document.getElementById('po-filters-panel');
      var isOpen = !panel.classList.contains('d-none');
      panel.classList.toggle('d-none', isOpen);
      filterBtn.classList.toggle('active', !isOpen);
    });
  }

  // Clear filters
  var clearBtn = document.getElementById('po-clear-filters-btn');
  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      document.getElementById('po-search').value = '';
      document.getElementById('po-status').value = 'all';
      document.getElementById('po-vendor').value = 'all';
      document.getElementById('po-date-from').value = '';
      document.getElementById('po-date-to').value = '';
      loadPOs(1);
    });
  }
});

function validateRecvQty(inp) {
  var val = parseInt(inp.value);
  var max = parseInt(inp.dataset.max) || 0;
  var errEl = document.getElementById('recv-err-' + inp.dataset.itemId);
  if (!errEl) return;
  if (isNaN(val) || val < 0) {
    errEl.textContent = 'Cannot be negative.';
    inp.classList.add('is-invalid');
  } else if (val > max) {
    errEl.textContent = 'Max ' + max + ' remaining.';
    inp.classList.add('is-invalid');
  } else {
    errEl.textContent = '';
    inp.classList.remove('is-invalid');
  }
}

async function submitReceive() {
  if (!receivePOId) return;
  // Validate all qty inputs before confirming
  var hasError = false;
  document.querySelectorAll('.recv-qty').forEach(function(inp) {
    validateRecvQty(inp);
    if (inp.classList.contains('is-invalid')) hasError = true;
  });
  if (hasError) return;
  if (!await showConfirm('Receive Goods', 'Are you sure you want to receive these goods? This will update your inventory stock.', 'Yes, Receive', 'ti-package-import')) return;
  var inputs = document.querySelectorAll('.recv-qty');
  var items = [];
  inputs.forEach(function(inp) {
    var qty = parseInt(inp.value) || 0;
    if (qty > 0) {
      var entry = {
        purchaseItemId: inp.dataset.itemId,
        productId: inp.dataset.productId,
        quantity: qty,
        unitCost: parseFloat(inp.dataset.unitCost) || 0
      };
      var itemId  = inp.dataset.itemId;
      var batchEl = document.querySelector('.recv-batch[data-item-id="' + itemId + '"]');
      var mfgEl   = document.querySelector('.recv-mfg[data-item-id="' + itemId + '"]');
      var expEl   = document.querySelector('.recv-expiry[data-item-id="' + itemId + '"]');
      if (batchEl && batchEl.value.trim()) entry.batchNo = batchEl.value.trim();
      if (mfgEl && mfgEl.value) entry.mfgDate = mfgEl.value;
      if (expEl && expEl.value) entry.expiryDate = expEl.value;
      items.push(entry);
    }
  });
  if (items.length === 0) {
    var errBox = document.getElementById('recv-save-error');
    errBox.textContent = 'Enter at least one quantity to receive.';
    errBox.classList.remove('d-none');
    return;
  }
  var dateError = items.some(function(it) {
    return it.mfgDate && it.expiryDate && it.expiryDate <= it.mfgDate;
  });
  if (dateError) {
    var dateErrBox = document.getElementById('recv-save-error');
    dateErrBox.textContent = 'Expiry date must be after the manufacturing date.';
    dateErrBox.classList.remove('d-none');
    return;
  }
  var notes = document.getElementById('recv-notes').value;
  var receiveDate = document.getElementById('recv-date').value;

  var btn = document.getElementById('recv-submit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Receiving...';

  try {
    var result = await ERP.api.partialReceivePurchaseOrder(receivePOId, items, notes || undefined, receiveDate || undefined);
    bootstrap.Modal.getInstance(document.getElementById('receiveModal')).hide();
    await loadPOs(poMeta.currentPage);
    document.getElementById('poReceiveSuccess').classList.remove('d-none');
    if (result && result.warning) showJournalWarning(result.warning);
  } catch(e) {
    var errBox = document.getElementById('recv-save-error');
    errBox.textContent = e.message || 'Failed to receive goods.';
    errBox.classList.remove('d-none');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-check me-1"></i>Receive Goods';
  }
}

function openReceiptsPanel(poId) {
  var po = poPOs.find(function(x) { return x.id === poId; });
  if (!po) return;
  var products = window.ERP.state.products || [];
  var receives = (po.receives || []).slice().sort(function(a, b) { return new Date(b.receiveDate || b.createdAt) - new Date(a.receiveDate || a.createdAt); });

  document.getElementById('receipts-po-sub').textContent = 'PO: ' + po.id + ' — ' + receives.length + ' receipt(s)';

  var html = '';
  if (!receives.length) {
    html = '<div style="text-align:center;color:#9CA3AF;padding:32px 0;font-size:0.875rem;">No receipts yet.</div>';
  } else {
    receives.forEach(function(rcv, i) {
      var dateStr = rcv.receiveDate || (rcv.createdAt ? new Date(rcv.createdAt).toLocaleDateString('en-GB') : '—');
      var total = (rcv.items || []).reduce(function(s, it) { return s + (it.quantity * it.unitCost); }, 0);
      html += '<div style="border:1px solid #E8EAF0;border-radius:8px;padding:14px 16px;margin-bottom:12px;">' +
        '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">' +
          '<div style="font-weight:600;font-size:0.875rem;color:#1A1D2E;"><i class="ti ti-receipt me-1" style="color:#CD0000;"></i>Receipt #' + (receives.length - i) + '</div>' +
          '<div style="font-size:0.78rem;color:#6B7280;">' + dateStr + (rcv.notes ? ' · ' + escHtml(rcv.notes) : '') + '</div>' +
        '</div>' +
        '<table style="width:100%;font-size:0.8rem;border-collapse:collapse;">' +
          '<thead><tr style="color:#6B7280;font-size:0.72rem;text-transform:uppercase;">' +
            '<th style="padding:4px 6px;text-align:left;">Product</th>' +
            '<th style="padding:4px 6px;text-align:center;">Qty</th>' +
            '<th style="padding:4px 6px;text-align:right;">Unit Cost</th>' +
            '<th style="padding:4px 6px;text-align:right;">Total</th>' +
          '</tr></thead><tbody>';
      (rcv.items || []).forEach(function(it) {
        var prod = products.find(function(p) { return p.id === it.productId; });
        html += '<tr style="border-top:1px solid #F0F2F8;">' +
          '<td style="padding:5px 6px;">' + (prod ? escHtml(prod.name) : 'Deleted Product') + '</td>' +
          '<td style="padding:5px 6px;text-align:center;">' + it.quantity + '</td>' +
          '<td style="padding:5px 6px;text-align:right;">' + ERP.formatCurrency(it.unitCost) + '</td>' +
          '<td style="padding:5px 6px;text-align:right;font-weight:600;">' + ERP.formatCurrency(it.quantity * it.unitCost) + '</td>' +
        '</tr>';
      });
      html += '</tbody><tfoot><tr>' +
        '<td colspan="3" style="padding:6px 6px 2px;text-align:right;font-size:0.78rem;color:#6B7280;">Receipt Total</td>' +
        '<td style="padding:6px 6px 2px;text-align:right;font-weight:700;color:#CD0000;">' + ERP.formatCurrency(total) + '</td>' +
      '</tr></tfoot></table></div>';
    });
  }
  document.getElementById('receipts-list').innerHTML = html;
  document.getElementById('poReceiptsOverlay').classList.remove('d-none');
}

function closeReceiptsPanel() {
  document.getElementById('poReceiptsOverlay').classList.add('d-none');
}
