var salesChart, purchChart, finChart;
window.ERP.onReady = function(){ renderSalesReport(); renderPurchaseReport(); renderInventoryReport(); renderFinancialReport(); };
// Build a ms timestamp from a date string + optional time string.
// If no date, returns null. If no time, defaults to start-of-day or end-of-day.
function buildTs(dateVal, timeVal, isEnd){
    if(!dateVal) return null;
    var t = timeVal || (isEnd ? '23:59:59' : '00:00:00');
    return new Date(dateVal + 'T' + t).getTime();
}
function groupByDate(items, dateKey){
    var map={};
    items.forEach(function(it){
        var d=new Date(it[dateKey]||it.createdAt||it.date).toISOString().split('T')[0];
        if(!map[d]) map[d]={date:d,count:0,total:0};
        map[d].count++; map[d].total+=(it.totalAmount||it.amount||0);
    });
    return Object.values(map).sort(function(a,b){return a.date.localeCompare(b.date);});
}
function renderSalesReport(){
    var state=window.ERP.state;
    var df=document.getElementById('salesFrom').value, dt=document.getElementById('salesTo').value;
    var dft=document.getElementById('salesFromTime').value, dtt=document.getElementById('salesToTime').value;
    var fromTs=buildTs(df, dft, false), toTs=buildTs(dt, dtt, true);
    var sales=(state.sales||[]).filter(function(s){
        var ts=s.createdAt||s.date||0;
        if(fromTs && ts<fromTs) return false;
        if(toTs && ts>toTs) return false;
        return true;
    });
    var grouped=groupByDate(sales,'createdAt');
    var html='',totalRev=0,totalOrd=0;
    grouped.forEach(function(g){
        html+='<tr><td>'+g.date+'</td><td class="text-end">'+g.count+'</td><td class="text-end">'+ERP.formatCurrency(g.total)+'</td></tr>';
        totalRev+=g.total; totalOrd+=g.count;
    });
    if(!grouped.length) html='<tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-chart-bar fs-1 d-block mb-2"></i>No sales found for selected period</td></tr>';
    else html+='<tr class="rpt-total-row"><td>Total</td><td class="text-end">'+totalOrd+'</td><td class="text-end">'+ERP.formatCurrency(totalRev)+'</td></tr>';
    document.getElementById('salesReportBody').innerHTML=html;
    if(salesChart) salesChart.destroy();
    if(grouped.length){
        salesChart=new ApexCharts(document.getElementById('salesChartContainer'),{
            chart:{type:'area',height:268,toolbar:{show:false},fontFamily:'Inter,sans-serif'},
            series:[{name:'Revenue',data:grouped.map(function(g){return g.total;})}],
            xaxis:{categories:grouped.map(function(g){return g.date;})},
            colors:['#3B4FE4'],stroke:{curve:'smooth'},fill:{type:'gradient',gradient:{opacityFrom:0.3,opacityTo:0.05}}
        });
        salesChart.render();
    }
}
function renderPurchaseReport(){
    var state=window.ERP.state;
    var df=document.getElementById('purchFrom').value, dt=document.getElementById('purchTo').value;
    var dft=document.getElementById('purchFromTime').value, dtt=document.getElementById('purchToTime').value;
    var fromTs=buildTs(df, dft, false), toTs=buildTs(dt, dtt, true);
    var pos=(state.purchaseOrders||[]).filter(function(po){
        var ts=po.createdAt||po.date||0;
        if(fromTs && ts<fromTs) return false;
        if(toTs && ts>toTs) return false;
        return true;
    });
    var grouped=groupByDate(pos,'createdAt');
    var html='',totalAmt=0,totalOrd=0;
    grouped.forEach(function(g){
        html+='<tr><td>'+g.date+'</td><td class="text-end">'+g.count+'</td><td class="text-end">'+ERP.formatCurrency(g.total)+'</td></tr>';
        totalAmt+=g.total; totalOrd+=g.count;
    });
    html+='<tr class="rpt-total-row"><td>Total</td><td class="text-end">'+totalOrd+'</td><td class="text-end">'+ERP.formatCurrency(totalAmt)+'</td></tr>';
    document.getElementById('purchReportBody').innerHTML=html;
    if(purchChart) purchChart.destroy();
    if(grouped.length){
        purchChart=new ApexCharts(document.getElementById('purchChartContainer'),{
            chart:{type:'bar',height:268,toolbar:{show:false},fontFamily:'Inter,sans-serif'},
            series:[{name:'Amount',data:grouped.map(function(g){return g.total;})}],
            xaxis:{categories:grouped.map(function(g){return g.date;})},
            colors:['#059669']
        });
        purchChart.render();
    }
}
function renderInventoryReport(){
    var state=window.ERP.state, products=state.products||[];
    var html='',totalValue=0;
    products.forEach(function(p){
        var value=(p.currentStock||0)*(p.unitCost||0);
        totalValue+=value;
        html+='<tr><td class="fw-bold">'+p.name+'</td><td>'+p.sku+'</td><td>'+(p.category||'—')+'</td>';
        html+='<td class="text-end">'+p.currentStock+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(p.unitCost||0)+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(value)+'</td></tr>';
    });
    document.getElementById('invReportBody').innerHTML=html||'<tr><td colspan="6" class="text-center text-muted py-5">No products found</td></tr>';
    document.getElementById('invReportFoot').innerHTML='<tr><td colspan="5" class="fw-bold">Total Stock Value</td><td class="text-end fw-bold">'+ERP.formatCurrency(totalValue)+'</td></tr>';
}
/* ====== Reports Tab ====== */
function rptOpen(type) {
  document.getElementById('rpt-tiles-view').classList.add('d-none');
  if (type === 'product') {
    document.getElementById('rpt-product-panel').classList.remove('d-none');
    // populate categories
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var cats = (state.categories || []).filter(function(c){ return !coId || c.companyId === coId; });
    var sel = document.getElementById('rptProdCategory');
    sel.innerHTML = '<option value="">All Categories</option>';
    cats.forEach(function(c){ sel.innerHTML += '<option value="'+c.id+'">'+c.name+'</option>'; });
    runProductReport();
  } else if (type === 'customer') {
    document.getElementById('rpt-customer-panel').classList.remove('d-none');
    runCustomerReport();
  } else if (type === 'vendor') {
    document.getElementById('rpt-vendor-panel').classList.remove('d-none');
    runVendorReport();
  } else if (type === 'sales') {
    document.getElementById('rpt-sales-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var custs = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Customer'; });
    custs.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptSalesCustomer');
    sel.innerHTML = '<option value="">All Customers</option>';
    custs.forEach(function(c){ sel.innerHTML += '<option value="'+c.id+'">'+c.name+'</option>'; });
    runSalesReport();
  } else if (type === 'purchase') {
    document.getElementById('rpt-purchase-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var vends = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Vendor'; });
    vends.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptPurchVendor');
    sel.innerHTML = '<option value="">All Vendors</option>';
    vends.forEach(function(v){ sel.innerHTML += '<option value="'+v.id+'">'+v.name+'</option>'; });
    runPurchaseReport();
  } else if (type === 'salesReturn') {
    document.getElementById('rpt-salesReturn-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var custs = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Customer'; });
    custs.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptSReturnCustomer');
    sel.innerHTML = '<option value="">All Customers</option>';
    custs.forEach(function(c){ sel.innerHTML += '<option value="'+c.id+'">'+c.name+'</option>'; });
    runSalesReturnReport();
  } else if (type === 'purchaseReturn') {
    document.getElementById('rpt-purchaseReturn-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var vends = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Vendor'; });
    vends.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptPReturnVendor');
    sel.innerHTML = '<option value="">All Vendors</option>';
    vends.forEach(function(v){ sel.innerHTML += '<option value="'+v.id+'">'+v.name+'</option>'; });
    runPurchaseReturnReport();
  } else if (type === 'salesByCustomer') {
    document.getElementById('rpt-salesByCustomer-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var custs = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Customer'; });
    custs.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptSBCCustomer');
    sel.innerHTML = '<option value="">All Customers</option>';
    custs.forEach(function(c){ sel.innerHTML += '<option value="'+c.id+'">'+c.name+'</option>'; });
    runSalesByCustomerReport();
  } else if (type === 'purchaseByVendor') {
    document.getElementById('rpt-purchaseByVendor-panel').classList.remove('d-none');
    var state = window.ERP.state;
    var coId = (state.currentUser || {}).companyId;
    var vends = (state.parties || []).filter(function(p){ return (!coId || p.companyId === coId) && p.type === 'Vendor'; });
    vends.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
    var sel = document.getElementById('rptPBVVendor');
    sel.innerHTML = '<option value="">All Vendors</option>';
    vends.forEach(function(v){ sel.innerHTML += '<option value="'+v.id+'">'+v.name+'</option>'; });
    runPurchaseByVendorReport();
  } else if (type === 'profitLoss') {
    document.getElementById('rpt-profitLoss-panel').classList.remove('d-none');
    rptPlSetPeriod('month');
    runProfitLoss();
  } else if (type === 'balanceSheet') {
    document.getElementById('rpt-balanceSheet-panel').classList.remove('d-none');
    rptBsSetDate('today');
    runBalanceSheet();
  }
}
function rptBack() {
  ['rpt-product-panel','rpt-customer-panel','rpt-vendor-panel','rpt-sales-panel',
   'rpt-purchase-panel','rpt-salesReturn-panel','rpt-purchaseReturn-panel',
   'rpt-salesByCustomer-panel','rpt-purchaseByVendor-panel',
   'rpt-profitLoss-panel','rpt-balanceSheet-panel']
    .forEach(function(id){ document.getElementById(id).classList.add('d-none'); });
  document.getElementById('rpt-tiles-view').classList.remove('d-none');
}
function rptProdClear() {
  document.getElementById('rptProdSearch').value = '';
  document.getElementById('rptProdCategory').value = '';
  document.getElementById('rptProdStock').value = '';
  document.getElementById('rptProdDate').value = '';
  runProductReport();
}
function rptCustClear() {
  document.getElementById('rptCustSearch').value = '';
  document.getElementById('rptCustBalance').value = '';
  runCustomerReport();
}
function rptVendClear() {
  document.getElementById('rptVendSearch').value = '';
  document.getElementById('rptVendBalance').value = '';
  runVendorReport();
}
function rptSalesClear() {
  document.getElementById('rptSalesSearch').value = '';
  document.getElementById('rptSalesCustomer').value = '';
  document.getElementById('rptSalesPayment').value = '';
  document.getElementById('rptSalesFrom').value = '';
  document.getElementById('rptSalesTo').value = '';
  runSalesReport();
}
function rptPurchClear() {
  document.getElementById('rptPurchSearch').value = '';
  document.getElementById('rptPurchVendor').value = '';
  document.getElementById('rptPurchStatus').value = '';
  document.getElementById('rptPurchFrom').value = '';
  document.getElementById('rptPurchTo').value = '';
  runPurchaseReport();
}
function rptSReturnClear() {
  document.getElementById('rptSReturnSearch').value = '';
  document.getElementById('rptSReturnCustomer').value = '';
  document.getElementById('rptSReturnFrom').value = '';
  document.getElementById('rptSReturnTo').value = '';
  runSalesReturnReport();
}
function rptPReturnClear() {
  document.getElementById('rptPReturnSearch').value = '';
  document.getElementById('rptPReturnVendor').value = '';
  document.getElementById('rptPReturnFrom').value = '';
  document.getElementById('rptPReturnTo').value = '';
  runPurchaseReturnReport();
}
function rptSBCClear() {
  document.getElementById('rptSBCCustomer').value = '';
  document.getElementById('rptSBCPayment').value = '';
  document.getElementById('rptSBCFrom').value = '';
  document.getElementById('rptSBCTo').value = '';
  runSalesByCustomerReport();
}
function rptPBVClear() {
  document.getElementById('rptPBVVendor').value = '';
  document.getElementById('rptPBVStatus').value = '';
  document.getElementById('rptPBVFrom').value = '';
  document.getElementById('rptPBVTo').value = '';
  runPurchaseByVendorReport();
}
function runProductReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var products = (state.products || []).filter(function(p){ return !coId || p.companyId === coId; });
  var search = (document.getElementById('rptProdSearch').value || '').trim().toLowerCase();
  var catId  = document.getElementById('rptProdCategory').value;
  var stockF = document.getElementById('rptProdStock').value;
  var asOf   = document.getElementById('rptProdDate').value;
  var lowThr = 10; // default low-stock threshold

  // Build category map
  var catMap = {};
  (state.categories || []).forEach(function(c){ catMap[c.id] = c.name; });

  // Date filter: products created on or before asOf
  var asOfTs = asOf ? new Date(asOf + 'T23:59:59').getTime() : null;
  if (asOfTs) products = products.filter(function(p){ return (p.createdAt || 0) <= asOfTs; });

  // Search filter
  if (search) products = products.filter(function(p){
    return p.name.toLowerCase().indexOf(search) !== -1 || (p.sku || '').toLowerCase().indexOf(search) !== -1;
  });

  // Category filter
  if (catId) products = products.filter(function(p){ return p.categoryId === catId; });

  // Stock filter
  if (stockF === 'in')  products = products.filter(function(p){ return (p.currentStock||0) > 0; });
  if (stockF === 'low') products = products.filter(function(p){ return (p.currentStock||0) > 0 && (p.currentStock||0) <= lowThr; });
  if (stockF === 'out') products = products.filter(function(p){ return (p.currentStock||0) <= 0; });

  // Sort by name
  products.sort(function(a,b){ return a.name.localeCompare(b.name); });

  var html = '', totalValue = 0, totalStock = 0;
  products.forEach(function(p, i){
    var stock = p.currentStock || 0;
    var cost  = p.unitCost || 0;
    var price = p.unitPrice || 0;
    var val   = stock * cost;
    totalValue += val; totalStock += stock;
    var badge = stock <= 0
      ? '<span class="erp-stock-badge-out">Out</span>'
      : stock <= lowThr
        ? '<span class="erp-stock-badge-low">Low</span>'
        : '';
    html += '<tr>';
    html += '<td class="text-muted erp-text-sm">'+(i+1)+'</td>';
    html += '<td class="text-muted erp-text-82">'+(p.itemNumber||'—')+'</td>';
    html += '<td class="fw-semibold">'+p.name+'</td>';
    html += '<td class="text-muted erp-text-82">'+(p.sku||'—')+'</td>';
    html += '<td>'+(catMap[p.categoryId]||p.category||'—')+'</td>';
    html += '<td class="text-end">'+ERP.formatCurrency(cost)+'</td>';
    html += '<td class="text-end">'+ERP.formatCurrency(price)+'</td>';
    html += '<td class="text-end">'+stock+' '+badge+'</td>';
    html += '<td class="text-end" class="fw-semibold">'+ERP.formatCurrency(val)+'</td>';
    html += '</tr>';
  });
  if (!products.length) {
    html = '<tr><td colspan="9" class="text-center text-muted py-5"><i class="ti ti-package d-block mb-2" class="fs-2"></i>No products match the selected filters</td></tr>';
  }
  document.getElementById('rptProductBody').innerHTML = html;
  document.getElementById('rptProductFoot').innerHTML = products.length
    ? '<tr><td colspan="7" class="fw-bold">Total &nbsp;<span class="rpt-count-span">('+products.length+' products)</span></td>'
      +'<td class="text-end fw-bold">'+totalStock+'</td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(totalValue)+'</td></tr>'
    : '';
  document.getElementById('rptProductSummary').innerHTML = products.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+products.length+'</b> products</span>'
      +'<span>Total Stock: <b>'+totalStock+'</b></span>'
      +'<span>Total Value: <b>'+ERP.formatCurrency(totalValue)+'</b></span></div>'
    : '';

  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search) parts.push('Search: '+search);
  if (catId)  parts.push('Category: '+(catMap[catId]||catId));
  if (stockF) parts.push('Stock: '+stockF);
  if (asOf)   parts.push('As of: '+asOf);
  if (!parts.length) parts.push('All products');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptPrintParams').textContent = parts.join('  |  ');
}
/* ====== Product Report Exports ====== */
function getProductReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var products = (state.products || []).filter(function(p){ return !coId || p.companyId === coId; });
  var search = (document.getElementById('rptProdSearch').value || '').trim().toLowerCase();
  var catId  = document.getElementById('rptProdCategory').value;
  var stockF = document.getElementById('rptProdStock').value;
  var asOf   = document.getElementById('rptProdDate').value;
  var lowThr = 10;
  var catMap = {};
  (state.categories || []).forEach(function(c){ catMap[c.id] = c.name; });
  var asOfTs = asOf ? new Date(asOf + 'T23:59:59').getTime() : null;
  if (asOfTs) products = products.filter(function(p){ return (p.createdAt||0) <= asOfTs; });
  if (search) products = products.filter(function(p){ return p.name.toLowerCase().indexOf(search)!==-1 || (p.sku||'').toLowerCase().indexOf(search)!==-1; });
  if (catId)  products = products.filter(function(p){ return p.categoryId === catId; });
  if (stockF === 'in')  products = products.filter(function(p){ return (p.currentStock||0) > 0; });
  if (stockF === 'low') products = products.filter(function(p){ return (p.currentStock||0) > 0 && (p.currentStock||0) <= lowThr; });
  if (stockF === 'out') products = products.filter(function(p){ return (p.currentStock||0) <= 0; });
  products.sort(function(a,b){ return a.name.localeCompare(b.name); });
  var totalValue = 0, totalStock = 0;
  products.forEach(function(p){ totalStock += (p.currentStock||0); totalValue += (p.currentStock||0)*(p.unitCost||0); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  var companyName = (company.info && company.info.name) || company.name || '';
  return { products: products, catMap: catMap, totalStock: totalStock, totalValue: totalValue, companyName: companyName };
}

function exportProductExcel() {
  var d = getProductReportData();
  var headers = ['#', 'Product Name', 'Item No.', 'Category', 'Cost Price', 'Sale Price', 'Stock', 'Stock Value'];
  var rows = d.products.map(function(p, i){
    return [
      i+1, p.name, p.sku||'',
      d.catMap[p.categoryId]||p.category||'',
      p.unitCost||0, p.unitPrice||0,
      p.currentStock||0,
      (p.currentStock||0)*(p.unitCost||0)
    ];
  });
  rows.push(['', 'TOTAL ('+d.products.length+' products)', '', '', '', '', d.totalStock, d.totalValue]);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  // Column widths
  ws['!cols'] = [{wch:4},{wch:28},{wch:12},{wch:16},{wch:12},{wch:12},{wch:8},{wch:14}];
  // Style header row (bold) — basic xlsx doesn't support full styling but we set the range
  ws['!ref'] = XLSX.utils.encode_range({s:{r:0,c:0},e:{r:rows.length,c:7}});
  XLSX.utils.book_append_sheet(wb, ws, 'Product Report');
  XLSX.writeFile(wb, 'Product_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}

/* ── Shared PDF header helper ── */
function pdfMakeHeader(doc, companyName, reportTitle) {
  var displayName = companyName || 'Company';
  var pageW = doc.internal.pageSize.getWidth();
  // Company name — large, bold, centered, uppercase
  doc.setFontSize(18); doc.setFont('helvetica','bold'); doc.setTextColor(0,0,0);
  doc.text(displayName.toUpperCase(), pageW/2, 16, { align:'center' });
  // Thick divider line below company name
  doc.setDrawColor(0,0,0); doc.setLineWidth(0.7);
  doc.line(14, 19, pageW-14, 19);
  // Report title — smaller, normal weight
  doc.setFontSize(9); doc.setFont('helvetica','bold'); doc.setTextColor(50,50,50);
  doc.text(reportTitle.toUpperCase(), pageW/2, 25, { align:'center' });
  // Generated date
  doc.setFontSize(7); doc.setFont('helvetica','normal'); doc.setTextColor(120,120,120);
  doc.text('Generated: '+new Date().toLocaleString(), pageW/2, 31, { align:'center' });
  // Thin divider below params
  doc.setDrawColor(180,180,180); doc.setLineWidth(0.3);
  doc.line(14, 33, pageW-14, 33);
  doc.setTextColor(0,0,0);
  return 37; // startY for the table
}

function exportProductPDF() {
  var d = getProductReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Product Report');
  var rows = d.products.map(function(p, i){
    return [
      i+1, p.name, p.sku||'',
      d.catMap[p.categoryId]||p.category||'—',
      ERP.formatCurrency(p.unitCost||0),
      ERP.formatCurrency(p.unitPrice||0),
      p.currentStock||0,
      ERP.formatCurrency((p.currentStock||0)*(p.unitCost||0))
    ];
  });
  doc.autoTable({
    startY: startY,
    head: [['#','Product Name','Item No.','Category','Cost Price','Sale Price','Stock','Stock Value']],
    body: rows,
    foot: [['','TOTAL ('+d.products.length+' products)','','','','',d.totalStock, ERP.formatCurrency(d.totalValue)]],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:8, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:8, fontStyle:'bold' },
    bodyStyles: { fontSize:8 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: {
      0:{halign:'center', cellWidth:8},
      4:{halign:'right'}, 5:{halign:'right'},
      6:{halign:'right'}, 7:{halign:'right'}
    },
    margin: { left:14, right:14 }
  });
  doc.save('Product_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Customer Report ====== */
function runCustomerReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var parties = (state.parties || []).filter(function(p){
    return (!coId || p.companyId === coId) && p.type === 'Customer';
  });
  var search = (document.getElementById('rptCustSearch').value || '').trim().toLowerCase();
  var balF = document.getElementById('rptCustBalance').value;
  if (search) parties = parties.filter(function(p){
    return (p.name||'').toLowerCase().indexOf(search) !== -1 ||
           (p.code||'').toLowerCase().indexOf(search) !== -1;
  });
  if (balF === 'positive') parties = parties.filter(function(p){ return (p.currentBalance||0) > 0; });
  if (balF === 'negative') parties = parties.filter(function(p){ return (p.currentBalance||0) < 0; });
  if (balF === 'zero')     parties = parties.filter(function(p){ return (p.currentBalance||0) === 0; });
  parties.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });

  var html = '', totalBalance = 0, totalCreditLimit = 0;
  parties.forEach(function(p, i){
    var bal = p.currentBalance || 0;
    var cl  = p.creditLimit || 0;
    totalBalance += bal; totalCreditLimit += cl;
    var balColor = bal > 0 ? '#DC2626' : bal < 0 ? '#059669' : '#64748b';
    html += '<tr>';
    html += '<td class="text-muted">'+(i+1)+'</td>';
    html += '<td class="text-muted">'+(p.code||'—')+'</td>';
    html += '<td class="fw-semibold">'+(p.name||'—')+'</td>';
    html += '<td>'+(p.phone||'—')+'</td>';
    html += '<td>'+(p.email||'—')+'</td>';
    html += '<td>'+(p.address||'—')+'</td>';
    html += '<td>'+(p.paymentTerms||'—')+'</td>';
    html += '<td class="text-end">'+ERP.formatCurrency(cl)+'</td>';
    html += '<td class="text-end">'+ERP.formatCurrency(p.openingBalance||0)+'</td>';
    html += '<td class="text-muted">'+(p.bankDetails||'—')+'</td>';
    html += '<td class="text-end fw-bold" style="color:'+balColor+'">'+ERP.formatCurrency(bal)+'</td>';
    html += '</tr>';
  });
  if (!parties.length) {
    html = '<tr><td colspan="11" class="text-center text-muted py-5"><i class="ti ti-users d-block mb-2" class="fs-2"></i>No customers match the selected filters</td></tr>';
  }
  document.getElementById('rptCustomerBody').innerHTML = html;
  document.getElementById('rptCustomerFoot').innerHTML = parties.length
    ? '<tr>'
      +'<td colspan="7" class="fw-bold">Total &nbsp;<span class="rpt-count-span">('+parties.length+' customers)</span></td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(totalCreditLimit)+'</td>'
      +'<td></td><td></td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(totalBalance)+'</td>'
      +'</tr>'
    : '';
  document.getElementById('rptCustomerSummary').innerHTML = parties.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+parties.length+'</b> customers</span>'
      +'<span>Total Credit Limit: <b>'+ERP.formatCurrency(totalCreditLimit)+'</b></span>'
      +'<span>Total Balance: <b>'+ERP.formatCurrency(totalBalance)+'</b></span></div>'
    : '';
  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptCustPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search) parts.push('Search: '+search);
  if (balF)   parts.push('Balance: '+balF);
  if (!parts.length) parts.push('All customers');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptCustPrintParams').textContent = parts.join('  |  ');
}
function getCustomerReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var parties = (state.parties || []).filter(function(p){
    return (!coId || p.companyId === coId) && p.type === 'Customer';
  });
  var search = (document.getElementById('rptCustSearch').value || '').trim().toLowerCase();
  var balF = document.getElementById('rptCustBalance').value;
  if (search) parties = parties.filter(function(p){
    return (p.name||'').toLowerCase().indexOf(search) !== -1 ||
           (p.code||'').toLowerCase().indexOf(search) !== -1;
  });
  if (balF === 'positive') parties = parties.filter(function(p){ return (p.currentBalance||0) > 0; });
  if (balF === 'negative') parties = parties.filter(function(p){ return (p.currentBalance||0) < 0; });
  if (balF === 'zero')     parties = parties.filter(function(p){ return (p.currentBalance||0) === 0; });
  parties.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
  var totalBalance = 0, totalCreditLimit = 0;
  parties.forEach(function(p){ totalBalance += (p.currentBalance||0); totalCreditLimit += (p.creditLimit||0); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { parties: parties, totalBalance: totalBalance, totalCreditLimit: totalCreditLimit,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportCustomerExcel() {
  var d = getCustomerReportData();
  var headers = ['#','Code','Name','Phone','Email','Address','Payment Terms','Credit Limit','Opening Balance','Bank Details','Balance'];
  var rows = d.parties.map(function(p, i){
    return [
      i+1, p.code||'', p.name||'',
      p.phone||'', p.email||'', p.address||'',
      p.paymentTerms||'', p.creditLimit||0,
      p.openingBalance||0, p.bankDetails||'',
      p.currentBalance||0
    ];
  });
  rows.push(['','TOTAL ('+d.parties.length+' customers)','','','','','',d.totalCreditLimit,'','',d.totalBalance]);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:4},{wch:12},{wch:24},{wch:14},{wch:22},{wch:24},{wch:14},{wch:14},{wch:14},{wch:22},{wch:14}];
  XLSX.utils.book_append_sheet(wb, ws, 'Customer Report');
  XLSX.writeFile(wb, 'Customer_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportCustomerPDF() {
  var d = getCustomerReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Customer List Report');
  var rows = d.parties.map(function(p, i){
    return [
      i+1, p.code||'', p.name||'',
      p.phone||'', p.email||'', p.address||'',
      p.paymentTerms||'',
      ERP.formatCurrency(p.creditLimit||0),
      ERP.formatCurrency(p.openingBalance||0),
      p.bankDetails||'',
      ERP.formatCurrency(p.currentBalance||0)
    ];
  });
  doc.autoTable({
    startY: startY,
    head: [['#','Code','Name','Phone','Email','Address','Terms','Credit Limit','Opening Bal.','Bank Details','Balance']],
    body: rows,
    foot: [['','TOTAL ('+d.parties.length+' customers)','','','','','',ERP.formatCurrency(d.totalCreditLimit),'','',ERP.formatCurrency(d.totalBalance)]],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: {
      0:{halign:'center', cellWidth:7},
      7:{halign:'right'}, 8:{halign:'right'}, 10:{halign:'right'}
    },
    margin: { left:10, right:10 }
  });
  doc.save('Customer_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Vendor Report ====== */
function runVendorReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var parties = (state.parties || []).filter(function(p){
    return (!coId || p.companyId === coId) && p.type === 'Vendor';
  });
  var search = (document.getElementById('rptVendSearch').value || '').trim().toLowerCase();
  var balF = document.getElementById('rptVendBalance').value;
  if (search) parties = parties.filter(function(p){
    return (p.name||'').toLowerCase().indexOf(search) !== -1 ||
           (p.code||'').toLowerCase().indexOf(search) !== -1;
  });
  if (balF === 'positive') parties = parties.filter(function(p){ return (p.currentBalance||0) > 0; });
  if (balF === 'negative') parties = parties.filter(function(p){ return (p.currentBalance||0) < 0; });
  if (balF === 'zero')     parties = parties.filter(function(p){ return (p.currentBalance||0) === 0; });
  parties.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });

  var html = '', totalBalance = 0, totalCreditLimit = 0;
  parties.forEach(function(p, i){
    var bal = p.currentBalance || 0;
    var cl  = p.creditLimit || 0;
    totalBalance += bal; totalCreditLimit += cl;
    var balColor = bal > 0 ? '#DC2626' : bal < 0 ? '#059669' : '#64748b';
    html += '<tr>';
    html += '<td class="text-muted erp-text-sm">'+(i+1)+'</td>';
    html += '<td class="text-muted erp-text-82">'+(p.code||'—')+'</td>';
    html += '<td class="fw-semibold">'+(p.name||'—')+'</td>';
    html += '<td>'+(p.phone||'—')+'</td>';
    html += '<td class="erp-text-82">'+(p.email||'—')+'</td>';
    html += '<td class="erp-text-82">'+(p.address||'—')+'</td>';
    html += '<td>'+(p.paymentTerms||'—')+'</td>';
    html += '<td class="text-end">'+ERP.formatCurrency(cl)+'</td>';
    html += '<td class="text-end">'+ERP.formatCurrency(p.openingBalance||0)+'</td>';
    html += '<td class="rpt-bank-cell">'+(p.bankDetails||'—')+'</td>';
    html += '<td class="text-end fw-bold" style="color:'+balColor+'">'+ERP.formatCurrency(bal)+'</td>';
    html += '</tr>';
  });
  if (!parties.length) {
    html = '<tr><td colspan="11" class="text-center text-muted py-5"><i class="ti ti-building-store d-block mb-2" class="fs-2"></i>No vendors match the selected filters</td></tr>';
  }
  document.getElementById('rptVendorBody').innerHTML = html;
  document.getElementById('rptVendorFoot').innerHTML = parties.length
    ? '<tr>'
      +'<td colspan="7" class="fw-bold">Total &nbsp;<span class="rpt-count-span">('+parties.length+' vendors)</span></td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(totalCreditLimit)+'</td>'
      +'<td></td><td></td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(totalBalance)+'</td>'
      +'</tr>'
    : '';
  document.getElementById('rptVendorSummary').innerHTML = parties.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+parties.length+'</b> vendors</span>'
      +'<span>Total Credit Limit: <b>'+ERP.formatCurrency(totalCreditLimit)+'</b></span>'
      +'<span>Total Balance: <b>'+ERP.formatCurrency(totalBalance)+'</b></span></div>'
    : '';
  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptVendPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search) parts.push('Search: '+search);
  if (balF)   parts.push('Balance: '+balF);
  if (!parts.length) parts.push('All vendors');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptVendPrintParams').textContent = parts.join('  |  ');
}
function getVendorReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var parties = (state.parties || []).filter(function(p){
    return (!coId || p.companyId === coId) && p.type === 'Vendor';
  });
  var search = (document.getElementById('rptVendSearch').value || '').trim().toLowerCase();
  var balF = document.getElementById('rptVendBalance').value;
  if (search) parties = parties.filter(function(p){
    return (p.name||'').toLowerCase().indexOf(search) !== -1 ||
           (p.code||'').toLowerCase().indexOf(search) !== -1;
  });
  if (balF === 'positive') parties = parties.filter(function(p){ return (p.currentBalance||0) > 0; });
  if (balF === 'negative') parties = parties.filter(function(p){ return (p.currentBalance||0) < 0; });
  if (balF === 'zero')     parties = parties.filter(function(p){ return (p.currentBalance||0) === 0; });
  parties.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
  var totalBalance = 0, totalCreditLimit = 0;
  parties.forEach(function(p){ totalBalance += (p.currentBalance||0); totalCreditLimit += (p.creditLimit||0); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { parties: parties, totalBalance: totalBalance, totalCreditLimit: totalCreditLimit,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportVendorExcel() {
  var d = getVendorReportData();
  var headers = ['#','Code','Name','Phone','Email','Address','Payment Terms','Credit Limit','Opening Balance','Bank Details','Balance'];
  var rows = d.parties.map(function(p, i){
    return [
      i+1, p.code||'', p.name||'',
      p.phone||'', p.email||'', p.address||'',
      p.paymentTerms||'', p.creditLimit||0,
      p.openingBalance||0, p.bankDetails||'',
      p.currentBalance||0
    ];
  });
  rows.push(['','TOTAL ('+d.parties.length+' vendors)','','','','','',d.totalCreditLimit,'','',d.totalBalance]);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:4},{wch:12},{wch:24},{wch:14},{wch:22},{wch:24},{wch:14},{wch:14},{wch:14},{wch:22},{wch:14}];
  XLSX.utils.book_append_sheet(wb, ws, 'Vendor Report');
  XLSX.writeFile(wb, 'Vendor_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportVendorPDF() {
  var d = getVendorReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Vendor List Report');
  var rows = d.parties.map(function(p, i){
    return [
      i+1, p.code||'', p.name||'',
      p.phone||'', p.email||'', p.address||'',
      p.paymentTerms||'',
      ERP.formatCurrency(p.creditLimit||0),
      ERP.formatCurrency(p.openingBalance||0),
      p.bankDetails||'',
      ERP.formatCurrency(p.currentBalance||0)
    ];
  });
  doc.autoTable({
    startY: startY,
    head: [['#','Code','Name','Phone','Email','Address','Terms','Credit Limit','Opening Bal.','Bank Details','Balance']],
    body: rows,
    foot: [['','TOTAL ('+d.parties.length+' vendors)','','','','','',ERP.formatCurrency(d.totalCreditLimit),'','',ERP.formatCurrency(d.totalBalance)]],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: {
      0:{halign:'center', cellWidth:7},
      7:{halign:'right'}, 8:{halign:'right'}, 10:{halign:'right'}
    },
    margin: { left:10, right:10 }
  });
  doc.save('Vendor_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Detailed Purchase Report ====== */
function runPurchaseReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var orders = (state.purchaseOrders || []).filter(function(po){ return !coId || po.companyId === coId; });

  var search   = (document.getElementById('rptPurchSearch').value || '').trim().toLowerCase();
  var vendId   = document.getElementById('rptPurchVendor').value;
  var status   = document.getElementById('rptPurchStatus').value;
  var dateFrom = document.getElementById('rptPurchFrom').value;
  var dateTo   = document.getElementById('rptPurchTo').value;

  var fromTs = dateFrom ? new Date(dateFrom + 'T00:00:00').getTime() : null;
  var toTs   = dateTo   ? new Date(dateTo   + 'T23:59:59').getTime() : null;

  if (fromTs)  orders = orders.filter(function(po){ return (po.createdAt||0) >= fromTs; });
  if (toTs)    orders = orders.filter(function(po){ return (po.createdAt||0) <= toTs; });
  if (search)  orders = orders.filter(function(po){ return po.id.toLowerCase().indexOf(search) !== -1; });
  if (vendId)  orders = orders.filter(function(po){ return po.vendorId === vendId; });
  if (status)  orders = orders.filter(function(po){ return (po.status||'') === status; });

  orders.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });

  var prodMap = {};
  (state.products || []).forEach(function(p){ prodMap[p.id] = p.name || p.id; });
  var partyMap = {};
  (state.parties || []).forEach(function(p){ partyMap[p.id] = p.name || p.id; });

  var statusColors = {
    'Draft':               'rpt-badge-grey',
    'Partially Received':  'rpt-badge-amber',
    'Received':            'rpt-badge-green',
    'Cancelled':           'rpt-badge-red',
    'Returned':            'rpt-badge-red'
  };

  var html = '', totalOrders = 0, totalItems = 0, grandTotal = 0;

  orders.forEach(function(po) {
    totalOrders++;
    grandTotal += po.totalAmount || 0;
    var items = po.items || [];
    totalItems += items.length;

    var vendName = po.vendorId ? (partyMap[po.vendorId] || po.vendorId) : '—';
    var dt = po.createdAt ? new Date(po.createdAt) : null;
    var dateStr = dt ? dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';

    var stBadge = '<span class="rpt-badge '+(statusColors[po.status]||'rpt-badge-grey')+'">'+po.status+'</span>';
    var returnBadge = po.returnStatus === 'full'
      ? '<span class="rpt-badge rpt-badge-red">Returned</span>'
      : po.returnStatus === 'partial'
        ? '<span class="rpt-badge rpt-badge-amber">Part.Rtn</span>'
        : '';

    // 1. PO header row (top)
    html += '<tr class="rpt-purch-inv-row">';
    html += '<td><span class="rpt-id-purchase">'+po.id+'</span>&nbsp;'+stBadge+returnBadge+'</td>';
    html += '<td class="rpt-secondary-text">'+vendName+'</td>';
    html += '<td class="rpt-meta-text">'+items.length+' item'+(items.length !== 1 ? 's' : '')+'</td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td class="text-end" class="rpt-date-cell">'+dateStr+'</td>';
    html += '</tr>';

    // 2. Line item rows (middle)
    items.forEach(function(item) {
      var prodName = prodMap[item.productId] || item.productId;
      var rcvBadge = item.receivedQuantity > 0
        ? '<span class="rpt-rcvd-badge">Rcvd:'+item.receivedQuantity+'</span>'
        : '';
      html += '<tr class="rpt-purch-item-row">';
      html += '<td></td>';
      html += '<td></td>';
      html += '<td class="rpt-indent-cell">'+prodName+rcvBadge+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+item.quantity+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+ERP.formatCurrency(item.unitCost||0)+'</td>';
      html += '<td class="text-end rpt-line-amt">'+ERP.formatCurrency(item.totalLineCost||0)+'</td>';
      html += '<td></td>';
      html += '</tr>';
    });

    // 3. PO total row (bottom)
    html += '<tr class="rpt-purch-inv-row" class="rpt-purch-item-row">';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td class="text-end" class="rpt-total-label">PO Total</td>';
    html += '<td class="text-end" class="rpt-total-val">'+ERP.formatCurrency(po.totalAmount||0)+'</td>';
    html += '<td></td>';
    html += '</tr>';
  });

  if (!orders.length) {
    html = '<tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-truck-delivery d-block mb-2" class="fs-2"></i>No purchase orders match the selected filters</td></tr>';
  }

  document.getElementById('rptPurchaseBody').innerHTML = html;
  document.getElementById('rptPurchaseFoot').innerHTML = orders.length
    ? '<tr><td colspan="5" class="fw-bold">Grand Total &nbsp;<span class="rpt-count-span">('+totalOrders+' orders, '+totalItems+' items)</span></td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(grandTotal)+'</td>'
      +'<td></td></tr>'
    : '';
  document.getElementById('rptPurchaseSummary').innerHTML = orders.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+totalOrders+'</b> orders</span>'
      +'<span>Total Items: <b>'+totalItems+'</b></span>'
      +'<span>Grand Total: <b>'+ERP.formatCurrency(grandTotal)+'</b></span></div>'
    : '';

  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptPurchPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search)  parts.push('PO: '+search);
  if (vendId)  parts.push('Vendor: '+(partyMap[vendId]||vendId));
  if (status)  parts.push('Status: '+status);
  if (dateFrom) parts.push('From: '+dateFrom);
  if (dateTo)   parts.push('To: '+dateTo);
  if (!parts.length) parts.push('All purchase orders');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptPurchPrintParams').textContent = parts.join('  |  ');
}
function getPurchaseReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var orders = (state.purchaseOrders || []).filter(function(po){ return !coId || po.companyId === coId; });

  var search   = (document.getElementById('rptPurchSearch').value || '').trim().toLowerCase();
  var vendId   = document.getElementById('rptPurchVendor').value;
  var status   = document.getElementById('rptPurchStatus').value;
  var dateFrom = document.getElementById('rptPurchFrom').value;
  var dateTo   = document.getElementById('rptPurchTo').value;

  var fromTs = dateFrom ? new Date(dateFrom + 'T00:00:00').getTime() : null;
  var toTs   = dateTo   ? new Date(dateTo   + 'T23:59:59').getTime() : null;
  if (fromTs)  orders = orders.filter(function(po){ return (po.createdAt||0) >= fromTs; });
  if (toTs)    orders = orders.filter(function(po){ return (po.createdAt||0) <= toTs; });
  if (search)  orders = orders.filter(function(po){ return po.id.toLowerCase().indexOf(search) !== -1; });
  if (vendId)  orders = orders.filter(function(po){ return po.vendorId === vendId; });
  if (status)  orders = orders.filter(function(po){ return (po.status||'') === status; });
  orders.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });

  var prodMap = {};
  (state.products || []).forEach(function(p){ prodMap[p.id] = p.name || p.id; });
  var partyMap = {};
  (state.parties || []).forEach(function(p){ partyMap[p.id] = p.name || p.id; });

  var grandTotal = 0, totalItems = 0;
  orders.forEach(function(po){ grandTotal += po.totalAmount||0; totalItems += (po.items||[]).length; });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { orders: orders, prodMap: prodMap, partyMap: partyMap,
           grandTotal: grandTotal, totalItems: totalItems,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportPurchaseExcel() {
  var d = getPurchaseReportData();
  var headers = ['PO No.','Vendor','Status','Product Name','Qty','Unit Cost','Received Qty','Line Cost','PO Total','Date & Time'];
  var rows = [];
  d.orders.forEach(function(po) {
    var vendName = po.vendorId ? (d.partyMap[po.vendorId]||po.vendorId) : '';
    var dtStr = po.createdAt ? new Date(po.createdAt).toLocaleString() : '';
    var items = po.items || [];
    items.forEach(function(item) {
      rows.push([
        po.id, vendName, po.status||'',
        d.prodMap[item.productId]||item.productId,
        item.quantity, item.unitCost||0, item.receivedQuantity||0,
        item.totalLineCost||0, po.totalAmount||0, dtStr
      ]);
    });
    if (!items.length) {
      rows.push([po.id, vendName, po.status||'','',0,0,0,0,po.totalAmount||0,dtStr]);
    }
  });
  rows.push(['GRAND TOTAL ('+d.orders.length+' orders, '+d.totalItems+' items)','','','','','','','',d.grandTotal,'']);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:14},{wch:22},{wch:18},{wch:28},{wch:6},{wch:12},{wch:10},{wch:14},{wch:14},{wch:22}];
  XLSX.utils.book_append_sheet(wb, ws, 'Purchase Report');
  XLSX.writeFile(wb, 'Purchase_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportPurchasePDF() {
  var d = getPurchaseReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Purchase Order Report');

  var tableRows = [];
  var headerRowIndexes = [];
  d.orders.forEach(function(po) {
    var vendName = po.vendorId ? (d.partyMap[po.vendorId]||po.vendorId) : '—';
    var dtStr = po.createdAt ? new Date(po.createdAt).toLocaleString() : '—';
    var items = po.items || [];
    headerRowIndexes.push(tableRows.length);
    tableRows.push([po.id+' ('+po.status+')', vendName, items.length+' item'+(items.length!==1?'s':''), '', '', ERP.formatCurrency(po.totalAmount||0), dtStr]);
    items.forEach(function(item) {
      tableRows.push(['', '', '  '+(d.prodMap[item.productId]||item.productId), item.quantity, ERP.formatCurrency(item.unitCost||0), ERP.formatCurrency(item.totalLineCost||0), '']);
    });
  });

  doc.autoTable({
    startY: startY,
    head: [['PO No.','Vendor','Product Name','Qty','Unit Cost','Total','Date & Time']],
    body: tableRows,
    foot: [['Grand Total ('+d.orders.length+' orders, '+d.totalItems+' items)','','','','',ERP.formatCurrency(d.grandTotal),'']],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    didParseCell: function(data) {
      if (data.section === 'body' && headerRowIndexes.indexOf(data.row) !== -1) {
        data.cell.styles.fillColor = [245, 243, 255];
        data.cell.styles.fontStyle = 'bold';
        data.cell.styles.fontSize = 7.5;
      }
    },
    columnStyles: {
      3:{halign:'right', cellWidth:12},
      4:{halign:'right', cellWidth:22},
      5:{halign:'right', cellWidth:24},
      6:{halign:'right', cellWidth:34}
    },
    margin: { left:10, right:10 }
  });
  doc.save('Purchase_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Detailed Sales Report ====== */
function runSalesReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var sales = (state.sales || []).filter(function(s){ return !coId || s.companyId === coId; });

  var search   = (document.getElementById('rptSalesSearch').value || '').trim().toLowerCase();
  var custId   = document.getElementById('rptSalesCustomer').value;
  var payM     = document.getElementById('rptSalesPayment').value;
  var dateFrom = document.getElementById('rptSalesFrom').value;
  var dateTo   = document.getElementById('rptSalesTo').value;

  var fromTs = dateFrom ? new Date(dateFrom + 'T00:00:00').getTime() : null;
  var toTs   = dateTo   ? new Date(dateTo   + 'T23:59:59').getTime() : null;

  if (fromTs) sales = sales.filter(function(s){ return (s.createdAt||0) >= fromTs; });
  if (toTs)   sales = sales.filter(function(s){ return (s.createdAt||0) <= toTs; });
  if (search) sales = sales.filter(function(s){ return s.id.toLowerCase().indexOf(search) !== -1; });
  if (custId) sales = sales.filter(function(s){ return s.customerId === custId; });
  if (payM)   sales = sales.filter(function(s){ return (s.paymentMethod||'') === payM; });

  sales.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });

  var prodMap = {};
  (state.products || []).forEach(function(p){ prodMap[p.id] = p.name || p.id; });
  var partyMap = {};
  (state.parties || []).forEach(function(p){ partyMap[p.id] = p.name || p.id; });

  var html = '', totalInvoices = 0, totalItems = 0, grandTotal = 0;

  sales.forEach(function(s) {
    totalInvoices++;
    grandTotal += s.totalAmount || 0;
    var items = s.items || [];
    totalItems += items.length;

    var custName = s.customerId ? (partyMap[s.customerId] || s.customerId) : '—';
    var dt = s.createdAt ? new Date(s.createdAt) : null;
    var dateStr = dt ? dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';

    var returnBadge = s.returnStatus === 'full'
      ? '<span class="rpt-badge rpt-badge-red">Returned</span>'
      : s.returnStatus === 'partial'
        ? '<span class="rpt-badge rpt-badge-amber">Part.Rtn</span>'
        : '';
    var payBadge = '<span class="rpt-badge rpt-badge-blue">'+s.paymentMethod+'</span>';

    // 1. Invoice header row (top)
    html += '<tr class="rpt-sales-inv-row">';
    html += '<td><span class="rpt-id-sale">'+s.id+'</span>&nbsp;'+payBadge+returnBadge+'</td>';
    html += '<td class="rpt-secondary-text">'+custName+'</td>';
    html += '<td class="rpt-meta-text">'+items.length+' item'+(items.length !== 1 ? 's' : '')+'</td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td class="text-end" class="rpt-date-cell">'+dateStr+'</td>';
    html += '</tr>';

    // 2. Line item rows (middle)
    items.forEach(function(item) {
      var prodName = prodMap[item.productId] || item.productId;
      html += '<tr class="rpt-sales-item-row">';
      html += '<td></td>';
      html += '<td></td>';
      html += '<td class="rpt-indent-cell">'+prodName+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+item.quantity+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+ERP.formatCurrency(item.unitPrice||0)+'</td>';
      html += '<td class="text-end rpt-line-amt">'+ERP.formatCurrency(item.totalLinePrice||0)+'</td>';
      html += '<td></td>';
      html += '</tr>';
    });

    // 3. Invoice total row (bottom)
    html += '<tr class="rpt-sales-inv-row" class="rpt-sales-item-row">';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td class="text-end" class="rpt-total-label">Invoice Total</td>';
    html += '<td class="text-end" class="rpt-total-val">'+ERP.formatCurrency(s.totalAmount||0)+'</td>';
    html += '<td></td>';
    html += '</tr>';
  });

  if (!sales.length) {
    html = '<tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-receipt d-block mb-2" class="fs-2"></i>No sales match the selected filters</td></tr>';
  }

  document.getElementById('rptSalesBody').innerHTML = html;
  document.getElementById('rptSalesFoot').innerHTML = sales.length
    ? '<tr><td colspan="5" class="fw-bold">Grand Total &nbsp;<span class="rpt-count-span">('+totalInvoices+' invoices, '+totalItems+' items)</span></td>'
      +'<td class="text-end fw-bold">'+ERP.formatCurrency(grandTotal)+'</td>'
      +'<td></td></tr>'
    : '';
  document.getElementById('rptSalesSummary').innerHTML = sales.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+totalInvoices+'</b> invoices</span>'
      +'<span>Total Items: <b>'+totalItems+'</b></span>'
      +'<span>Grand Total: <b>'+ERP.formatCurrency(grandTotal)+'</b></span></div>'
    : '';

  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptSalesPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search)   parts.push('Invoice: '+search);
  if (custId)   parts.push('Customer: '+(partyMap[custId]||custId));
  if (payM)     parts.push('Payment: '+payM);
  if (dateFrom) parts.push('From: '+dateFrom);
  if (dateTo)   parts.push('To: '+dateTo);
  if (!parts.length) parts.push('All sales');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptSalesPrintParams').textContent = parts.join('  |  ');
}
function getSalesReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var sales = (state.sales || []).filter(function(s){ return !coId || s.companyId === coId; });

  var search   = (document.getElementById('rptSalesSearch').value || '').trim().toLowerCase();
  var custId   = document.getElementById('rptSalesCustomer').value;
  var payM     = document.getElementById('rptSalesPayment').value;
  var dateFrom = document.getElementById('rptSalesFrom').value;
  var dateTo   = document.getElementById('rptSalesTo').value;

  var fromTs = dateFrom ? new Date(dateFrom + 'T00:00:00').getTime() : null;
  var toTs   = dateTo   ? new Date(dateTo   + 'T23:59:59').getTime() : null;
  if (fromTs) sales = sales.filter(function(s){ return (s.createdAt||0) >= fromTs; });
  if (toTs)   sales = sales.filter(function(s){ return (s.createdAt||0) <= toTs; });
  if (search) sales = sales.filter(function(s){ return s.id.toLowerCase().indexOf(search) !== -1; });
  if (custId) sales = sales.filter(function(s){ return s.customerId === custId; });
  if (payM)   sales = sales.filter(function(s){ return (s.paymentMethod||'') === payM; });
  sales.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });

  var prodMap = {};
  (state.products || []).forEach(function(p){ prodMap[p.id] = p.name || p.id; });
  var partyMap = {};
  (state.parties || []).forEach(function(p){ partyMap[p.id] = p.name || p.id; });

  var grandTotal = 0, totalItems = 0;
  sales.forEach(function(s){ grandTotal += s.totalAmount||0; totalItems += (s.items||[]).length; });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { sales: sales, prodMap: prodMap, partyMap: partyMap,
           grandTotal: grandTotal, totalItems: totalItems,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportSalesExcel() {
  var d = getSalesReportData();
  var headers = ['Invoice No.','Customer','Payment Method','Product Name','Qty','Unit Price','Discount','Line Total','Invoice Total','Date & Time'];
  var rows = [];
  d.sales.forEach(function(s) {
    var custName = s.customerId ? (d.partyMap[s.customerId]||s.customerId) : '';
    var dtStr = s.createdAt ? new Date(s.createdAt).toLocaleString() : '';
    var items = s.items || [];
    items.forEach(function(item) {
      rows.push([
        s.id, custName, s.paymentMethod||'',
        d.prodMap[item.productId]||item.productId,
        item.quantity, item.unitPrice||0, item.discount||0,
        item.totalLinePrice||0, s.totalAmount||0, dtStr
      ]);
    });
    if (!items.length) {
      rows.push([s.id, custName, s.paymentMethod||'','',0,0,0,0,s.totalAmount||0,dtStr]);
    }
  });
  rows.push(['GRAND TOTAL ('+d.sales.length+' invoices, '+d.totalItems+' items)','','','','','','','',d.grandTotal,'']);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:14},{wch:22},{wch:14},{wch:28},{wch:6},{wch:12},{wch:10},{wch:14},{wch:14},{wch:22}];
  XLSX.utils.book_append_sheet(wb, ws, 'Sales Report');
  XLSX.writeFile(wb, 'Sales_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportSalesPDF() {
  var d = getSalesReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Sales Report');

  // Build flat rows array tracking which are invoice headers
  var tableRows = [];
  var headerRowIndexes = [];
  d.sales.forEach(function(s) {
    var custName = s.customerId ? (d.partyMap[s.customerId]||s.customerId) : '—';
    var dtStr = s.createdAt ? new Date(s.createdAt).toLocaleString() : '—';
    var items = s.items || [];
    // Invoice header row
    headerRowIndexes.push(tableRows.length);
    tableRows.push([s.id+' ('+s.paymentMethod+')', custName, items.length+' item'+(items.length!==1?'s':''), '', '', ERP.formatCurrency(s.totalAmount||0), dtStr]);
    // Line item rows
    items.forEach(function(item) {
      tableRows.push(['', '', '  '+d.prodMap[item.productId]||item.productId, item.quantity, ERP.formatCurrency(item.unitPrice||0), ERP.formatCurrency(item.totalLinePrice||0), '']);
    });
  });

  doc.autoTable({
    startY: startY,
    head: [['Invoice No.','Customer','Product Name','Qty','Unit Price','Total','Date & Time']],
    body: tableRows,
    foot: [['Grand Total ('+d.sales.length+' invoices, '+d.totalItems+' items)','','','','',ERP.formatCurrency(d.grandTotal),'']],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    didParseCell: function(data) {
      if (data.section === 'body' && headerRowIndexes.indexOf(data.row) !== -1) {
        data.cell.styles.fillColor = [240, 244, 255];
        data.cell.styles.fontStyle = 'bold';
        data.cell.styles.fontSize = 7.5;
      }
    },
    columnStyles: {
      3:{halign:'right', cellWidth:12},
      4:{halign:'right', cellWidth:22},
      5:{halign:'right', cellWidth:24},
      6:{halign:'right', cellWidth:34}
    },
    margin: { left:10, right:10 }
  });
  doc.save('Sales_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
function renderFinancialReport(){
    var state=window.ERP.state;
    var income=(state.sales||[]).reduce(function(s,x){return s+(x.totalAmount||0);},0);
    var expenses=(state.purchaseOrders||[]).reduce(function(s,x){return s+(x.totalAmount||0);},0);
    var profit=income-expenses;
    var received=(state.payments||[]).filter(function(p){return p.type==='Payment Received'||p.type==='Receipt';}).reduce(function(s,p){return s+(p.amount||0);},0);
    var made=(state.payments||[]).filter(function(p){return p.type==='Payment Made';}).reduce(function(s,p){return s+(p.amount||0);},0);
    var kpis='';
    [{label:'Total Income',value:income,color:'#059669'},{label:'Total Expenses',value:expenses,color:'#dc2626'},
     {label:'Net Profit',value:profit,color:profit>=0?'#059669':'#dc2626'},{label:'Payments Received',value:received,color:'#3B4FE4'},
     {label:'Payments Made',value:made,color:'#ea580c'}
    ].forEach(function(c){
        kpis+='<div class="rpt-kpi-card"><div class="rpt-kpi-label">'+c.label+'</div><div class="rpt-kpi-value" style="color:'+c.color+'">'+ERP.formatCurrency(c.value)+'</div></div>';
    });
    document.getElementById('financialCards').innerHTML=kpis;
    if(finChart) finChart.destroy();
    finChart=new ApexCharts(document.getElementById('finChartContainer'),{
        chart:{type:'donut',height:268,fontFamily:'Inter,sans-serif'},
        series:[income,expenses,received,made],
        labels:['Income','Expenses','Received','Paid'],
        colors:['#059669','#dc2626','#3B4FE4','#ea580c']
    });
    finChart.render();
}
/* ====== Purchase Return Report ====== */
function runPurchaseReturnReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var returns = (state.purchaseReturns || []).filter(function(pr){ return !coId || pr.companyId === coId; });

  var search   = (document.getElementById('rptPReturnSearch').value || '').trim().toLowerCase();
  var vendId   = document.getElementById('rptPReturnVendor').value;
  var dateFrom = document.getElementById('rptPReturnFrom').value;
  var dateTo   = document.getElementById('rptPReturnTo').value;

  if (search)   returns = returns.filter(function(pr){ return (pr.id||'').toLowerCase().indexOf(search) !== -1; });
  if (vendId)   returns = returns.filter(function(pr){ return pr.vendorId === vendId; });
  if (dateFrom) returns = returns.filter(function(pr){ return pr.createdAt >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   returns = returns.filter(function(pr){ return pr.createdAt <= new Date(dateTo+'T23:59:59').getTime(); });

  returns.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });

  var partyMap = {}, prodMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });
  (state.products||[]).forEach(function(p){ prodMap[p.id] = p.name; });

  var html = '', grandTotal = 0, totalReturns = returns.length, totalItems = 0;

  returns.forEach(function(pr) {
    var items = pr.items || [];
    totalItems += items.length;
    grandTotal += (pr.totalAmount || 0);
    var vendName = pr.vendorId ? (partyMap[pr.vendorId] || pr.vendorId) : '—';
    var dt = pr.createdAt ? new Date(pr.createdAt) : null;
    var dateStr = dt ? dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';

    // 1. Debit memo header row (top)
    html += '<tr class="rpt-purch-header-row">';
    html += '<td><span class="rpt-id-purch-ret">'+pr.id+'</span></td>';
    html += '<td class="rpt-secondary-text">'+vendName+'</td>';
    html += '<td class="rpt-meta-text">'+items.length+' item'+(items.length !== 1 ? 's' : '')+(pr.reason ? ' &nbsp;<span class="rpt-reason-purch">Reason: '+pr.reason+'</span>' : '')+'</td>';
    html += '<td></td><td></td><td></td>';
    html += '<td class="text-end" class="rpt-date-cell">'+dateStr+'</td>';
    html += '</tr>';

    // 2. Line item rows (middle)
    items.forEach(function(item) {
      var prodName = prodMap[item.productId] || item.productId;
      html += '<tr>';
      html += '<td></td><td></td>';
      html += '<td class="rpt-indent-cell">'+prodName+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+item.quantity+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+ERP.formatCurrency(item.unitCost||0)+'</td>';
      html += '<td class="text-end rpt-line-amt">'+ERP.formatCurrency(item.totalLineCost||0)+'</td>';
      html += '<td></td>';
      html += '</tr>';
    });

    // 3. Debit memo total row (bottom)
    html += '<tr class="rpt-purch-total-row">';
    html += '<td></td><td></td><td></td><td></td>';
    html += '<td class="text-end" class="rpt-total-label">Return Total</td>';
    html += '<td class="text-end rpt-total-val text-erp-purple">'+ERP.formatCurrency(pr.totalAmount||0)+'</td>';
    html += '<td></td>';
    html += '</tr>';
  });

  if (!returns.length) {
    html = '<tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-truck-return d-block mb-2" class="fs-2"></i>No purchase returns match the selected filters</td></tr>';
  }

  document.getElementById('rptPReturnBody').innerHTML = html;
  document.getElementById('rptPReturnFoot').innerHTML = returns.length
    ? '<tr><td colspan="5" class="fw-bold">Grand Total &nbsp;<span class="rpt-count-span">('+totalReturns+' returns, '+totalItems+' items)</span></td>'
      +'<td class="text-end fw-bold text-erp-purple">'+ERP.formatCurrency(grandTotal)+'</td>'
      +'<td></td></tr>'
    : '';
  document.getElementById('rptPReturnSummary').innerHTML = returns.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+totalReturns+'</b> returns</span>'
      +'<span>Total Items: <b>'+totalItems+'</b></span>'
      +'<span>Grand Total: <b>'+ERP.formatCurrency(grandTotal)+'</b></span></div>'
    : '';

  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptPReturnPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search)   parts.push('Memo: '+search);
  if (vendId)   parts.push('Vendor: '+(partyMap[vendId]||vendId));
  if (dateFrom) parts.push('From: '+dateFrom);
  if (dateTo)   parts.push('To: '+dateTo);
  if (!parts.length) parts.push('All returns');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptPReturnPrintParams').textContent = parts.join('  |  ');
}
function getPurchaseReturnReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var returns = (state.purchaseReturns || []).filter(function(pr){ return !coId || pr.companyId === coId; });
  var search   = (document.getElementById('rptPReturnSearch').value || '').trim().toLowerCase();
  var vendId   = document.getElementById('rptPReturnVendor').value;
  var dateFrom = document.getElementById('rptPReturnFrom').value;
  var dateTo   = document.getElementById('rptPReturnTo').value;
  if (search)   returns = returns.filter(function(pr){ return (pr.id||'').toLowerCase().indexOf(search) !== -1; });
  if (vendId)   returns = returns.filter(function(pr){ return pr.vendorId === vendId; });
  if (dateFrom) returns = returns.filter(function(pr){ return pr.createdAt >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   returns = returns.filter(function(pr){ return pr.createdAt <= new Date(dateTo+'T23:59:59').getTime(); });
  returns.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });
  var partyMap = {}, prodMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });
  (state.products||[]).forEach(function(p){ prodMap[p.id] = p.name; });
  var grandTotal = 0;
  returns.forEach(function(pr){ grandTotal += (pr.totalAmount||0); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { returns: returns, grandTotal: grandTotal, partyMap: partyMap, prodMap: prodMap,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportPurchaseReturnExcel() {
  var d = getPurchaseReturnReportData();
  var headers = ['Debit Memo No.','Vendor','Product Name','Qty','Unit Cost','Line Total','Date & Time','Reason'];
  var rows = [];
  d.returns.forEach(function(pr){
    var vendName = d.partyMap[pr.vendorId] || pr.vendorId || '—';
    var dt = pr.createdAt ? new Date(pr.createdAt).toLocaleString() : '—';
    (pr.items||[]).forEach(function(item){
      rows.push([pr.id, vendName, d.prodMap[item.productId]||item.productId, item.quantity, item.unitCost||0, item.totalLineCost||0, dt, pr.reason||'']);
    });
    rows.push([pr.id+' TOTAL', vendName, '', '', '', pr.totalAmount||0, dt, pr.reason||'']);
  });
  rows.push(['GRAND TOTAL','','','','',d.grandTotal,'','']);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:18},{wch:20},{wch:24},{wch:6},{wch:14},{wch:14},{wch:20},{wch:24}];
  XLSX.utils.book_append_sheet(wb, ws, 'Purchase Return Report');
  XLSX.writeFile(wb, 'Purchase_Return_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportPurchaseReturnPDF() {
  var d = getPurchaseReturnReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Purchase Return Report');
  var rows = [];
  d.returns.forEach(function(pr){
    var vendName = d.partyMap[pr.vendorId] || pr.vendorId || '—';
    var dt = pr.createdAt ? new Date(pr.createdAt).toLocaleString() : '—';
    (pr.items||[]).forEach(function(item){
      rows.push([pr.id, vendName, d.prodMap[item.productId]||item.productId, item.quantity, ERP.formatCurrency(item.unitCost||0), ERP.formatCurrency(item.totalLineCost||0), dt]);
    });
    rows.push([{content:'Return Total', colSpan:5, styles:{fontStyle:'bold',fillColor:[245,243,255]}}, {content:ERP.formatCurrency(pr.totalAmount||0), styles:{halign:'right',fontStyle:'bold',fillColor:[245,243,255],textColor:[109,40,217]}}, '']);
  });
  doc.autoTable({
    startY: startY,
    head: [['Debit Memo No.','Vendor','Product Name','Qty','Unit Cost','Total','Date & Time']],
    body: rows,
    foot: [['Grand Total','','','','',ERP.formatCurrency(d.grandTotal),'']],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: { 3:{halign:'right'}, 4:{halign:'right'}, 5:{halign:'right'} },
    margin: { left:10, right:10 }
  });
  doc.save('Purchase_Return_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Sales Return Report ====== */
function runSalesReturnReport() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var returns = (state.salesReturns || []).filter(function(sr){ return !coId || sr.companyId === coId; });

  var search   = (document.getElementById('rptSReturnSearch').value || '').trim().toLowerCase();
  var custId   = document.getElementById('rptSReturnCustomer').value;
  var dateFrom = document.getElementById('rptSReturnFrom').value;
  var dateTo   = document.getElementById('rptSReturnTo').value;

  if (search)   returns = returns.filter(function(sr){ return (sr.id||'').toLowerCase().indexOf(search) !== -1; });
  if (custId)   returns = returns.filter(function(sr){ return sr.customerId === custId; });
  if (dateFrom) returns = returns.filter(function(sr){ return sr.createdAt >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   returns = returns.filter(function(sr){ return sr.createdAt <= new Date(dateTo+'T23:59:59').getTime(); });

  returns.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });

  // Build maps
  var partyMap = {}, prodMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });
  (state.products||[]).forEach(function(p){ prodMap[p.id] = p.name; });

  var html = '', grandTotal = 0, totalReturns = returns.length, totalItems = 0;

  returns.forEach(function(sr) {
    var items = sr.items || [];
    totalItems += items.length;
    grandTotal += (sr.totalAmount || 0);
    var custName = sr.customerId ? (partyMap[sr.customerId] || sr.customerId) : '—';
    var dt = sr.createdAt ? new Date(sr.createdAt) : null;
    var dateStr = dt ? dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';

    // 1. Credit memo header row (top)
    html += '<tr class="rpt-sale-ret-header-row">';
    html += '<td><span class="rpt-id-sale-ret">'+sr.id+'</span></td>';
    html += '<td class="rpt-secondary-text">'+custName+'</td>';
    html += '<td class="rpt-meta-text">'+items.length+' item'+(items.length !== 1 ? 's' : '')+(sr.reason ? ' &nbsp;<span class="rpt-reason-sale">Reason: '+sr.reason+'</span>' : '')+'</td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td class="text-end" class="rpt-date-cell">'+dateStr+'</td>';
    html += '</tr>';

    // 2. Line item rows (middle)
    items.forEach(function(item) {
      var prodName = prodMap[item.productId] || item.productId;
      html += '<tr>';
      html += '<td></td>';
      html += '<td></td>';
      html += '<td class="rpt-indent-cell">'+prodName+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+item.quantity+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+ERP.formatCurrency(item.unitPrice||0)+'</td>';
      html += '<td class="text-end rpt-line-amt">'+ERP.formatCurrency(item.totalLinePrice||0)+'</td>';
      html += '<td></td>';
      html += '</tr>';
    });

    // 3. Credit memo total row (bottom)
    html += '<tr class="rpt-sale-ret-total-row">';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td></td>';
    html += '<td class="text-end" class="rpt-total-label">Return Total</td>';
    html += '<td class="text-end rpt-total-val text-erp-rose">'+ERP.formatCurrency(sr.totalAmount||0)+'</td>';
    html += '<td></td>';
    html += '</tr>';
  });

  if (!returns.length) {
    html = '<tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-receipt-refund d-block mb-2" class="fs-2"></i>No sales returns match the selected filters</td></tr>';
  }

  document.getElementById('rptSReturnBody').innerHTML = html;
  document.getElementById('rptSReturnFoot').innerHTML = returns.length
    ? '<tr><td colspan="5" class="fw-bold">Grand Total &nbsp;<span class="rpt-count-span">('+totalReturns+' returns, '+totalItems+' items)</span></td>'
      +'<td class="text-end fw-bold text-erp-rose">'+ERP.formatCurrency(grandTotal)+'</td>'
      +'<td></td></tr>'
    : '';
  document.getElementById('rptSReturnSummary').innerHTML = returns.length
    ? '<div class="rpt-summary-bar d-print-none"><span><b>'+totalReturns+'</b> returns</span>'
      +'<span>Total Items: <b>'+totalItems+'</b></span>'
      +'<span>Grand Total: <b>'+ERP.formatCurrency(grandTotal)+'</b></span></div>'
    : '';

  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptSReturnPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (search)   parts.push('Memo: '+search);
  if (custId)   parts.push('Customer: '+(partyMap[custId]||custId));
  if (dateFrom) parts.push('From: '+dateFrom);
  if (dateTo)   parts.push('To: '+dateTo);
  if (!parts.length) parts.push('All returns');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptSReturnPrintParams').textContent = parts.join('  |  ');
}
function getSalesReturnReportData() {
  var state = window.ERP.state;
  var coId = (state.currentUser || {}).companyId;
  var returns = (state.salesReturns || []).filter(function(sr){ return !coId || sr.companyId === coId; });
  var search   = (document.getElementById('rptSReturnSearch').value || '').trim().toLowerCase();
  var custId   = document.getElementById('rptSReturnCustomer').value;
  var dateFrom = document.getElementById('rptSReturnFrom').value;
  var dateTo   = document.getElementById('rptSReturnTo').value;
  if (search)   returns = returns.filter(function(sr){ return (sr.id||'').toLowerCase().indexOf(search) !== -1; });
  if (custId)   returns = returns.filter(function(sr){ return sr.customerId === custId; });
  if (dateFrom) returns = returns.filter(function(sr){ return sr.createdAt >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   returns = returns.filter(function(sr){ return sr.createdAt <= new Date(dateTo+'T23:59:59').getTime(); });
  returns.sort(function(a,b){ return (b.createdAt||0) - (a.createdAt||0); });
  var partyMap = {}, prodMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });
  (state.products||[]).forEach(function(p){ prodMap[p.id] = p.name; });
  var grandTotal = 0;
  returns.forEach(function(sr){ grandTotal += (sr.totalAmount||0); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { returns: returns, grandTotal: grandTotal, partyMap: partyMap, prodMap: prodMap,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportSalesReturnExcel() {
  var d = getSalesReturnReportData();
  var headers = ['Credit Memo No.','Customer','Product Name','Qty','Unit Price','Line Total','Date & Time','Reason'];
  var rows = [];
  d.returns.forEach(function(sr){
    var custName = d.partyMap[sr.customerId] || sr.customerId || '—';
    var dt = sr.createdAt ? new Date(sr.createdAt).toLocaleString() : '—';
    (sr.items||[]).forEach(function(item){
      rows.push([sr.id, custName, d.prodMap[item.productId]||item.productId, item.quantity, item.unitPrice||0, item.totalLinePrice||0, dt, sr.reason||'']);
    });
    rows.push([sr.id+' TOTAL', custName, '', '', '', sr.totalAmount||0, dt, sr.reason||'']);
  });
  rows.push(['GRAND TOTAL','','','','',d.grandTotal,'','']);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:18},{wch:20},{wch:24},{wch:6},{wch:14},{wch:14},{wch:20},{wch:24}];
  XLSX.utils.book_append_sheet(wb, ws, 'Sales Return Report');
  XLSX.writeFile(wb, 'Sales_Return_Report_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportSalesReturnPDF() {
  var d = getSalesReturnReportData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Sales Return Report');
  var rows = [];
  d.returns.forEach(function(sr){
    var custName = d.partyMap[sr.customerId] || sr.customerId || '—';
    var dt = sr.createdAt ? new Date(sr.createdAt).toLocaleString() : '—';
    (sr.items||[]).forEach(function(item){
      rows.push([sr.id, custName, d.prodMap[item.productId]||item.productId, item.quantity, ERP.formatCurrency(item.unitPrice||0), ERP.formatCurrency(item.totalLinePrice||0), dt]);
    });
    rows.push([{content:'Return Total', colSpan:5, styles:{fontStyle:'bold',fillColor:[255,241,242]}}, {content:ERP.formatCurrency(sr.totalAmount||0), styles:{halign:'right',fontStyle:'bold',fillColor:[255,241,242],textColor:[190,18,60]}}, '']);
  });
  doc.autoTable({
    startY: startY,
    head: [['Credit Memo No.','Customer','Product Name','Qty','Unit Price','Total','Date & Time']],
    body: rows,
    foot: [['Grand Total','','','','',ERP.formatCurrency(d.grandTotal),'']],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: { 3:{halign:'right'}, 4:{halign:'right'}, 5:{halign:'right'} },
    margin: { left:10, right:10 }
  });
  doc.save('Sales_Return_Report_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Sales by Customer Report ====== */
function runSalesByCustomerReport() {
  var state = window.ERP.state;
  var coId  = (state.currentUser || {}).companyId;
  var sales = (state.sales || []).filter(function(s){ return !coId || s.companyId === coId; });

  var custId   = document.getElementById('rptSBCCustomer').value;
  var payMeth  = document.getElementById('rptSBCPayment').value;
  var dateFrom = document.getElementById('rptSBCFrom').value;
  var dateTo   = document.getElementById('rptSBCTo').value;

  if (custId)   sales = sales.filter(function(s){ return s.customerId === custId; });
  if (payMeth)  sales = sales.filter(function(s){ return (s.paymentMethod||'') === payMeth; });
  if (dateFrom) sales = sales.filter(function(s){ return (s.createdAt||0) >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   sales = sales.filter(function(s){ return (s.createdAt||0) <= new Date(dateTo+'T23:59:59').getTime(); });

  // Build maps
  var partyMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });

  // Group by customer
  var grouped = {};   // custId -> { name, sales[] }
  sales.forEach(function(s) {
    var key  = s.customerId || '__walkin__';
    var name = s.customerId ? (partyMap[s.customerId] || s.customerId) : 'Walk-in / Cash';
    if (!grouped[key]) grouped[key] = { name: name, sales: [] };
    grouped[key].sales.push(s);
  });

  // Sort customers by name
  var custKeys = Object.keys(grouped).sort(function(a,b){
    return grouped[a].name.localeCompare(grouped[b].name);
  });

  var html = '', grandTotal = 0, totalCustomers = custKeys.length, totalInvoices = sales.length;

  var payBadgeColors = { 'Cash':'rpt-badge-blue', 'Credit':'rpt-badge-amber', 'Card':'rpt-badge-grey', 'Bank Transfer':'rpt-badge-grey' };

  custKeys.forEach(function(key) {
    var grp   = grouped[key];
    var custTotal = 0;
    grp.sales.forEach(function(s){ custTotal += (s.totalAmount||0); });
    grandTotal += custTotal;

    // Sort invoices newest first
    grp.sales.sort(function(a,b){ return (b.createdAt||0)-(a.createdAt||0); });

    // 1. Customer header row (top)
    html += '<tr class="rpt-cust-header-row">';
    html += '<td colspan="2"><span class="rpt-id-customer">'+grp.name+'</span></td>';
    html += '<td class="rpt-meta-text">'+grp.sales.length+' invoice'+(grp.sales.length!==1?'s':'')+'</td>';
    html += '<td></td><td></td><td></td>';
    html += '</tr>';

    // 2. Invoice rows (middle)
    grp.sales.forEach(function(s) {
      var dt = s.createdAt ? new Date(s.createdAt) : null;
      var dateStr = dt ? dt.toLocaleDateString()+' '+dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';
      var payBadge = '<span class="rpt-badge '+(payBadgeColors[s.paymentMethod]||'rpt-badge-grey')+'">'+( s.paymentMethod||'—')+'</span>';
      var itemCount = (s.items||[]).length;
      html += '<tr>';
      html += '<td></td>';
      html += '<td class="rpt-indent-id-sale">'+s.id+'</td>';
      html += '<td class="erp-text-83">'+payBadge+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+itemCount+'</td>';
      html += '<td class="text-end rpt-line-amt">'+ERP.formatCurrency(s.totalAmount||0)+'</td>';
      html += '<td class="text-end" class="rpt-date-cell">'+dateStr+'</td>';
      html += '</tr>';
    });

    // 3. Customer total row (bottom)
    html += '<tr class="rpt-cust-total-row">';
    html += '<td></td><td></td><td></td><td></td>';
    html += '<td class="text-end" class="rpt-total-label">Customer Total</td>';
    html += '<td class="text-end rpt-total-val text-success">'+ERP.formatCurrency(custTotal)+'</td>';
    html += '</tr>';
  });

  if (!custKeys.length) {
    html = '<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-users-group d-block mb-2" class="fs-2"></i>No sales match the selected filters</td></tr>';
  }

  document.getElementById('rptSBCBody').innerHTML = html;
  document.getElementById('rptSBCFoot').innerHTML = custKeys.length
    ? '<tr>'
      +'<td colspan="4" class="fw-bold">Grand Total &nbsp;<span class="rpt-count-span">('+totalCustomers+' customers, '+totalInvoices+' invoices)</span></td>'
      +'<td class="text-end fw-bold text-success">'+ERP.formatCurrency(grandTotal)+'</td>'
      +'<td></td>'
      +'</tr>'
    : '';
  document.getElementById('rptSBCSummary').innerHTML = custKeys.length
    ? '<div class="rpt-summary-bar d-print-none">'
      +'<span><b>'+totalCustomers+'</b> customers</span>'
      +'<span>Total Invoices: <b>'+totalInvoices+'</b></span>'
      +'<span>Grand Total: <b>'+ERP.formatCurrency(grandTotal)+'</b></span>'
      +'</div>'
    : '';

  // Print header
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptSBCPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (custId)  parts.push('Customer: '+(partyMap[custId]||custId));
  if (payMeth) parts.push('Method: '+payMeth);
  if (dateFrom) parts.push('From: '+dateFrom);
  if (dateTo)   parts.push('To: '+dateTo);
  if (!parts.length) parts.push('All customers');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptSBCPrintParams').textContent = parts.join('  |  ');
}
function getSalesByCustomerData() {
  var state = window.ERP.state;
  var coId  = (state.currentUser || {}).companyId;
  var sales = (state.sales || []).filter(function(s){ return !coId || s.companyId === coId; });
  var custId   = document.getElementById('rptSBCCustomer').value;
  var payMeth  = document.getElementById('rptSBCPayment').value;
  var dateFrom = document.getElementById('rptSBCFrom').value;
  var dateTo   = document.getElementById('rptSBCTo').value;
  if (custId)   sales = sales.filter(function(s){ return s.customerId === custId; });
  if (payMeth)  sales = sales.filter(function(s){ return (s.paymentMethod||'') === payMeth; });
  if (dateFrom) sales = sales.filter(function(s){ return (s.createdAt||0) >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   sales = sales.filter(function(s){ return (s.createdAt||0) <= new Date(dateTo+'T23:59:59').getTime(); });
  var partyMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });
  var grouped = {};
  sales.forEach(function(s) {
    var key = s.customerId || '__walkin__';
    var name = s.customerId ? (partyMap[s.customerId]||s.customerId) : 'Walk-in / Cash';
    if (!grouped[key]) grouped[key] = { name: name, sales: [] };
    grouped[key].sales.push(s);
  });
  var custKeys = Object.keys(grouped).sort(function(a,b){ return grouped[a].name.localeCompare(grouped[b].name); });
  var grandTotal = 0;
  custKeys.forEach(function(k){ grouped[k].sales.forEach(function(s){ grandTotal += (s.totalAmount||0); }); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { grouped: grouped, custKeys: custKeys, grandTotal: grandTotal, partyMap: partyMap,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportSalesByCustomerExcel() {
  var d = getSalesByCustomerData();
  var headers = ['Customer','Invoice No.','Payment Method','Items','Amount','Date & Time'];
  var rows = [];
  d.custKeys.forEach(function(key) {
    var grp = d.grouped[key];
    var custTotal = 0;
    grp.sales.sort(function(a,b){ return (b.createdAt||0)-(a.createdAt||0); });
    grp.sales.forEach(function(s) {
      custTotal += (s.totalAmount||0);
      rows.push([grp.name, s.id, s.paymentMethod||'—', (s.items||[]).length, s.totalAmount||0, s.createdAt ? new Date(s.createdAt).toLocaleString() : '—']);
    });
    rows.push([grp.name+' TOTAL','','','',custTotal,'']);
  });
  rows.push(['GRAND TOTAL','','','',d.grandTotal,'']);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:24},{wch:16},{wch:16},{wch:8},{wch:14},{wch:22}];
  XLSX.utils.book_append_sheet(wb, ws, 'Sales by Customer');
  XLSX.writeFile(wb, 'Sales_By_Customer_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportSalesByCustomerPDF() {
  var d = getSalesByCustomerData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Sales by Customer Report');
  var rows = [];
  d.custKeys.forEach(function(key) {
    var grp = d.grouped[key];
    var custTotal = 0;
    grp.sales.sort(function(a,b){ return (b.createdAt||0)-(a.createdAt||0); });
    grp.sales.forEach(function(s) {
      custTotal += (s.totalAmount||0);
      rows.push([grp.name, s.id, s.paymentMethod||'—', (s.items||[]).length, ERP.formatCurrency(s.totalAmount||0), s.createdAt ? new Date(s.createdAt).toLocaleString() : '—']);
    });
    rows.push([{content:'Customer Total', colSpan:4, styles:{fontStyle:'bold',fillColor:[236,253,245]}}, {content:ERP.formatCurrency(custTotal), styles:{halign:'right',fontStyle:'bold',fillColor:[236,253,245],textColor:[5,150,105]}}, '']);
  });
  doc.autoTable({
    startY: startY,
    head: [['Customer','Invoice No.','Payment Method','Items','Amount','Date & Time']],
    body: rows,
    foot: [['Grand Total','','','',ERP.formatCurrency(d.grandTotal),'']],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: { 3:{halign:'right'}, 4:{halign:'right'} },
    margin: { left:10, right:10 }
  });
  doc.save('Sales_By_Customer_'+new Date().toISOString().split('T')[0]+'.pdf');
}
/* ====== Purchase by Vendor Report ====== */
function runPurchaseByVendorReport() {
  var state = window.ERP.state;
  var coId  = (state.currentUser || {}).companyId;
  var orders = (state.purchaseOrders || []).filter(function(po){ return !coId || po.companyId === coId; });

  var vendId   = document.getElementById('rptPBVVendor').value;
  var status   = document.getElementById('rptPBVStatus').value;
  var dateFrom = document.getElementById('rptPBVFrom').value;
  var dateTo   = document.getElementById('rptPBVTo').value;

  if (vendId)   orders = orders.filter(function(po){ return po.vendorId === vendId; });
  if (status)   orders = orders.filter(function(po){ return (po.status||'') === status; });
  if (dateFrom) orders = orders.filter(function(po){ return (po.createdAt||0) >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   orders = orders.filter(function(po){ return (po.createdAt||0) <= new Date(dateTo+'T23:59:59').getTime(); });

  var partyMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });

  // Group by vendor
  var grouped = {};
  orders.forEach(function(po) {
    var key  = po.vendorId || '__unknown__';
    var name = po.vendorId ? (partyMap[po.vendorId] || po.vendorId) : 'Unknown Vendor';
    if (!grouped[key]) grouped[key] = { name: name, orders: [] };
    grouped[key].orders.push(po);
  });

  var vendKeys = Object.keys(grouped).sort(function(a,b){
    return grouped[a].name.localeCompare(grouped[b].name);
  });

  var statusColors = { 'Draft':'rpt-badge-grey', 'Partially Received':'rpt-badge-amber',
    'Received':'rpt-badge-blue', 'Cancelled':'rpt-badge-red', 'Returned':'rpt-badge-red' };

  var html = '', grandTotal = 0, totalVendors = vendKeys.length, totalOrders = orders.length;

  vendKeys.forEach(function(key) {
    var grp = grouped[key];
    var vendTotal = 0;
    grp.orders.forEach(function(po){ vendTotal += (po.totalAmount||0); });
    grandTotal += vendTotal;

    grp.orders.sort(function(a,b){ return (b.createdAt||0)-(a.createdAt||0); });

    // 1. Vendor header row (top)
    html += '<tr class="rpt-vend-header-row">';
    html += '<td colspan="2"><span class="rpt-id-vendor">'+grp.name+'</span></td>';
    html += '<td class="rpt-meta-text">'+grp.orders.length+' order'+(grp.orders.length!==1?'s':'')+'</td>';
    html += '<td></td><td></td><td></td>';
    html += '</tr>';

    // 2. PO rows (middle)
    grp.orders.forEach(function(po) {
      var dt = po.createdAt ? new Date(po.createdAt) : null;
      var dateStr = dt ? dt.toLocaleDateString()+' '+dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—';
      var stBadge = '<span class="rpt-badge '+(statusColors[po.status]||'rpt-badge-grey')+'">'+( po.status||'—')+'</span>';
      var itemCount = (po.items||[]).length;
      html += '<tr>';
      html += '<td></td>';
      html += '<td class="rpt-indent-id-vend">'+po.id+'</td>';
      html += '<td class="erp-text-83">'+stBadge+'</td>';
      html += '<td class="text-end" class="erp-text-83">'+itemCount+'</td>';
      html += '<td class="text-end rpt-line-amt">'+ERP.formatCurrency(po.totalAmount||0)+'</td>';
      html += '<td class="text-end" class="rpt-date-cell">'+dateStr+'</td>';
      html += '</tr>';
    });

    // 3. Vendor total row (bottom)
    html += '<tr class="rpt-vend-total-row">';
    html += '<td></td><td></td><td></td><td></td>';
    html += '<td class="text-end" class="rpt-total-label">Vendor Total</td>';
    html += '<td class="text-end rpt-total-val text-erp-orange">'+ERP.formatCurrency(vendTotal)+'</td>';
    html += '</tr>';
  });

  if (!vendKeys.length) {
    html = '<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-building-store d-block mb-2" class="fs-2"></i>No purchase orders match the selected filters</td></tr>';
  }

  document.getElementById('rptPBVBody').innerHTML = html;
  document.getElementById('rptPBVFoot').innerHTML = vendKeys.length
    ? '<tr>'
      +'<td colspan="4" class="fw-bold">Grand Total &nbsp;<span class="rpt-count-span">('+totalVendors+' vendors, '+totalOrders+' orders)</span></td>'
      +'<td class="text-end fw-bold text-erp-orange">'+ERP.formatCurrency(grandTotal)+'</td>'
      +'<td></td>'
      +'</tr>'
    : '';
  document.getElementById('rptPBVSummary').innerHTML = vendKeys.length
    ? '<div class="rpt-summary-bar d-print-none">'
      +'<span><b>'+totalVendors+'</b> vendors</span>'
      +'<span>Total Orders: <b>'+totalOrders+'</b></span>'
      +'<span>Grand Total: <b>'+ERP.formatCurrency(grandTotal)+'</b></span>'
      +'</div>'
    : '';

  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  document.getElementById('rptPBVPrintCompany').textContent = (company.info && company.info.name) || company.name || '';
  var parts = [];
  if (vendId)   parts.push('Vendor: '+(partyMap[vendId]||vendId));
  if (status)   parts.push('Status: '+status);
  if (dateFrom) parts.push('From: '+dateFrom);
  if (dateTo)   parts.push('To: '+dateTo);
  if (!parts.length) parts.push('All vendors');
  parts.push('Generated: '+new Date().toLocaleString());
  document.getElementById('rptPBVPrintParams').textContent = parts.join('  |  ');
}
function getPurchaseByVendorData() {
  var state = window.ERP.state;
  var coId  = (state.currentUser || {}).companyId;
  var orders = (state.purchaseOrders || []).filter(function(po){ return !coId || po.companyId === coId; });
  var vendId   = document.getElementById('rptPBVVendor').value;
  var status   = document.getElementById('rptPBVStatus').value;
  var dateFrom = document.getElementById('rptPBVFrom').value;
  var dateTo   = document.getElementById('rptPBVTo').value;
  if (vendId)   orders = orders.filter(function(po){ return po.vendorId === vendId; });
  if (status)   orders = orders.filter(function(po){ return (po.status||'') === status; });
  if (dateFrom) orders = orders.filter(function(po){ return (po.createdAt||0) >= new Date(dateFrom+'T00:00:00').getTime(); });
  if (dateTo)   orders = orders.filter(function(po){ return (po.createdAt||0) <= new Date(dateTo+'T23:59:59').getTime(); });
  var partyMap = {};
  (state.parties||[]).forEach(function(p){ partyMap[p.id] = p.name; });
  var grouped = {};
  orders.forEach(function(po) {
    var key = po.vendorId || '__unknown__';
    var name = po.vendorId ? (partyMap[po.vendorId]||po.vendorId) : 'Unknown Vendor';
    if (!grouped[key]) grouped[key] = { name: name, orders: [] };
    grouped[key].orders.push(po);
  });
  var vendKeys = Object.keys(grouped).sort(function(a,b){ return grouped[a].name.localeCompare(grouped[b].name); });
  var grandTotal = 0;
  vendKeys.forEach(function(k){ grouped[k].orders.forEach(function(po){ grandTotal += (po.totalAmount||0); }); });
  var coId = (state.currentUser || {}).companyId;
  var company = (state.companies || []).find(function(c){ return c.id === coId; }) || (state.companies && state.companies.length === 1 ? state.companies[0] : {});
  return { grouped: grouped, vendKeys: vendKeys, grandTotal: grandTotal, partyMap: partyMap,
           companyName: (company.info && company.info.name) || company.name || '' };
}
function exportPurchaseByVendorExcel() {
  var d = getPurchaseByVendorData();
  var headers = ['Vendor','PO No.','Status','Items','Amount','Date & Time'];
  var rows = [];
  d.vendKeys.forEach(function(key) {
    var grp = d.grouped[key];
    var vendTotal = 0;
    grp.orders.sort(function(a,b){ return (b.createdAt||0)-(a.createdAt||0); });
    grp.orders.forEach(function(po) {
      vendTotal += (po.totalAmount||0);
      rows.push([grp.name, po.id, po.status||'—', (po.items||[]).length, po.totalAmount||0, po.createdAt ? new Date(po.createdAt).toLocaleString() : '—']);
    });
    rows.push([grp.name+' TOTAL','','','',vendTotal,'']);
  });
  rows.push(['GRAND TOTAL','','','',d.grandTotal,'']);
  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet([headers].concat(rows));
  ws['!cols'] = [{wch:24},{wch:16},{wch:18},{wch:8},{wch:14},{wch:22}];
  XLSX.utils.book_append_sheet(wb, ws, 'Purchase by Vendor');
  XLSX.writeFile(wb, 'Purchase_By_Vendor_'+new Date().toISOString().split('T')[0]+'.xlsx');
}
function exportPurchaseByVendorPDF() {
  var d = getPurchaseByVendorData();
  var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var startY = pdfMakeHeader(doc, d.companyName, 'Purchase by Vendor Report');
  var rows = [];
  d.vendKeys.forEach(function(key) {
    var grp = d.grouped[key];
    var vendTotal = 0;
    grp.orders.sort(function(a,b){ return (b.createdAt||0)-(a.createdAt||0); });
    grp.orders.forEach(function(po) {
      vendTotal += (po.totalAmount||0);
      rows.push([grp.name, po.id, po.status||'—', (po.items||[]).length, ERP.formatCurrency(po.totalAmount||0), po.createdAt ? new Date(po.createdAt).toLocaleString() : '—']);
    });
    rows.push([{content:'Vendor Total', colSpan:4, styles:{fontStyle:'bold',fillColor:[255,247,237]}}, {content:ERP.formatCurrency(vendTotal), styles:{halign:'right',fontStyle:'bold',fillColor:[255,247,237],textColor:[234,88,12]}}, '']);
  });
  doc.autoTable({
    startY: startY,
    head: [['Vendor','PO No.','Status','Items','Amount','Date & Time']],
    body: rows,
    foot: [['Grand Total','','','',ERP.formatCurrency(d.grandTotal),'']],
    headStyles: { fillColor:[0,0,0], textColor:255, fontSize:7, fontStyle:'bold' },
    footStyles: { fillColor:[220,220,220], textColor:[0,0,0], fontSize:7, fontStyle:'bold' },
    bodyStyles: { fontSize:7 },
    alternateRowStyles: { fillColor:[245,245,245] },
    columnStyles: { 3:{halign:'right'}, 4:{halign:'right'} },
    margin: { left:10, right:10 }
  });
  doc.save('Purchase_By_Vendor_'+new Date().toISOString().split('T')[0]+'.pdf');
}

// ── Profit & Loss ─────────────────────────────────────────────────────────────
function rptPlFmtDate(d) {
  return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
function rptPlSetPeriod(period) {
  var now = new Date(), y = now.getFullYear(), m = now.getMonth();
  var from, to;
  if (period === 'month') {
    from = new Date(y, m, 1); to = new Date(y, m+1, 0);
  } else if (period === 'quarter') {
    var q = Math.floor(m/3);
    from = new Date(y, q*3, 1); to = new Date(y, q*3+3, 0);
  } else {
    from = new Date(y, 0, 1); to = new Date(y, 11, 31);
  }
  document.getElementById('rptPlFrom').value = rptPlFmtDate(from);
  document.getElementById('rptPlTo').value   = rptPlFmtDate(to);
}
async function runProfitLoss() {
  var from = document.getElementById('rptPlFrom').value;
  var to   = document.getElementById('rptPlTo').value;
  if (!from || !to) { alert('Please select a date range.'); return; }
  document.getElementById('rptPlReport').classList.add('d-none');
  document.getElementById('rptPlLoading').classList.remove('d-none');
  try {
    var data = await ERP.api.getProfitLoss(from, to);
    document.getElementById('rptPlLoading').classList.add('d-none');
    rptRenderPL(data, from, to);
    document.getElementById('rptPlReport').classList.remove('d-none');
  } catch(e) {
    document.getElementById('rptPlLoading').classList.add('d-none');
    alert('Error: ' + e.message);
  }
}
function rptRenderPL(data, from, to) {
  document.getElementById('rptPlPeriodLabel').textContent = 'Period: ' + from + ' to ' + to;
  var grossProfit = (data.totalRevenue||0) - (data.totalCogs||0);
  var netProfit   = grossProfit - (data.totalExpenses||0);
  var html = '';
  html += '<tr class="pl-section-row"><td colspan="2">Revenue</td></tr>';
  html += rptRenderSubTypeRows(data.revenue||{});
  html += '<tr class="pl-subtotal-row"><td>Total Revenue</td><td class="text-end">' + ERP.formatCurrency(data.totalRevenue||0) + '</td></tr>';
  html += '<tr class="pl-section-row"><td colspan="2">Cost of Goods Sold</td></tr>';
  html += rptRenderSubTypeRows(data.cogs||{});
  html += '<tr class="pl-subtotal-row"><td>Total COGS</td><td class="text-end">' + ERP.formatCurrency(data.totalCogs||0) + '</td></tr>';
  html += '<tr class="pl-total-row ' + (grossProfit>=0?'profit':'loss') + '"><td>Gross Profit</td><td class="text-end">' + ERP.formatCurrency(grossProfit) + '</td></tr>';
  html += '<tr class="pl-section-row"><td colspan="2">Operating Expenses</td></tr>';
  html += rptRenderSubTypeRows(data.expenses||{});
  html += '<tr class="pl-subtotal-row"><td>Total Expenses</td><td class="text-end">' + ERP.formatCurrency(data.totalExpenses||0) + '</td></tr>';
  html += '<tr class="pl-total-row ' + (netProfit>=0?'profit':'loss') + '"><td>' + (netProfit>=0?'Net Profit':'Net Loss') + '</td><td class="text-end">' + ERP.formatCurrency(Math.abs(netProfit)) + '</td></tr>';
  document.getElementById('rptPlBody').innerHTML = html;
}
function rptRenderSubTypeRows(subTypeMap) {
  var html = '';
  Object.keys(subTypeMap).forEach(function(subType) {
    var accounts = subTypeMap[subType];
    if (!accounts || !accounts.length) return;
    html += '<tr class="pl-sub-type"><td colspan="2">' + subType.replace(/_/g,' ').replace(/\b\w/g,function(c){return c.toUpperCase();}) + '</td></tr>';
    accounts.forEach(function(acc) {
      html += '<tr><td style="padding-left:28px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
    });
  });
  if (!html) html = '<tr><td colspan="2" class="text-center text-muted py-2" style="font-size:0.8rem;">No transactions for this period.</td></tr>';
  return html;
}

// ── Balance Sheet ─────────────────────────────────────────────────────────────
function rptBsSetDate(preset) {
  var now = new Date();
  var d;
  if (preset === 'today') {
    d = now;
  } else if (preset === 'monthEnd') {
    d = new Date(now.getFullYear(), now.getMonth()+1, 0);
  } else {
    d = new Date(now.getFullYear(), 11, 31);
  }
  document.getElementById('rptBsAsOf').value = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
async function runBalanceSheet() {
  var asOf = document.getElementById('rptBsAsOf').value;
  if (!asOf) { alert('Please select a date.'); return; }
  document.getElementById('rptBsReport').classList.add('d-none');
  document.getElementById('rptBsLoading').classList.remove('d-none');
  try {
    var data = await ERP.api.getBalanceSheet(asOf);
    document.getElementById('rptBsLoading').classList.add('d-none');
    rptRenderBS(data, asOf);
    document.getElementById('rptBsReport').classList.remove('d-none');
  } catch(e) {
    document.getElementById('rptBsLoading').classList.add('d-none');
    alert('Error: ' + e.message);
  }
}
function rptRenderBS(data, asOf) {
  var assetsHtml = rptRenderBsGrouped(data.assets||{});
  document.getElementById('rptBsAssetsBody').innerHTML = assetsHtml || '<tr><td colspan="2" class="text-center text-muted py-3">No data.</td></tr>';
  document.getElementById('rptBsTotalAssets').textContent = ERP.formatCurrency(data.totalAssets||0);

  var liabHtml = rptRenderBsGrouped(data.liabilities||{});
  liabHtml += rptRenderBsGrouped(data.equity||{});
  if (data.retainedEarnings !== undefined) {
    liabHtml += '<tr><td style="padding-left:20px!important;font-size:0.85rem;font-style:italic;">Retained Earnings</td><td class="text-end">' + ERP.formatCurrency(data.retainedEarnings||0) + '</td></tr>';
  }
  document.getElementById('rptBsLiabEquityBody').innerHTML = liabHtml || '<tr><td colspan="2" class="text-center text-muted py-3">No data.</td></tr>';
  document.getElementById('rptBsTotalLiabEquity').textContent = ERP.formatCurrency(data.totalLiabEquity||0);

  var diff = Math.abs((data.totalAssets||0) - (data.totalLiabEquity||0));
  var checkEl = document.getElementById('rptBsBalanceCheck');
  if (diff < 0.01) {
    checkEl.innerHTML = '<span class="bs-balanced"><i class="ti ti-circle-check me-1"></i>Balance Sheet is balanced as of ' + asOf + '</span>';
  } else {
    checkEl.innerHTML = '<span class="bs-unbalanced"><i class="ti ti-alert-triangle me-1"></i>Out of balance by ' + ERP.formatCurrency(diff) + '</span>';
  }
}
function rptRenderBsGrouped(grouped) {
  var html = '';
  Object.keys(grouped).forEach(function(subType) {
    var accounts = grouped[subType];
    if (!accounts || !accounts.length) return;
    var label = subType.replace(/_/g,' ').replace(/\b\w/g,function(c){return c.toUpperCase();});
    html += '<tr class="bs-section-row"><td colspan="2">' + label + '</td></tr>';
    accounts.forEach(function(acc) {
      html += '<tr><td style="padding-left:20px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
    });
  });
  return html;
}
