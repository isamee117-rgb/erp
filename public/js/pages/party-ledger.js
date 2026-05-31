var plPage=1, plPerPage=20;
var plEntries=[], plOpeningBalance=0, plIsCustomer=true, plLoading=false;

window.ERP.onReady = function(){ populateParties(); };

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
    plLoad();
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.sdd-wrap')) document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
});

document.addEventListener('DOMContentLoaded', function() {
    var filterBtn = document.getElementById('pl-filter-toggle-btn');
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            var panel  = document.getElementById('pl-filters-panel');
            var isOpen = !panel.classList.contains('d-none');
            panel.classList.toggle('d-none', isOpen);
            filterBtn.classList.toggle('active', !isOpen);
        });
    }
    var clearBtn = document.getElementById('pl-clear-filters-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value   = '';
            plLoad();
        });
    }
    ['dateFrom','dateTo'].forEach(function(id){
        var el=document.getElementById(id);
        if(el) el.addEventListener('change', function(){ plLoad(); });
    });
});

function plLoad(){
    var partyId=document.getElementById('partySelect').value;
    if(!partyId){ renderPage(); return; }
    if(plLoading) return;
    plLoading=true; plPage=1;

    var tbody=document.getElementById('ledgerBody');
    if(tbody) tbody.innerHTML='<tr><td colspan="6" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';

    var from=document.getElementById('dateFrom').value||'';
    var to=document.getElementById('dateTo').value||'';

    ERP.api.getPartyLedger(partyId, from, to)
        .then(function(res){
            plEntries=res.entries||[];
            plOpeningBalance=res.openingBalance||0;
            plIsCustomer=res.isCustomer!==false;
            renderPage();
        })
        .catch(function(e){
            if(tbody) tbody.innerHTML='<tr><td colspan="6" class="text-center text-danger py-4">Error: '+e.message+'</td></tr>';
        })
        .finally(function(){ plLoading=false; });
}

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
    if(!partyId){ document.getElementById('ledgerBody').innerHTML='<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-file-text fs-1 d-block mb-2 text-muted"></i>Select a party to view ledger</td></tr>'; document.getElementById('plPagInfo').textContent=''; document.getElementById('plPag').innerHTML=''; return; }

    var balance=plOpeningBalance;
    var allRows=[];
    if(balance!==0) allRows.push({isOpening:true, bal:balance});

    plEntries.forEach(function(e){
        if(plIsCustomer) balance+=e.credit-e.debit;
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
function plGoTo(p){ plPage=p; renderPage(); }
function buildPlPag(totalPages, currentPage){
    var ph='';
    ph+='<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage>1?' onclick="plGoTo('+(currentPage-1)+')"':'')+'>&#171;</a></li>';
    var _s={},_l=0;
    for(var p=1;p<=Math.min(2,totalPages);p++) _s[p]=true;
    for(var p=Math.max(1,currentPage-2);p<=Math.min(totalPages,currentPage+2);p++) _s[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _s[p]=true;
    for(var i=1;i<=totalPages;i++){
        if(!_s[i]) continue;
        if(_l>0&&i-_l>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        ph+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="plGoTo('+i+')">'+i+'</a></li>';
        _l=i;
    }
    ph+='<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage<totalPages?' onclick="plGoTo('+(currentPage+1)+')"':'')+'>&#187;</a></li>';
    document.getElementById('plPag').innerHTML=ph;
}
