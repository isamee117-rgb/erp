window.ERP.onReady = function() { renderPage(); };

function renderPage() {
  var state = window.ERP.state;
  var user = state.currentUser;
  if (!user) return;
  var isSuperAdmin = user.systemRole === 'Super Admin';
  var companyId = user.companyId;
  var container = document.getElementById('dashboard-container');

  if (isSuperAdmin) {
    renderSuperAdminDashboard(state, container);
  } else {
    renderUserDashboard(state, container, companyId);
  }
}

function renderSuperAdminDashboard(state, container) {
  var companies = state.companies || [];
  var allSales = state.sales || [];
  var allReturns = state.salesReturns || [];
  var allUsers = state.users || [];

  var saasRevenue = companies.reduce(function(acc, co) { return acc + (co.registrationPayment || 0); }, 0);
  var grossVolume = allSales.reduce(function(acc, s) { return acc + (s.totalAmount || 0); }, 0);
  var returnVolume = allReturns.reduce(function(acc, r) { return acc + (r.totalAmount || 0); }, 0);
  var bizVolume = grossVolume - returnVolume;
  var totalSeats = companies.reduce(function(acc, co) { return acc + (co.maxUserLimit || 0); }, 0);
  var activeSeats = allUsers.filter(function(u) { return u.companyId !== null; }).length;
  var seatUtil = totalSeats > 0 ? Math.round((activeSeats / totalSeats) * 100) : 0;
  var activeCompanies = companies.filter(function(c) { return c.status === 'Active'; }).length;

  var html = '<div class="db-page-header"><div class="d-flex align-items-center justify-content-between">' +
    '<div><div class="db-page-title"><i class="ti ti-globe me-2"></i>Global Insight Command</div>' +
    '<div class="db-page-subtitle">System-wide telemetry and SaaS financial metrics.</div></div>' +
    '<span class="db-master-badge">MASTER VIEW</span></div></div>';

  html += '<div class="db-kpi-grid">';
  var cards = [
    { label: 'SaaS Earnings', value: ERP.formatCurrency(saasRevenue), icon: 'ti-coin', color: 'db-kpi-icon-green', sub: 'Total Registration Fees' },
    { label: 'Net Business Volume', value: ERP.formatCurrency(bizVolume), icon: 'ti-bolt', color: 'db-kpi-icon-blue', sub: 'Gross ' + ERP.formatCurrency(grossVolume) + ' · Returns ' + ERP.formatCurrency(returnVolume) },
    { label: 'Platform Tenants', value: companies.length, icon: 'ti-building', color: 'db-kpi-icon-purple', sub: activeCompanies + ' Active Workspaces' },
    { label: 'Seat Utilization', value: seatUtil + '%', icon: 'ti-users', color: 'db-kpi-icon-orange', sub: activeSeats + ' / ' + totalSeats + ' Seats' }
  ];
  cards.forEach(function(c) {
    html += '<div class="db-kpi-card">' +
      '<div class="db-kpi-icon-wrap ' + c.color + '"><i class="ti ' + c.icon + '"></i></div>' +
      '<div class="db-kpi-label">' + c.label + '</div>' +
      '<div class="db-kpi-value">' + c.value + '</div>' +
      '<div class="db-kpi-sub">' + c.sub + '</div>' +
      '</div>';
  });
  html += '</div>';

  html += '<div class="db-row db-row-8-4">' +
    '<div class="db-section-card"><div class="db-section-header"><i class="ti ti-trending-up" class="text-erp-primary"></i><span class="db-section-title">Global Sales Velocity</span></div>' +
    '<div class="db-chart-body"><div id="sales-chart" class="db-chart-div"></div></div></div>' +
    '<div class="db-section-card"><div class="db-section-header"><i class="ti ti-chart-donut" class="text-erp-primary"></i><span class="db-section-title">SaaS Fee Distribution</span></div>' +
    '<div class="db-chart-body"><div id="pie-chart" class="db-chart-div"></div></div></div></div>';

  html += '<div class="db-section-card"><div class="db-section-header"><i class="ti ti-trophy erp-text-amber"></i><span class="db-section-title">Enterprise Tenant Leaderboard</span></div>' +
    '<div class="table-responsive"><table class="db-table">' +
    '<thead><tr><th class="db-th">Rank & Organization</th><th class="db-th">Status</th><th class="db-th">Business Activity</th><th class="db-th" class="text-end">SaaS Revenue</th></tr></thead>' +
    '<tbody id="leaderboard-body"></tbody></table></div></div>';

  container.innerHTML = html;

  var sorted = companies.slice().sort(function(a, b) { return (b.registrationPayment || 0) - (a.registrationPayment || 0); });
  var tbody = document.getElementById('leaderboard-body');
  var tbodyHtml = '';
  sorted.forEach(function(co, idx) {
    var coSales = allSales.filter(function(s) { return s.companyId === co.id; }).reduce(function(acc, s) { return acc + (s.totalAmount || 0); }, 0);
    var coUsers = allUsers.filter(function(u) { return u.companyId === co.id; }).length;
    tbodyHtml += '<tr><td class="db-td"><div class="d-flex align-items-center gap-3">' +
      '<span class="db-rank">#' + (idx + 1) + '</span>' +
      '<div class="db-company-avatar">' + (co.name || '?').charAt(0).toUpperCase() + '</div>' +
      '<div><div class="db-company-name">' + (co.name || '') + '</div><div class="erp-text-mono">ID: ' + (co.id || '').substring(0, 8) + '</div></div>' +
      '</div></td>' +
      '<td class="db-td"><span class="db-badge ' + (co.status === 'Blocked' ? 'db-badge-red' : 'db-badge-green') + '">' + (co.status || 'Active') + '</span></td>' +
      '<td class="db-td"><div class="db-kpi-value">' + ERP.formatCurrency(coSales) + '</div><div class="db-kpi-sub">' + coUsers + ' Users</div></td>' +
      '<td class="db-td" class="db-revenue-cell">' + ERP.formatCurrency(co.registrationPayment || 0) + '</td></tr>';
  });
  if (!sorted.length) tbodyHtml = '<tr><td colspan="4" class="db-td db-empty"><i class="ti ti-building db-empty-icon"></i>No companies found</td></tr>';
  tbody.innerHTML = tbodyHtml;

  var trendData = buildTrendData(allSales, allReturns);
  renderAreaChart('sales-chart', trendData);

  var distData = companies.filter(function(co) { return (co.registrationPayment || 0) > 0; }).map(function(co) { return { name: co.name, value: co.registrationPayment || 0 }; });
  renderPieChart('pie-chart', distData);
}

function renderUserDashboard(state, container, companyId) {
  var products = (state.products || []).filter(function(p) { return p.companyId === companyId; });
  var sales = (state.sales || []).filter(function(s) { return s.companyId === companyId; });
  var salesReturns = (state.salesReturns || []).filter(function(r) { return r.companyId === companyId; });
  var pos = (state.purchaseOrders || []).filter(function(po) { return po.companyId === companyId; });

  var grossSales = sales.reduce(function(acc, s) { return acc + (s.total || s.totalAmount || 0); }, 0);
  var totalReturns = salesReturns.reduce(function(acc, r) { return acc + (r.totalAmount || 0); }, 0);
  var totalSales = grossSales - totalReturns;
  var inventoryVal = products.reduce(function(acc, p) { return acc + ((p.currentStock || 0) * (p.costPrice || p.unitCost || 0)); }, 0);
  var lowStock = products.filter(function(p) { return (p.currentStock || 0) <= (p.reorderLevel || 0); }).length;
  var pendingPOs = pos.filter(function(po) { return po.status === 'Draft'; }).length;

  var html = '<div class="db-page-header">' +
    '<div class="db-page-title"><i class="ti ti-layout-dashboard me-2"></i>Business Dashboard</div>' +
    '<div class="db-page-subtitle">Real-time performance metrics for your organization.</div></div>';

  html += '<div class="db-kpi-grid">';
  var cards = [
    { label: 'Net Sales', value: ERP.formatCurrency(totalSales), icon: 'ti-trending-up', color: 'db-kpi-icon-green', sub: sales.length + ' orders · ' + salesReturns.length + ' returns' },
    { label: 'Inventory Value', value: ERP.formatCurrency(inventoryVal), icon: 'ti-package', color: 'db-kpi-icon-blue', sub: products.length + ' products tracked' },
    { label: 'Low Stock Alerts', value: lowStock, icon: 'ti-alert-triangle', color: 'db-kpi-icon-orange', sub: 'Products at reorder level' },
    { label: 'Pending Purchases', value: pendingPOs, icon: 'ti-shopping-bag', color: 'db-kpi-icon-purple', sub: 'Draft purchase orders' }
  ];
  cards.forEach(function(c) {
    html += '<div class="db-kpi-card">' +
      '<div class="db-kpi-icon-wrap ' + c.color + '"><i class="ti ' + c.icon + '"></i></div>' +
      '<div class="db-kpi-label">' + c.label + '</div>' +
      '<div class="db-kpi-value">' + c.value + '</div>' +
      '<div class="db-kpi-sub">' + c.sub + '</div>' +
      '</div>';
  });
  html += '</div>';

  html += '<div class="db-row db-row-8-4">' +
    '<div class="db-section-card"><div class="db-section-header"><i class="ti ti-chart-area" class="text-erp-primary"></i><span class="db-section-title">Revenue Trend (Last 7 Days)</span></div>' +
    '<div class="db-chart-body"><div id="sales-chart" class="db-chart-div"></div></div></div>' +
    '<div class="db-section-card"><div class="db-section-header"><i class="ti ti-activity" class="text-success"></i><span class="db-section-title">Recent Transactions</span></div>' +
    '<div id="activity-list"></div></div></div>';

  container.innerHTML = html;

  var trendData = buildTrendData(sales, salesReturns);
  renderAreaChart('sales-chart', trendData);

  var activityEl = document.getElementById('activity-list');
  var recentSales = sales.slice(-10).reverse();
  if (recentSales.length === 0) {
    activityEl.innerHTML = '<div class="db-empty"><span class="db-empty-icon ti ti-shopping-bag"></span>No transactions yet</div>';
  } else {
    var aHtml = '';
    recentSales.forEach(function(sale) {
      var saleId = (sale.id || '').slice(-6);
      var time = new Date(sale.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      var date = new Date(sale.createdAt).toLocaleDateString();
      aHtml += '<div class="db-activity-item">' +
        '<div><div class="db-activity-id">#' + saleId + '</div><div class="db-activity-time">' + date + ' &middot; ' + time + '</div></div>' +
        '<div class="db-activity-amount">' + ERP.formatCurrency(sale.total || sale.totalAmount || 0) + '</div></div>';
    });
    activityEl.innerHTML = aHtml;
  }
}

function buildTrendData(sales, returns) {
  returns = returns || [];
  var days = [];
  for (var i = 6; i >= 0; i--) {
    var d = new Date(); d.setDate(d.getDate() - i);
    days.push(d.toISOString().split('T')[0]);
  }
  return days.map(function(date) {
    var salesTotal = sales.filter(function(s) {
      return new Date(s.createdAt).toISOString().split('T')[0] === date;
    }).reduce(function(acc, s) { return acc + (s.total || s.totalAmount || 0); }, 0);
    var returnsTotal = returns.filter(function(r) {
      return new Date(r.createdAt).toISOString().split('T')[0] === date;
    }).reduce(function(acc, r) { return acc + (r.totalAmount || 0); }, 0);
    return { label: new Date(date).toLocaleDateString(undefined, { weekday: 'short' }), value: Math.max(0, salesTotal - returnsTotal) };
  });
}

function renderAreaChart(elId, data) {
  var el = document.getElementById(elId);
  if (!el) return;
  new ApexCharts(el, {
    chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', sparkline: { enabled: false } },
    series: [{ name: 'Revenue', data: data.map(function(d) { return d.value; }) }],
    xaxis: { categories: data.map(function(d) { return d.label; }), axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
    yaxis: { labels: { formatter: function(val) { return ERP.formatCurrency(val); }, style: { fontSize: '11px', colors: '#94a3b8' } } },
    colors: ['#3B4FE4'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02 } },
    stroke: { curve: 'smooth', width: 2.5 },
    dataLabels: { enabled: false },
    grid: { borderColor: '#F0F2F8', strokeDashArray: 3, padding: { left: 8, right: 8 } },
    tooltip: { y: { formatter: function(val) { return ERP.formatCurrency(val); } } }
  }).render();
}

function renderPieChart(elId, data) {
  var el = document.getElementById(elId);
  if (!el || data.length === 0) {
    if (el) el.innerHTML = '<div class="db-empty"><span class="db-empty-icon ti ti-chart-donut"></span>No data available</div>';
    return;
  }
  new ApexCharts(el, {
    chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
    series: data.map(function(d) { return d.value; }),
    labels: data.map(function(d) { return d.name; }),
    colors: ['#3B4FE4','#059669','#ea580c','#7c3aed','#0d9488','#ca8a04'],
    legend: { position: 'bottom', fontSize: '11px', fontFamily: 'Inter, sans-serif' },
    dataLabels: { enabled: false },
    plotOptions: { pie: { donut: { size: '65%' } } },
    tooltip: { y: { formatter: function(val) { return ERP.formatCurrency(val); } } }
  }).render();
}
