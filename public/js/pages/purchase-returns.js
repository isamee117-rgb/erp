var prReturns=[], prMeta={currentPage:1,lastPage:1,total:0};
var prSearchTimer=null, prLoading=false;
var prReturnablePOs=[];

function prGetFilters(){
    return {
        page:   prMeta.currentPage,
        search: (document.getElementById('searchInput').value||'').trim(),
        from:   document.getElementById('dateFrom').value||'',
        to:     document.getElementById('dateTo').value||'',
    };
}

function loadPurchaseReturns(page){
    if(prLoading) return;
    prLoading=true; prMeta.currentPage=page||1;
    var tbody=document.getElementById('returnsBody');
    if(tbody) tbody.innerHTML='<tr><td colspan="8" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>';
    ERP.api.getPurchaseReturns(prGetFilters())
        .then(function(res){
            prReturns=res.data||[];
            var m=res.meta||{};
            prMeta={currentPage:m.current_page||1,lastPage:m.last_page||1,total:m.total||0};
            renderPage();
        })
        .catch(function(e){
            if(tbody) tbody.innerHTML='<tr><td colspan="8" class="text-center text-danger py-4">Error: '+e.message+'</td></tr>';
        })
        .finally(function(){ prLoading=false; });
}

window.ERP.onReady = function(){ loadPurchaseReturns(1); };
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('dateFrom').value=''; document.getElementById('dateTo').value=''; loadPurchaseReturns(1); }
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('searchInput').addEventListener('input', function(){
        clearTimeout(prSearchTimer);
        prSearchTimer=setTimeout(function(){ loadPurchaseReturns(1); }, 400);
    });
    ['dateFrom','dateTo'].forEach(function(id){ document.getElementById(id).addEventListener('change', function(){ loadPurchaseReturns(1); }); });
    document.getElementById('pret-filter-toggle-btn').addEventListener('click', function(){
        var panel = document.getElementById('pret-filters-panel');
        var isOpen = !panel.classList.contains('d-none');
        panel.classList.toggle('d-none');
        this.classList.toggle('active', !isOpen);
    });
    document.getElementById('pret-clear-filters-btn').addEventListener('click', function(){ clearFilters(); });
});
function renderPage(){
    var products=window.ERP.state.products||[];
    var html='';
    prReturns.forEach(function(r){
        var items=r.items||[];
        html+='<tr class="cursor-pointer" onclick="toggleExpand(\''+r.id+'\')">';
        html+='<td><i class="ti ti-chevron-right" id="chev-'+r.id+'"></i></td>';
        html+='<td><span class="badge-pill badge-orange">'+r.id+'</span></td>';
        html+='<td>'+new Date(r.createdAt).toLocaleDateString()+'</td>';
        html+='<td>'+(r.originalPurchaseNo||r.originalPurchaseId||'—')+'</td>';
        html+='<td>'+(r.vendorName||'—')+'</td>';
        html+='<td>'+items.length+'</td>';
        html+='<td>'+ERP.formatCurrency(r.totalAmount||0)+'</td>';
        html+='<td><span class="text-muted">'+(r.reason||'—')+'</span></td></tr>';
        html+='<tr id="exp-'+r.id+'" class="d-none expand-row"><td colspan="8"><div class="p-3"><div class="row">';
        html+='<div class="col-md-7"><h4 class="mb-3 erp-table-section-header">Return Items</h4>';
        html+='<table class="table table-sm mb-0"><thead><tr><th class="po-th-col" style="width:36px;">#</th><th class="po-th-col">Product</th><th class="po-th-col text-center">Qty</th><th class="po-th-col text-end">Unit Cost</th><th class="po-th-col text-end">Line Total</th></tr></thead><tbody>';
        items.forEach(function(it, idx){
            var prod=products.find(function(p){return p.id===it.productId;});
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
    if(!prReturns.length) html='<tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-repeat fs-1 d-block mb-2 text-muted"></i>No purchase returns found</td></tr>';
    document.getElementById('returnsBody').innerHTML=html;
    var start=(prMeta.currentPage-1)*50;
    document.getElementById('paginationInfo').textContent='Showing '+(prMeta.total?start+1:0)+' to '+Math.min(start+50,prMeta.total)+' of '+prMeta.total;
    var totalPages=prMeta.lastPage||1, cur=prMeta.currentPage||1, ph='',_pgS={},_pgL=0;
    ph+='<li class="page-item '+(cur<=1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(cur>1?' onclick="loadPurchaseReturns('+(cur-1)+')"':'')+'>&#171;</a></li>';
    for(var p=1;p<=Math.min(2,totalPages);p++) _pgS[p]=true;
    for(var p=Math.max(1,cur-2);p<=Math.min(totalPages,cur+2);p++) _pgS[p]=true;
    for(var p=Math.max(1,totalPages-1);p<=totalPages;p++) _pgS[p]=true;
    for(var i=1;i<=totalPages;i++){
      if(!_pgS[i]) continue;
      if(_pgL>0&&i-_pgL>1) ph+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
      ph+='<li class="page-item '+(i===cur?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="loadPurchaseReturns('+i+')">'+i+'</a></li>';
      _pgL=i;
    }
    ph+='<li class="page-item '+(cur>=totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)"'+(cur<totalPages?' onclick="loadPurchaseReturns('+(cur+1)+')"':'')+'>&#187;</a></li>';
    document.getElementById('pagination').innerHTML=ph;
    populatePOSelect();
}
function toggleExpand(id){ var r=document.getElementById('exp-'+id),c=document.getElementById('chev-'+id); if(r.classList.contains('d-none')){r.classList.remove('d-none');c.className='ti ti-chevron-down';}else{r.classList.add('d-none');c.className='ti ti-chevron-right';} }
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

var prReturnableTimer=null;
function populatePOSelect(){
    var optsEl=document.getElementById('poSelect-opts');
    optsEl.innerHTML='<div class="sdd-no-res" style="color:#9CA3AF;">Type PO no. or vendor name to search...</div>';
}
function sddSearchPOs(query){
    var optsEl=document.getElementById('poSelect-opts');
    if(!query||query.length<2){
        optsEl.innerHTML='<div class="sdd-no-res" style="color:#9CA3AF;">Type PO no. or vendor name to search...</div>';
        return;
    }
    clearTimeout(prReturnableTimer);
    prReturnableTimer=setTimeout(function(){
        optsEl.innerHTML='<div class="sdd-no-res">Searching...</div>';
        ERP.api.getReturnablePurchases(query).then(function(res){
            prReturnablePOs=res||[];
            var html='';
            prReturnablePOs.forEach(function(po){
                var vendorName=po.vendorName||'—';
                var label=escHtml(po.id)+' — '+escHtml(vendorName)+' — '+escHtml(ERP.formatCurrency(po.totalAmount));
                html+='<div class="sdd-opt" onclick="sddSelectPO(\''+escHtml(po.id)+'\',\''+escHtml(po.id+' — '+vendorName)+'\')">'+label+'</div>';
            });
            html+='<div class="sdd-no-res"'+(prReturnablePOs.length?' style="display:none;"':'')+'>No purchase orders found</div>';
            optsEl.innerHTML=html;
        }).catch(function(){
            optsEl.innerHTML='<div class="sdd-no-res">Error searching</div>';
        });
    },350);
}
function onPOSelected(){
    var poId = document.getElementById('poSelect').value;
    document.getElementById('poItemsContainer').classList.add('d-none');
    document.getElementById('poReceiptsGrouped').innerHTML = '';
    if (!poId) return;

    var po = prReturnablePOs.find(function(p){ return p.id === poId; });
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

function showPretConfirm() {
    return new Promise(function(resolve) {
        var overlay = document.getElementById('pretConfirmOverlay');
        overlay.classList.remove('d-none');
        var okBtn     = document.getElementById('pretConfirmOk');
        var cancelBtn = document.getElementById('pretConfirmCancel');
        var resolved  = false;
        function cleanup() { okBtn.removeEventListener('click', onOk); cancelBtn.removeEventListener('click', onCancel); }
        function onOk()     { if (resolved) return; resolved = true; cleanup(); overlay.classList.add('d-none'); resolve(true); }
        function onCancel() { if (resolved) return; resolved = true; cleanup(); overlay.classList.add('d-none'); resolve(false); }
        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
    });
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

    if (!await showPretConfirm()) return;

    var reason = document.getElementById('returnReason').value;
    try {
        var result = await ERP.api.createPurchaseReturn(poId, items, reason);
        bootstrap.Modal.getInstance(document.getElementById('newPReturnModal')).hide();
        await loadPurchaseReturns(1);
        document.getElementById('pretSuccessOverlay').classList.remove('d-none');
        if (result && result.warning) showJournalWarning(result.warning);
    } catch(e) { showPretError(e.message || 'Failed to create return.'); }
}

document.addEventListener('DOMContentLoaded', function() {
    var okBtn = document.getElementById('pretSuccessOk');
    if (okBtn) okBtn.addEventListener('click', function() { document.getElementById('pretSuccessOverlay').classList.add('d-none'); });
});
