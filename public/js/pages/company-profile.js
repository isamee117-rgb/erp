var _currentLogoUrl = '';

window.ERP.onReady = function(){ renderPage(); };

function renderPage(){
    var state=window.ERP.state;
    var company=(state.companies||[]).find(function(c){return c.id===(state.currentUser?state.currentUser.companyId:null);});
    var info=(company&&company.info)||{};
    document.getElementById('cpName').value=company?company.name:'';
    document.getElementById('cpAddress').value=info.address||'';
    document.getElementById('cpTagline').value=info.tagline||'';
    document.getElementById('cpPhone').value=info.phone||'';
    document.getElementById('cpEmail').value=info.email||'';
    document.getElementById('cpWebsite').value=info.website||'';
    document.getElementById('cpTaxId').value=info.taxId||'';
    _currentLogoUrl = info.logoUrl||'';
    setLogoPreview(_currentLogoUrl);
}

function setLogoPreview(url){
    var img=document.getElementById('logoImg');
    var placeholder=document.getElementById('logoPlaceholder');
    var removeBtn=document.getElementById('removeLogoBtn');
    if(url){
        // data: URLs and http URLs are used as-is; relative paths get the base asset URL prepended
        var absUrl = (url.startsWith('data:') || url.startsWith('http'))
            ? url
            : '{{ asset('') }}'.replace(/\/$/, '') + url;
        img.src = absUrl;
        img.classList.remove('d-none');
        placeholder.classList.add('d-none');
        removeBtn.classList.remove('d-none');
    } else {
        img.src='';
        img.classList.add('d-none');
        placeholder.classList.remove('d-none');
        removeBtn.classList.add('d-none');
    }
}

document.getElementById('logoFileInput').addEventListener('change', async function(){
    var file = this.files[0];
    if(!file) return;
    var status = document.getElementById('logoUploadStatus');
    var btn = document.getElementById('uploadLogoBtn');

    // Local preview immediately
    var reader = new FileReader();
    reader.onload = function(e){ setLogoPreview(e.target.result); };
    reader.readAsDataURL(file);

    // Upload to server
    status.textContent = 'Uploading...';
    status.className = 'cp-upload-status';
    status.classList.remove('d-none');
    btn.disabled = true;

    try {
        var result = await ERP.api.uploadCompanyLogo(file);
        if(result.success){
            _currentLogoUrl = result.url;
            setLogoPreview(_currentLogoUrl);
            status.textContent = 'Logo uploaded successfully';
            status.classList.add('success');
            await ERP.sync();
        } else {
            status.textContent = result.error || 'Upload failed';
            status.classList.add('error');
        }
    } catch(e){
        status.textContent = 'Upload failed: ' + e.message;
        status.classList.add('error');
    }
    btn.disabled = false;
    this.value = '';
    setTimeout(function(){ status.classList.add('d-none'); }, 3000);
});

function removeLogo(){
    _currentLogoUrl = '';
    setLogoPreview('');
}

function saveProfile(){
    document.getElementById('cpConfirmModal').classList.remove('d-none');
}

async function doSaveProfile(){
    document.getElementById('cpConfirmModal').classList.add('d-none');
    var data={
        name:document.getElementById('cpName').value,
        address:document.getElementById('cpAddress').value,
        tagline:document.getElementById('cpTagline').value,
        phone:document.getElementById('cpPhone').value,
        email:document.getElementById('cpEmail').value,
        website:document.getElementById('cpWebsite').value,
        taxId:document.getElementById('cpTaxId').value,
        logoUrl:_currentLogoUrl
    };
    try{
        await ERP.api.updateCompanyInfo(data);
        await ERP.sync();
        renderPage();
        document.getElementById('cpSuccessModal').classList.remove('d-none');
    }catch(e){alert('Error: '+e.message);}
}
