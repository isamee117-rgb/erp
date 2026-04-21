var posCart = (function() {
  try { return JSON.parse(localStorage.getItem('leanerp_pos_cart')) || []; } catch(e) { return []; }
})();
var posPayment = 'Cash';
var posSelectedCategory = null;
var lastSaleData = null;
var posSelectedCustomerCategory = null;

window.ERP.onReady = function() { renderPage(); };

function renderPage() {
  populateCustomers();
  renderCategories();
  renderProducts();
  renderCart();
  setPayment(posPayment);
}

function getCompanyProducts() {
  var coId = (window.ERP.state.currentUser || {}).companyId;
  return (window.ERP.state.products || []).filter(function(p) { return p.companyId === coId; });
}

function getCompanyParties() {
  var coId = (window.ERP.state.currentUser || {}).companyId;
  return (window.ERP.state.parties || []).filter(function(p) { return p.companyId === coId; });
}

function getCompanyCategories() {
  var coId = (window.ERP.state.currentUser || {}).companyId;
  return (window.ERP.state.categories || []).filter(function(c) { return c.companyId === coId; });
}

/* ── Searchable Dropdown helpers ── */
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function sddToggle(wrapId) {
  var wrap = document.getElementById(wrapId);
  var isOpen = wrap.classList.contains('open');
  // close all open SDDs first
  document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
  if (!isOpen) {
    wrap.classList.add('open');
    var inp = wrap.querySelector('.sdd-search-inp');
    if (inp) { inp.value = ''; sddFilterOpts(wrapId, ''); setTimeout(function(){ inp.focus(); }, 50); }
  }
}
function sddFilterOpts(wrapId, query) {
  var wrap = document.getElementById(wrapId);
  var q = query.toLowerCase().trim();
  var opts = wrap.querySelectorAll('.sdd-opt');
  var visible = 0;
  opts.forEach(function(o) {
    var match = !q || o.textContent.toLowerCase().indexOf(q) !== -1;
    o.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  var noRes = wrap.querySelector('.sdd-no-res');
  if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}
function sddSelectCustomer(customerId, customerName) {
  document.getElementById('pos-customer').value = customerId;
  document.getElementById('pos-customer-disp').textContent = customerName;
  document.getElementById('pos-customer-disp').style.color = '#1A1D2E';
  var trigger = document.getElementById('pos-customer-trigger');
  trigger.classList.remove('is-invalid');
  document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
  // Resolve customer category for tier pricing
  var parties = window.ERP.state.parties || [];
  var customer = parties.find(function(p) { return p.id === customerId; });
  posSelectedCustomerCategory = customer ? (customer.category || null) : null;
  renderProducts();
  renderCart();
}
// Close SDD on outside click
document.addEventListener('click', function(e) {
  if (!e.target.closest('.sdd-wrap')) {
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
  }
});

function populateCustomers() {
  var customers = getCompanyParties().filter(function(p) { return p.type === 'Customer'; });
  var optsWrap = document.getElementById('pos-customer-opts');
  var html = '';
  customers.forEach(function(c) {
    html += '<div class="sdd-opt" onclick="sddSelectCustomer(\'' + escHtml(c.id) + '\',\'' + escHtml(c.name) + '\')">' + escHtml(c.name) + '</div>';
  });
  html += '<div class="sdd-no-res" class="d-none">No customers found</div>';
  optsWrap.innerHTML = html;
}

function renderCategories() {
  var cats = getCompanyCategories();
  var container = document.getElementById('pos-categories');
  var html = '<button class="pos-cat-btn ' + (!posSelectedCategory ? 'active' : '') + '" onclick="posSelectedCategory=null;renderProducts();renderCategories();">All</button>';
  cats.forEach(function(c) {
    html += '<button class="pos-cat-btn ' + (posSelectedCategory === c.id ? 'active' : '') + '" onclick="posSelectedCategory=\'' + c.id + '\';renderProducts();renderCategories();">' + c.name + '</button>';
  });
  container.innerHTML = html;
}

function renderProducts() {
  var search = (document.getElementById('pos-search').value || '').toLowerCase();
  var products = getCompanyProducts().filter(function(p) {
    var ms = p.name.toLowerCase().indexOf(search) !== -1 || p.sku.toLowerCase().indexOf(search) !== -1 || (p.barcode || '').toLowerCase().indexOf(search) !== -1;
    var mc = !posSelectedCategory || p.categoryId === posSelectedCategory;
    return ms && mc;
  });

  var grid = document.getElementById('pos-product-grid');
  if (products.length === 0) {
    grid.innerHTML = '<div class="text-center py-5 text-muted"><i class="ti ti-package erp-icon-xl d-block"></i><div class="mt-2 pos-empty-msg">No products found</div></div>';
    return;
  }
  var html = '<div class="row g-2">';
  products.forEach(function(p) {
    var outOfStock = (p.currentStock || 0) <= 0;
    var isLow = (p.currentStock || 0) <= (p.reorderLevel || 0);
    var stockBadge = outOfStock
      ? '<span class="pos-badge-out">Out</span>'
      : isLow
      ? '<span class="pos-badge-low">' + p.currentStock + '</span>'
      : '<span class="pos-badge-ok">' + p.currentStock + '</span>';

    html += '<div class="col-6 col-md-4 col-xl-4"><div class="pos-product-card' + (outOfStock ? ' disabled' : '') + '" onclick="' + (outOfStock ? '' : 'addToCart(\'' + p.id + '\')') + '">' +
      '<div class="p-2">' +
      '<div class="pos-product-icon"><i class="ti ti-package erp-icon-1-3"></i></div>' +
      '<div class="pos-product-name mb-1">' + p.name + '</div>' +
      '<div class="pos-product-sku mb-2">Item No: ' + (p.itemNumber || p.sku || '') + '</div>' +
      '<div class="d-flex justify-content-between align-items-center">' +
      '<span class="pos-product-price">' + ERP.formatCurrency(resolveProductPrice(p)) + '</span>' +
      stockBadge + '</div></div></div></div>';
  });
  html += '</div>';
  grid.innerHTML = html;
}

function getProductUomConversions(product) {
  return product.uomConversions || [];
}

function getDefaultSalesConversion(product) {
  var convs = getProductUomConversions(product);
  return convs.find(function(c) { return c.isDefaultSalesUnit; }) || null;
}

function resolveProductPrice(product) {
  if (!posSelectedCustomerCategory) return product.unitPrice || 0;
  var tiers = product.priceTiers || [];
  for (var i = 0; i < tiers.length; i++) {
    if (tiers[i].category === posSelectedCustomerCategory) return tiers[i].price;
  }
  return product.unitPrice || 0;
}

function addToCart(productId) {
  var products = getCompanyProducts();
  var p = products.find(function(x) { return x.id === productId; });
  if (!p || p.currentStock <= 0) return;

  var defConv = getDefaultSalesConversion(p);
  var uomId = defConv ? defConv.uomId : null;
  var uomMultiplier = defConv ? defConv.multiplier : 1;
  // Stock in the selected UOM
  var maxInUom = Math.floor(p.currentStock / uomMultiplier);
  if (maxInUom <= 0) return;

  var existing = posCart.find(function(c) { return c.productId === productId; });
  if (existing) {
    var maxInUomExisting = Math.floor(p.currentStock / (existing.uomMultiplier || 1));
    if (existing.quantity >= maxInUomExisting) return;
    existing.quantity++;
  } else {
    posCart.push({ productId: productId, quantity: 1, discount: 0, uomId: uomId, uomMultiplier: uomMultiplier });
  }
  renderCart();
}

function updateCartQty(productId, delta) {
  var products = getCompanyProducts();
  var p = products.find(function(x) { return x.id === productId; });
  var item = posCart.find(function(c) { return c.productId === productId; });
  if (!item || !p) return;
  var maxInUom = Math.floor(p.currentStock / (item.uomMultiplier || 1));
  item.quantity = Math.max(1, Math.min(maxInUom, item.quantity + delta));
  renderCart();
}

function setCartQtyDirect(productId, input) {
  var products = getCompanyProducts();
  var p = products.find(function(x) { return x.id === productId; });
  var item = posCart.find(function(c) { return c.productId === productId; });
  if (!item || !p) return;
  var val = parseInt(input.value);
  if (isNaN(val) || val < 0) { renderCart(); return; }
  var maxInUom = Math.floor(p.currentStock / (item.uomMultiplier || 1));
  if (val > maxInUom) { val = maxInUom; }
  if (val === 0) { removeFromCart(productId); return; }
  item.quantity = val;
  renderCart();
}

function setCartItemUom(productId, uomId) {
  var products = getCompanyProducts();
  var p = products.find(function(x) { return x.id === productId; });
  var item = posCart.find(function(c) { return c.productId === productId; });
  if (!item || !p) return;
  var conv = (getProductUomConversions(p)).find(function(c) { return c.uomId === uomId; });
  item.uomId = uomId || null;
  item.uomMultiplier = conv ? conv.multiplier : 1;
  // Clamp quantity to what's available in the new unit
  var maxInUom = Math.floor(p.currentStock / item.uomMultiplier);
  if (item.quantity > maxInUom) item.quantity = Math.max(1, maxInUom);
  renderCart();
}

function setCartDiscount(productId, val) {
  var item = posCart.find(function(c) { return c.productId === productId; });
  if (!item) return;
  var discount = parseFloat(val) || 0;
  var products = getCompanyProducts();
  var p = products.find(function(x) { return x.id === productId; });
  if (p) {
    var multiplier = item.uomMultiplier || 1;
    var lineTotal = resolveProductPrice(p) * multiplier * item.quantity;
    if (discount > lineTotal) discount = lineTotal;
  }
  item.discount = discount;
  renderCart();
}

function setCartLineTotal(productId, grossTotal, input) {
  var newTotal = parseFloat(input.value) || 0;
  if (newTotal < 0) newTotal = 0;
  if (newTotal > grossTotal) newTotal = grossTotal;
  input.value = newTotal.toFixed(2);
  var newDiscount = parseFloat((grossTotal - newTotal).toFixed(2));
  setCartDiscount(productId, newDiscount);
}

function removeFromCart(productId) {
  posCart = posCart.filter(function(c) { return c.productId !== productId; });
  renderCart();
}

function clearCart() {
  posSelectedCustomerCategory = null;
  posCart = [];
  renderCart();
}

function renderCart() {
  try { localStorage.setItem('leanerp_pos_cart', JSON.stringify(posCart)); } catch(e) {}
  var products = getCompanyProducts();
  var container = document.getElementById('pos-cart');
  var subtotal = 0, totalDiscount = 0;

  if (posCart.length === 0) {
    container.innerHTML = '<div class="text-center py-5 text-muted"><i class="ti ti-shopping-cart erp-icon-lg d-block"></i><div class="mt-2 pos-cart-lbl">Cart is empty</div><div class="pos-cart-empty-sub">Select products to start</div></div>';
  } else {
    var html = '<div class="pos-cart-col-header">' +
      '<span class="pos-cch-name">Product</span>' +
      '<span class="pos-cch-uom">UOM</span>' +
      '<span class="pos-cch-disc">Disc.</span>' +
      '<span class="pos-cch-qty">Qty</span>' +
      '<span class="pos-cch-total">Total</span>' +
      '<span class="pos-cch-del"></span>' +
    '</div>';
    posCart.forEach(function(item) {
      var p = products.find(function(x) { return x.id === item.productId; });
      if (!p) return;
      var multiplier = item.uomMultiplier || 1;
      var unitPriceInUom = resolveProductPrice(p) * multiplier;
      var lineTotal = (unitPriceInUom * item.quantity) - item.discount;
      subtotal += unitPriceInUom * item.quantity;
      totalDiscount += item.discount;
      var maxInUom = Math.floor(p.currentStock / multiplier);

      // Build UOM selector — always shown; disabled if no conversions
      var convs = getProductUomConversions(p);
      var hasMultiple = convs.length > 0;
      var uomSelHtml = '<select class="pos-uom-sel"' +
        (hasMultiple ? ' onchange="setCartItemUom(\'' + item.productId + '\',this.value)"' : ' disabled') + '>' +
        '<option value=""' + (!item.uomId ? ' selected' : '') + '>' + (p.uom || 'Base') + '</option>';
      convs.forEach(function(c) {
        uomSelHtml += '<option value="' + c.uomId + '"' + (item.uomId === c.uomId ? ' selected' : '') + '>' + c.uomName + '</option>';
      });
      uomSelHtml += '</select>';

      html += '<div class="pos-cart-item pos-ci-row">' +
        '<div class="pos-ci-name">' + p.name + '</div>' +
        uomSelHtml +
        '<input type="number" class="pos-ci-input" placeholder="Disc." value="' + (item.discount || '') + '" onchange="setCartDiscount(\'' + item.productId + '\',this.value)">' +
        '<div class="pos-qty-group">' +
          '<button type="button" class="pos-qty-btn" onclick="updateCartQty(\'' + item.productId + '\',-1)"><i class="ti ti-minus"></i></button>' +
          '<input type="number" class="pos-qty-input" value="' + item.quantity + '" min="0" max="' + maxInUom + '" onclick="this.select()" onchange="setCartQtyDirect(\'' + item.productId + '\',this)">' +
          '<button type="button" class="pos-qty-btn" onclick="updateCartQty(\'' + item.productId + '\',1)"><i class="ti ti-plus"></i></button>' +
        '</div>' +
        '<div class="pos-line-price-wrap" title="Click to edit line total">' +
          '<span class="pos-line-price-prefix">Rs.</span>' +
          '<input type="number" class="pos-line-price" value="' + lineTotal.toFixed(2) + '" min="0" max="' + (unitPriceInUom * item.quantity).toFixed(2) + '" step="0.01" onclick="this.select()" onchange="setCartLineTotal(\'' + item.productId + '\',' + (unitPriceInUom * item.quantity).toFixed(2) + ',this)">' +
          '<i class="ti ti-pencil pos-line-price-icon"></i>' +
        '</div>' +
        '<button type="button" class="pos-remove-btn" onclick="removeFromCart(\'' + item.productId + '\')"><i class="ti ti-trash"></i></button>' +
      '</div>';
    });
    container.innerHTML = html;
  }

  var grandTotal = subtotal - totalDiscount;
  document.getElementById('pos-subtotal').textContent = ERP.formatCurrency(subtotal);
  document.getElementById('pos-discount').textContent = ERP.formatCurrency(totalDiscount);
  document.getElementById('pos-total').textContent = ERP.formatCurrency(grandTotal);
  document.getElementById('pos-checkout').disabled = posCart.length === 0;
}

function setPayment(method) {
  posPayment = method;
  document.getElementById('btn-cash').className = 'btn w-100 pos-pay-btn ' + (method === 'Cash' ? 'active' : '');
  document.getElementById('btn-credit').className = 'btn w-100 pos-pay-btn ' + (method === 'Credit' ? 'active' : '');
}

function closePosCustomerError() {
  document.getElementById('posCustomerError').classList.add('d-none');
  // Highlight the SDD trigger so user knows what to fix
  var trigger = document.getElementById('pos-customer-trigger');
  trigger.classList.add('is-invalid');
  // Open the SDD so user can immediately pick a customer
  sddToggle('pos-customer-sdd');
  // Remove highlight once a customer is selected (sddSelectCustomer already removes it)
}

async function completeSale() {
  var validCart = posCart.filter(function(c) { return c.quantity > 0; });
  if (validCart.length === 0) return;

  // Validate customer selection
  var customerId = document.getElementById('pos-customer').value;
  if (!customerId) {
    document.getElementById('posCustomerError').classList.remove('d-none');
    return;
  }

  var btn = document.getElementById('pos-checkout');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

  try {
    var saleData = {
      customerId: customerId,
      paymentMethod: posPayment,
      items: validCart.map(function(c) {
        return { productId: c.productId, quantity: c.quantity, uomId: c.uomId || null, discount: c.discount || 0 };
      })
    };
    var result = await ERP.api.createSale(saleData);
    lastSaleData = result;
    posCart = [];
    // Reset customer SDD for next sale
    document.getElementById('pos-customer').value = '';
    document.getElementById('pos-customer-disp').textContent = '— Select Customer —';
    document.getElementById('pos-customer-disp').style.color = '#B0B7C9';
    await ERP.sync();

    document.getElementById('sale-success-id').textContent = result.id || '';
    document.getElementById('sale-success-amount').textContent = ERP.formatCurrency(result.totalAmount || 0);
    new bootstrap.Modal(document.getElementById('saleSuccessModal')).show();

    renderPage();
  } catch(e) {
    alert(e.message || 'Sale failed');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-check me-1"></i>Complete Sale';
  }
}

function printLastSale() {
  if (!lastSaleData) return;
  var sale = lastSaleData;
  var parties = window.ERP.state.parties || [];
  var products = getCompanyProducts();
  var cust = parties.find(function(p) { return p.id === sale.customerId; });
  var custName = cust ? cust.name : 'Walk-in Customer';
  var format = window.ERP.state.invoiceFormat || 'A4';

  // Company info from state
  var companies = window.ERP.state.companies || [];
  var currentUser = window.ERP.state.currentUser || {};
  var company = companies.find(function(c){ return c.id === currentUser.companyId; }) || {};
  var info = company.info || {};
  var companyName   = info.name      || company.name || 'LeanERP';
  var companyAddr   = info.address   || '';
  var companyPhone  = info.phone     || '';
  var companyEmail  = info.email     || '';
  var companyLogo   = info.logoUrl   || '';

  var saleDate = new Date(sale.createdAt || Date.now());
  var dateStr  = saleDate.toLocaleDateString() + ' ' + saleDate.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});

  // Build absolute logo URL
  var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).getAttribute ? document.querySelector('meta[name="base-url"]').getAttribute('content') : '';
  var logoSrc = companyLogo ? (companyLogo.startsWith('http') ? companyLogo : baseUrl + companyLogo) : '';

  var win = window.open('', '_blank');

  if (format === 'Thermal') {
    /* ── THERMAL 80mm ──────────────────────────────────── */
    var itemRows = '';
    (sale.items || []).forEach(function(item) {
      var prod     = products.find(function(p){ return p.id === item.productId; });
      var name     = prod ? prod.name : 'Item';
      var price    = item.unitPrice || 0;
      var qty      = item.quantity  || 1;
      var disc     = item.discount  || 0;
      var total    = item.totalLinePrice || 0;

      itemRows +=
        '<tr>' +
          '<td class="td-prod">' + name + '</td>' +
          '<td class="td-qty">' + qty + '</td>' +
          '<td class="td-price">' + ERP.formatCurrency(price).split(' ').slice(1).join(' ') + '</td>' +
          '<td class="ta-r td-total">' + ERP.formatCurrency(total).split(' ').slice(1).join(' ') + '</td>' +
        '</tr>' +
        (disc > 0 ? '<tr><td colspan="4" class="item-disc">Disc: ' + ERP.formatCurrency(disc) + '</td></tr>' : '') +
        '<tr class="item-sep-row"><td colspan="4"><div class="item-sep"></div></td></tr>';
    });

    var lines =
      '<table class="item-tbl">' +
        '<thead><tr class="item-hdr"><td class="td-prod">PRODUCT</td><td class="td-qty">QTY</td><td class="td-price">PRICE</td><td class="ta-r td-total">TOTAL</td></tr></thead>' +
        '<tbody>' + itemRows + '</tbody>' +
      '</table>';

    var logoHtml = ''; // Logo excluded from thermal receipt

    win.document.write(
      '<html><head><title>Receipt</title><style>' +
      'body{font-family:monospace;font-size:12px;width:302px;margin:0 auto;padding:8px 4px;}' +
      'h2{text-align:center;font-size:24px;font-weight:900;margin:0 0 4px;font-family:monospace;letter-spacing:1px;}' +
      '.sub{text-align:center;font-size:10px;margin:0 0 2px;color:#333;}' +
      '.row{display:flex;justify-content:space-between;font-size:11px;margin:2px 0;}' +
      '.divider{border-top:1px dashed #000;margin:5px 0;}' +
      '.item-tbl{width:100%;border-collapse:collapse;margin:4px 0 0;table-layout:fixed;}' +
      '.item-hdr td{font-size:11px;font-weight:900;text-transform:uppercase;color:#000;padding:4px 0;border-top:1px dashed #aaa;border-bottom:1px dashed #aaa;}' +
      '.item-tbl td{font-size:11px;padding:3px 0;vertical-align:top;}' +
      '.td-prod{width:44%;word-break:break-word;}' +
      '.td-qty{width:8%;text-align:center;}' +
      '.td-price{width:28%;text-align:left;}' +
      '.td-total{width:20%;text-align:right;}' +
      '.ta-r{text-align:right;}' +
      '.item-disc{font-size:10px;color:#c00;padding-left:2px;}' +
      '.item-sep{border-top:1px dashed #aaa;margin:3px 0;}' +
      '.item-sep-row td{padding:0;}' +
      '.total-row{display:flex;justify-content:space-between;font-size:13px;font-weight:bold;margin:5px 0;}' +
      '.footer{text-align:center;font-size:10px;margin-top:10px;color:#555;}' +
      '@media print{@page{margin:0;width:80mm;size:80mm auto;}}' +
      '</style></head><body>' +
      logoHtml +
      '<h2>' + companyName + '</h2>' +
      (companyAddr   ? '<div class="sub">' + companyAddr + '</div>'         : '') +
      (companyPhone  ? '<div class="sub">Tel: ' + companyPhone + '</div>'   : '') +
      (companyEmail  ? '<div class="sub">' + companyEmail + '</div>'        : '') +
      '<div class="divider"></div>' +
      '<div class="row"><span>Invoice:</span><span>' + (sale.id || '') + '</span></div>' +
      '<div class="row"><span>Date:</span><span>' + dateStr + '</span></div>' +
      '<div class="row"><span>Customer:</span><span>' + custName + '</span></div>' +
      '<div class="row"><span>Payment:</span><span>' + posPayment + '</span></div>' +
      '<div class="divider"></div>' +
      lines +
      '<div class="divider"></div>' +
      '<div class="total-row"><span>TOTAL</span><span>' + ERP.formatCurrency(sale.totalAmount || 0) + '</span></div>' +
      '<div class="divider"></div>' +
      '<div class="footer">Thank you for your purchase!</div>' +
      '</body></html>'
    );

  } else {
    /* ── A4 INVOICE ────────────────────────────────────── */
    var itemsHtml = '';
    (sale.items || []).forEach(function(item) {
      var prod  = products.find(function(p){ return p.id === item.productId; });
      var name  = prod ? prod.name : 'Item';
      var mrp   = ERP.formatCurrency(prod ? prod.unitPrice : item.unitPrice);
      var price = ERP.formatCurrency(item.unitPrice || 0);
      var qty   = item.quantity || 1;
      var disc  = ERP.formatCurrency(item.discount || 0);
      var total = ERP.formatCurrency(item.totalLinePrice || 0);
      itemsHtml +=
        '<tr>' +
          '<td>' + name + '</td>' +
          '<td class="tc">' + mrp + '</td>' +
          '<td class="tc">' + price + '</td>' +
          '<td class="tc">' + qty + '</td>' +
          '<td class="tc">' + disc + '</td>' +
          '<td class="tr">' + total + '</td>' +
        '</tr>';
    });

    var logoBlock = logoSrc
      ? '<img src="' + logoSrc + '" style="max-height:72px;max-width:200px;object-fit:contain;margin-bottom:8px;display:block;" />'
      : '';

    win.document.write(
      '<html><head><title>Invoice</title><style>' +
      '@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");' +
      '*{box-sizing:border-box;margin:0;padding:0;}' +
      'body{font-family:Inter,Arial,sans-serif;font-size:13px;color:#1A1D2E;padding:36px 44px;}' +
      '.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;}' +
      '.company-info h1{font-size:1.4rem;font-weight:700;color:#1A1D2E;margin-bottom:4px;}' +
      '.company-info p{font-size:0.8rem;color:#6B7280;margin:1px 0;}' +
      '.invoice-meta{text-align:right;}' +
      '.invoice-meta .inv-no{font-size:1.1rem;font-weight:700;color:#3B4FE4;}' +
      '.invoice-meta p{font-size:0.8rem;color:#6B7280;margin:2px 0;}' +
      '.divider{border:none;border-top:1px solid #E8EAF0;margin:16px 0;}' +
      '.details-row{display:flex;gap:40px;margin-bottom:20px;}' +
      '.details-row .field label{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9CA3AF;}' +
      '.details-row .field p{font-size:0.85rem;font-weight:600;color:#1A1D2E;margin-top:2px;}' +
      'table{width:100%;border-collapse:collapse;margin:0 0 20px;}' +
      'thead tr{background:#F1F3FF;}' +
      'th{padding:9px 12px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#3B4FE4;text-align:left;}' +
      'th.tc,td.tc{text-align:center;}' +
      'th.tr,td.tr{text-align:right;}' +
      'td{padding:9px 12px;font-size:0.83rem;border-bottom:1px solid #F3F4F6;color:#374151;}' +
      'tbody tr:last-child td{border-bottom:none;}' +
      '.total-box{display:flex;justify-content:flex-end;margin-bottom:24px;}' +
      '.total-inner{background:#F1F3FF;border-radius:8px;padding:14px 24px;min-width:220px;}' +
      '.total-inner .t-row{display:flex;justify-content:space-between;font-size:0.83rem;color:#374151;margin:2px 0;}' +
      '.total-inner .t-grand{display:flex;justify-content:space-between;font-size:1rem;font-weight:700;color:#3B4FE4;margin-top:8px;padding-top:8px;border-top:1px solid #C7D2FE;}' +
      '.notes{font-size:0.78rem;color:#6B7280;border-top:1px solid #E8EAF0;padding-top:12px;}' +
      '.footer{text-align:center;font-size:0.75rem;color:#9CA3AF;margin-top:20px;}' +
      '@media print{@page{margin:15mm;}body{padding:0;}}' +
      '</style></head><body>' +

      '<div class="header">' +
        '<div class="company-info">' +
          logoBlock +
          '<h1>' + companyName + '</h1>' +
          (companyAddr  ? '<p>' + companyAddr  + '</p>' : '') +
          (companyPhone ? '<p>Tel: ' + companyPhone + '</p>' : '') +
          (companyEmail ? '<p>' + companyEmail + '</p>' : '') +
        '</div>' +
        '<div class="invoice-meta">' +
          '<div class="inv-no">INVOICE</div>' +
          '<p style="font-weight:600;color:#1A1D2E;">' + (sale.id || '') + '</p>' +
          '<p>' + dateStr + '</p>' +
        '</div>' +
      '</div>' +

      '<hr class="divider">' +

      '<div class="details-row">' +
        '<div class="field"><label>Customer</label><p>' + custName + '</p></div>' +
        '<div class="field"><label>Payment Mode</label><p>' + posPayment + '</p></div>' +
      '</div>' +

      '<table>' +
        '<thead><tr>' +
          '<th>Product / Description</th>' +
          '<th class="tc">MRP</th>' +
          '<th class="tc">Price</th>' +
          '<th class="tc">Qty</th>' +
          '<th class="tc">Discount</th>' +
          '<th class="tr">Total</th>' +
        '</tr></thead>' +
        '<tbody>' + itemsHtml + '</tbody>' +
      '</table>' +

      '<div class="total-box">' +
        '<div class="total-inner">' +
          '<div class="t-grand"><span>GRAND TOTAL</span><span>' + ERP.formatCurrency(sale.totalAmount || 0) + '</span></div>' +
        '</div>' +
      '</div>' +

      '<div class="notes">Notes: Thank you for your purchase. Please retain this invoice for your records.</div>' +
      '<div class="footer">Powered by Syncstack Solutions</div>' +
      '</body></html>'
    );
  }

  win.document.close();
  win.print();
}

// ── Barcode Scanner Support ──────────────────────────────────────────────────
// Scanners type the barcode then press Enter. On Enter we:
//   1. Try exact barcode match, then exact SKU match (scanner use-case)
//   2. If exactly 1 product in the filtered list (keyboard typing use-case)
//   3. Add to cart & clear search; show brief feedback
function posScannerEnter(e) {
  if (e.key !== 'Enter') return;
  e.preventDefault();
  var val = (document.getElementById('pos-search').value || '').trim();
  if (!val) return;
  var lower = val.toLowerCase();
  var all = getCompanyProducts();
  // Prefer exact barcode or SKU match (scanner sends full code)
  var match = all.find(function(p) {
    return (p.barcode || '').toLowerCase() === lower || p.sku.toLowerCase() === lower;
  });
  // Fallback: if only 1 product visible in the filtered grid, use it
  if (!match) {
    var visible = all.filter(function(p) {
      return p.name.toLowerCase().indexOf(lower) !== -1 ||
             p.sku.toLowerCase().indexOf(lower) !== -1 ||
             (p.barcode || '').toLowerCase().indexOf(lower) !== -1;
    });
    if (visible.length === 1) match = visible[0];
  }
  if (match) {
    if ((match.currentStock || 0) <= 0) {
      posScanFeedback('Out of stock: ' + match.name, false);
      return;
    }
    addToCart(match.id);
    document.getElementById('pos-search').value = '';
    renderProducts();
    posScanFeedback('\u2713 Added: ' + match.name, true);
  } else {
    posScanFeedback('\u2717 Not found: ' + val, false);
  }
}
function posScanFeedback(msg, ok) {
  var el = document.getElementById('pos-scan-feedback');
  if (!el) return;
  el.textContent = msg;
  el.style.color = ok ? '#059669' : '#EF4444';
  el.style.opacity = '1';
  clearTimeout(el._t);
  el._t = setTimeout(function() { el.style.opacity = '0'; }, 2500);
}
