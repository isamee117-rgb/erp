var currentPage=1, perPage=15;

function payRefetchIfNeeded(callback) {
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
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('typeFilter').value=''; document.getElementById('dateFrom').value=''; document.getElementById('dateTo').value=''; currentPage=1; renderPage(); }
document.addEventListener('DOMContentLoaded', function(){
    ['searchInput','typeFilter','dateFrom','dateTo'].forEach(function(id){
        document.getElementById(id).addEventListener(id==='searchInput'?'input':'change', function(){ currentPage=1; if(id==='dateFrom'||id==='dateTo'){ payRefetchIfNeeded(renderPage); }else{ renderPage(); } });
    });
});
function getFiltered(){
    var state=window.ERP.state, search=(document.getElementById('searchInput').value||'').toLowerCase(),
        tf=document.getElementById('typeFilter').value, df=document.getElementById('dateFrom').value, dt=document.getElementById('dateTo').value;
    return (state.payments||[]).slice().reverse().filter(function(p){
        var party=(state.parties||[]).find(function(x){return x.id===p.partyId;});
        var str=((party?party.name:'')+' '+(p.referenceNo||'')+' '+(p.notes||'')+' '+(p.type||'')).toLowerCase();
        if(search && str.indexOf(search)===-1) return false;
        var normalType=(p.type==='Receipt')?'Payment Received':p.type;
        if(tf && normalType!==tf) return false;
        var pd=new Date(p.createdAt||p.date).toISOString().split('T')[0];
        if(df && pd<df) return false;
        if(dt && pd>dt) return false;
        return true;
    });
}
function renderPage(){
    var state=window.ERP.state, filtered=getFiltered(), total=filtered.length,
        totalPages=Math.max(1,Math.ceil(total/perPage)), start=(currentPage-1)*perPage, page=filtered.slice(start,start+perPage);
    var html='';
    page.forEach(function(p){
        var party=(state.parties||[]).find(function(x){return x.id===p.partyId;});
        var isReceived=(p.type==='Payment Received'||p.type==='Receipt');
        html+='<tr>';
        html+='<td>'+new Date(p.createdAt||p.date).toLocaleDateString()+'</td>';
        html+='<td class="fw-bold">'+(party?party.name:'—')+'</td>';
        html+='<td><span class="badge-pill '+(isReceived?'badge-green':'badge-red')+'">'+(isReceived?'Payment Received':(p.type||'—'))+'</span></td>';
        html+='<td class="fw-bold">'+ERP.formatCurrency(p.amount||0)+'</td>';
        var refNo = p.referenceNo || '';
        var refHtml = '—';
        if(refNo){
            if(refNo.indexOf('SR-')===0){
                refHtml = '<span class="badge-pill badge-orange" style="font-size:10px;margin-right:4px;">CM</span>'+escHtml(refNo);
            } else if(refNo.indexOf('PR-')===0){
                refHtml = '<span class="badge-pill badge-orange" style="font-size:10px;margin-right:4px;">DM</span>'+escHtml(refNo);
            } else {
                refHtml = escHtml(refNo);
            }
        }
        html+='<td>'+refHtml+'</td>';
        html+='<td><span class="text-muted">'+(p.notes||'—')+'</span></td>';
        html+='</tr>';
    });
    if(!page.length) html='<tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-wallet fs-1 d-block mb-2 text-muted"></i>No payments found</td></tr>';
    document.getElementById('paymentsBody').innerHTML=html;
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
    populatePartySelect();
}
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
function sddSelectParty(partyId, label){
    document.getElementById('pmParty').value = partyId;
    document.getElementById('pmParty-disp').textContent = label;
    document.getElementById('pmParty-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
    updateRefDropdown();
}
function sddSelectRef(refId, label){
    document.getElementById('pmRef').value = refId;
    document.getElementById('pmRef-disp').textContent = label;
    document.getElementById('pmRef-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
}
function sddSelectAcct(acctId, label){
    document.getElementById('pmAcct').value = acctId;
    document.getElementById('pmAcct-disp').textContent = label;
    document.getElementById('pmAcct-disp').style.color = '#1A1D2E';
    document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
}
function populateAcctSelect(){
    var accounts = (window.ERP.state.chartOfAccounts || []).filter(function(a){ return a.isActive; });
    var html = '';
    accounts.forEach(function(a){
        var label = escHtml(a.code) + ' — ' + escHtml(a.name) + ' <span class="erp-dropdown-type-label">(' + escHtml(a.type) + ')</span>';
        html += '<div class="sdd-opt" onclick="sddSelectAcct(\'' + escHtml(a.id) + '\',\'' + escHtml(a.code + ' — ' + a.name) + '\')">' + label + '</div>';
    });
    if (!html) html = '<div class="sdd-no-res">No accounts found — set up Chart of Accounts first</div>';
    else html += '<div class="sdd-no-res" style="display:none;">No accounts found</div>';
    document.getElementById('pmAcct-opts').innerHTML = html;
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.sdd-wrap')) document.querySelectorAll('.sdd-wrap.open').forEach(function(w){w.classList.remove('open');});
});

function populatePartySelect(){
    var html='';
    (window.ERP.state.parties||[]).forEach(function(p){
        var label=escHtml(p.name)+' <span class="erp-dropdown-type-label">('+escHtml(p.type)+')</span>';
        html+='<div class="sdd-opt" onclick="sddSelectParty(\''+escHtml(p.id)+'\',\''+escHtml(p.name)+' ('+escHtml(p.type)+')\')">'+label+'</div>';
    });
    html+='<div class="sdd-no-res" class="d-none">No parties found</div>';
    document.getElementById('pmParty-opts').innerHTML=html;
}
function updateRefDropdown(){
    var partyId=document.getElementById('pmParty').value;
    var state=window.ERP.state;
    var party=(state.parties||[]).find(function(p){return p.id===partyId;});
    // Reset ref SDD
    document.getElementById('pmRef').value='';
    document.getElementById('pmRef-disp').textContent='-- Select Reference --';
    document.getElementById('pmRef-disp').style.color='#B0B7C9';
    var html='';
    if(partyId && party){
        var isCustomer=(party.type||'').toLowerCase().indexOf('customer')!==-1;
        if(isCustomer){
            (state.sales||[]).filter(function(s){return s.customerId===partyId;}).forEach(function(s){
                var lbl=escHtml(s.id)+' — '+escHtml(ERP.formatCurrency(s.totalAmount))+' ('+escHtml(new Date(s.createdAt).toLocaleDateString())+')';
                html+='<div class="sdd-opt" onclick="sddSelectRef(\''+escHtml(s.id)+'\',\''+escHtml(s.id)+'\')">' +lbl+'</div>';
            });
            (state.salesReturns||[]).filter(function(r){return r.customerId===partyId;}).forEach(function(r){
                var lbl=escHtml(r.id)+' — '+escHtml(ERP.formatCurrency(r.totalAmount))+' ('+escHtml(new Date(r.createdAt).toLocaleDateString())+')';
                html+='<div class="sdd-opt" onclick="sddSelectRef(\''+escHtml(r.id)+'\',\''+escHtml(r.id)+'\')">'+lbl+'</div>';
            });
        } else {
            (state.purchaseOrders||[]).filter(function(po){return po.vendorId===partyId;}).forEach(function(po){
                var lbl=escHtml(po.id)+' — '+escHtml(ERP.formatCurrency(po.totalAmount))+' ('+escHtml(new Date(po.createdAt).toLocaleDateString())+')';
                html+='<div class="sdd-opt" onclick="sddSelectRef(\''+escHtml(po.id)+'\',\''+escHtml(po.id)+'\')">' +lbl+'</div>';
            });
            (state.purchaseReturns||[]).filter(function(r){return r.vendorId===partyId;}).forEach(function(r){
                var lbl=escHtml(r.id)+' — '+escHtml(ERP.formatCurrency(r.totalAmount))+' ('+escHtml(new Date(r.createdAt).toLocaleDateString())+')';
                html+='<div class="sdd-opt" onclick="sddSelectRef(\''+escHtml(r.id)+'\',\''+escHtml(r.id)+'\')">'+lbl+'</div>';
            });
        }
    }
    if(!html) html='<div class="sdd-no-res">'+(!partyId?'Select a party first':'No references found')+'</div>';
    else html+='<div class="sdd-no-res" class="d-none">No references found</div>';
    document.getElementById('pmRef-opts').innerHTML=html;
}
function openAddPayment(){
    ['pmAmount','pmNotes'].forEach(function(id){document.getElementById(id).value='';});
    // Reset Party SDD
    document.getElementById('pmParty').value='';
    document.getElementById('pmParty-disp').textContent='Select Party';
    document.getElementById('pmParty-disp').style.color='#B0B7C9';
    document.getElementById('pmType').value='Payment Received';
    // Reset G/L Account SDD
    document.getElementById('pmAcct').value='';
    document.getElementById('pmAcct-disp').textContent='— Select Account —';
    document.getElementById('pmAcct-disp').style.color='#B0B7C9';
    updateRefDropdown();
    populatePartySelect();
    populateAcctSelect();
}
async function savePayment(){
    var partyId=document.getElementById('pmParty').value;
    if(!partyId){alert('Please select a party');return;}
    var glAcct = document.getElementById('pmAcct').value;
    var rawType = document.getElementById('pmType').value;
    // Map display values to backend enum values
    var typeMap = { 'Payment Received': 'Receipt', 'Payment Made': 'Payment' };
    var data={
        partyId:partyId, type: typeMap[rawType] || rawType,
        amount:parseFloat(document.getElementById('pmAmount').value)||0,
        referenceNo:document.getElementById('pmRef').value,
        notes:document.getElementById('pmNotes').value || null,
        glAccountId: glAcct || null,
        paymentMethod: 'Cash'
    };
    if(data.amount<=0){alert('Amount must be positive');return;}
    try{
        await ERP.api.addPayment(data);
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){alert('Error: '+e.message);}
}

/* ── Cash Reconciliation ─────────────────────────────── */
var crModalEl = null;
var reconLog = JSON.parse(localStorage.getItem('crReconLog') || '[]');

function openCashRecon(){
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = String(today.getMonth()+1).padStart(2,'0');
    var dd = String(today.getDate()).padStart(2,'0');
    var hh = String(today.getHours()).padStart(2,'0');
    var mi = String(today.getMinutes()).padStart(2,'0');
    document.getElementById('crDateFrom').value = yyyy+'-'+mm+'-'+dd;
    document.getElementById('crDateTo').value   = yyyy+'-'+mm+'-'+dd;
    document.getElementById('crTimeFrom').value = '00:00';
    document.getElementById('crTimeTo').value   = hh+':'+mi;
    document.getElementById('crHeaderDate').textContent = 'Date: '+yyyy+'-'+mm+'-'+dd;

    var state = window.ERP.state || {};
    var cashierSel = document.getElementById('crPharmacist');
    cashierSel.innerHTML = '<option value="">Select Cashier</option>';
    (state.users||[]).filter(function(u){ return u.isActive; }).forEach(function(u){
        var opt = document.createElement('option');
        opt.value = u.username || u.id;
        opt.textContent = u.username || u.id;
        if(state.currentUser && u.id === state.currentUser.id) opt.selected = true;
        cashierSel.appendChild(opt);
    });

    document.getElementById('crOpeningBalance').value = 0;
    document.getElementById('crReturns').value = 0;
    document.getElementById('crManualTotalCash').value = 0;

    ['d5000','d1000','d500','d100','d50','d20','d10','dCoins'].forEach(function(id){ document.getElementById(id).value=0; });
    document.getElementById('crDenomFields').style.display = 'none';
    document.getElementById('crDenomChevron').style.transform = 'rotate(0deg)';

    ['crVarianceReason','crAuthorizedBy'].forEach(function(id){ document.getElementById(id).value=''; });

    refreshCrSystemData();
    calcCrTotal();
    crModalEl = new bootstrap.Modal(document.getElementById('cashReconModal'));
    crModalEl.show();
}
function closeCashRecon(){
    if(crModalEl) crModalEl.hide();
}
function toggleDenominations(){
    var fields = document.getElementById('crDenomFields');
    var chevron = document.getElementById('crDenomChevron');
    var open = fields.style.display !== 'none';
    fields.style.display = open ? 'none' : 'block';
    chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
}
function crFmt(val){
    return 'PKR '+Number(val).toLocaleString('en-PK', {minimumFractionDigits:0, maximumFractionDigits:0});
}
function refreshCrSystemData(){
    var dateFrom = document.getElementById('crDateFrom').value;
    var dateTo   = document.getElementById('crDateTo').value;
    var timeFrom = document.getElementById('crTimeFrom').value || '00:00';
    var timeTo   = document.getElementById('crTimeTo').value   || '23:59';
    if(!dateFrom || !dateTo) return;

    // Build range boundaries
    var rangeStart = new Date(dateFrom + 'T' + timeFrom + ':00');
    var rangeEnd   = new Date(dateTo   + 'T' + timeTo   + ':59');

    var state = window.ERP.state || {};
    var cashSales = 0, paymentsRcvd = 0;

    (state.sales||[]).forEach(function(s){
        if(!s.createdAt) return;
        var txDate = new Date(s.createdAt);
        if(txDate >= rangeStart && txDate <= rangeEnd && (s.paymentMethod||'').toLowerCase()==='cash'){
            cashSales += (s.totalAmount||0);
        }
    });

    (state.payments||[]).forEach(function(p){
        var ts = p.date || p.createdAt;
        if(!ts) return;
        var txDate = new Date(ts);
        if(txDate >= rangeStart && txDate <= rangeEnd && (p.type==='Payment Received'||p.type==='Receipt')){
            paymentsRcvd += (p.amount||0);
        }
    });

    document.getElementById('crCashSales').value = cashSales.toFixed(2);
    document.getElementById('crPaymentsReceived').value = paymentsRcvd.toFixed(2);
    document.getElementById('crHeaderDate').textContent = dateFrom + ' ' + timeFrom + ' → ' + dateTo + ' ' + timeTo;
    calcCrExpected();
}
function calcCrExpected(){
    var ob = parseFloat(document.getElementById('crOpeningBalance').value)||0;
    var cs = parseFloat(document.getElementById('crCashSales').value)||0;
    var pr = parseFloat(document.getElementById('crPaymentsReceived').value)||0;
    var re = parseFloat(document.getElementById('crReturns').value)||0;
    var expected = ob + cs + pr - re;
    document.getElementById('crExpectedClosing').textContent = crFmt(expected);
    document.getElementById('crHeaderDate').textContent = (document.getElementById('crDateFrom').value||'') + ' → ' + (document.getElementById('crDateTo').value||'');
    calcCrVariance();
}
function calcCrTotal(){
    var denominations = {d5000:5000,d1000:1000,d500:500,d100:100,d50:50,d20:20,d10:10,dCoins:1};
    var total = 0;
    Object.keys(denominations).forEach(function(id){
        total += (parseInt(document.getElementById(id).value)||0) * denominations[id];
    });
    document.getElementById('crTotalCash').textContent = crFmt(total);
    if(document.getElementById('crDenomFields').style.display !== 'none'){
        document.getElementById('crManualTotalCash').value = total;
        calcCrVariance();
    }
}
function calcCrVariance(){
    var ob = parseFloat(document.getElementById('crOpeningBalance').value)||0;
    var cs = parseFloat(document.getElementById('crCashSales').value)||0;
    var pr = parseFloat(document.getElementById('crPaymentsReceived').value)||0;
    var re = parseFloat(document.getElementById('crReturns').value)||0;
    var expected = ob + cs + pr - re;
    var actual = parseFloat(document.getElementById('crManualTotalCash').value)||0;
    var diff = Math.abs(expected - actual);
    var diffEl = document.getElementById('crVarDiff');
    document.getElementById('crVarExpected').textContent = crFmt(expected);
    document.getElementById('crVarActual').textContent = crFmt(actual);
    diffEl.className = '';
    if(actual < expected){
        diffEl.textContent = crFmt(diff)+' (Short)';
        diffEl.className = 'cr-diff-short';
    } else if(actual > expected){
        diffEl.textContent = crFmt(diff)+' (Over)';
        diffEl.className = 'cr-diff-over';
    } else {
        diffEl.textContent = crFmt(0);
        diffEl.className = 'cr-diff-ok';
    }
}
document.addEventListener('DOMContentLoaded', function(){
    var submitBtn = document.querySelector('.cr-btn-submit');
    if(submitBtn) submitBtn.addEventListener('click', function(){
        var ob = parseFloat(document.getElementById('crOpeningBalance').value)||0;
        var cs = parseFloat(document.getElementById('crCashSales').value)||0;
        var pr = parseFloat(document.getElementById('crPaymentsReceived').value)||0;
        var re = parseFloat(document.getElementById('crReturns').value)||0;
        var expected = ob + cs + pr - re;
        var actual = parseFloat(document.getElementById('crManualTotalCash').value)||0;
        var denominations = {};
        ['d5000','d1000','d500','d100','d50','d20','d10','dCoins'].forEach(function(id){
            denominations[id] = parseInt(document.getElementById(id).value)||0;
        });
        var entry = {
            id: Date.now(),
            dateFrom: document.getElementById('crDateFrom').value,
            dateTo:   document.getElementById('crDateTo').value,
            timeFrom: document.getElementById('crTimeFrom').value,
            timeTo:   document.getElementById('crTimeTo').value,
            cashier: document.getElementById('crPharmacist').value || '—',
            openingBalance: ob,
            cashSales: cs,
            paymentsReceived: pr,
            returns: re,
            denominations: denominations,
            expected: expected,
            actual: actual,
            variance: actual - expected,
            reason: document.getElementById('crVarianceReason').value,
            authorizedBy: document.getElementById('crAuthorizedBy').value,
            status: 'Submitted'
        };
        var log = JSON.parse(localStorage.getItem('crReconLog')||'[]');
        log.unshift(entry);
        localStorage.setItem('crReconLog', JSON.stringify(log));
        closeCashRecon();
        alert('Reconciliation submitted successfully.');
    });
});
var reconLogModalEl = null;
function openReconLog(){
    renderReconLog();
    reconLogModalEl = new bootstrap.Modal(document.getElementById('reconLogModal'));
    reconLogModalEl.show();
}
function closeReconLog(){
    if(reconLogModalEl) reconLogModalEl.hide();
}
function renderReconLog(){
    var log = JSON.parse(localStorage.getItem('crReconLog')||'[]');
    var tbody = document.getElementById('reconLogBody');
    if(!log.length){
        tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-5"><i class="ti ti-inbox d-block mb-2" class="fs-2"></i>No reconciliations submitted yet</td></tr>';
        return;
    }
    var html = '';
    log.forEach(function(r, i){
        var varClass = r.variance < 0 ? 'color:#DC2626;' : r.variance > 0 ? 'color:#D97706;' : 'color:#059669;';
        var varText = r.variance < 0 ? crFmt(Math.abs(r.variance))+' Short' : r.variance > 0 ? crFmt(r.variance)+' Over' : 'Balanced';
        html += '<tr>';
        html += '<td class="cr-td-muted">'+(i+1)+'</td>';
        html += '<td class="cr-td fw-medium">'+(r.dateFrom||r.date||'—')+'</td>';
        html += '<td class="cr-td-muted">'+(r.timeFrom||r.time||'—')+'</td>';
        html += '<td class="cr-td fw-medium">'+(r.dateTo||r.date||'—')+'</td>';
        html += '<td class="cr-td-muted">'+(r.timeTo||'—')+'</td>';
        html += '<td class="cr-td">'+r.cashier+'</td>';
        html += '<td class="cr-td text-end">'+crFmt(r.expected)+'</td>';
        html += '<td class="cr-td text-end">'+crFmt(r.actual)+'</td>';
        html += '<td class="cr-var-td" style="'+varClass+'">'+varText+'</td>';
        html += '<td class="cr-td"><span class="cr-status-badge">'+r.status+'</span></td>';
        html += '<td class="cr-td"><button onclick="viewReconDetail('+i+')" class="cr-view-btn"><i class="ti ti-eye"></i> View</button></td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

function viewReconDetail(idx){
    var log = JSON.parse(localStorage.getItem('crReconLog')||'[]');
    var r = log[idx];
    if(!r) return;
    var varClass = r.variance < 0 ? '#DC2626' : r.variance > 0 ? '#D97706' : '#059669';
    var varText  = r.variance < 0 ? crFmt(Math.abs(r.variance))+' Short' : r.variance > 0 ? crFmt(r.variance)+' Over' : 'Balanced';

    function row(label, value, bold){
        return '<div class="cr-row-item">'
            +'<span class="cr-row-label">'+label+'</span>'
            +'<span class="cr-row-val" style="font-weight:'+(bold?'700':'500')+';">'+value+'</span>'
            +'</div>';
    }

    var html = ''
        +'<div class="cr-period-box">'
        +'<div>'
        +'<div class="cr-period-label">Period</div>'
        +'<div class="cr-period-val">'+(r.dateFrom||r.date||'—')+' '+( r.timeFrom||'')+' → '+(r.dateTo||r.date||'—')+' '+(r.timeTo||'')+'</div>'
        +'</div>'
        +'<span class="cr-status-badge-lg">'+r.status+'</span>'
        +'</div>'
        +'<div class="cr-info-box">'
        + row('Cashier', r.cashier||'—')
        + row('Opening Balance', crFmt(r.openingBalance||0))
        + row('Cash Sales', crFmt(r.cashSales||0))
        + row('Payments Received', crFmt(r.paymentsReceived||0))
        + row('Returns / Refunds', crFmt(r.returns||0))
        + row('Expected Closing', crFmt(r.expected||0), true)
        +'</div>'
        +'<div class="cr-info-box">';

    // Denominations (if any)
    var denoms = [{k:'d5000',l:'5000 × '},{k:'d1000',l:'1000 × '},{k:'d500',l:'500 × '},{k:'d100',l:'100 × '},{k:'d50',l:'50 × '},{k:'d20',l:'20 × '},{k:'d10',l:'10 × '},{k:'dCoins',l:'Coins'}];
    var hasDenom = denoms.some(function(d){ return r.denominations && r.denominations[d.k]; });
    if(hasDenom){
        denoms.forEach(function(d){
            var qty = (r.denominations&&r.denominations[d.k])||0;
            if(qty) html += row(d.l+qty, crFmt(qty * (d.k==='dCoins'?1:parseInt(d.k.replace('d','')))));
        });
    }
    html += row('Total Cash (Actual)', crFmt(r.actual||0), true)+'</div>';

    if(r.variance !== 0){
        html += '<div class="'+(r.variance<0?'cr-variance-neg':'cr-variance-pos')+'">'
            + row('Variance', '<span style="color:'+varClass+';font-weight:700;">'+varText+'</span>')
            + row('Reason', r.reason||'—')
            + row('Authorized By', r.authorizedBy||'—')
            +'</div>';
    }

    document.getElementById('crDetailContent').innerHTML = html;
    document.getElementById('crDetailOverlay').style.display = 'flex';
}
