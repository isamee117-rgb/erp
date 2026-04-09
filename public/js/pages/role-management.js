var MODULES=[
    {key:'Dashboard',label:'Dashboard'},{key:'POS',label:'POS Terminal'},{key:'Inventory',label:'Inventory Master'},
    {key:'Purchases',label:'Purchases'},{key:'Sales',label:'Sales History'},{key:'Returns',label:'Returns'},
    {key:'Parties',label:'Parties'},{key:'Finance',label:'Finance'},{key:'Reports',label:'Reports'},
    {key:'Settings',label:'Settings'},{key:'Roles',label:'Role Management'},{key:'Users',label:'User Management'}
];
var ACTIONS=['view','create','edit','delete'];
window.ERP.onReady = function(){ renderPage(); };
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('searchInput').addEventListener('input', function(){ renderPage(); });
});
function renderPage(){
    var state=window.ERP.state, user=state.currentUser;
    if(!user) return;
    var tenantRoles=(state.customRoles||[]).filter(function(r){return r.companyId===user.companyId;});
    var search=(document.getElementById('searchInput').value||'').toLowerCase();
    var filtered=tenantRoles.filter(function(r){return r.name.toLowerCase().indexOf(search)!==-1;});
    var html='';
    filtered.forEach(function(role){
        var userCount=(state.users||[]).filter(function(u){return u.roleId===role.id;}).length;
        var perms=role.permissions||{};
        var summary=MODULES.filter(function(m){return perms[m.key]&&perms[m.key].view;}).map(function(m){return m.label;}).join(', ')||'None';
        html+='<tr>';
        html+='<td class="fw-bold">'+role.name+'</td>';
        html+='<td><span class="badge-pill badge-blue">'+userCount+' users</span></td>';
        html+='<td><span class="text-muted" class="erp-text-82">'+summary+'</span></td>';
        html+='<td><div class="d-flex align-items-center">';
        html+='<button class="inv-action-btn" title="Edit" onclick="openEditRole(\''+role.id+'\')"><i class="ti ti-edit"></i></button>';
        html+='<button class="inv-action-btn inv-action-danger" title="Delete" onclick="deleteRole(\''+role.id+'\')"><i class="ti ti-trash"></i></button>';
        html+='</div></td></tr>';
    });
    if(!filtered.length) html='<tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-shield fs-1 d-block mb-2 text-muted"></i>No roles found</td></tr>';
    document.getElementById('rolesBody').innerHTML=html;
}
function buildPermGrid(permissions){
    var html='';
    MODULES.forEach(function(m){
        var p=(permissions&&permissions[m.key])||{view:false,create:false,edit:false,delete:false};
        html+='<tr><td class="fw-bold" class="erp-text-82">'+m.label+'</td>';
        ACTIONS.forEach(function(a){
            html+='<td class="text-center"><input type="checkbox" class="form-check-input" id="perm-'+m.key+'-'+a+'" '+(p[a]?'checked':'')+' onchange="handlePermChange(\''+m.key+'\',\''+a+'\')"></td>';
        });
        html+='</tr>';
    });
    document.getElementById('permBody').innerHTML=html;
}
function handlePermChange(mod,action){
    if(action==='view'){
        var viewChecked=document.getElementById('perm-'+mod+'-view').checked;
        if(!viewChecked){
            ['create','edit','delete'].forEach(function(a){document.getElementById('perm-'+mod+'-'+a).checked=false;});
        }
    } else {
        var isChecked=document.getElementById('perm-'+mod+'-'+action).checked;
        if(isChecked) document.getElementById('perm-'+mod+'-view').checked=true;
    }
}
function getPermissions(){
    var perms={};
    MODULES.forEach(function(m){
        perms[m.key]={};
        ACTIONS.forEach(function(a){ perms[m.key][a]=document.getElementById('perm-'+m.key+'-'+a).checked; });
    });
    return perms;
}
function openRoleModal(role){
    document.getElementById('editRoleId').value='';
    document.getElementById('roleName').value='';
    document.getElementById('roleDesc').value='';
    document.getElementById('roleModalTitle').innerHTML='<i class="ti ti-shield-plus me-2"></i>Add Role';
    buildPermGrid(null);
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}
function openEditRole(id){
    var role=(window.ERP.state.customRoles||[]).find(function(r){return r.id===id;});
    if(!role) return;
    document.getElementById('editRoleId').value=role.id;
    document.getElementById('roleName').value=role.name;
    document.getElementById('roleDesc').value=role.description||'';
    document.getElementById('roleModalTitle').innerHTML='<i class="ti ti-shield-check me-2"></i>Edit Role';
    buildPermGrid(role.permissions);
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}
async function saveRole(){
    var editId=document.getElementById('editRoleId').value;
    var name=document.getElementById('roleName').value;
    if(!name){alert('Role name is required');return;}
    var data={name:name,description:document.getElementById('roleDesc').value,permissions:getPermissions()};
    try{
        if(editId){ data.id=editId; await ERP.api.updateCustomRole(data); }
        else{ await ERP.api.addCustomRole(data); }
        bootstrap.Modal.getInstance(document.getElementById('roleModal')).hide();
        await ERP.sync(); renderPage();
    }catch(e){alert('Error: '+e.message);}
}
async function deleteRole(id){
    if(!confirm('Delete this role?')) return;
    try{await ERP.api.deleteCustomRole(id);await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
