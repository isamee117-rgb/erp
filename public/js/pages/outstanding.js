var recPage=1, payPage=1, outPerPage=20;
var osCustomers=[], osVendors=[], osCustTotals={}, osVendTotals={};
var osLoading=false, osSearchTimer=null;

function osGetFilters() {
    return {
        search: (document.getElementById('osSearchInput').value || '').trim(),
        from:   document.getElementById('dateFrom').value || '',
        to:     document.getElementById('dateTo').value   || '',
    };
}

function loadOutstanding() {
    if (osLoading) return;
    osLoading = true;
    recPage = 1; payPage = 1;

    var f = osGetFilters();

    // Show loading in both tabs
    ['receivableBody','payableBody'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';
    });

    Promise.all([
        ERP.api.getOutstanding('customer', f.search, f.from, f.to),
        ERP.api.getOutstanding('vendor',   f.search, f.from, f.to),
    ]).then(function(results) {
        osCustomers  = results[0].data   || [];
        osCustTotals = results[0].totals || {};
        osVendors    = results[1].data   || [];
        osVendTotals = results[1].totals || {};
        renderPage();
    }).catch(function(e) {
        ['receivableBody','payableBody'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Error: ' + e.message + '</td></tr>';
        });
    }).finally(function() { osLoading = false; });
}

window.ERP.onReady = function(){
    loadOutstanding();
    document.getElementById('osSearchInput').addEventListener('input', function(){
        clearTimeout(osSearchTimer);
        osSearchTimer = setTimeout(function(){ loadOutstanding(); }, 400);
    });
    var filterBtn = document.getElementById('os-filter-toggle-btn');
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            var panel  = document.getElementById('os-filters-panel');
            var isOpen = !panel.classList.contains('d-none');
            panel.classList.toggle('d-none', isOpen);
            filterBtn.classList.toggle('active', !isOpen);
        });
    }
    var clearBtn = document.getElementById('os-clear-filters-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            document.getElementById('osSearchInput').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value   = '';
            loadOutstanding();
        });
    }
    ['dateFrom','dateTo'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', function(){ loadOutstanding(); });
    });
};

function renderPage(){
    renderTab(osCustomers, osCustTotals, 'receivableBody', 'receivableFoot', 'recPagInfo', 'recPag', recPage, 'recPage');
    renderTab(osVendors,   osVendTotals, 'payableBody',    'payableFoot',    'payPagInfo', 'payPag', payPage, 'payPage');
}

function renderTab(rows, totals, bodyId, footId, pagInfoId, pagId, currentPage, pageVar){
    var total=rows.length, totalPages=Math.max(1,Math.ceil(total/outPerPage));
    if(currentPage>totalPages) currentPage=totalPages;
    var start=(currentPage-1)*outPerPage, pageRows=rows.slice(start, start+outPerPage);
    var html='';
    pageRows.forEach(function(r){
        html+='<tr><td class="fw-bold">'+r.name+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(r.invoiced)+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(r.paid)+'</td>';
        html+='<td class="text-end"><span class="fw-bold '+(r.outstanding>0?'text-danger':'text-success')+'">'+ERP.formatCurrency(r.outstanding)+'</span></td></tr>';
    });
    if(!total) html='<tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-building-bank fs-1 d-block mb-2 text-muted"></i>No outstanding balances</td></tr>';
    document.getElementById(bodyId).innerHTML=html;
    var fhtml='<tr><td class="fw-bold">Total</td><td class="text-end">'+ERP.formatCurrency(totals.invoiced||0)+'</td><td class="text-end">'+ERP.formatCurrency(totals.paid||0)+'</td><td class="text-end fw-bold">'+ERP.formatCurrency(totals.outstanding||0)+'</td></tr>';
    document.getElementById(footId).innerHTML=total?fhtml:'';
    document.getElementById(pagInfoId).textContent='Showing '+(total?start+1:0)+' to '+Math.min(start+outPerPage,total)+' of '+total;
    var ph='';
    ph+='<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage>1?' onclick="osGoTo(\''+pageVar+'\','+(currentPage-1)+')"':'')+'>&#171;</a></li>';
    var _s={},_l=0;
    for(var p=1;p<=Math.min(2,totalPages);p++) _s[p]=true;
    for(var p=Math.max(1,currentPage-2);p<=Math.min(totalPages,currentPage+2);p++) _s[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _s[p]=true;
    for(var i=1;i<=totalPages;i++){
        if(!_s[i]) continue;
        if(_l>0&&i-_l>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        ph+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="osGoTo(\''+pageVar+'\','+i+')">'+i+'</a></li>';
        _l=i;
    }
    ph+='<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage<totalPages?' onclick="osGoTo(\''+pageVar+'\','+(currentPage+1)+')"':'')+'>&#187;</a></li>';
    document.getElementById(pagId).innerHTML=ph;
}

function osGoTo(pageVar, page){
    if(pageVar==='recPage') recPage=page;
    else payPage=page;
    renderPage();
}
