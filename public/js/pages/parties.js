var currentPage=1, perPage=15;
var selectedParties=new Set();
function getType(){ return (document.getElementById('party-type').getAttribute('data-type')||'Customer'); }
window.ERP.onReady = function(){ renderPage(); };
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('entityTypeFilter').value=''; currentPage=1; renderPage(); }
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('searchInput').addEventListener('input', function(){ currentPage=1; renderPage(); });
    document.getElementById('entityTypeFilter').addEventListener('change', function(){ currentPage=1; renderPage(); });
});
function getFiltered(){
    var state=window.ERP.state, type=getType(), search=(document.getElementById('searchInput').value||'').toLowerCase(),
        etFilter=document.getElementById('entityTypeFilter').value;
    return (state.parties||[]).filter(function(p){
        if(p.type!==type) return false;
        var str=(p.code+' '+p.name+' '+(p.phone||'')+' '+(p.email||'')).toLowerCase();
        if(search && str.indexOf(search)===-1) return false;
        if(etFilter && p.subType!==etFilter) return false;
        return true;
    });
}
function renderPage(){
    var state=window.ERP.state, type=getType(), filtered=getFiltered(), total=filtered.length,
        totalPages=Math.max(1,Math.ceil(total/perPage)), start=(currentPage-1)*perPage, page=filtered.slice(start,start+perPage);
    var etSel=document.getElementById('entityTypeFilter');
    var etHtml='<option value="">All Entity Types</option>';
    (state.entityTypes||[]).forEach(function(e){ etHtml+='<option value="'+e.name+'">'+e.name+'</option>'; });
    etSel.innerHTML=etHtml;
    var html='';
    page.forEach(function(p){
        html+='<tr>';
        html+='<td class="pty-chk-col" class="erp-col-chk-pad"><input type="checkbox" class="pty-chk pty-row-chk" data-id="'+p.id+'" '+(selectedParties.has(p.id)?'checked':'')+' onclick="toggleSelectParty(\''+p.id+'\',this)"></td>';
        html+='<td><span class="pty-code">'+(p.code||'—')+'</span></td>';
        html+='<td class="fw-normal text-dark">'+p.name+'</td>';
        html+='<td>'+(p.phone||'—')+'</td>';
        html+='<td>'+(p.email||'—')+'</td>';
        html+='<td>'+(p.subType||'—')+'</td>';
        html+='<td>'+(p.category||'—')+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(p.currentBalance||p.openingBalance||0)+'</td>';
        html+='<td class="text-end">'+ERP.formatCurrency(p.creditLimit||0)+'</td>';
        html+='<td class="text-center"><div class="d-flex justify-content-center gap-1">';
        html+='<button class="pty-action-btn" onclick="openEditModal(\''+p.id+'\')"><i class="ti ti-edit"></i></button>';
        html+='<button class="pty-action-btn pty-action-danger" onclick="deleteParty(\''+p.id+'\')"><i class="ti ti-trash"></i></button>';
        html+='</div></td></tr>';
    });
    if(!page.length) html='<tr><td colspan="10" class="text-center text-muted py-4">No '+type.toLowerCase()+'s found</td></tr>';
    document.getElementById('partiesBody').innerHTML=html;
    updatePtyBulkBar();
    // sync select-all state
    var allChks=document.querySelectorAll('.pty-row-chk');
    var checkedChks=Array.from(allChks).filter(function(c){return selectedParties.has(c.dataset.id);});
    var sa=document.getElementById('pty-select-all');
    if(sa){sa.checked=allChks.length>0&&checkedChks.length===allChks.length;sa.indeterminate=checkedChks.length>0&&checkedChks.length<allChks.length;}
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
    populateDropdowns();
}
function goToPage(p){currentPage=p;renderPage();}
function populateDropdowns(){
    var state=window.ERP.state;
    var stHtml='<option value="">Select...</option>';
    (state.entityTypes||[]).forEach(function(e){ stHtml+='<option value="'+e.name+'">'+e.name+'</option>'; });
    document.getElementById('pSubType').innerHTML=stHtml;
    var bcHtml='<option value="">Select...</option>';
    (state.businessCategories||[]).forEach(function(b){ bcHtml+='<option value="'+b.name+'">'+b.name+'</option>'; });
    document.getElementById('pBizCat').innerHTML=bcHtml;
}
function openAddModal(){
    var type=getType();
    document.getElementById('modalTitle').innerHTML='<i class="ti ti-users me-2"></i>Add '+type;
    document.getElementById('editId').value='';
    ['pCode','pName','pPhone','pEmail','pAddress','pBank'].forEach(function(id){document.getElementById(id).value='';});
    document.getElementById('pTerms').value='0';
    document.getElementById('pCreditLimit').value='0';
    document.getElementById('pOpenBal').value='0';
    document.getElementById('pSubType').value='';
    document.getElementById('pBizCat').value='';
    resetPartyAccountingSection(type);
    populatePartyAccountingDropdowns(type);
}
function openEditModal(id){
    var p=(window.ERP.state.parties||[]).find(function(x){return x.id===id;});
    if(!p) return;
    var type=getType();
    document.getElementById('modalTitle').innerHTML='<i class="ti ti-users me-2"></i>Edit '+type;
    document.getElementById('editId').value=p.id;
    document.getElementById('pCode').value=p.code||'';
    document.getElementById('pName').value=p.name||'';
    document.getElementById('pPhone').value=p.phone||'';
    document.getElementById('pEmail').value=p.email||'';
    document.getElementById('pAddress').value=p.address||'';
    document.getElementById('pSubType').value=p.subType||'';
    document.getElementById('pBizCat').value=p.category||'';
    document.getElementById('pTerms').value=p.paymentTerms||0;
    document.getElementById('pCreditLimit').value=p.creditLimit||0;
    document.getElementById('pOpenBal').value=p.openingBalance||0;
    document.getElementById('pBank').value=p.bankDetails||'';
    resetPartyAccountingSection(type);
    populatePartyAccountingDropdowns(type);
    new bootstrap.Modal(document.getElementById('partyModal')).show();
}
function confirmSaveParty(){
    var name=document.getElementById('pName').value;
    if(!name){alert('Name is required');return;}
    document.getElementById('ptySaveConfirm').classList.remove('d-none');
}
async function doSaveParty(){
    document.getElementById('ptySaveConfirm').classList.add('d-none');
    var editId=document.getElementById('editId').value;
    var data={
        type:getType(), code:document.getElementById('pCode').value, name:document.getElementById('pName').value,
        phone:document.getElementById('pPhone').value, email:document.getElementById('pEmail').value,
        address:document.getElementById('pAddress').value, subType:document.getElementById('pSubType').value,
        category:document.getElementById('pBizCat').value, paymentTerms:parseInt(document.getElementById('pTerms').value)||0,
        creditLimit:parseFloat(document.getElementById('pCreditLimit').value)||0, openingBalance:parseFloat(document.getElementById('pOpenBal').value)||0,
        bankDetails:document.getElementById('pBank').value
    };
    try{
        if(editId){ data.id=editId; await ERP.api.updateParty(data); }
        else{ await ERP.api.addParty(data); }
        bootstrap.Modal.getInstance(document.getElementById('partyModal')).hide();
        // Save accounting mappings (non-blocking)
        await savePartyAccountingMappings(data.type);
        await ERP.sync(); renderPage();
        document.getElementById('ptySaveSuccess').classList.remove('d-none');
    }catch(e){alert('Error: '+e.message);}
}

// ── Party Accounting Helpers ──────────────────────────────────────────────────
function resetPartyAccountingSection(type) {
    var section = document.getElementById('ptyAcctSection');
    var btn = section.previousElementSibling;
    section.style.display = 'none';
    if (btn) btn.classList.remove('open');
    // Show correct field group
    document.getElementById('pty-acct-customer').style.display = type === 'Customer' ? '' : 'none';
    document.getElementById('pty-acct-vendor').style.display   = type === 'Vendor'   ? '' : 'none';
}

function populatePartyAccountingDropdowns(type) {
    var accounts = (window.ERP.state.chartOfAccounts || []).filter(function(a){ return a.isActive; });
    var mappings = window.ERP.state.accountMappings || {};

    var fields = type === 'Customer'
        ? [
            { elId: 'pf-acct-ar',           key: 'accounts_receivable' },
            { elId: 'pf-acct-cash',         key: 'cash_account' },
            { elId: 'pf-acct-disc-allowed',  key: 'discount_allowed' },
          ]
        : [
            { elId: 'pf-acct-ap',            key: 'accounts_payable' },
            { elId: 'pf-acct-cash-vendor',   key: 'cash_account' },
            { elId: 'pf-acct-disc-received', key: 'discount_received' },
          ];

    fields.forEach(function(f) {
        var sel = document.getElementById(f.elId);
        sel.innerHTML = '<option value="">— Not set —</option>';
        accounts.forEach(function(a) {
            var opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = a.code + ' — ' + a.name;
            sel.appendChild(opt);
        });
        var current = mappings[f.key];
        if (current && current.accountId) sel.value = current.accountId;
    });
}

function togglePartyAccounting() {
    var section = document.getElementById('ptyAcctSection');
    var btn = document.querySelector('.pm-acct-wrap .pm-acct-toggle');
    var open = section.style.display !== 'none';
    section.style.display = open ? 'none' : 'block';
    btn.classList.toggle('open', !open);
}

async function savePartyAccountingMappings(type) {
    var fields = type === 'Customer'
        ? [
            { elId: 'pf-acct-ar',           key: 'accounts_receivable' },
            { elId: 'pf-acct-cash',         key: 'cash_account' },
            { elId: 'pf-acct-disc-allowed',  key: 'discount_allowed' },
          ]
        : [
            { elId: 'pf-acct-ap',            key: 'accounts_payable' },
            { elId: 'pf-acct-cash-vendor',   key: 'cash_account' },
            { elId: 'pf-acct-disc-received', key: 'discount_received' },
          ];

    var mappings = [];
    fields.forEach(function(f) {
        var val = document.getElementById(f.elId).value;
        if (val) mappings.push({ mappingKey: f.key, accountId: val });
    });
    if (!mappings.length) return;
    try {
        await ERP.api.saveMappings(mappings);
    } catch(e) {
        console.warn('Accounting mapping save failed:', e.message);
    }
}
// ── Multi-select ──
var selectedParties = new Set();
var ptySelectMode = false;
function togglePtySelectMode(){
    ptySelectMode = !ptySelectMode;
    var wrap = document.querySelector('.pty-page-wrap');
    var btn = document.getElementById('pty-sel-toggle-btn');
    if(ptySelectMode){ wrap.classList.add('pty-select-active'); btn.classList.add('active'); }
    else{ wrap.classList.remove('pty-select-active'); btn.classList.remove('active'); clearPartySelection(); }
}
function toggleSelectAllParties(chk){
    var boxes=document.querySelectorAll('.pty-row-chk');
    boxes.forEach(function(cb){cb.checked=chk.checked;if(chk.checked)selectedParties.add(cb.dataset.id);else selectedParties.delete(cb.dataset.id);});
    updatePtyBulkBar();
}
function toggleSelectParty(id,chk){
    if(chk.checked)selectedParties.add(id);else selectedParties.delete(id);
    updatePtyBulkBar();
    var all=document.querySelectorAll('.pty-row-chk');
    var checked=Array.from(all).filter(function(c){return c.checked;});
    var sa=document.getElementById('pty-select-all');
    if(sa){sa.checked=all.length>0&&checked.length===all.length;sa.indeterminate=checked.length>0&&checked.length<all.length;}
}
function updatePtyBulkBar(){
    var bar=document.getElementById('pty-bulk-bar');
    document.getElementById('pty-sel-count').textContent=selectedParties.size;
    if(selectedParties.size>0)bar.classList.remove('d-none');else bar.classList.add('d-none');
}
function clearPartySelection(){
    selectedParties.clear();
    document.querySelectorAll('.pty-row-chk').forEach(function(cb){cb.checked=false;});
    var sa=document.getElementById('pty-select-all');if(sa){sa.checked=false;sa.indeterminate=false;}
    updatePtyBulkBar();
    // exit select mode
    ptySelectMode=false;
    var wrap=document.querySelector('.pty-page-wrap');if(wrap)wrap.classList.remove('pty-select-active');
    var btn=document.getElementById('pty-sel-toggle-btn');if(btn)btn.classList.remove('active');
}

// ── Delete (single + bulk) ──
var _ptyDeleteId=null, _ptyBulkDelete=false;
function deleteParty(id){
    _ptyDeleteId=id; _ptyBulkDelete=false;
    var type=getType();
    document.getElementById('ptyDeleteConfirmTitle').textContent='Delete '+type+'?';
    document.getElementById('ptyDeleteConfirmSub').textContent='Are you sure you want to delete this '+type.toLowerCase()+'? This cannot be undone.';
    document.getElementById('ptyDeleteConfirm').classList.remove('d-none');
}
function confirmDeleteSelectedParties(){
    var n=selectedParties.size; if(!n)return;
    _ptyBulkDelete=true; _ptyDeleteId=null;
    var type=getType();
    document.getElementById('ptyDeleteConfirmTitle').textContent='Delete '+n+' '+type+(n>1?'s':'')+'?';
    document.getElementById('ptyDeleteConfirmSub').textContent='All '+n+' selected '+type.toLowerCase()+(n>1?'s':'')+' will be permanently removed. This cannot be undone.';
    document.getElementById('ptyDeleteConfirm').classList.remove('d-none');
}
async function doPtyDelete(){
    document.getElementById('ptyDeleteConfirm').classList.add('d-none');
    if(_ptyBulkDelete){await _doBulkDeleteParties();return;}
    if(!_ptyDeleteId)return;
    try{
        await ERP.api.deleteParty(_ptyDeleteId);_ptyDeleteId=null;
        await ERP.sync();renderPage();
        document.getElementById('ptyDeleteSuccessMsg').textContent=getType()+' has been removed from the system.';
        document.getElementById('ptyDeleteSuccess').classList.remove('d-none');
    }catch(e){
        _ptyDeleteId=null;
        document.getElementById('ptyDeleteErrorMsg').textContent=e.message||'An error occurred.';
        document.getElementById('ptyDeleteError').classList.remove('d-none');
    }
}
async function _doBulkDeleteParties(){
    var ids=Array.from(selectedParties),errors=[],ok=0;
    for(var i=0;i<ids.length;i++){
        try{await ERP.api.deleteParty(ids[i]);ok++;}
        catch(e){
            var pt=(window.ERP.state.parties||[]).find(function(x){return x.id===ids[i];});
            errors.push((pt?pt.name:ids[i])+': '+(e.message||'Error'));
        }
    }
    clearPartySelection();
    await ERP.sync();renderPage();
    if(errors.length===0){
        document.getElementById('ptyDeleteSuccessMsg').textContent=ok+' '+getType().toLowerCase()+(ok>1?'s':'')+' deleted successfully.';
        document.getElementById('ptyDeleteSuccess').classList.remove('d-none');
    }else{
        var msg=(ok>0?ok+' deleted. ':'')+errors.length+' could not be deleted: '+errors.join('; ');
        document.getElementById('ptyDeleteErrorMsg').textContent=msg;
        document.getElementById('ptyDeleteError').classList.remove('d-none');
    }
}
