var plPage=1, plPerPage=20;

function plRefetchIfNeeded(callback) {
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

window.ERP.onReady = function(){ populateParties(); renderPage(); };

/* ── SDD helpers ── */
function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function sddToggle(wrapId){
    var wrap=document.getElementById(wrapId), isOpen=wrap.classList.contains('open');
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
    if(!isOpen){
        wrap.classList.add('open');
        var inp=wrap.querySelector('.sdd-search-inp');
        if(inp){inp.value='';sddFilterOpts(wrapId,'');setTimeout(function(){inp.focus();},50);}
    }
}
function sddFilterOpts(wrapId,query){
    var wrap=document.getElementById(wrapId),q=query.toLowerCase().trim(),opts=wrap.querySelectorAll('.sdd-opt'),visible=0;
    opts.forEach(function(o){var m=!q||o.textContent.toLowerCase().indexOf(q)!==-1;o.style.display=m?'':'none';if(m)visible++;});
    var nr=wrap.querySelector('.sdd-no-res');if(nr)nr.style.display=visible===0?'':'none';
}
function sddSelectParty(partyId, label){
    document.getElementById('partySelect').value = partyId;
    document.getElementById('partySelect-disp').textContent = label;
    document.getElementById('partySelect-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
    plPage=1; renderPage();
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.sdd-wrap')) document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
});

function populateParties(){
    var html='';
    (window.ERP.state.parties||[]).forEach(function(p){
        var label=escHtml(p.name)+' <span class="erp-dropdown-type-label">('+escHtml(p.type)+')</span>';
        html+='<div class="sdd-opt" onclick="sddSelectParty(\''+escHtml(p.id)+'\',\''+escHtml(p.name)+' ('+escHtml(p.type)+')\')">'+label+'</div>';
    });
    html+='<div class="sdd-no-res" class="d-none">No parties found</div>';
    document.getElementById('partySelect-opts').innerHTML=html;
}
function renderPage(){
    var partyId=document.getElementById('partySelect').value;
    if(!partyId){ document.getElementById('ledgerBody').innerHTML='<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-file-text fs-1 d-block mb-2 text-muted"></i>Select a party to view ledger</td></tr>'; return; }
    var state=window.ERP.state, df=document.getElementById('dateFrom').value, dt=document.getElementById('dateTo').value;
    var party=(state.parties||[]).find(function(p){return p.id===partyId;});
    var isCustomer=party&&(party.type||'').toLowerCase().indexOf('customer')!==-1;
    var entries=[];
    // Sales → Credit for customer (they owe us)
    (state.sales||[]).filter(function(s){return s.customerId===partyId;}).forEach(function(s){
        entries.push({date:s.createdAt,type:'Sale',ref:s.id,debit:0,credit:s.totalAmount||0});
    });
    // Purchases → Debit for vendor (we owe them)
    (state.purchaseOrders||[]).filter(function(po){return po.vendorId===partyId;}).forEach(function(po){
        entries.push({date:po.createdAt,type:'Purchase',ref:po.id,debit:po.totalAmount||0,credit:0});
    });
    // Payments
    (state.payments||[]).filter(function(p){return p.partyId===partyId;}).forEach(function(p){
        var isReceived=(p.type==='Payment Received'||p.type==='Receipt');
        if(isReceived)
            // Customer paid us → Debit (reduces their outstanding credit)
            entries.push({date:p.date,type:'Payment Received',ref:p.referenceNo||'—',debit:p.amount||0,credit:0});
        else
            // We paid vendor → Credit (reduces our outstanding debit)
            entries.push({date:p.date,type:'Payment Made',ref:p.referenceNo||'—',debit:0,credit:p.amount||0});
    });
    // Sale Returns → Debit for customer (reduces what they owe us)
    (state.salesReturns||[]).filter(function(r){return r.customerId===partyId;}).forEach(function(r){
        entries.push({date:r.createdAt,type:'Sale Return',ref:r.id,debit:r.totalAmount||0,credit:0});
    });
    // Purchase Returns → Credit for vendor (reduces what we owe them)
    (state.purchaseReturns||[]).filter(function(r){return r.vendorId===partyId;}).forEach(function(r){
        entries.push({date:r.createdAt,type:'Purchase Return',ref:r.id,debit:0,credit:r.totalAmount||0});
    });
    entries.sort(function(a,b){return a.date-b.date;});
    if(df){var fromTs=new Date(df).setHours(0,0,0,0);entries=entries.filter(function(e){return e.date>=fromTs;});}
    if(dt){var toTs=new Date(dt).setHours(23,59,59,999);entries=entries.filter(function(e){return e.date<=toTs;});}
    var balance=party?(party.openingBalance||0):0;
    // Pre-compute all rows with running balances
    var allRows=[];
    if(balance!==0){
        allRows.push({isOpening:true, bal:balance});
    }
    entries.forEach(function(e){
        if(isCustomer) balance+=e.credit-e.debit;
        else balance+=e.debit-e.credit;
        var badgeColor='badge-blue';
        if(e.type==='Sale') badgeColor='badge-red';
        else if(e.type==='Purchase') badgeColor='badge-green';
        else if(e.type==='Payment Received') badgeColor='badge-green';
        else if(e.type==='Payment Made') badgeColor='badge-orange';
        else if(e.type.indexOf('Return')!==-1) badgeColor='badge-gray';
        allRows.push({e:e, bal:balance, badgeColor:badgeColor});
    });
    var total=allRows.length, totalPages=Math.max(1,Math.ceil(total/plPerPage));
    if(plPage>totalPages) plPage=totalPages;
    var start=(plPage-1)*plPerPage, pageRows=allRows.slice(start, start+plPerPage);
    var html='';
    pageRows.forEach(function(row){
        if(row.isOpening){
            html+='<tr class="opening-row"><td>—</td><td><span class="badge-pill badge-gray">Opening Balance</span></td><td>—</td><td class="text-end">—</td><td class="text-end">—</td><td class="text-end fw-bold">'+ERP.formatCurrency(row.bal)+'</td></tr>';
        } else {
            var e=row.e;
            html+='<tr><td>'+new Date(e.date).toLocaleDateString()+'</td>';
            html+='<td><span class="badge-pill '+row.badgeColor+'">'+e.type+'</span></td>';
            html+='<td>'+e.ref+'</td>';
            html+='<td class="text-end">'+(e.debit?'<span class="text-danger fw-bold">'+ERP.formatCurrency(e.debit)+'</span>':'<span class="text-muted">—</span>')+'</td>';
            html+='<td class="text-end">'+(e.credit?'<span class="text-success fw-bold">'+ERP.formatCurrency(e.credit)+'</span>':'<span class="text-muted">—</span>')+'</td>';
            html+='<td class="text-end fw-bold">'+ERP.formatCurrency(row.bal)+'</td></tr>';
        }
    });
    if(!allRows.length) html='<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-file-text fs-1 d-block mb-2 text-muted"></i>No transactions found</td></tr>';
    document.getElementById('ledgerBody').innerHTML=html;
    document.getElementById('plPagInfo').textContent='Showing '+(total?start+1:0)+' to '+Math.min(start+plPerPage,total)+' of '+total+' entries';
    buildPlPag(totalPages, plPage);
}
function buildPlPag(totalPages, currentPage){
    var ph='';
    ph+='<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage>1?' onclick="plPage='+(currentPage-1)+';renderPage()"':'')+'>&#171;</a></li>';
    var _s={},_l=0;
    for(var p=1;p<=Math.min(2,totalPages);p++) _s[p]=true;
    for(var p=Math.max(1,currentPage-2);p<=Math.min(totalPages,currentPage+2);p++) _s[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _s[p]=true;
    for(var i=1;i<=totalPages;i++){
        if(!_s[i]) continue;
        if(_l>0&&i-_l>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        ph+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="plPage='+i+';renderPage()">'+i+'</a></li>';
        _l=i;
    }
    ph+='<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage<totalPages?' onclick="plPage='+(currentPage+1)+';renderPage()"':'')+'>&#187;</a></li>';
    document.getElementById('plPag').innerHTML=ph;
}
