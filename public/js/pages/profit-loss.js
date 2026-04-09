window.ERP.onReady = function() {
    setPeriod('month');
    loadPL();
};

function setPeriod(period) {
    var now = new Date();
    var y = now.getFullYear(), m = now.getMonth();
    var from, to;
    if (period === 'month') {
        from = new Date(y, m, 1);
        to   = new Date(y, m + 1, 0);
    } else if (period === 'quarter') {
        var q = Math.floor(m / 3);
        from  = new Date(y, q * 3, 1);
        to    = new Date(y, q * 3 + 3, 0);
    } else {
        from = new Date(y, 0, 1);
        to   = new Date(y, 11, 31);
    }
    document.getElementById('plFrom').value = fmtDate(from);
    document.getElementById('plTo').value   = fmtDate(to);
}

function fmtDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

async function loadPL() {
    var from = document.getElementById('plFrom').value;
    var to   = document.getElementById('plTo').value;
    if (!from || !to) { alert('Please select a date range.'); return; }

    document.getElementById('plReport').style.display = 'none';
    document.getElementById('plLoading').style.display = '';
    try {
        var data = await ERP.api.getProfitLoss(from, to);
        document.getElementById('plLoading').style.display = 'none';
        renderPL(data, from, to);
        document.getElementById('plReport').style.display = '';
    } catch(e) {
        document.getElementById('plLoading').style.display = 'none';
        alert('Error: ' + e.message);
    }
}

function renderPL(data, from, to) {
    document.getElementById('plPeriodLabel').textContent = 'Period: ' + from + ' to ' + to;

    var revenue  = data.revenue  || {};
    var cogs     = data.cogs     || {};
    var expenses = data.expenses || {};
    var netProfit = (data.totalRevenue || 0) - (data.totalCogs || 0) - (data.totalExpenses || 0);
    var grossProfit = (data.totalRevenue || 0) - (data.totalCogs || 0);

    var html = '';

    // Revenue section
    html += '<tr class="pl-section-row"><td colspan="2">Revenue</td></tr>';
    html += renderSubTypeRows(revenue);
    html += '<tr class="pl-subtotal-row">' +
        '<td>Total Revenue</td>' +
        '<td class="text-end">' + ERP.formatCurrency(data.totalRevenue || 0) + '</td>' +
        '</tr>';

    // COGS section
    html += '<tr class="pl-section-row"><td colspan="2">Cost of Goods Sold</td></tr>';
    html += renderSubTypeRows(cogs);
    html += '<tr class="pl-subtotal-row">' +
        '<td>Total COGS</td>' +
        '<td class="text-end">' + ERP.formatCurrency(data.totalCogs || 0) + '</td>' +
        '</tr>';

    // Gross Profit
    html += '<tr class="pl-total-row ' + (grossProfit >= 0 ? 'profit' : 'loss') + '">' +
        '<td>Gross Profit</td>' +
        '<td class="text-end">' + ERP.formatCurrency(grossProfit) + '</td>' +
        '</tr>';

    // Expenses section
    html += '<tr class="pl-section-row"><td colspan="2">Operating Expenses</td></tr>';
    html += renderSubTypeRows(expenses);
    html += '<tr class="pl-subtotal-row">' +
        '<td>Total Expenses</td>' +
        '<td class="text-end">' + ERP.formatCurrency(data.totalExpenses || 0) + '</td>' +
        '</tr>';

    // Net Profit
    html += '<tr class="pl-total-row ' + (netProfit >= 0 ? 'profit' : 'loss') + '">' +
        '<td>' + (netProfit >= 0 ? 'Net Profit' : 'Net Loss') + '</td>' +
        '<td class="text-end">' + ERP.formatCurrency(Math.abs(netProfit)) + '</td>' +
        '</tr>';

    document.getElementById('plBody').innerHTML = html;
}

function renderSubTypeRows(subTypeMap) {
    var html = '';
    Object.keys(subTypeMap).forEach(function(subType) {
        var accounts = subTypeMap[subType];
        if (!accounts || !accounts.length) return;
        html += '<tr class="pl-sub-type"><td colspan="2">' + subType.replace(/_/g, ' ').replace(/\b\w/g, function(c){return c.toUpperCase();}) + '</td></tr>';
        accounts.forEach(function(acc) {
            html += '<tr>' +
                '<td style="padding-left:28px!important;">' +
                    '<code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code || '') + '</code>' +
                    ' ' + (acc.name || '') +
                '</td>' +
                '<td class="text-end">' + ERP.formatCurrency(acc.balance || 0) + '</td>' +
                '</tr>';
        });
    });
    if (!html) html = '<tr><td colspan="2" class="text-center text-muted py-2" style="font-size:0.8rem;">No transactions for this period.</td></tr>';
    return html;
}