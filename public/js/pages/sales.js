var sSortKey = 'createdAt', sSortDir = 'desc', sCurrentPage = 1, sPerPage = 10;
var sExpandedId = null;

function sRefetchIfNeeded(callback) {
    var loadedFrom = window.ERP.state.transactionLoadedFrom;
    var requestedFrom = (document.getElementById('sale-date-from').value || '');
    var requestedTo   = (document.getElementById('sale-date-to').value   || '');

    if (loadedFrom && requestedFrom && requestedFrom < loadedFrom) {
        var tbody = document.getElementById('salesTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

        ERP.api.syncTransactions({ from: requestedFrom, to: requestedTo || undefined })
            .then(function(txData) {
                ERP.mergeState(txData);
                if (txData.loadedFrom) {
                    window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
                }
                if (typeof callback === 'function') callback();
            })
            .catch(function(e) {
                alert('Error loading data: ' + e.message);
            });
    } else {
        if (typeof callback === 'function') callback();
    }
}

window.ERP.onReady = function() {
  renderPage();

  var sSearch = document.getElementById('sale-search');
  if (sSearch) sSearch.addEventListener('input', function() { sCurrentPage = 1; renderPage(); });

  var sPayment = document.getElementById('sale-payment');
  if (sPayment) sPayment.addEventListener('change', function() { sCurrentPage = 1; renderPage(); });

  ['sale-date-from', 'sale-date-to'].forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', function() {
        sCurrentPage = 1;
        sRefetchIfNeeded(renderPage);
    });
    el.addEventListener('input', function() {
        if (!this.value) return;
        var parts = this.value.split('-');
        if (parts[0] && parts[0].length > 4) {
            parts[0] = parts[0].slice(-4);
            this.value = parts.join('-');
            sCurrentPage = 1;
            sRefetchIfNeeded(renderPage);
        }
    });
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
      sCurrentPage = 1;
      renderPage();
    });
  }
};

function getFilteredSales() {
  var state = window.ERP.state;
  var search = (document.getElementById('sale-search').value || '').toLowerCase();
  var payFilter = document.getElementById('sale-payment').value;
  var dateFrom = document.getElementById('sale-date-from').value;
  var dateTo = document.getElementById('sale-date-to').value;
  var parties = state.parties || [];
  var fromTs = dateFrom ? new Date(dateFrom.replace(/^(\d{5,})-/, function(_, y) { return y.slice(-4) + '-'; })).setHours(0,0,0,0) : null;
  var toTs   = dateTo   ? new Date(dateTo.replace(/^(\d{5,})-/, function(_, y) { return y.slice(-4) + '-'; })).setHours(23,59,59,999) : null;

  var list = (state.sales || []).filter(function(s) {
    var cust = parties.find(function(p) { return p.id === s.customerId; });
    var custName = cust ? cust.name : 'Walk-in';
    var ms = (s.id || '').toLowerCase().indexOf(search) !== -1 || custName.toLowerCase().indexOf(search) !== -1;
    var mp = payFilter === 'all' || s.paymentMethod === payFilter;
    var md = (!fromTs || s.createdAt >= fromTs) && (!toTs || s.createdAt <= toTs);
    return ms && mp && md;
  });

  list.sort(function(a, b) {
    var va = a[sSortKey] || '', vb = b[sSortKey] || '';
    if (va < vb) return sSortDir === 'asc' ? -1 : 1;
    if (va > vb) return sSortDir === 'asc' ? 1 : -1;
    return 0;
  });
  return list;
}

function sSortToggle(key) {
  if (sSortKey === key) sSortDir = sSortDir === 'asc' ? 'desc' : 'asc';
  else { sSortKey = key; sSortDir = 'asc'; }
  renderPage();
}

function renderPage() {
  var state = window.ERP.state;
  var filtered = getFilteredSales();
  var totalPages = Math.max(1, Math.ceil(filtered.length / sPerPage));
  if (sCurrentPage > totalPages) sCurrentPage = totalPages;
  var start = (sCurrentPage - 1) * sPerPage;
  var page = filtered.slice(start, start + sPerPage);
  var parties = state.parties || [];
  var products = state.products || [];
  var salesReturns = state.salesReturns || [];

  var tbody = document.getElementById('sale-tbody');
  var html = '';
  if (page.length === 0) {
    html = '<tr><td colspan="9" class="text-center text-muted py-4">No sales records found</td></tr>';
  } else {
    page.forEach(function(sale) {
      var cust = parties.find(function(p) { return p.id === sale.customerId; });
      var custName = cust ? cust.name : 'Walk-in Customer';
      var isExpanded = sExpandedId === sale.id;
      var saleReturn = salesReturns.find(function(r) { return r.originalSaleId === sale.id; });
      var payBadge = sale.paymentMethod === 'Cash' ? 'pg-badge pg-badge-cash' : 'pg-badge pg-badge-credit';
      var statusBadge = sale.isReturned ? '<span class="pg-badge pg-badge-returned">Returned</span>' : '<span class="pg-badge pg-badge-completed">Completed</span>';

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
        if (saleReturn) {
          html += '<div class="d-flex justify-content-between pt-1 mt-1 erp-border-top-light erp-text-85 erp-text-danger"><span>Credited Amount</span><span class="fw-semibold">-' + ERP.formatCurrency(saleReturn.totalAmount || 0) + '</span></div>';
        }
        html += '</div></div></div></div></td></tr>';
      }
    });
  }
  tbody.innerHTML = html;

  document.getElementById('sale-info').textContent = 'Showing ' + (filtered.length ? start + 1 : 0) + ' to ' + Math.min(start + sPerPage, filtered.length) + ' of ' + filtered.length + ' sales';

  var pagHtml = '';
  pagHtml += '<li class="page-item ' + (sCurrentPage <= 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();sCurrentPage--;renderPage();">\u00AB</a></li>';
  var sShown = {}, sLast = 0;
  for (var p = 1; p <= Math.min(2, totalPages); p++) sShown[p] = true;
  for (var p = Math.max(1, sCurrentPage - 2); p <= Math.min(totalPages, sCurrentPage + 2); p++) sShown[p] = true;
  for (var p = Math.max(1, totalPages - 1); p <= totalPages; p++) sShown[p] = true;
  for (var pg = 1; pg <= totalPages; pg++) {
    if (!sShown[pg]) continue;
    if (sLast > 0 && pg - sLast > 1) pagHtml += '<li class="page-item disabled"><a class="page-link">\u2026</a></li>';
    pagHtml += '<li class="page-item ' + (pg === sCurrentPage ? 'active' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();sCurrentPage=' + pg + ';renderPage();">' + pg + '</a></li>';
    sLast = pg;
  }
  pagHtml += '<li class="page-item ' + (sCurrentPage >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="event.preventDefault();sCurrentPage++;renderPage();">\u00BB</a></li>';
  document.getElementById('sale-pagination').innerHTML = pagHtml;
}

function toggleSaleExpand(id) {
  sExpandedId = sExpandedId === id ? null : id;
  renderPage();
}

function printSale(saleId) {
  var sale = (window.ERP.state.sales || []).find(function(s) { return s.id === saleId; });
  if (!sale) return;
  var parties = window.ERP.state.parties || [];
  var products = window.ERP.state.products || [];
  var cust = parties.find(function(p) { return p.id === sale.customerId; });
  var custName = cust ? cust.name : 'Walk-in Customer';

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
