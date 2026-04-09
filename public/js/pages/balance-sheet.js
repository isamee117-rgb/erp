window.ERP.onReady = function() {
    setToday();
    loadBS();
};

function fmtDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function setToday() {
    document.getElementById('bsAsOf').value = fmtDate(new Date());
}

function setMonthEnd() {
    var now = new Date();
    document.getElementById('bsAsOf').value = fmtDate(new Date(now.getFullYear(), now.getMonth() + 1, 0));
}

function setYearEnd() {
    document.getElementById('bsAsOf').value = new Date().getFullYear() + '-12-31';
}

async function loadBS() {
    var asOf = document.getElementById('bsAsOf').value;
    if (!asOf) { alert('Please select a date.'); return; }

    document.getElementById('bsReport').style.display = 'none';
    document.getElementById('bsLoading').style.display = '';
    try {
        var data = await ERP.api.getBalanceSheet(asOf);
        document.getElementById('bsLoading').style.display = 'none';
        renderBS(data, asOf);
        document.getElementById('bsReport').style.display = '';
    } catch(e) {
        document.getElementById('bsLoading').style.display = 'none';
        alert('Error: ' + e.message);
    }
}

// Controller returns assets/liabilities/equity as {sub_type: [{code, name, balance}]}
function renderBS(data, asOf) {
    var assetsHtml = renderBsGrouped(data.assets || {});
    document.getElementById('bsAssetsBody').innerHTML = assetsHtml || emptyRow();
    document.getElementById('bsTotalAssets').textContent = ERP.formatCurrency(data.totalAssets || 0);

    var liabHtml = renderBsGrouped(data.liabilities || {});
    liabHtml += renderBsGrouped(data.equity || {});
    if (data.retainedEarnings !== undefined) {
        liabHtml += '<tr>' +
            '<td style="padding-left:20px!important;font-size:0.85rem;font-style:italic;">Retained Earnings</td>' +
            '<td class="text-end">' + ERP.formatCurrency(data.retainedEarnings || 0) + '</td>' +
            '</tr>';
    }
    document.getElementById('bsLiabEquityBody').innerHTML = liabHtml || emptyRow();
    document.getElementById('bsTotalLiabEquity').textContent = ERP.formatCurrency(data.totalLiabEquity || 0);

    // Balance check
    var diff = Math.abs((data.totalAssets || 0) - (data.totalLiabEquity || 0));
    var checkEl = document.querySelector('#bsBalanceCheck .set-card-body');
    if (diff < 0.01) {
        checkEl.innerHTML = '<span class="bs-balanced"><i class="ti ti-circle-check me-1"></i>Balance Sheet is balanced as of ' + asOf + '</span>';
    } else {
        checkEl.innerHTML = '<span class="bs-unbalanced"><i class="ti ti-alert-triangle me-1"></i>Out of balance by ' + ERP.formatCurrency(diff) + ' — Assets: ' + ERP.formatCurrency(data.totalAssets || 0) + ' | Liabilities + Equity: ' + ERP.formatCurrency(data.totalLiabEquity || 0) + '</span>';
    }
}

function renderBsGrouped(grouped) {
    var html = '';
    Object.keys(grouped).forEach(function(subType) {
        var accounts = grouped[subType];
        if (!accounts || !accounts.length) return;
        var label = subType.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
        html += '<tr class="bs-section-row"><td colspan="2">' + label + '</td></tr>';
        accounts.forEach(function(acc) {
            html += '<tr>' +
                '<td style="padding-left:20px!important;">' +
                    '<code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code || '') + '</code>' +
                    ' ' + (acc.name || '') +
                '</td>' +
                '<td class="text-end">' + ERP.formatCurrency(acc.balance || 0) + '</td>' +
                '</tr>';
        });
    });
    return html;
}

function emptyRow() {
    return '<tr><td colspan="2" class="text-center text-muted py-3" style="font-size:0.82rem;">No data for selected period.</td></tr>';
}