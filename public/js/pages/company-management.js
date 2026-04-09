var selectedCoId=null;
function togglePwdVis(inputId, btn) {
  var inp = document.getElementById(inputId);
  var icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'ti ti-eye-off';
  } else {
    inp.type = 'password';
    icon.className = 'ti ti-eye';
  }
}
window.ERP.onReady = function(){ renderPage(); };
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('searchInput').addEventListener('input', function(){ renderPage(); });
});
function renderPage(){
    var state=window.ERP.state, search=(document.getElementById('searchInput').value||'').toLowerCase();
    var companies=(state.companies||[]).filter(function(c){
        return c.name.toLowerCase().indexOf(search)!==-1||c.id.toLowerCase().indexOf(search)!==-1;
    });
    var html='';
    companies.forEach(function(co){
        var isBlocked=co.status==='Blocked';
        html+='<tr>';
        html+='<td><div class="fw-bold">'+co.name+'</div><div class="company-id-badge">ID: '+co.id.slice(0,8)+'</div></td>';
        html+='<td><span class="badge-pill '+(isBlocked?'badge-red':'badge-green')+'">'+co.status+'</span></td>';
        html+='<td><span class="fw-bold">'+co.maxUserLimit+'</span></td>';
        html+='<td><span class="badge-pill badge-blue">'+(co.saasPlan||'—')+'</span></td>';
        html+='<td class="text-end">'+ERP.formatCurrency(co.registrationPayment||0)+'</td>';
        html+='<td><div class="d-flex align-items-center">';
        html+='<button class="btn btn-sm '+(isBlocked?'btn-outline-success':'btn-outline-danger')+' me-1 erp-btn-action-sm" onclick="toggleStatus(\''+co.id+'\',\''+(isBlocked?'Active':'Blocked')+'\')">'+(isBlocked?'Unblock':'Block')+'</button>';
        html+='<button class="inv-action-btn" title="Update User Limit" onclick="openUpdateLimit(\''+co.id+'\',\''+co.name+'\','+co.maxUserLimit+')"><i class="ti ti-users"></i></button>';
        html+='<button class="inv-action-btn" title="Reset Admin Password" onclick="openResetPwd(\''+co.id+'\',\''+co.name+'\')"><i class="ti ti-key"></i></button>';
        html+='<button class="inv-action-btn" title="View / Edit Details" onclick="openCompanyDetails(\''+co.id+'\')"><i class="ti ti-edit"></i></button>';
        html+='</div></td></tr>';
    });
    if(!companies.length) html='<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-briefcase fs-1 d-block mb-2 text-muted"></i>No companies found</td></tr>';
    document.getElementById('companiesBody').innerHTML=html;
}
function openAddCompany(){
    ['coName','coAdminUser','coAdminPass'].forEach(function(id){document.getElementById(id).value='';});
    document.getElementById('coLimit').value='10';
    document.getElementById('coPayment').value='0';
    document.getElementById('coPlan').value='Monthly';
}
async function createCompany(){
    var name=document.getElementById('coName').value;
    var adminUser=document.getElementById('coAdminUser').value;
    var adminPass=document.getElementById('coAdminPass').value;
    if(!name||!adminUser||!adminPass){alert('All fields required');return;}
    try{
        await ERP.api.createCompany(name,adminUser,adminPass,parseInt(document.getElementById('coLimit').value)||10,parseFloat(document.getElementById('coPayment').value)||0,document.getElementById('coPlan').value);
        bootstrap.Modal.getInstance(document.getElementById('addCompanyModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){alert('Error: '+e.message);}
}
async function toggleStatus(id,status){
    try{await ERP.api.updateCompanyStatus(id,status);await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
function openUpdateLimit(id,name,currentLimit){
    selectedCoId=id;
    document.getElementById('updateLimitLabel').textContent='Company: '+name;
    document.getElementById('updateLimitInput').value=currentLimit;
    new bootstrap.Modal(document.getElementById('updateLimitModal')).show();
}
async function doUpdateLimit(){
    var limit=parseInt(document.getElementById('updateLimitInput').value)||1;
    try{
        await ERP.api.updateCompanyLimit(selectedCoId,limit);
        bootstrap.Modal.getInstance(document.getElementById('updateLimitModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){alert('Error: '+e.message);}
}
function openResetPwd(id,name){
    selectedCoId=id;
    document.getElementById('resetPwdLabel').textContent='Company: '+name;
    document.getElementById('resetPwdInput').value='';
    new bootstrap.Modal(document.getElementById('resetPwdModal')).show();
}
async function doResetPassword(){
    var pwd=document.getElementById('resetPwdInput').value;
    if(!pwd){alert('Enter a password');return;}
    try{
        await ERP.api.updateCompanyAdminPassword(selectedCoId,pwd);
        bootstrap.Modal.getInstance(document.getElementById('resetPwdModal')).hide();
        await ERP.sync(); renderPage();
        alert('Password reset successfully');
    }catch(e){alert('Error: '+e.message);}
}
function openCompanyDetails(id){
    selectedCoId=id;
    var co=(window.ERP.state.companies||[]).find(function(c){return c.id===id;});
    if(!co){return;}
    var adminUser=(window.ERP.state.users||[]).find(function(u){return u.companyId===id&&u.systemRole==='Company Admin';});
    document.getElementById('cdName').value=co.name||'';
    document.getElementById('cdStatus').value=co.status||'';
    document.getElementById('cdPlan').value=co.saasPlan||'Monthly';
    document.getElementById('cdLimit').value=co.maxUserLimit||1;
    document.getElementById('cdPayment').value=co.registrationPayment||0;
    document.getElementById('cdAdminUser').value=adminUser?adminUser.username:'—';
    document.getElementById('cdAdminPass').value='••••••';
    var info=co.info||{};
    document.getElementById('cdInfoName').value=info.name||'';
    document.getElementById('cdTagline').value=info.tagline||'';
    document.getElementById('cdAddress').value=info.address||'';
    document.getElementById('cdPhone').value=info.phone||'';
    document.getElementById('cdEmail').value=info.email||'';
    document.getElementById('cdWebsite').value=info.website||'';
    document.getElementById('cdTaxId').value=info.taxId||'';
    new bootstrap.Modal(document.getElementById('companyDetailsModal')).show();
}
async function saveCompanyDetails(){
    if(!selectedCoId){return;}
    var data={
        name:document.getElementById('cdName').value,
        saasPlan:document.getElementById('cdPlan').value,
        maxUserLimit:parseInt(document.getElementById('cdLimit').value)||1,
        registrationPayment:parseFloat(document.getElementById('cdPayment').value)||0,
        infoName:document.getElementById('cdInfoName').value,
        infoTagline:document.getElementById('cdTagline').value,
        infoAddress:document.getElementById('cdAddress').value,
        infoPhone:document.getElementById('cdPhone').value,
        infoEmail:document.getElementById('cdEmail').value,
        infoWebsite:document.getElementById('cdWebsite').value,
        infoTaxId:document.getElementById('cdTaxId').value
    };
    try{
        await ERP.api.updateCompanyDetails(selectedCoId,data);
        bootstrap.Modal.getInstance(document.getElementById('companyDetailsModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){alert('Error: '+e.message);}
}
