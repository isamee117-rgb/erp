var currentPage=1, perPage=10;

function prRefetchIfNeeded(callback) {
    var loadedFrom = window.ERP.state.transactionLoadedFrom;
    var requestedFrom = (document.getElementById('dateFrom').value || '');
    var requestedTo   = (document.getElementById('dateTo').value   || '');
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
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('dateFrom').value=''; document.getElementById('dateTo').value=''; currentPage=1; renderPage(); }
document.addEventListener('DOMContentLoaded', function(){
    ['searchInput','dateFrom','dateTo'].forEach(function(id){ document.getElementById(id).addEventListener(id==='searchInput'?'input':'change', function(){ currentPage=1; if(id==='searchInput'){ renderPage(); }else{ prRefetchIfNeeded(renderPage); } }); });
    document.getElementById('pret-filter-toggle-btn').addEventListener('click', function(){
        var panel = document.getElementById('pret-filters-panel');
        var isOpen = !panel.classList.contains('d-none');
        panel.classList.toggle('d-none');
        this.classList.toggle('active', !isOpen);
    });
    document.getElementById('pret-clear-filters-btn').addEventListener('click', function(){ clearFilters(); });
});
function getFiltered(){
    var state=window.ERP.state, search=(document.getElementById('searchInput').value||'').toLowerCase(),
        df=document.getElementById('dateFrom').value, dt=document.getElementById('dateTo').value;
    return (state.purchaseReturns||[]).slice().reverse().filter(function(r){
        var po=(state.purchaseOrders||[]).find(function(p){return p.id===r.originalPurchaseId;});
        var party=po?(state.parties||[]).find(function(p){return p.id===po.vendorId;}):null;
        var str=(r.id+' '+(po?po.id:'')+' '+(party?party.name:'')+' '+(r.reason||'')).toLowerCase();
        if(search && str.indexOf(search)===-1) return false;
        var rd=new Date(r.createdAt).toISOString().split('T')[0];
        if(df && rd<df) return false;
        if(dt && rd>dt) return false;
        return true;
    });
}
function renderPage(){
    var state=window.ERP.state, filtered=getFiltered(), total=filtered.length,
        totalPages=Math.max(1,Math.ceil(total/perPage)), start=(currentPage-1)*perPage, page=filtered.slice(start,start+perPage);
    var html='';
    page.forEach(function(r){
        var po=(state.purchaseOrders||[]).find(function(p){return p.id===r.originalPurchaseId;});
        var party=po?(state.parties||[]).find(function(p){return p.id===po.vendorId;}):null;
        var items=r.items||[];
        html+='<tr class="cursor-pointer" onclick="toggleExpand(\''+r.id+'\')">';
        html+='<td><i class="ti ti-chevron-right" id="chev-'+r.id+'"></i></td>';
        html+='<td><span class="badge-pill badge-orange">'+r.id+'</span></td>';
        html+='<td>'+new Date(r.createdAt).toLocaleDateString()+'</td>';
        html+='<td>'+(po?po.id:'—')+'</td>';
        html+='<td>'+(party?party.name:'—')+'</td>';
        html+='<td>'+items.length+'</td>';
        html+='<td>'+ERP.formatCurrency(r.totalAmount||0)+'</td>';
        html+='<td><span class="text-muted">'+(r.reason||'—')+'</span></td></tr>';
        html+='<tr id="exp-'+r.id+'" class="d-none expand-row"><td colspan="8"><div class="p-3"><div class="row">';
        html+='<div class="col-md-7"><h4 class="mb-3 erp-table-section-header">Return Items</h4>';
        html+='<table class="table table-sm mb-0"><thead><tr><th class="po-th-col" style="width:36px;">#</th><th class="po-th-col">Product</th><th class="po-th-col text-center">Qty</th><th class="po-th-col text-end">Unit Cost</th><th class="po-th-col text-end">Line Total</th></tr></thead><tbody>';
        items.forEach(function(it, idx){
            var prod=(state.products||[]).find(function(p){return p.id===it.productId;});
            var cost=it.unitCost||0;
            html+='<tr>'+
                '<td class="text-center" style="color:#9CA3AF;font-size:0.78rem;">'+(idx+1)+'</td>'+
                '<td>'+(prod?prod.name:'Unknown')+'</td>'+
                '<td class="text-center">'+it.quantity+'</td>'+
                '<td class="text-end">'+ERP.formatCurrency(cost)+'</td>'+
                '<td class="text-end fw-semibold">'+ERP.formatCurrency(it.quantity*cost)+'</td>'+
                '</tr>';
        });
        html+='</tbody></table></div>';
        html+='<div class="col-md-5"><h4 class="mb-3 erp-table-section-header">Summary</h4>';
        html+='<div class="erp-summary-box">';
        html+='<div class="d-flex justify-content-between erp-text-sm"><span class="text-muted">Total Value</span><span class="fw-semibold">'+ERP.formatCurrency(r.totalAmount||0)+'</span></div>';
        html+='</div></div></div></div></td></tr>';
    });
    if(!page.length) html='<tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-repeat fs-1 d-block mb-2 text-muted"></i>No purchase returns found</td></tr>';
    document.getElementById('returnsBody').innerHTML=html;
    document.getElementById('paginationInfo').textContent='Showing '+(total?start+1:0)+' to '+Math.min(start+perPage,total)+' of '+total;
    var ph='';
    ph+='<li class="page-item '+(currentPage<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage>1?' onclick="goToPage('+(currentPage-1)+')"':'')+'>&#171;</a></li>';
    var _pgS={},_pgL=0;
    for(var p=1;p<=Math.min(2,totalPages);p++) _pgS[p]=true;
    for(var p=Math.max(1,currentPage-2);p<=Math.min(totalPages,currentPage+2);p++) _pgS[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _pgS[p]=true;
    for(var i=1;i<=totalPages;i++){
      if(!_pgS[i]) continue;
      if(_pgL>0&&i-_pgL>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
      ph+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="goToPage('+i+')">'+i+'</a></li>';
      _pgL=i;
    }
    ph+='<li class="page-item '+(currentPage>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(currentPage<totalPages?' onclick="goToPage('+(currentPage+1)+')"':'')+'>&#187;</a></li>';
    document.getElementById('pagination').innerHTML=ph;
    populatePOSelect();
}
function toggleExpand(id){ var r=document.getElementById('exp-'+id),c=document.getElementById('chev-'+id); if(r.classList.contains('d-none')){r.classList.remove('d-none');c.className='ti ti-chevron-down';}else{r.classList.add('d-none');c.className='ti ti-chevron-right';} }
function goToPage(p){currentPage=p;renderPage();}
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
function sddSelectPO(poId, label){
    document.getElementById('poSelect').value = poId;
    document.getElementById('poSelect-disp').textContent = label;
    document.getElementById('poSelect-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
    onPOSelected();
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.sdd-wrap')) document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
});
document.addEventListener('DOMContentLoaded',function(){
    var modal=document.getElementById('newPReturnModal');
    if(modal) modal.addEventListener('hidden.bs.modal',function(){
        document.getElementById('poSelect').value='';
        document.getElementById('poSelect-disp').textContent='-- Select a PO --';
        document.getElementById('poSelect-disp').style.color='#B0B7C9';
        document.getElementById('poItemsContainer').classList.add('d-none');
        document.getElementById('poReceiptsGrouped').innerHTML='';
        document.getElementById('returnReason').value='';
        hidePretError();
    });
});

function populatePOSelect(){
    var state=window.ERP.state, html='';
    (state.purchaseOrders||[]).filter(function(po){return po.status==='Received'||po.status==='Partially Received';}).forEach(function(po){
        var party=(state.parties||[]).find(function(p){return p.id===po.vendorId;});
        var label=escHtml(po.id)+' — '+escHtml(party?party.name:'—')+' — '+escHtml(ERP.formatCurrency(po.totalAmount));
        html+='<div class="sdd-opt" onclick="sddSelectPO(\''+escHtml(po.id)+'\',\''+escHtml(po.id+' — '+(party?party.name:'—'))+'\')">'+label+'</div>';
    });
    html+='<div class="sdd-no-res" class="d-none">No purchase orders found</div>';
    document.getElementById('poSelect-opts').innerHTML=html;
}
function onPOSelected(){
    var poId = document.getElementById('poSelect').value;
    document.getElementById('poItemsContainer').classList.add('d-none');
    document.getElementById('poReceiptsGrouped').innerHTML = '';
    if (!poId) return;

    var po = (window.ERP.state.purchaseOrders || []).find(function(p){ return p.id === poId; });
    if (!po) return;

    var receives = (po.receives || []).slice().sort(function(a, b){
        return new Date(a.receiveDate || a.createdAt) - new Date(b.receiveDate || b.createdAt);
    });

    if (!receives.length) {
        document.getElementById('poReceiptsGrouped').innerHTML =
            '<div class="text-center text-muted py-3" style="font-size:0.85rem;">No receipts found for this PO.</div>';
        document.getElementById('poItemsContainer').classList.remove('d-none');
        return;
    }

    var products = window.ERP.state.products || [];
    var inputIdx = 0;
    var html = '';

    receives.forEach(function(rcv, i){
        var dateStr = rcv.receiveDate || (rcv.createdAt ? new Date(rcv.createdAt).toLocaleDateString('en-GB') : '—');
        html += '<div class="pr-rcv-group mb-3">' +
            '<div class="pr-rcv-group-header">' +
                '<span class="pr-rcv-id">Receipt #' + (i + 1) + '</span>' +
                '<span class="pr-rcv-date">' + dateStr + '</span>' +
            '</div>' +
            '<table class="table table-sm mb-0" style="table-layout:fixed;">' +
            '<thead><tr>' +
                '<th class="po-th-col" style="width:36px;">#</th>' +
                '<th class="po-th-col">Product</th>' +
                '<th class="po-th-col text-center" style="width:90px;">Received</th>' +
                '<th class="po-th-col" style="width:120px;">Return Qty</th>' +
                '<th class="po-th-col text-end" style="width:110px;">Unit Cost</th>' +
            '</tr></thead><tbody>';

        (rcv.items || []).forEach(function(it, i){
            var prod = products.find(function(p){ return p.id === it.productId; });
            var key = escHtml(rcv.id) + '-' + inputIdx;
            html += '<tr>' +
                '<td class="po-td-center" style="color:#9CA3AF;font-size:0.78rem;">' + (i + 1) + '</td>' +
                '<td class="po-td-item">' + (prod ? escHtml(prod.name) : 'Unknown') + '</td>' +
                '<td class="po-td-center">' + it.quantity + '</td>' +
                '<td class="po-td-input">' +
                    '<input type="number" class="form-control pm-input text-center po-input-sm pr-ret-qty" ' +
                        'min="0" max="' + it.quantity + '" value="0" ' +
                        'data-max="' + it.quantity + '" data-product-id="' + escHtml(it.productId) + '" ' +
                        'data-unit-cost="' + it.unitCost + '" id="retQty-' + key + '" ' +
                        'oninput="validateRetQty(this,\'' + key + '\')">' +
                    '<div class="text-danger" style="font-size:0.72rem;min-height:14px;" id="ret-err-' + key + '"></div>' +
                '</td>' +
                '<td class="po-td-input text-end" style="font-weight:600;">' + ERP.formatCurrency(it.unitCost || 0) + '</td>' +
                '</tr>';
            inputIdx++;
        });
        html += '</tbody></table></div>';
    });

    document.getElementById('poReceiptsGrouped').innerHTML = html;
    document.getElementById('poItemsContainer').classList.remove('d-none');
}

function validateRetQty(inp, key){
    var val = parseInt(inp.value), max = parseInt(inp.dataset.max) || 0;
    var k = key || inp.id.replace('retQty-', '');
    var errEl = document.getElementById('ret-err-' + k);
    if (!errEl) return;
    if (isNaN(val) || val < 0) {
        errEl.textContent = 'Cannot be negative.'; inp.classList.add('is-invalid');
    } else if (val > max) {
        errEl.textContent = 'Max ' + max + '.'; inp.classList.add('is-invalid');
    } else {
        errEl.textContent = ''; inp.classList.remove('is-invalid');
    }
}

function showPretError(msg) {
    var box = document.getElementById('pret-save-error');
    document.getElementById('pret-save-error-msg').textContent = msg;
    box.classList.remove('d-none');
}
function hidePretError() {
    document.getElementById('pret-save-error').classList.add('d-none');
}

async function submitReturn(){
    hidePretError();
    var poId = document.getElementById('poSelect').value;
    if (!poId) { showPretError('Please select a purchase order.'); return; }

    var hasError = false;
    document.querySelectorAll('.pr-ret-qty').forEach(function(inp){
        validateRetQty(inp);
        if (inp.classList.contains('is-invalid')) hasError = true;
    });
    if (hasError) return;

    var items = [];
    document.querySelectorAll('.pr-ret-qty').forEach(function(inp){
        var qty = parseInt(inp.value) || 0;
        if (qty > 0) items.push({ productId: inp.dataset.productId, quantity: qty, unitCost: parseFloat(inp.dataset.unitCost) || 0 });
    });
    if (!items.length) { showPretError('Please enter at least one return quantity.'); return; }

    var reason = document.getElementById('returnReason').value;
    try {
        await ERP.api.createPurchaseReturn(poId, items, reason);
        bootstrap.Modal.getInstance(document.getElementById('newPReturnModal')).hide();
        await ERP.sync(); renderPage();
    } catch(e) { showPretError(e.message || 'Failed to create return.'); }
}
