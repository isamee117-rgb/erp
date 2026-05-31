var srReturns = [], srMeta = { currentPage: 1, lastPage: 1, total: 0 };
var srSearchTimer = null, srLoading = false;
var srReturnableSales = [];

function srGetFilters() {
    return {
        page:   srMeta.currentPage,
        search: (document.getElementById('searchInput').value || '').trim(),
        from:   document.getElementById('dateFrom').value || '',
        to:     document.getElementById('dateTo').value || '',
    };
}

function loadSaleReturns(page) {
    if (srLoading) return;
    srLoading = true;
    srMeta.currentPage = page || 1;

    var tbody = document.getElementById('returnsBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

    ERP.api.getSaleReturns(srGetFilters())
        .then(function(res) {
            srReturns = res.data || [];
            var m = res.meta || {};
            srMeta = {
                currentPage: m.current_page || 1,
                lastPage:    m.last_page    || 1,
                total:       m.total        || 0,
            };
            renderPage();
        })
        .catch(function(e) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Error: ' + e.message + '</td></tr>';
        })
        .finally(function() { srLoading = false; });
}

window.ERP.onReady = function() { loadSaleReturns(1); };

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(srSearchTimer);
        srSearchTimer = setTimeout(function() { loadSaleReturns(1); }, 400);
    });
    ['dateFrom', 'dateTo'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() { loadSaleReturns(1); });
    });

    document.getElementById('sret-filter-toggle-btn').addEventListener('click', function() {
        var panel = document.getElementById('sret-filters-panel');
        var isOpen = !panel.classList.contains('d-none');
        panel.classList.toggle('d-none', isOpen);
        this.classList.toggle('active', !isOpen);
    });

    document.getElementById('sret-clear-filters-btn').addEventListener('click', function() { clearFilters(); });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sdd-wrap')) {
            document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
        }
    });

    var modal = document.getElementById('newSReturnModal');
    if (modal) modal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('saleSelect').value = '';
        document.getElementById('saleSelect-disp').textContent = '-- Select an Invoice --';
        document.getElementById('saleSelect-disp').style.color = '#B0B7C9';
        document.getElementById('saleItemsContainer').classList.add('d-none');
        document.getElementById('saleItemsGrouped').innerHTML = '';
        document.getElementById('returnReason').value = '';
        hideSretError();
    });
});

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    loadSaleReturns(1);
}

function renderPage() {
    var products = window.ERP.state.products || [];
    var html = '';
    srReturns.forEach(function(r) {
        var items = r.items || [];
        html += '<tr class="cursor-pointer" onclick="toggleExpand(\'' + r.id + '\')">';
        html += '<td><i class="ti ti-chevron-right" id="chev-' + r.id + '"></i></td>';
        html += '<td><span class="badge-pill badge-purple">' + r.id + '</span></td>';
        html += '<td>' + new Date(r.createdAt).toLocaleDateString() + '</td>';
        html += '<td>' + (r.originalSaleNo || r.originalSaleId || '—') + '</td>';
        html += '<td>' + (r.customerName || '—') + '</td>';
        html += '<td>' + items.length + '</td>';
        html += '<td class="text-end">' + ERP.formatCurrency(r.totalAmount || 0) + '</td>';
        html += '<td><span class="text-muted">' + (r.reason || '—') + '</span></td></tr>';

        html += '<tr id="exp-' + r.id + '" class="d-none expand-row"><td colspan="8"><div class="p-3"><div class="row">';
        html += '<div class="col-md-7"><h4 class="mb-3 erp-table-section-header">Return Items</h4>';
        html += '<table class="table table-sm mb-0"><thead><tr>' +
            '<th class="po-th-col" style="width:36px;">#</th>' +
            '<th class="po-th-col">Product</th>' +
            '<th class="po-th-col text-center">Qty</th>' +
            '<th class="po-th-col text-end">Unit Price</th>' +
            '<th class="po-th-col text-end">Line Total</th>' +
            '</tr></thead><tbody>';
        items.forEach(function(it, idx) {
            var prod = products.find(function(p) { return p.id === it.productId; });
            var price = it.unitPrice || it.price || 0;
            html += '<tr>' +
                '<td class="text-center" style="color:#9CA3AF;font-size:0.78rem;">' + (idx + 1) + '</td>' +
                '<td>' + (prod ? prod.name : 'Unknown') + '</td>' +
                '<td class="text-center">' + it.quantity + '</td>' +
                '<td class="text-end">' + ERP.formatCurrency(price) + '</td>' +
                '<td class="text-end fw-semibold">' + ERP.formatCurrency(it.quantity * price) + '</td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        html += '<div class="col-md-5"><h4 class="mb-3 erp-table-section-header">Summary</h4>';
        html += '<div class="erp-summary-box">';
        html += '<div class="d-flex justify-content-between erp-text-sm"><span class="text-muted">Total Credited</span><span class="fw-semibold">' + ERP.formatCurrency(r.totalAmount || 0) + '</span></div>';
        html += '</div></div></div></div></td></tr>';
    });
    if (!srReturns.length) html = '<tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-receipt-refund fs-1 d-block mb-2 text-muted"></i>No sales returns found</td></tr>';
    document.getElementById('returnsBody').innerHTML = html;

    var start = (srMeta.currentPage - 1) * 50;
    document.getElementById('paginationInfo').textContent = 'Showing ' + (srMeta.total ? start + 1 : 0) + ' to ' + Math.min(start + 50, srMeta.total) + ' of ' + srMeta.total;

    var totalPages = srMeta.lastPage || 1, cur = srMeta.currentPage || 1;
    var ph = '';
    ph += '<li class="page-item ' + (cur <= 1 ? 'disabled' : '') + '"><a class="page-link" href="javascript:void(0)"' + (cur > 1 ? ' onclick="loadSaleReturns(' + (cur - 1) + ')"' : '') + '>&#171;</a></li>';
    var _pgS = {}, _pgL = 0;
    for (var p = 1; p <= Math.min(2, totalPages); p++) _pgS[p] = true;
    for (var p = Math.max(1, cur - 2); p <= Math.min(totalPages, cur + 2); p++) _pgS[p] = true;
    for (var p = Math.max(1, totalPages - 1); p <= totalPages; p++) _pgS[p] = true;
    for (var i = 1; i <= totalPages; i++) {
        if (!_pgS[i]) continue;
        if (_pgL > 0 && i - _pgL > 1) ph += '<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        ph += '<li class="page-item ' + (i === cur ? 'active' : '') + '"><a class="page-link" href="javascript:void(0)" onclick="loadSaleReturns(' + i + ')">' + i + '</a></li>';
        _pgL = i;
    }
    ph += '<li class="page-item ' + (cur >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="javascript:void(0)"' + (cur < totalPages ? ' onclick="loadSaleReturns(' + (cur + 1) + ')"' : '') + '>&#187;</a></li>';
    document.getElementById('pagination').innerHTML = ph;

    populateSaleSelect();
}

function toggleExpand(id) {
    var r = document.getElementById('exp-' + id), c = document.getElementById('chev-' + id);
    if (r.classList.contains('d-none')) {
        r.classList.remove('d-none'); c.className = 'ti ti-chevron-down';
    } else {
        r.classList.add('d-none'); c.className = 'ti ti-chevron-right';
    }
}

/* ── SDD helpers ── */
function escHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

function sddToggle(wrapId) {
    var wrap = document.getElementById(wrapId), isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
    if (!isOpen) {
        wrap.classList.add('open');
        var inp = wrap.querySelector('.sdd-search-inp');
        if (inp) { inp.value = ''; sddFilterOpts(wrapId, ''); setTimeout(function() { inp.focus(); }, 50); }
    }
}

function sddFilterOpts(wrapId, query) {
    var wrap = document.getElementById(wrapId), q = query.toLowerCase().trim();
    var opts = wrap.querySelectorAll('.sdd-opt'), visible = 0;
    opts.forEach(function(o) { var m = !q || o.textContent.toLowerCase().indexOf(q) !== -1; o.style.display = m ? '' : 'none'; if (m) visible++; });
    var nr = wrap.querySelector('.sdd-no-res'); if (nr) nr.style.display = visible === 0 ? '' : 'none';
}

function sddSelectInvoice(saleId, label) {
    document.getElementById('saleSelect').value = saleId;
    document.getElementById('saleSelect-disp').textContent = label;
    document.getElementById('saleSelect-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
    onSaleSelected();
}

function populateSaleSelect() {
    var optsEl = document.getElementById('saleSelect-opts');
    optsEl.innerHTML = '<div class="sdd-no-res">Loading...</div>';
    ERP.api.getReturnableSales().then(function(res) {
        srReturnableSales = res || [];
        var html = '';
        srReturnableSales.forEach(function(s) {
            var dateStr = new Date(s.createdAt).toLocaleDateString('en-GB');
            var custName = s.customerName || '—';
            var label = escHtml(s.id) + ' — ' + escHtml(custName) + ' — ' + escHtml(ERP.formatCurrency(s.totalAmount)) + ' — ' + dateStr;
            html += '<div class="sdd-opt" onclick="sddSelectInvoice(\'' + escHtml(s.id) + '\',\'' + escHtml(s.id + ' — ' + custName) + '\')">' + label + '</div>';
        });
        html += '<div class="sdd-no-res"' + (srReturnableSales.length ? ' style="display:none;"' : '') + '>No invoices found</div>';
        optsEl.innerHTML = html;
    }).catch(function() {
        optsEl.innerHTML = '<div class="sdd-no-res">Error loading invoices</div>';
    });
}

function onSaleSelected() {
    var saleId = document.getElementById('saleSelect').value;
    var container = document.getElementById('saleItemsContainer');
    var grouped = document.getElementById('saleItemsGrouped');
    grouped.innerHTML = '';
    container.classList.add('d-none');
    if (!saleId) return;

    var sale = srReturnableSales.find(function(s) { return s.id === saleId; });
    if (!sale) return;

    var custName = sale.customerName || '—';
    var products = window.ERP.state.products || [];
    var dateStr = new Date(sale.createdAt).toLocaleDateString('en-GB');

    var html = '<div class="pr-rcv-group mb-3">' +
        '<div class="pr-rcv-group-header">' +
            '<span class="sr-inv-id">' + escHtml(sale.id) + '</span>' +
            '<span class="pr-rcv-date">' + escHtml(custName) + ' &nbsp;·&nbsp; ' + dateStr + '</span>' +
        '</div>' +
        '<table class="table table-sm mb-0" style="table-layout:fixed;">' +
        '<thead><tr>' +
            '<th class="po-th-col" style="width:36px;">#</th>' +
            '<th class="po-th-col">Product</th>' +
            '<th class="po-th-col text-center" style="width:90px;">Sold Qty</th>' +
            '<th class="po-th-col" style="width:120px;">Return Qty</th>' +
            '<th class="po-th-col text-end" style="width:110px;">Unit Price</th>' +
        '</tr></thead><tbody>';

    (sale.items || []).forEach(function(it, i) {
        var prod = products.find(function(p) { return p.id === it.productId; });
        var key = escHtml(saleId) + '-' + i;
        html += '<tr>' +
            '<td class="po-td-center" style="color:#9CA3AF;font-size:0.78rem;">' + (i + 1) + '</td>' +
            '<td class="po-td-item">' + (prod ? escHtml(prod.name) : 'Unknown') + '</td>' +
            '<td class="po-td-center">' + it.quantity + '</td>' +
            '<td class="po-td-input">' +
                '<input type="number" class="form-control pm-input text-center po-input-sm sr-ret-qty" ' +
                    'min="0" max="' + it.quantity + '" value="0" ' +
                    'data-max="' + it.quantity + '" data-product-id="' + escHtml(it.productId) + '" ' +
                    'data-unit-price="' + (it.unitPrice || 0) + '" id="retQty-' + key + '" ' +
                    'oninput="validateSretQty(this,\'' + key + '\')">' +
                '<div class="text-danger" style="font-size:0.72rem;min-height:14px;" id="sret-err-' + key + '"></div>' +
            '</td>' +
            '<td class="po-td-input text-end" style="font-weight:600;">' + ERP.formatCurrency(it.unitPrice || 0) + '</td>' +
            '</tr>';
    });

    html += '</tbody></table></div>';
    grouped.innerHTML = html;
    container.classList.remove('d-none');
}

function validateSretQty(inp, key) {
    var val = parseInt(inp.value), max = parseInt(inp.dataset.max) || 0;
    var k = key || inp.id.replace('retQty-', '');
    var errEl = document.getElementById('sret-err-' + k);
    if (!errEl) return;
    if (isNaN(val) || val < 0) {
        errEl.textContent = 'Cannot be negative.'; inp.classList.add('is-invalid');
    } else if (val > max) {
        errEl.textContent = 'Max ' + max + '.'; inp.classList.add('is-invalid');
    } else {
        errEl.textContent = ''; inp.classList.remove('is-invalid');
    }
}

function showSretConfirm() {
    return new Promise(function(resolve) {
        var overlay = document.getElementById('sretConfirmOverlay');
        overlay.classList.remove('d-none');
        var okBtn     = document.getElementById('sretConfirmOk');
        var cancelBtn = document.getElementById('sretConfirmCancel');
        var resolved  = false;
        function cleanup() { okBtn.removeEventListener('click', onOk); cancelBtn.removeEventListener('click', onCancel); }
        function onOk()     { if (resolved) return; resolved = true; cleanup(); overlay.classList.add('d-none'); resolve(true); }
        function onCancel() { if (resolved) return; resolved = true; cleanup(); overlay.classList.add('d-none'); resolve(false); }
        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
    });
}

function showSretError(msg) {
    document.getElementById('sret-save-error-msg').textContent = msg;
    document.getElementById('sret-save-error').classList.remove('d-none');
}

function hideSretError() {
    document.getElementById('sret-save-error').classList.add('d-none');
}

async function submitReturn() {
    hideSretError();
    var saleId = document.getElementById('saleSelect').value;
    if (!saleId) { showSretError('Please select a sales invoice.'); return; }

    var hasError = false;
    document.querySelectorAll('.sr-ret-qty').forEach(function(inp) {
        validateSretQty(inp);
        if (inp.classList.contains('is-invalid')) hasError = true;
    });
    if (hasError) return;

    var items = [];
    document.querySelectorAll('.sr-ret-qty').forEach(function(inp) {
        var qty = parseInt(inp.value) || 0;
        if (qty > 0) items.push({
            productId: inp.dataset.productId,
            quantity: qty,
            unitPrice: parseFloat(inp.dataset.unitPrice) || 0
        });
    });
    if (!items.length) { showSretError('Please enter at least one return quantity.'); return; }

    if (!await showSretConfirm()) return;

    var reason = document.getElementById('returnReason').value;
    try {
        var result = await ERP.api.createSaleReturn(saleId, items, reason);
        bootstrap.Modal.getInstance(document.getElementById('newSReturnModal')).hide();
        await loadSaleReturns(1);
        document.getElementById('sretSuccessOverlay').classList.remove('d-none');
        if (result && result.warning) showJournalWarning(result.warning);
    } catch(e) {
        showSretError(e.message || 'Failed to create return.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var okBtn = document.getElementById('sretSuccessOk');
    if (okBtn) okBtn.addEventListener('click', function() { document.getElementById('sretSuccessOverlay').classList.add('d-none'); });
});
