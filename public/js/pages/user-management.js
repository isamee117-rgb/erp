var selectedUserId=null;
var editUserId=null;
window.ERP.onReady = function(){ renderPage(); };
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('searchInput').addEventListener('input', function(){ renderPage(); });
});
function renderPage(){
    var state=window.ERP.state, user=state.currentUser;
    if(!user) return;
    var companyId=user.companyId;
    var companyUsers=(state.users||[]).filter(function(u){return u.companyId===companyId;});
    var company=(state.companies||[]).find(function(c){return c.id===companyId;});
    var maxLimit=company?company.maxUserLimit:0;
    document.getElementById('userLimitInfo').textContent='Users: '+companyUsers.length+' / '+maxLimit;
    var search=(document.getElementById('searchInput').value||'').toLowerCase();
    var filtered=companyUsers.filter(function(u){return u.username.toLowerCase().indexOf(search)!==-1;});
    var tenantRoles=(state.customRoles||[]).filter(function(r){return r.companyId===companyId;});
    var roleHtml='<option value="">Select Role...</option>';
    tenantRoles.forEach(function(r){roleHtml+='<option value="'+r.id+'">'+r.name+'</option>';});
    document.getElementById('uRole').innerHTML=roleHtml;
    if(companyUsers.length>=maxLimit) document.getElementById('addUserBtn').disabled=true;
    else document.getElementById('addUserBtn').disabled=false;
    var html='';
    filtered.forEach(function(u){
        var role=tenantRoles.find(function(r){return r.id===u.roleId;});
        html+='<tr>';
        html+='<td><div class="d-flex align-items-center"><div class="user-avatar">'+u.username.charAt(0).toUpperCase()+'</div><span class="fw-bold">'+u.username+'</span></div></td>';
        html+='<td>'+(u.name||'<span class="text-muted">—</span>')+'</td>';
        html+='<td>'+(role?'<span class="badge-pill badge-blue">'+role.name+'</span>':'<span class="badge-pill badge-gray">Admin / Owner</span>')+'</td>';
        html+='<td><span class="badge-pill '+(u.isActive?'badge-green':'badge-red')+'">'+(u.isActive?'Active':'Inactive')+'</span></td>';
        html+='<td><div class="d-flex align-items-center">';
        html+='<button class="btn btn-sm '+(u.isActive?'btn-outline-danger':'btn-outline-success')+' me-1 erp-btn-action-sm" onclick="toggleStatus(\''+u.id+'\','+(!u.isActive)+')">'+(u.isActive?'Deactivate':'Activate')+'</button>';
        html+='<button class="inv-action-btn me-1" title="Edit User" onclick="openEditUser(\''+u.id+'\')"><i class="ti ti-pencil"></i></button>';
        html+='<button class="inv-action-btn" title="Reset Password" onclick="openPasswordModal(\''+u.id+'\',\''+u.username+'\')"><i class="ti ti-key"></i></button>';
        html+='</div></td></tr>';
    });
    if(!filtered.length) html='<tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-user-cog fs-1 d-block mb-2 text-muted"></i>No users found</td></tr>';
    document.getElementById('usersBody').innerHTML=html;
}
function togglePwdVisibility(){
    var inp=document.getElementById('uPassword');
    var icon=document.getElementById('uPwdEyeIcon');
    if(inp.type==='password'){inp.type='text';icon.className='ti ti-eye-off';}
    else{inp.type='password';icon.className='ti ti-eye';}
}
function openAddUser(){
    editUserId=null;
    document.getElementById('userModalTitle').innerHTML='<i class="ti ti-user-plus me-2"></i>Add User';
    document.getElementById('uName').value='';
    document.getElementById('uUsername').value='';
    document.getElementById('uPassword').value='';
    document.getElementById('uPassword').type='password';
    document.getElementById('uPassword').required=true;
    document.getElementById('uPwdEyeIcon').className='ti ti-eye';
    document.getElementById('uPasswordHint').style.display='none';
    document.getElementById('uRole').value='';
    document.getElementById('userError').classList.add('d-none');
}
function openEditUser(id){
    var state=window.ERP.state;
    var u=(state.users||[]).find(function(x){return x.id===id;});
    if(!u) return;
    editUserId=id;
    document.getElementById('userModalTitle').innerHTML='<i class="ti ti-pencil me-2"></i>Edit User';
    document.getElementById('uName').value=u.name||'';
    document.getElementById('uUsername').value=u.username||'';
    document.getElementById('uPassword').value='';
    document.getElementById('uPassword').type='password';
    document.getElementById('uPassword').required=false;
    document.getElementById('uPwdEyeIcon').className='ti ti-eye';
    document.getElementById('uPasswordHint').style.display='';
    document.getElementById('uRole').value=u.roleId||'';
    document.getElementById('userError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
async function saveUser(){
    var name=document.getElementById('uName').value;
    var username=document.getElementById('uUsername').value;
    var password=document.getElementById('uPassword').value;
    var roleId=document.getElementById('uRole').value;
    var errEl=document.getElementById('userError');
    errEl.classList.add('d-none');
    try{
        var result;
        if(editUserId){
            // Edit mode
            if(!username){errEl.textContent='Username is required';errEl.classList.remove('d-none');return;}
            var payload={name:name,username:username,roleId:roleId||null};
            if(password) payload.password=password;
            result=await ERP.api.updateUser(editUserId,payload);
            if(password && result.success) await ERP.api.updateUserPassword(editUserId,password);
        } else {
            // Add mode
            if(!username||!password){errEl.textContent='Username and password required';errEl.classList.remove('d-none');return;}
            result=await ERP.api.addUser({name:name,username:username,password:password,roleId:roleId||null,isActive:true});
        }
        if(result&&result.error){errEl.textContent=result.error;errEl.classList.remove('d-none');return;}
        bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){errEl.textContent=e.message;errEl.classList.remove('d-none');}
}
async function toggleStatus(id,active){
    try{await ERP.api.setUserStatus(id,active);await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
function openPasswordModal(id,username){
    selectedUserId=id;
    document.getElementById('pwdUserLabel').textContent='For user: '+username;
    document.getElementById('newPwd').value='';
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
}
async function resetPassword(){
    var pwd=document.getElementById('newPwd').value;
    if(!pwd){alert('Enter a password');return;}
    try{
        await ERP.api.updateUserPassword(selectedUserId,pwd);
        bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
        await ERP.sync(); renderPage();
        alert('Password updated');
    }catch(e){alert('Error: '+e.message);}
}
