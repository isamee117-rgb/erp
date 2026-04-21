var recPage=1, payPage=1, outPerPage=20;

function osRefetchIfNeeded(callback) {
    var loadedFrom = window.ERP.state.transactionLoadedFrom;
    var requestedFrom = (document.getElementById('dateFrom') ? document.getElementById('dateFrom').value : '') || '';
    var requestedTo   = (document.getElementById('dateTo')   ? document.getElementById('dateTo').value   : '') || '';
    if (loadedFrom && requestedFrom && requestedFrom < loadedFrom) {
        ERP.api.syncTransactions({ from: requestedFrom, to: requestedTo || undefined })
            .then(function(txData) {
                ERP.mergeState(txData);
                if (txData.loadedFrom) window.ERP.state.transactionLoadedFrom = txData.loadedFrom;
                if (typeof callback === 'function') callback();
            })
            .catch(function(e) { alert('Error loading data: ' + e.message); });
    } else {
        if (typeof callback === 'function') callback();
    }
}

window.ERP.onReady = function(){ renderPage(); };
function renderPage(){
    var state=window.ERP.state;
    var df=document.getElementById('dateFrom').value, dt=document.getElementById('dateTo').value;
    var fromTs=df?new Date(df).setHours(0,0,0,0):null, toTs=dt?new Date(dt).setHours(23,59,59,999):null;
    var customers=(state.parties||[]).filter(function(p){return p.type==='Customer';});
    var vendors=(state.parties||[]).filter(function(p){return p.type==='Vendor';});
    renderTab(customers,'Customer','receivableBody','receivableFoot','recPagInfo','recPag',recPage,'recPage',fromTs,toTs);
    renderTab(vendors,'Vendor','payableBody','payableFoot','payPagInfo','payPag',payPage,'payPage',fromTs,toTs);
}
function inRange(ts,fromTs,toTs){ return (!fromTs||ts>=fromTs)&&(!toTs||ts<=toTs); }
function renderTab(parties,type,bodyId,footId,pagInfoId,pagId,currentPage,pageVar,fromTs,toTs){
    var state=window.ERP.state;
    var rows=[], totalSales=0, totalPayments=0, totalOutstanding=0;
    parties.forEach(function(party){
        var sales=0, payments=0;
        if(type==='Customer'){
            (state.sales||[]).filter(function(s){return s.customerId===party.id&&inRange(s.createdAt,fromTs,toTs);}).forEach(function(s){sales+=(s.totalAmount||0);});
            (state.payments||[]).filter(function(p){return p.partyId===party.id&&(p.type==='Payment Received'||p.type==='Receipt')&&inRange(p.date,fromTs,toTs);}).forEach(function(p){payments+=(p.amount||0);});
            (state.salesReturns||[]).filter(function(r){return r.customerId===party.id&&inRange(r.createdAt,fromTs,toTs);}).forEach(function(r){sales-=(r.totalAmount||0);});
        } else {
            (state.purchaseOrders||[]).filter(function(po){return po.vendorId===party.id&&inRange(po.createdAt,fromTs,toTs);}).forEach(function(po){sales+=(po.totalAmount||0);});
            (state.payments||[]).filter(function(p){return p.partyId===party.id&&p.type==='Payment Made'&&inRange(p.date,fromTs,toTs);}).forEach(function(p){payments+=(p.amount||0);});
            (state.purchaseReturns||[]).filter(function(r){return r.vendorId===party.id&&inRange(r.createdAt,fromTs,toTs);}).forEach(function(r){sales-=(r.totalAmount||0);});
        }
        var outstanding=sales-payments+(fromTs||toTs?0:(party.openingBalance||0));
        if(Math.abs(outstanding)>0.01){
            rows.push({name:party.name,sales:sales,payments:payments,outstanding:outstanding});
            totalSales+=sales; totalPayments+=payments; totalOutstanding+=outstanding;
        }
    });
    rows.sort(function(a,b){return b.outstanding-a.outstanding;});
    var total=rows.length, totalPages=Math.max(1,Math.ceil(total/outPerPage));
    if(currentPage>totalPages) currentPage=totalPages;
    var start=(currentPage-1)*outPerPage, pageRows=rows.slice(start, start+outPerPage);
    var html='';
    pageRows.forEach(function(r){
        html+='<tr><td class="fw-bold">'+r.name+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(r.sales)+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(r.payments)+'</td>';
        html+='<td class="text-end"><span class="fw-bold '+(r.outstanding>0?'text-danger':'text-success')+'">'+ERP.formatCurrency(r.outstanding)+'</span></td></tr>';
    });
    if(!total) html='<tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-building-bank fs-1 d-block mb-2 text-muted"></i>No outstanding balances</td></tr>';
    document.getElementById(bodyId).innerHTML=html;
    var fhtml='<tr><td class="fw-bold">Total</td><td class="text-end">'+ERP.formatCurrency(totalSales)+'</td><td class="text-end">'+ERP.formatCurrency(totalPayments)+'</td><td class="text-end fw-bold">'+ERP.formatCurrency(totalOutstanding)+'</td></tr>';
    document.getElementById(footId).innerHTML=total?fhtml:'';
    document.getElementById(pagInfoId).textContent='Showing '+(total?start+1:0)+' to '+Math.min(start+outPerPage,total)+' of '+total;
    var ph='';
    ph+='<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage>1?' onclick="'+pageVar+'='+(currentPage-1)+';renderPage()"':'')+'>&#171;</a></li>';
    var _s={},_l=0;
    for(var p=1;p<=Math.min(2,totalPages);p++) _s[p]=true;
    for(var p=Math.max(1,currentPage-2);p<=Math.min(totalPages,currentPage+2);p++) _s[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _s[p]=true;
    for(var i=1;i<=totalPages;i++){
        if(!_s[i]) continue;
        if(_l>0&&i-_l>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        ph+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="'+pageVar+'='+i+';renderPage()">'+i+'</a></li>';
        _l=i;
    }
    ph+='<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage<totalPages?' onclick="'+pageVar+'='+(currentPage+1)+';renderPage()"':'')+'>&#187;</a></li>';
    document.getElementById(pagId).innerHTML=ph;
}
