var currentPage=1, perPage=20;
window.ERP.onReady = function(){ renderPage(); };
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('typeFilter').value=''; document.getElementById('dateFilter').value='all'; currentPage=1; renderPage(); }
document.addEventListener('DOMContentLoaded', function(){
    ['searchInput','typeFilter','dateFilter'].forEach(function(id){
        document.getElementById(id).addEventListener(id==='searchInput'?'input':'change', function(){ currentPage=1; renderPage(); });
    });
});
function getFiltered(){
    var state=window.ERP.state, search=(document.getElementById('searchInput').value||'').toLowerCase(),
        tf=document.getElementById('typeFilter').value, df=document.getElementById('dateFilter').value;
    var now=Date.now(), oneDay=86400000;
    return (state.ledger||[]).filter(function(e){
        if(e.transactionType.indexOf('Adjustment')===-1) return false;
        var prod=(state.products||[]).find(function(p){return p.id===e.productId;});
        var str=((prod?prod.name:'')+' '+(prod?prod.sku:'')+' '+e.referenceId+' '+e.transactionType).toLowerCase();
        if(search && str.indexOf(search)===-1) return false;
        if(tf && e.transactionType!==tf) return false;
        if(df==='today' && (now-(e.createdAt||new Date(e.date).getTime()))>oneDay) return false;
        if(df==='7d' && (now-(e.createdAt||new Date(e.date).getTime()))>7*oneDay) return false;
        if(df==='30d' && (now-(e.createdAt||new Date(e.date).getTime()))>30*oneDay) return false;
        return true;
    }).slice().reverse();
}
function renderPage(){
    var filtered=getFiltered(), total=filtered.length, totalPages=Math.max(1,Math.ceil(total/perPage)),
        start=(currentPage-1)*perPage, page=filtered.slice(start,start+perPage);
    var state=window.ERP.state, html='';
    page.forEach(function(e){
        var prod=(state.products||[]).find(function(p){return p.id===e.productId;});
        var typeLabel=e.transactionType.replace('Adjustment_','').replace(/_/g,' ');
        var badgeClass=e.transactionType.indexOf('Damage')!==-1?'bg-red-lt':e.transactionType.indexOf('Theft')!==-1?'bg-orange-lt':'bg-yellow-lt';
        html+='<tr>';
        html+='<td>'+new Date(e.createdAt||e.date).toLocaleDateString()+'</td>';
        html+='<td class="fw-bold">'+(prod?prod.name:'Deleted Product')+'<div class="text-muted small">'+(prod?prod.sku:'')+'</div></td>';
        html+='<td><span class="badge '+badgeClass+'">'+typeLabel+'</span></td>';
        html+='<td class="text-end"><span class="'+(e.quantityChange>0?'text-success':'text-danger')+' fw-bold">'+(e.quantityChange>0?'+':'')+e.quantityChange+'</span></td>';
        html+='<td>'+e.referenceId+'</td>';
        html+='</tr>';
    });
    if(!page.length) html='<tr><td colspan="5" class="text-center text-muted py-4">No adjustment entries found</td></tr>';
    document.getElementById('adjustmentsBody').innerHTML=html;
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
}
function goToPage(p){currentPage=p;renderPage();}
