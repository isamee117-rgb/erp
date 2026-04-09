var currentPage=1, perPage=15;
window.ERP.onReady = function(){ renderPage(); };
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('dateFrom').value=''; document.getElementById('dateTo').value=''; currentPage=1; renderPage(); }
document.addEventListener('DOMContentLoaded', function(){
    ['searchInput','dateFrom','dateTo'].forEach(function(id){ document.getElementById(id).addEventListener(id==='searchInput'?'input':'change', function(){ currentPage=1; renderPage(); }); });
});
function getFiltered(){
    var state=window.ERP.state, search=(document.getElementById('searchInput').value||'').toLowerCase(),
        df=document.getElementById('dateFrom').value, dt=document.getElementById('dateTo').value;
    return (state.salesReturns||[]).slice().reverse().filter(function(r){
        var sale=(state.sales||[]).find(function(s){return s.id===r.originalSaleId;});
        var party=sale?(state.parties||[]).find(function(p){return p.id===sale.customerId;}):null;
        var str=(r.id+' '+(sale?sale.id:'')+' '+(party?party.name:'')+' '+(r.reason||'')).toLowerCase();
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
        var sale=(state.sales||[]).find(function(s){return s.id===r.originalSaleId;});
        var party=sale?(state.parties||[]).find(function(p){return p.id===sale.customerId;}):null;
        var items=r.items||[];
        html+='<tr class="cursor-pointer" onclick="toggleExpand(\''+r.id+'\')">';
        html+='<td><i class="ti ti-chevron-right" id="chev-'+r.id+'"></i></td>';
        html+='<td><span class="badge-pill badge-purple">'+r.id+'</span></td>';
        html+='<td>'+new Date(r.createdAt).toLocaleDateString()+'</td>';
        html+='<td>'+(sale?sale.id:'—')+'</td>';
        html+='<td class="fw-bold">'+(party?party.name:'—')+'</td>';
        html+='<td>'+items.length+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(r.totalAmount||0)+'</td>';
        html+='<td><span class="text-muted">'+(r.reason||'—')+'</span></td></tr>';
        html+='<tr id="exp-'+r.id+'" class="d-none"><td colspan="8" class="erp-expand-row-td"><table class="table table-sm inv-table mb-0"><thead><tr><th class="inv-th">Product</th><th class="inv-th">Qty</th><th class="inv-th">Price</th><th class="inv-th">Subtotal</th></tr></thead><tbody>';
        items.forEach(function(it){
            var prod=(state.products||[]).find(function(p){return p.id===it.productId;});
            var price=it.price||it.unitPrice||0;
            html+='<tr><td>'+(prod?prod.name:'Unknown')+'</td><td>'+it.quantity+'</td><td>'+ERP.formatCurrency(price)+'</td><td>'+ERP.formatCurrency(it.quantity*price)+'</td></tr>';
        });
        html+='</tbody></table></td></tr>';
    });
    if(!page.length) html='<tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-receipt-refund fs-1 d-block mb-2 text-muted"></i>No sales returns found</td></tr>';
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
    populateSaleSelect();
}
function toggleExpand(id){ var r=document.getElementById('exp-'+id),c=document.getElementById('chev-'+id); if(r.style.display==='none'){r.style.display='';c.className='ti ti-chevron-down';}else{r.style.display='none';c.className='ti ti-chevron-right';} }
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
function sddSelectInvoice(saleId, label){
    document.getElementById('saleSelect').value = saleId;
    document.getElementById('saleSelect-disp').textContent = label;
    document.getElementById('saleSelect-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
    onSaleSelected();
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.sdd-wrap')) document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
});
// Reset SDD when modal closes
document.addEventListener('DOMContentLoaded',function(){
    var modal=document.getElementById('newSReturnModal');
    if(modal) modal.addEventListener('hidden.bs.modal',function(){
        document.getElementById('saleSelect').value='';
        document.getElementById('saleSelect-disp').textContent='-- Select an Invoice --';
        document.getElementById('saleSelect-disp').style.color='#B0B7C9';
        document.getElementById('saleItemsContainer').style.display='none';
        document.getElementById('returnReason').value='';
    });
});

function populateSaleSelect(){
    var state=window.ERP.state, html='';
    (state.sales||[]).forEach(function(s){
        var party=(state.parties||[]).find(function(p){return p.id===s.customerId;});
        var label=escHtml(s.id)+' — '+escHtml(party?party.name:'—')+' — '+escHtml(ERP.formatCurrency(s.totalAmount));
        html+='<div class="sdd-opt" onclick="sddSelectInvoice(\''+escHtml(s.id)+'\',\''+escHtml(s.id+' — '+(party?party.name:'—'))+'\')">'+label+'</div>';
    });
    html+='<div class="sdd-no-res" class="d-none">No invoices found</div>';
    document.getElementById('saleSelect-opts').innerHTML=html;
}
function onSaleSelected(){
    var saleId=document.getElementById('saleSelect').value,cont=document.getElementById('saleItemsContainer');
    if(!saleId){cont.style.display='none';return;} cont.style.display='';
    var sale=(window.ERP.state.sales||[]).find(function(s){return s.id===saleId;});
    if(!sale) return;
    var html='';
    (sale.items||[]).forEach(function(it,i){
        var prod=(window.ERP.state.products||[]).find(function(p){return p.id===it.productId;});
        var qty=it.quantity||0;
        html+='<tr><td>'+(prod?prod.name:'Unknown')+'</td><td>'+qty+'</td>';
        html+='<td><input type="number" class="form-control form-control-sm" min="0" max="'+qty+'" value="0" id="retQty-'+i+'" class="erp-qty-input"></td>';
        html+='<td>'+ERP.formatCurrency(it.unitPrice||0)+'</td></tr>';
    });
    document.getElementById('saleItemsBody').innerHTML=html;
}
async function submitReturn(){
    var saleId=document.getElementById('saleSelect').value;
    if(!saleId){alert('Please select a sales invoice');return;}
    var sale=(window.ERP.state.sales||[]).find(function(s){return s.id===saleId;});
    if(!sale) return;
    var items=[];
    (sale.items||[]).forEach(function(it,i){
        var qty=parseInt(document.getElementById('retQty-'+i).value)||0;
        if(qty>0) items.push({productId:it.productId,quantity:qty,unitPrice:it.unitPrice||0});
    });
    if(!items.length){alert('Please enter return quantities');return;}
    var reason=document.getElementById('returnReason').value;
    try{
        await ERP.api.createSaleReturn(saleId,items,reason);
        bootstrap.Modal.getInstance(document.getElementById('newSReturnModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){alert('Error: '+e.message);}
}
