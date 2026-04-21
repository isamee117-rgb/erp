var currentPage=1, perPage=10, sortKey='code', sortDir='asc';
function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Dynamic Fields ────────────────────────────────────────────────────────────

function buildDynamicInput(field, value, idPrefix) {
    var id = idPrefix + field.key;
    var attrs = 'id="' + id + '" data-dynamic-field="' + field.key + '" ';
    switch (field.type) {
        case 'date':
            return '<input type="date" class="form-control pm-input" ' + attrs + 'value="' + escHtml(String(value || '')) + '">';
        case 'number':
            return '<input type="number" step="0.01" class="form-control pm-input" ' + attrs + 'value="' + escHtml(String(value || '')) + '">';
        case 'textarea':
            return '<textarea class="form-control pm-input" ' + attrs + 'rows="2" style="height:auto;">' + escHtml(String(value || '')) + '</textarea>';
        case 'dropdown':
            var opts = '<option value="">— Select —</option>';
            (field.options || []).forEach(function(o) {
                opts += '<option value="' + escHtml(o) + '"' + (value === o ? ' selected' : '') + '>' + escHtml(o) + '</option>';
            });
            return '<select class="form-select pm-input" ' + attrs + '>' + opts + '</select>';
        case 'boolean':
            return '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" ' + attrs +
                (value ? ' checked' : '') + '><label class="form-check-label" for="' + id + '">' + escHtml(field.label) + '</label></div>';
        default:
            return '<input type="text" class="form-control pm-input" ' + attrs + 'value="' + escHtml(String(value || '')) + '">';
    }
}

function collectDynamicFields(idPrefix) {
    var result = {};
    var inputs = document.querySelectorAll('[data-dynamic-field]');
    inputs.forEach(function(input) {
        var key = input.getAttribute('data-dynamic-field');
        if (!key || input.id.indexOf(idPrefix) !== 0) return;
        if (input.type === 'checkbox') {
            result[key] = input.checked;
        } else {
            var v = input.value.trim();
            result[key] = v === '' ? null : v;
        }
    });
    return result;
}

function renderPartyDynamicFields(partyData) {
    var container = document.getElementById('pty-dynamic-fields');
    if (!container) return;

    var fs = window.ERP.state.fieldSettings || { enabledKeys: { customer: [] }, definitions: [] };
    var enabledKeys = (fs.enabledKeys && fs.enabledKeys.customer) ? fs.enabledKeys.customer : [];
    var definitions = fs.definitions || [];

    var enabledFields = definitions.filter(function(f) {
        return f.entity === 'customer' && enabledKeys.indexOf(f.key) !== -1;
    });

    if (enabledFields.length === 0) { container.innerHTML = ''; return; }

    var html = '';
    enabledFields.forEach(function(f) {
        var val = (partyData && partyData[f.key] !== undefined && partyData[f.key] !== null)
            ? partyData[f.key] : '';
        html += '<div class="col-md-6"><label class="pm-label">' + escHtml(f.label) + '</label>';
        html += buildDynamicInput(f, val, 'pty-dyn-');
        html += '</div>';
    });

    container.innerHTML = html;
}

// ── Party Column Visibility ───────────────────────────────────────────────────

var _ptyVisibleDynCols = null;

function getPtyVisibleDynCols() {
    if (_ptyVisibleDynCols !== null) return _ptyVisibleDynCols;
    var companyId = (window.ERP.state.currentUser || {}).companyId || 'default';
    var stored = localStorage.getItem('pty_dyn_cols_' + companyId);
    _ptyVisibleDynCols = stored ? JSON.parse(stored) : {};
    return _ptyVisibleDynCols;
}

function savePtyVisibleDynCols() {
    var companyId = (window.ERP.state.currentUser || {}).companyId || 'default';
    localStorage.setItem('pty_dyn_cols_' + companyId, JSON.stringify(_ptyVisibleDynCols));
}

function renderPtyColumnsMenu() {
    var menu = document.getElementById('ptyColsMenu');
    if (!menu) return;
    var fs = window.ERP.state.fieldSettings || { enabledKeys: { customer: [] }, definitions: [] };
    var enabledKeys = (fs.enabledKeys && fs.enabledKeys.customer) ? fs.enabledKeys.customer : [];
    var definitions = fs.definitions || [];
    var enabledFields = definitions.filter(function(f) {
        return f.entity === 'customer' && enabledKeys.indexOf(f.key) !== -1;
    });
    var visible = getPtyVisibleDynCols();

    if (enabledFields.length === 0) {
        menu.innerHTML = '<li class="text-muted" style="font-size:0.78rem;padding:4px 0;">No dynamic fields enabled</li>';
        return;
    }

    var html = '';
    enabledFields.forEach(function(f) {
        var checked = visible[f.key] !== false;
        html += '<li><label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:3px 0;">' +
            '<input type="checkbox" ' + (checked ? 'checked' : '') + ' onchange="togglePtyDynCol(\'' + f.key + '\',this.checked)">' +
            escHtml(f.label) + '</label></li>';
    });
    menu.innerHTML = html;
}

function togglePtyDynCol(key, visible) {
    var cols = getPtyVisibleDynCols();
    cols[key] = visible;
    _ptyVisibleDynCols = cols;
    savePtyVisibleDynCols();
    renderPage();
}
var selectedParties=new Set();
function getType(){ return (document.getElementById('party-type').getAttribute('data-type')||'Customer'); }
window.ERP.onReady = function(){ renderPage(); };
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('entityTypeFilter').value=''; currentPage=1; renderPage(); }
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('searchInput').addEventListener('input', function(){ currentPage=1; renderPage(); });
    document.getElementById('entityTypeFilter').addEventListener('change', function(){ currentPage=1; renderPage(); });
    var ptyFilterBtn = document.getElementById('pty-filter-toggle-btn');
    if (ptyFilterBtn) {
        ptyFilterBtn.addEventListener('click', function() {
            var panel = document.getElementById('pty-filters-panel');
            var isOpen = !panel.classList.contains('d-none');
            panel.classList.toggle('d-none', isOpen);
            ptyFilterBtn.classList.toggle('active', !isOpen);
        });
    }
    // Click-outside: close party accounting SDDs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#ptyAcctSection .sdd-wrap')) {
            document.querySelectorAll('#ptyAcctSection .sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
        }
    });
});
function getFiltered(){
    var state=window.ERP.state, type=getType(), search=(document.getElementById('searchInput').value||'').toLowerCase(),
        etFilter=document.getElementById('entityTypeFilter').value;
    var dynFilters = {};
    document.querySelectorAll('[data-dyn-filter]').forEach(function(el) {
        var key = el.getAttribute('data-dyn-filter');
        var val = el.value ? el.value.trim() : '';
        if (val) dynFilters[key] = val.toLowerCase();
    });
    return (state.parties||[]).filter(function(p){
        if(p.type!==type) return false;
        var str=(p.code+' '+p.name+' '+(p.phone||'')+' '+(p.email||'')).toLowerCase();
        if(search && str.indexOf(search)===-1) return false;
        if(etFilter && p.subType!==etFilter) return false;
        var dynMatch = true;
        Object.keys(dynFilters).forEach(function(key) {
            var pval = p[key];
            if (pval === null || pval === undefined) { dynMatch = false; return; }
            if (String(pval).toLowerCase().indexOf(dynFilters[key]) === -1) dynMatch = false;
        });
        return dynMatch;
    }).sort(function(a, b) {
        var va = (a[sortKey] || ''), vb = (b[sortKey] || '');
        if (va < vb) return sortDir === 'asc' ? -1 : 1;
        if (va > vb) return sortDir === 'asc' ? 1 : -1;
        return 0;
    });
}
function togglePtySort(key) {
    if (sortKey === key) { sortDir = sortDir === 'asc' ? 'desc' : 'asc'; }
    else { sortKey = key; sortDir = 'asc'; }
    currentPage = 1;
    renderPage();
}
function renderPage(){
    // Dynamic field column visibility setup
    var fs = window.ERP.state.fieldSettings || { enabledKeys: { customer: [] }, definitions: [] };
    var enabledCustKeys = (fs.enabledKeys && fs.enabledKeys.customer) ? fs.enabledKeys.customer : [];
    var definitions = fs.definitions || [];
    var visible = getPtyVisibleDynCols();
    var visibleDynFields = definitions.filter(function(f) {
        return f.entity === 'customer' && enabledCustKeys.indexOf(f.key) !== -1 && visible[f.key] !== false;
    });

    // Update thead dynamic columns
    var theadRow = document.getElementById('pty-thead-row');
    if (theadRow) {
        theadRow.querySelectorAll('.pty-dyn-th').forEach(function(th) { th.remove(); });
        var lastTh = theadRow.querySelector('th:last-child');
        visibleDynFields.forEach(function(f) {
            var th = document.createElement('th');
            th.className = 'pty-dyn-th';
            th.textContent = f.label;
            theadRow.insertBefore(th, lastTh);
        });
    }

    renderPtyColumnsMenu();

    var dynFiltersContainer = document.getElementById('pty-dyn-filters');
    if (dynFiltersContainer) {
        if (visibleDynFields.length > 0) {
            dynFiltersContainer.style.removeProperty('display');
            var filterHtml = visibleDynFields.map(function(f) {
                var existing = document.querySelector('[data-dyn-filter="' + f.key + '"]');
                var currentVal = existing ? existing.value : '';
                if (f.type === 'dropdown') {
                    var opts = '<option value="">All ' + escHtml(f.label) + '</option>';
                    (f.options || []).forEach(function(o) {
                        opts += '<option value="' + escHtml(o) + '"' + (currentVal === o ? ' selected' : '') + '>' + escHtml(o) + '</option>';
                    });
                    return '<select class="form-select inv-input" data-dyn-filter="' + f.key + '" style="min-width:140px;max-width:180px;" onchange="currentPage=1;renderPage();">' + opts + '</select>';
                }
                return '<input type="text" class="form-control inv-input" ' +
                    'data-dyn-filter="' + f.key + '" placeholder="Filter ' + escHtml(f.label) + '..." ' +
                    'value="' + escHtml(currentVal) + '" oninput="currentPage=1;renderPage();" style="min-width:140px;max-width:200px;">';
            }).join('');
            dynFiltersContainer.innerHTML = filterHtml;
        } else {
            dynFiltersContainer.style.display = 'none';
            dynFiltersContainer.innerHTML = '';
        }
    }

    var state=window.ERP.state, type=getType(), filtered=getFiltered(), total=filtered.length,
        totalPages=Math.max(1,Math.ceil(total/perPage)), start=(currentPage-1)*perPage, page=filtered.slice(start,start+perPage);
    var etSel=document.getElementById('entityTypeFilter');
    var etHtml='<option value="">All Entity Types</option>';
    (state.entityTypes||[]).forEach(function(e){ etHtml+='<option value="'+e.name+'">'+e.name+'</option>'; });
    etSel.innerHTML=etHtml;
    var html='';
    page.forEach(function(p){
        var dynCells = visibleDynFields.map(function(f) {
            var val = p[f.key];
            var display = (val === null || val === undefined || val === '') ? '<span class="text-muted">—</span>' :
                (f.type === 'boolean' ? (val ? '<i class="ti ti-check text-success"></i>' : '<i class="ti ti-x text-danger"></i>') :
                escHtml(String(val)));
            return '<td>' + display + '</td>';
        }).join('');
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
        html+=dynCells;
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
    ['pName','pPhone','pEmail','pTerms','pCreditLimit'].forEach(function(id) {
        document.getElementById(id).classList.remove('is-invalid');
        var err = document.getElementById(id+'-error');
        if(err) err.classList.add('d-none');
    });
    document.getElementById('pty-save-error').classList.add('d-none');
    document.getElementById('pTerms').value='0';
    document.getElementById('pCreditLimit').value='0';
    document.getElementById('pOpenBal').value='0';
    document.getElementById('pSubType').value='';
    document.getElementById('pBizCat').value='';
    resetPartyAccountingSection(type);
    populatePartyAccountingDropdowns(type);
    renderPartyDynamicFields(null);
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
    renderPartyDynamicFields(p);
    new bootstrap.Modal(document.getElementById('partyModal')).show();
}
function confirmSaveParty(){
    var nameInput    = document.getElementById('pName');
    var phoneInput   = document.getElementById('pPhone');
    var emailInput   = document.getElementById('pEmail');
    var termsInput   = document.getElementById('pTerms');
    var creditInput  = document.getElementById('pCreditLimit');
    var hasError = false;

    function setFieldError(input, errorId, condition) {
        if(condition) {
            input.classList.add('is-invalid');
            document.getElementById(errorId).classList.remove('d-none');
            if(!hasError){ input.focus(); hasError = true; }
        } else {
            input.classList.remove('is-invalid');
            document.getElementById(errorId).classList.add('d-none');
        }
    }

    setFieldError(nameInput, 'pName-error', !nameInput.value.trim());
    setFieldError(phoneInput, 'pPhone-error', phoneInput.value.trim() !== '' && !/^[0-9+\-\s()]{7,20}$/.test(phoneInput.value.trim()));
    setFieldError(emailInput, 'pEmail-error', emailInput.value.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim()));
    setFieldError(termsInput, 'pTerms-error', parseInt(termsInput.value) < 0);
    setFieldError(creditInput, 'pCreditLimit-error', parseFloat(creditInput.value) < 0);

    if(hasError) return;
    document.getElementById('pty-save-error').classList.add('d-none');
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
    var dynFields = collectDynamicFields('pty-dyn-');
    Object.keys(dynFields).forEach(function(k) { data[k] = dynFields[k]; });
    try{
        if(editId){ data.id=editId; await ERP.api.updateParty(data); }
        else{ await ERP.api.addParty(data); }
        bootstrap.Modal.getInstance(document.getElementById('partyModal')).hide();
        // Save accounting mappings (non-blocking)
        await savePartyAccountingMappings(data.type);
        await ERP.sync(); renderPage();
        document.getElementById('ptySaveSuccess').classList.remove('d-none');
    }catch(e){
        document.getElementById('pty-save-error-msg').textContent = e.message || 'Failed to save record.';
        document.getElementById('pty-save-error').classList.remove('d-none');
    }
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
            { sddId: 'sdd-pty-acct-ar',           elId: 'pf-acct-ar',           key: 'accounts_receivable' },
            { sddId: 'sdd-pty-acct-cash',          elId: 'pf-acct-cash',         key: 'cash_account' },
            { sddId: 'sdd-pty-acct-disc-allowed',  elId: 'pf-acct-disc-allowed', key: 'discount_allowed' },
          ]
        : [
            { sddId: 'sdd-pty-acct-ap',            elId: 'pf-acct-ap',            key: 'accounts_payable' },
            { sddId: 'sdd-pty-acct-cash-vendor',   elId: 'pf-acct-cash-vendor',   key: 'cash_account' },
            { sddId: 'sdd-pty-acct-disc-received', elId: 'pf-acct-disc-received', key: 'discount_received' },
          ];

    fields.forEach(function(f) {
        var optsWrap = document.getElementById(f.sddId + '-opts');
        var dispEl   = document.getElementById(f.sddId + '-disp');
        var hiddenEl = document.getElementById(f.elId);

        hiddenEl.value = '';
        dispEl.textContent = '— Not set —';
        dispEl.style.color = '#B0B7C9';

        optsWrap.innerHTML = '';
        var notSet = document.createElement('div');
        notSet.className = 'sdd-opt';
        notSet.dataset.val = '';
        notSet.dataset.label = '— Not set —';
        notSet.textContent = '— Not set —';
        notSet.style.color = '#9CA3AF';
        notSet.onclick = function() { ptyAcctSddSelect(f.sddId, '', '— Not set —'); };
        optsWrap.appendChild(notSet);

        accounts.forEach(function(a) {
            var label = a.code + ' — ' + a.name;
            var div = document.createElement('div');
            div.className = 'sdd-opt';
            div.dataset.val = a.id;
            div.dataset.label = label;
            div.textContent = label;
            div.onclick = function() { ptyAcctSddSelect(f.sddId, a.id, label); };
            optsWrap.appendChild(div);
        });

        var current = mappings[f.key];
        if (current && current.accountId) {
            var match = accounts.find(function(a) { return a.id === current.accountId; });
            if (match) {
                var label = match.code + ' — ' + match.name;
                hiddenEl.value = match.id;
                dispEl.textContent = label;
                dispEl.style.color = '';
            }
        }
    });
}

function ptyAcctSddToggle(wrapId) {
    var wrap = document.getElementById(wrapId);
    var isOpen = wrap.classList.contains('open');
    document.querySelectorAll('#ptyAcctSection .sdd-wrap.open').forEach(function(w) { w.classList.remove('open'); });
    if (!isOpen) {
        wrap.classList.add('open');
        var inp = wrap.querySelector('.sdd-search-inp');
        if (inp) { inp.value = ''; ptyAcctSddFilter(wrapId, ''); inp.focus(); }
    }
}

function ptyAcctSddFilter(wrapId, query) {
    var optsWrap = document.getElementById(wrapId + '-opts');
    var q = query.toLowerCase();
    optsWrap.querySelectorAll('.sdd-opt').forEach(function(opt) {
        opt.style.display = opt.dataset.label.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
}

function ptyAcctSddSelect(wrapId, accountId, label) {
    var wrap = document.getElementById(wrapId);
    var dispEl = document.getElementById(wrapId + '-disp');
    var hiddenId = wrapId.replace('sdd-pty-', 'pf-');
    document.getElementById(hiddenId).value = accountId;
    dispEl.textContent = label;
    dispEl.style.color = accountId ? '' : '#B0B7C9';
    wrap.classList.remove('open');
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
