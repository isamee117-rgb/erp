var ilPage=1, ilPerPage=20;
window.ERP.onReady = function(){ populateProducts(); renderPage(); };

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
function sddSelectProduct(productId, label){
    document.getElementById('productSelect').value=productId;
    document.getElementById('productSelect-disp').textContent=label;
    document.getElementById('productSelect-disp').style.color='#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
    ilPage=1; renderPage();
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.sdd-wrap')) document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
});

function populateProducts(){
    var html='';
    (window.ERP.state.products||[]).forEach(function(p){
        var label=escHtml(p.name)+' <span class="erp-dropdown-type-label">('+escHtml(p.sku)+')</span>';
        html+='<div class="sdd-opt" onclick="sddSelectProduct(\''+escHtml(p.id)+'\',\''+escHtml(p.name)+' ('+escHtml(p.sku)+')\')">'+label+'</div>';
    });
    html+='<div class="sdd-no-res" class="d-none">No products found</div>';
    document.getElementById('productSelect-opts').innerHTML=html;
}
function renderPage(){
    var productId=document.getElementById('productSelect').value;
    if(!productId){
        document.getElementById('ledgerBody').innerHTML='<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-receipt fs-1 d-block mb-2 text-muted"></i>Select a product to view ledger</td></tr>';
        document.getElementById('ilPagInfo').textContent=''; document.getElementById('ilPag').innerHTML=''; return;
    }
    var state=window.ERP.state, df=document.getElementById('dateFrom').value, dt=document.getElementById('dateTo').value;
    var entries=(state.ledger||[]).filter(function(e){return e.productId===productId;});
    entries.sort(function(a,b){return new Date(a.createdAt||a.date)-new Date(b.createdAt||b.date);});
    if(df) entries=entries.filter(function(e){return new Date(e.createdAt||e.date).toISOString().split('T')[0]>=df;});
    if(dt) entries=entries.filter(function(e){return new Date(e.createdAt||e.date).toISOString().split('T')[0]<=dt;});
    // Pre-compute all rows with running balance
    var allRows=[], bal=0;
    entries.forEach(function(e){
        bal+=e.quantityChange;
        allRows.push({e:e, bal:bal});
    });
    var total=allRows.length, totalPages=Math.max(1,Math.ceil(total/ilPerPage));
    if(ilPage>totalPages) ilPage=totalPages;
    var start=(ilPage-1)*ilPerPage, pageRows=allRows.slice(start, start+ilPerPage);
    var html='';
    pageRows.forEach(function(row){
        var e=row.e, qtyIn=e.quantityChange>0?e.quantityChange:0, qtyOut=e.quantityChange<0?Math.abs(e.quantityChange):0;
        var typeLabel=e.transactionType.replace(/_/g,' '), badgeColor='badge-blue';
        if(e.transactionType.indexOf('Sale')!==-1) badgeColor='badge-red';
        else if(e.transactionType.indexOf('Purchase')!==-1) badgeColor='badge-green';
        else if(e.transactionType.indexOf('Adjustment')!==-1) badgeColor='badge-gray';
        html+='<tr><td>'+new Date(e.createdAt||e.date).toLocaleDateString()+'</td>';
        html+='<td><span class="badge-pill '+badgeColor+'">'+typeLabel+'</span></td>';
        html+='<td>'+e.referenceId+'</td>';
        html+='<td class="text-end">'+(qtyIn?'<span class="text-success fw-bold">+'+qtyIn+'</span>':'<span class="text-muted">—</span>')+'</td>';
        html+='<td class="text-end">'+(qtyOut?'<span class="text-danger fw-bold">-'+qtyOut+'</span>':'<span class="text-muted">—</span>')+'</td>';
        html+='<td class="text-end fw-bold">'+row.bal+'</td></tr>';
    });
    if(!total) html='<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-receipt fs-1 d-block mb-2 text-muted"></i>No inventory movements found</td></tr>';
    document.getElementById('ledgerBody').innerHTML=html;
    document.getElementById('ilPagInfo').textContent='Showing '+(total?start+1:0)+' to '+Math.min(start+ilPerPage,total)+' of '+total+' entries';
    buildLedgerPag('ilPag', totalPages, ilPage, 'ilPage');
}
function buildLedgerPag(elId, totalPages, currentPage, varName){
    var ph='';
    ph+='<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage>1?' onclick="'+varName+'='+(currentPage-1)+';renderPage()"':'')+'>&#171;</a></li>';
    var _s={},_l=0;
    for(var p=1;p<=Math.min(2,totalPages);p++) _s[p]=true;
    for(var p=Math.max(1,currentPage-2);p<=Math.min(totalPages,currentPage+2);p++) _s[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _s[p]=true;
    for(var i=1;i<=totalPages;i++){
        if(!_s[i]) continue;
        if(_l>0&&i-_l>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        ph+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="'+varName+'='+i+';renderPage()">'+i+'</a></li>';
        _l=i;
    }
    ph+='<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage<totalPages?' onclick="'+varName+'='+(currentPage+1)+';renderPage()"':'')+'>&#187;</a></li>';
    document.getElementById(elId).innerHTML=ph;
}
