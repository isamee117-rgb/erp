var sSales = [], sMeta = { currentPage: 1, lastPage: 1, total: 0 };
var sExpandedId = null;
var sSearchTimer = null;
var sLoading = false;

function sGetFilters() {
    return {
        page:    sMeta.currentPage,
        search:  (document.getElementById('sale-search').value || '').trim(),
        payment: document.getElementById('sale-payment').value || 'all',
        from:    document.getElementById('sale-date-from').value || '',
        to:      document.getElementById('sale-date-to').value || '',
    };
}

function loadSales(page) {
    if (sLoading) return;
    sLoading = true;
    sMeta.currentPage = page || 1;
    sExpandedId = null;

    var tbody = document.getElementById('sale-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

    ERP.api.getSales(sGetFilters())
        .then(function(res) {
            sSales = res.data || [];
            var m = res.meta || {};
            sMeta = {
                currentPage: m.current_page || 1,
                lastPage:    m.last_page    || 1,
                total:       m.total        || 0,
            };
            renderTable();
        })
        .catch(function(e) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Error loading sales: ' + e.message + '</td></tr>';
        })
        .finally(function() { sLoading = false; });
}

window.ERP.onReady = function() {
    loadSales(1);

    var sSearch = document.getElementById('sale-search');
    if (sSearch) sSearch.addEventListener('input', function() {
        clearTimeout(sSearchTimer);
        sSearchTimer = setTimeout(function() { loadSales(1); }, 400);
    });

    var sPayment = document.getElementById('sale-payment');
    if (sPayment) sPayment.addEventListener('change', function() { loadSales(1); });

    ['sale-date-from', 'sale-date-to'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function() { loadSales(1); });
    });

    var filterBtn = document.getElementById('sale-filter-toggle-btn');
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            var panel = document.getElementById('sale-filters-panel');
            var isOpen = !panel.classList.contains('d-none');
            panel.classList.toggle('d-none', isOpen);
            filterBtn.classList.toggle('active', !isOpen);
        });
    }

    var clearBtn = document.getElementById('sale-clear-filters-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            document.getElementById('sale-search').value = '';
            document.getElementById('sale-payment').value = 'all';
            document.getElementById('sale-date-from').value = '';
            document.getElementById('sale-date-to').value = '';
            loadSales(1);
        });
    }
};

function renderTable() {
    var products = window.ERP.state.products || [];
    var tbody = document.getElementById('sale-tbody');
    var html = '';

    if (sSales.length === 0) {
        html = '<tr><td colspan="9" class="text-center text-muted py-4">No sales records found</td></tr>';
    } else {
        sSales.forEach(function(sale) {
            var custName = sale.customerName || 'Walk-in Customer';
            var isExpanded = sExpandedId === sale.id;
            var returns = sale.returns || [];
            var totalCredited = returns.reduce(function(acc, r) { return acc + (r.totalAmount || 0); }, 0);
            var payBadge = sale.paymentMethod === 'Cash' ? 'pg-badge pg-badge-cash' : 'pg-badge pg-badge-credit';
            var statusBadge = sale.isReturned
                ? '<span class="pg-badge pg-badge-returned">Returned</span>'
                : '<span class="pg-badge pg-badge-completed">Completed</span>';

            html += '<tr class="cursor-pointer" onclick="toggleSaleExpand(\'' + sale.id + '\')">' +
                '<td><i class="ti erp-icon-sm ' + (isExpanded ? 'ti-chevron-down text-erp-primary' : 'ti-chevron-right erp-text-placeholder') + '"></i></td>' +
                '<td><span class="pg-id">' + (sale.id || '') + '</span></td>' +
                '<td>' + new Date(sale.createdAt).toLocaleDateString() + '</td>' +
                '<td><i class="ti ti-user me-1 erp-text-placeholder"></i>' + custName + '</td>' +
                '<td class="text-center">' + (sale.items ? sale.items.length : 0) + '</td>' +
                '<td class="text-end"><span' + (sale.isReturned ? ' class="erp-text-returned"' : '') + '>' + ERP.formatCurrency(sale.totalAmount || 0) + '</span></td>' +
                '<td><span class="' + payBadge + '"><i class="ti ' + (sale.paymentMethod === 'Cash' ? 'ti-cash' : 'ti-credit-card') + ' me-1"></i>' + (sale.paymentMethod || '') + '</span></td>' +
                '<td>' + statusBadge + '</td>' +
                '<td class="text-center no-print"><button class="pg-action-btn" onclick="event.stopPropagation();printSale(\'' + sale.id + '\')" title="Print"><i class="ti ti-printer"></i></button></td>' +
                '</tr>';

            if (isExpanded) {
                html += '<tr class="expand-row"><td colspan="9"><div class="p-3"><div class="row"><div class="col-md-7">' +
                    '<h4 class="mb-3 erp-table-section-header">Order Items</h4>' +
                    '<table class="table table-sm mb-0"><thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Line Total</th></tr></thead><tbody>';
                (sale.items || []).forEach(function(item) {
                    var prod = products.find(function(p) { return p.id === item.productId; });
                    html += '<tr><td>' + (prod ? prod.name : 'Deleted Product') + '</td>' +
                        '<td class="text-center">' + (item.quantity || 0) + '</td>' +
                        '<td class="text-end">' + ERP.formatCurrency(item.unitPrice || 0) + '</td>' +
                        '<td class="text-end fw-semibold">' + ERP.formatCurrency(item.totalLinePrice || 0) + '</td></tr>';
                });
                var saleItems = sale.items || [];
                var saleSubtotal = saleItems.reduce(function(s, it) { return s + (it.totalLinePrice || 0) + (it.discount || 0); }, 0);
                var saleTotalDiscount = saleItems.reduce(function(s, it) { return s + (it.discount || 0); }, 0);
                html += '</tbody></table></div><div class="col-md-5">' +
                    '<h4 class="mb-3 erp-table-section-header">Summary</h4>' +
                    '<div class="erp-summary-box">' +
                    '<div class="d-flex justify-content-between mb-1 erp-text-85"><span class="text-muted">Subtotal</span><span>' + ERP.formatCurrency(saleSubtotal) + '</span></div>' +
                    (saleTotalDiscount > 0 ? '<div class="d-flex justify-content-between mb-1 erp-text-85 erp-text-danger"><span>Discount</span><span>-' + ERP.formatCurrency(saleTotalDiscount) + '</span></div>' : '') +
                    '<div class="d-flex justify-content-between pt-1 mt-1 erp-border-top-light erp-text-85"><span class="text-muted">Grand Total</span><span class="fw-semibold">' + ERP.formatCurrency(sale.totalAmount || 0) + '</span></div>';
                if (totalCredited > 0) {
                    html += '<div class="d-flex justify-content-between pt-1 mt-1 erp-border-top-light erp-text-85 erp-text-danger"><span>Credited Amount</span><span class="fw-semibold">-' + ERP.formatCurrency(totalCredited) + '</span></div>';
                }
                html += '</div></div></div></div></td></tr>';
            }
        });
    }
    tbody.innerHTML = html;

    var start = (sMeta.currentPage - 1) * 50;
    document.getElementById('sale-info').textContent =
        'Showing ' + (sMeta.total ? start + 1 : 0) + ' to ' +
        Math.min(start + 50, sMeta.total) + ' of ' + sMeta.total + ' sales';

    renderPagination();
}

function renderPagination() {
    var totalPages = sMeta.lastPage || 1;
    var cur = sMeta.currentPage || 1;
    var pagHtml = '';

    pagHtml += '<li class="page-item ' + (cur <= 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();loadSales(' + (cur - 1) + ');">«</a></li>';

    var shown = {}, last = 0;
    for (var p = 1; p <= Math.min(2, totalPages); p++) shown[p] = true;
    for (var p = Math.max(1, cur - 2); p <= Math.min(totalPages, cur + 2); p++) shown[p] = true;
    for (var p = Math.max(1, totalPages - 1); p <= totalPages; p++) shown[p] = true;

    for (var pg = 1; pg <= totalPages; pg++) {
        if (!shown[pg]) continue;
        if (last > 0 && pg - last > 1) pagHtml += '<li class="page-item disabled"><a class="page-link">…</a></li>';
        pagHtml += '<li class="page-item ' + (pg === cur ? 'active' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();loadSales(' + pg + ');">' + pg + '</a></li>';
        last = pg;
    }

    pagHtml += '<li class="page-item ' + (cur >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();loadSales(' + (cur + 1) + ');">»</a></li>';
    document.getElementById('sale-pagination').innerHTML = pagHtml;
}

function toggleSaleExpand(id) {
    sExpandedId = sExpandedId === id ? null : id;
    renderTable();
}

function printSale(saleId) {
    var sale = sSales.find(function(s) { return s.id === saleId; });
    if (!sale) return;
    var products = window.ERP.state.products || [];
    var custName = sale.customerName || 'Walk-in Customer';

    var win = window.open('', '_blank');
    var itemsHtml = '';
    (sale.items || []).forEach(function(item) {
        var prod = products.find(function(p) { return p.id === item.productId; });
        itemsHtml += '<tr><td>' + (prod ? prod.name : 'Item') + '</td><td class="text-center">' + item.quantity + '</td>' +
            '<td class="text-end">' + ERP.formatCurrency(item.unitPrice || 0) + '</td>' +
            '<td class="text-end">' + ERP.formatCurrency(item.totalLinePrice || 0) + '</td></tr>';
    });

    win.document.write('<html><head><title>Invoice ' + sale.id + '</title><style>body{font-family:Inter,Arial,sans-serif;margin:40px;} table{width:100%;border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #E8EAF0;padding:10px;} th{background:#F8F9FC;font-weight:600;font-size:0.8rem;text-transform:uppercase;color:#64748b;} h1{color:#1A1D2E;}</style></head><body>' +
        '<h1>Invoice</h1><p><strong>Invoice #:</strong> ' + sale.id + '</p><p><strong>Date:</strong> ' + new Date(sale.createdAt).toLocaleString() + '</p>' +
        '<p><strong>Customer:</strong> ' + custName + '</p><p><strong>Payment:</strong> ' + (sale.paymentMethod || '') + '</p>' +
        '<table><thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr></thead><tbody>' + itemsHtml + '</tbody></table>' +
        '<h2 style="text-align:right;color:#3B4FE4;">Total: ' + ERP.formatCurrency(sale.totalAmount || 0) + '</h2></body></html>');
    win.document.close();
    win.print();
}
