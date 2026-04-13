var SEQUENCE_LABELS={
    po_number:{label:'Purchase Order No.'},sale_invoice:{label:'Sales Invoice No.'},
    sale_return:{label:'Credit Memo No.'},purchase_return:{label:'Debit Memo No.'},
    customer_no:{label:'Customer No.'},vendor_no:{label:'Vendor No.'},
    item_no:{label:'Item No.'}
};
window.ERP.onReady = function(){ renderPage(); };
function renderPage(){
    var state=window.ERP.state;
    var currSel = document.getElementById('currencySelect');
    currSel.value = state.currency||'Rs.';
    var currencyLocked = (state.documentSequences||[]).some(function(s){ return s.isLocked; });
    var currActions = document.getElementById('currencyActions');
    if (currencyLocked) {
        currSel.disabled = true;
        currSel.style.background = '#F8F9FC';
        currSel.style.color = '#64748b';
        currSel.style.cursor = 'not-allowed';
        currActions.innerHTML = '<span class="set-lock-msg"><i class="ti ti-lock me-1"></i>Locked — currency cannot be changed once transactions exist</span>';
    } else {
        currSel.disabled = false;
        currSel.style.background = '';
        currSel.style.color = '';
        currSel.style.cursor = '';
        currActions.innerHTML = '<button id="saveCurrencyBtn" class="btn btn-sm btn-primary mt-1 erp-btn-action-sm"><i class="ti ti-device-floppy me-1"></i>Save Currency</button>';
        document.getElementById('saveCurrencyBtn').addEventListener('click', saveCurrency);
    }
    document.getElementById('invoiceFormatSelect').value=state.invoiceFormat||'A4';
    document.getElementById('costingMethodSelect').value=state.costingMethod||'moving_average';
    updateCostingDesc();
    var user=state.currentUser;
    if(user){
        document.getElementById('settingsAvatar').textContent=user.username.charAt(0).toUpperCase();
        document.getElementById('settingsUsername').textContent=user.username;
        document.getElementById('settingsRole').textContent=user.systemRole;
    }
    renderTags('categoriesList',state.categories||[],'deleteCategory');
    renderTags('uomsList',state.uoms||[],'deleteUOM');
    renderTags('entityTypesList',state.entityTypes||[],'deleteEntityType');
    renderTags('bizCatsList',state.businessCategories||[],'deleteBizCat');
    renderSequences();
    renderDynamicFieldSettings();
}
function renderTags(containerId,items,deleteFn){
    var html='';
    items.forEach(function(it){
        html+='<span class="set-tag">'+it.name+' <a href="javascript:void(0)" onclick="'+deleteFn+'(\''+it.id+'\')" class="del-btn"><i class="ti ti-x"></i></a></span>';
    });
    if(!items.length) html='<span class="text-muted small">None added yet</span>';
    document.getElementById(containerId).innerHTML=html;
}
function renderSequences(){
    var seqs=window.ERP.state.documentSequences||[];
    var html='';
    Object.keys(SEQUENCE_LABELS).forEach(function(type){
        var seq=seqs.find(function(s){return s.type===type;});
        var prefix=seq?seq.prefix:'';
        var nextNum=seq?seq.nextNumber:1;
        var locked=seq?seq.isLocked:false;
        var preview=prefix+String(nextNum).padStart(5,'0');
        var lockedTip=locked?' title="Sequence in use — prefix is always editable; next number can only increase"':'';
        html+='<tr>';
        html+='<td class="fw-bold" class="erp-text-82">'+SEQUENCE_LABELS[type].label+'</td>';
        var inputCls='form-control seq-input-base '+(locked?'seq-input-ro':'seq-input-rw');
        html+='<td><input type="text" class="'+inputCls+'" id="seqPfx-'+type+'" value="'+prefix+'" '+(locked?'readonly tabindex="-1" title="Locked — prefix cannot be changed once in use"':'oninput="updateSeqPreview(\''+type+'\')"')+' autocomplete="off"></td>';
        html+='<td><input type="number" class="'+inputCls+'" id="seqNum-'+type+'" value="'+nextNum+'" '+(locked?'readonly tabindex="-1" title="Locked — next number is managed automatically"':'min="1" oninput="updateSeqPreview(\''+type+'\')"')+' autocomplete="off"></td>';
        html+='<td><code id="seqPrev-'+type+'" class="bk-seq-preview">'+preview+'</code></td>';
        html+='<td>'+(locked?'<span class="badge-pill badge-orange"><i class="ti ti-lock me-1"></i>In Use</span>':'<span class="badge-pill badge-green">Editable</span>')+'</td>';
        html+='<td>'+(locked?'<span class="set-auto-managed">Auto-managed</span>':'<button class="btn btn-sm btn-primary erp-btn-action-sm" onclick="saveSeq(\''+type+'\')"><i class="ti ti-device-floppy me-1"></i>Save</button>')+'</td>';
        html+='</tr>';
    });
    document.getElementById('seqBody').innerHTML=html;
}
function updateSeqPreview(type){
    var pfx=document.getElementById('seqPfx-'+type);
    var num=document.getElementById('seqNum-'+type);
    var prev=document.getElementById('seqPrev-'+type);
    if(pfx&&num&&prev) prev.textContent=(pfx.value||'')+String(parseInt(num.value)||1).padStart(5,'0');
}
function updateCostingDesc(){
    var v=document.getElementById('costingMethodSelect').value;
    document.getElementById('costingDesc').textContent=v==='fifo'?'FIFO sells the oldest inventory first. Cost layers are tracked per purchase batch and consumed in order.':'Moving Average recalculates the weighted average cost each time new stock is received.';
}
async function saveCurrency(){
    var btn = document.getElementById('saveCurrencyBtn');
    if(btn){ btn.disabled=true; btn.innerHTML='<i class="ti ti-loader me-1"></i>Saving...'; }
    try{
        await ERP.api.updateCurrency(document.getElementById('currencySelect').value);
        await ERP.sync();
        renderPage();
        var b = document.getElementById('saveCurrencyBtn');
        if(b){ b.innerHTML='<i class="ti ti-circle-check me-1"></i>Saved!'; b.style.background='#10B981'; setTimeout(function(){ renderPage(); },1500); }
    }catch(e){
        if(btn){ btn.disabled=false; btn.innerHTML='<i class="ti ti-device-floppy me-1"></i>Save Currency'; }
        alert('Error: '+e.message);
    }
}
async function saveInvoiceFormat(){
    try{await ERP.api.updateInvoiceFormat(document.getElementById('invoiceFormatSelect').value);await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
async function saveCostingMethod(){
    try{await ERP.api.updateCostingMethod(document.getElementById('costingMethodSelect').value);await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
async function addCategory(){
    var v=document.getElementById('newCategory').value.trim(); if(!v)return;
    try{await ERP.api.createCategory(window.ERP.state.currentUser.companyId,v);document.getElementById('newCategory').value='';await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
var _stgPendingDelete = null;
function deleteCategory(id){
    _stgPendingDelete=async function(){await ERP.api.deleteCategory(id);};
    document.getElementById('stgDeleteConfirm').classList.remove('d-none');
}
async function addUOM(){
    var v=document.getElementById('newUOM').value.trim(); if(!v)return;
    try{await ERP.api.createUOM(window.ERP.state.currentUser.companyId,v);document.getElementById('newUOM').value='';await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
function deleteUOM(id){
    _stgPendingDelete=async function(){await ERP.api.deleteUOM(id);};
    document.getElementById('stgDeleteConfirm').classList.remove('d-none');
}
async function addEntityType(){
    var v=document.getElementById('newEntityType').value.trim(); if(!v)return;
    try{await ERP.api.createEntityType(v);document.getElementById('newEntityType').value='';await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
function deleteEntityType(id){
    _stgPendingDelete=async function(){await ERP.api.deleteEntityType(id);};
    document.getElementById('stgDeleteConfirm').classList.remove('d-none');
}
async function addBizCat(){
    var v=document.getElementById('newBizCat').value.trim(); if(!v)return;
    try{await ERP.api.createBusinessCategory(v);document.getElementById('newBizCat').value='';await ERP.sync();renderPage();}catch(e){alert('Error: '+e.message);}
}
function deleteBizCat(id){
    _stgPendingDelete=async function(){await ERP.api.deleteBusinessCategory(id);};
    document.getElementById('stgDeleteConfirm').classList.remove('d-none');
}
function cancelStgDelete(){
    _stgPendingDelete=null;
    document.getElementById('stgDeleteConfirm').classList.add('d-none');
}
async function doStgDelete(){
    document.getElementById('stgDeleteConfirm').classList.add('d-none');
    if(!_stgPendingDelete) return;
    try{
        await _stgPendingDelete();
        _stgPendingDelete=null;
        await ERP.sync(); renderPage();
        document.getElementById('stgDeleteSuccess').classList.remove('d-none');
    }catch(e){
        _stgPendingDelete=null;
        document.getElementById('stgDeleteErrorMsg').textContent=e.message||'An error occurred.';
        document.getElementById('stgDeleteError').classList.remove('d-none');
    }
}
var _stgPendingSeqType = null;
function saveSeq(type){
    var prefix = document.getElementById('seqPfx-'+type).value.trim();
    var nextNum = parseInt(document.getElementById('seqNum-'+type).value)||1;
    if(!prefix){ alert('Prefix cannot be empty.'); return; }
    _stgPendingSeqType = type;
    var label = (SEQUENCE_LABELS[type]||{}).label || type;
    document.getElementById('stgSeqConfirmMsg').textContent =
        'Save "' + label + '" with prefix "' + prefix + '" starting at ' + nextNum + '?';
    document.getElementById('stgSeqConfirm').classList.remove('d-none');
}
async function doSaveSeq(){
    document.getElementById('stgSeqConfirm').classList.add('d-none');
    var type = _stgPendingSeqType;
    if(!type) return;
    _stgPendingSeqType = null;
    var prefix  = document.getElementById('seqPfx-'+type).value.trim();
    var nextNum = parseInt(document.getElementById('seqNum-'+type).value)||1;
    try{
        await ERP.api.updateDocumentSequence(type, prefix, nextNum);
        await ERP.sync();
        renderPage();
        var label = (SEQUENCE_LABELS[type]||{}).label || type;
        document.getElementById('stgSeqSuccessMsg').textContent =
            '"' + label + '" sequence saved successfully.';
        document.getElementById('stgSeqSuccess').classList.remove('d-none');
    }catch(e){
        alert('Error: '+e.message);
    }
}

// ══════════════════════════════════════════════════════════════════
// Bulk Data Uploader
// ══════════════════════════════════════════════════════════════════
var bkState = { type:null, headers:[], rows:[], mapping:{}, validated:false };

var BK_FIELDS = {
  product: [
    {key:'name',         label:'Product Name',          required:true},
    {key:'sku',          label:'Item Number',            required:false},
    {key:'unitPrice',    label:'Unit Price (Sale)',       required:true},
    {key:'unitCost',     label:'Unit Cost',              required:false},
    {key:'initialStock', label:'Opening Stock',          required:false},
    {key:'reorderLevel', label:'Reorder Level',          required:false},
    {key:'type',         label:'Type (Product/Service)', required:false},
    {key:'category',     label:'Category',               required:false},
    {key:'uom',          label:'Unit of Measure',        required:false},
    {key:'barcode',      label:'Barcode',                required:false}
  ],
  customer: [
    {key:'name',           label:'Customer Name',    required:true},
    {key:'phone',          label:'Phone',            required:false},
    {key:'email',          label:'Email',            required:false},
    {key:'address',        label:'Address',          required:false},
    {key:'openingBalance', label:'Opening Balance',  required:false}
  ],
  vendor: [
    {key:'name',           label:'Vendor Name',      required:true},
    {key:'phone',          label:'Phone',            required:false},
    {key:'email',          label:'Email',            required:false},
    {key:'address',        label:'Address',          required:false},
    {key:'openingBalance', label:'Opening Balance',  required:false}
  ]
};

var BK_TYPE_LABELS = { product:'Product Master', customer:'Customer Master', vendor:'Vendor Master' };

function bkGoStep(n) {
  for (var i=1;i<=4;i++) {
    var s=document.getElementById('bk-s'+i);
    if(s) s.style.display = (i===n)?'block':'none';
    var d=document.getElementById('bkd'+i);
    if(d){ d.classList.remove('active','done');
      if(i<n) d.classList.add('done');
      else if(i===n) d.classList.add('active');
    }
    var l=document.getElementById('bkl'+i);
    if(l){ l.classList.toggle('done', i<n); }
  }
  if(n===2) {
    document.getElementById('bk-type-label').textContent = BK_TYPE_LABELS[bkState.type]||'';
    document.getElementById('bk-file-inp').value='';
  }
  if(n===1) { bkState.type=null; bkState.headers=[]; bkState.rows=[]; bkState.mapping={}; bkState.validated=false; }
}

function bkSelectType(type) {
  bkState.type = type;
  bkGoStep(2);
}

function bkHandleFile(file) {
  if (!file) return;
  var ext = file.name.split('.').pop().toLowerCase();
  if (ext === 'csv') {
    var reader = new FileReader();
    reader.onload = function(e) {
      var parsed = bkParseCSV(e.target.result);
      bkState.headers = parsed.headers;
      bkState.rows = parsed.rows.filter(function(r){ return r.some(function(c){return (c||'').toString().trim();}); });
      bkAutoMap();
      bkGoStep(3);
      bkRenderPreview();
      bkRenderMapping();
    };
    reader.readAsText(file);
  } else if (ext==='xlsx'||ext==='xls') {
    if (typeof XLSX === 'undefined') { alert('Excel library not loaded. Please refresh the page.'); return; }
    var reader = new FileReader();
    reader.onload = function(e) {
      var wb = XLSX.read(new Uint8Array(e.target.result), {type:'array'});
      var ws = wb.Sheets[wb.SheetNames[0]];
      var data = XLSX.utils.sheet_to_json(ws, {header:1, defval:''});
      bkState.headers = (data[0]||[]).map(function(h){return (h||'').toString();});
      bkState.rows = data.slice(1).filter(function(r){ return r.some(function(c){return (c||'').toString().trim();}); });
      bkAutoMap();
      bkGoStep(3);
      bkRenderPreview();
      bkRenderMapping();
    };
    reader.readAsArrayBuffer(file);
  } else {
    alert('Unsupported file type. Please use .csv, .xlsx or .xls');
  }
}

function bkParseCSV(text) {
  var lines = text.split(/\r?\n/);
  function parseLine(line) {
    var res=[],cur='',inQ=false;
    for(var i=0;i<line.length;i++){
      var c=line[i];
      if(c==='"'){if(inQ&&line[i+1]==='"'){cur+='"';i++;}else inQ=!inQ;}
      else if(c===','&&!inQ){res.push(cur.trim());cur='';}
      else cur+=c;
    }
    res.push(cur.trim()); return res;
  }
  var rows=lines.map(parseLine);
  return {headers:rows[0]||[], rows:rows.slice(1)};
}

function bkAutoMap() {
  bkState.mapping = {};
  var fields = BK_FIELDS[bkState.type]||[];
  fields.forEach(function(f){
    for(var i=0;i<bkState.headers.length;i++){
      var h=(bkState.headers[i]||'').toLowerCase().replace(/[\s_\-]/g,'');
      var fk=f.key.toLowerCase().replace(/[\s_\-]/g,'');
      var fl=f.label.toLowerCase().replace(/[\s_\-]/g,'');
      if(h===fk||h===fl||h.indexOf(fk)!==-1||fk.indexOf(h)!==-1){
        bkState.mapping[f.key]=i; break;
      }
    }
  });
}

function bkRenderPreview() {
  var h=bkState.headers, rows=bkState.rows.slice(0,3);
  var html='<thead class="erp-thead-light"><tr>'+h.map(function(c){return '<th class="bk-preview-th">'+bkEsc(c)+'</th>';}).join('')+'</tr></thead><tbody>';
  rows.forEach(function(r){
    html+='<tr>'+h.map(function(_,i){return '<td class="bk-preview-td">'+bkEsc((r[i]||'').toString())+'</td>';}).join('')+'</tr>';
  });
  html+='</tbody>';
  document.getElementById('bk-preview-table').innerHTML=html;
}

function bkRenderMapping() {
  var fields=BK_FIELDS[bkState.type]||[], headers=bkState.headers;
  var html='';
  fields.forEach(function(f){
    var mapped=bkState.mapping[f.key];
    var hasMapped = mapped!==undefined && mapped!==null && mapped!=='';
    var sel='<select class="form-select pm-input bk-col-select" onchange="bkState.mapping[\''+f.key+'\']=this.value===\'\'?undefined:parseInt(this.value);bkState.validated=false;document.getElementById(\'bk-import-btn\').style.display=\'none\';document.getElementById(\'bk-valid-badge\').style.display=\'none\';bkUpdateRowStatus(\''+f.key+'\',this.value);"><option value="">— Skip —</option>';
    headers.forEach(function(h,i){
      sel+='<option value="'+i+'"'+(mapped===i?' selected':'')+'>'+bkEsc(h)+'</option>';
    });
    sel+='</select>';
    var req=f.required?'<span class="bk-req-label">Required</span>':'<span class="bk-opt-label">Optional</span>';
    var statusIcon=hasMapped?'<i class="ti ti-circle-check bk-icon-ok"></i>':'<i class="ti ti-circle-dashed bk-icon-empty"></i>';
    html+='<tr id="bkrow-'+f.key+'"><td class="bk-map-label-td">'+f.label+'</td><td class="cr-td">'+req+'</td><td class="bk-map-select-td">'+sel+'</td><td class="bk-map-status-td" id="bkst-'+f.key+'">'+statusIcon+'</td></tr>';
  });
  document.getElementById('bk-map-body').innerHTML=html;
  bkState.validated=false;
}

function bkUpdateRowStatus(key, val) {
  var el=document.getElementById('bkst-'+key);
  if(!el) return;
  el.innerHTML=val!==''?'<i class="ti ti-circle-check bk-icon-ok"></i>':'<i class="ti ti-circle-dashed bk-icon-empty"></i>';
}

function bkValidate() {
  var fields=BK_FIELDS[bkState.type]||[];
  var errors=[];
  fields.forEach(function(f){
    if(f.required && (bkState.mapping[f.key]===undefined||bkState.mapping[f.key]==='')) {
      errors.push('"'+f.label+'" is required but not mapped.');
    }
  });
  if(bkState.rows.length===0){ errors.push('File has no data rows.'); }
  if(errors.length){
    alert('Please fix the following:\n\n'+errors.join('\n'));
    return;
  }
  bkState.validated=true;
  var badge=document.getElementById('bk-valid-badge');
  badge.style.display='flex';
  var btn=document.getElementById('bk-import-btn');
  btn.style.display='inline-flex';
  document.getElementById('bk-row-cnt').textContent=bkState.rows.length;
}

async function bkStartImport() {
  if(!bkState.validated){ bkValidate(); return; }
  bkGoStep(4);
  var rows=bkState.rows, total=rows.length;
  var ok=0, err=0, skip=0;
  var errHtml='';
  document.getElementById('bk-done-btns').style.display='none';

  function upd(i) {
    var pct=Math.round((i/total)*100);
    document.getElementById('bk-prog-bar').style.width=pct+'%';
    document.getElementById('bk-prog-pct').textContent=pct+'%';
    document.getElementById('bk-prog-lbl').textContent='Processing row '+i+' of '+total+'...';
    document.getElementById('bk-ok-n').textContent=ok;
    document.getElementById('bk-err-n').textContent=err;
    document.getElementById('bk-skip-n').textContent=skip;
  }

  function getVal(row, key) {
    var idx=bkState.mapping[key];
    if(idx===undefined||idx===null||idx==='') return '';
    return (row[parseInt(idx)]||'').toString().trim();
  }

  for(var i=0;i<rows.length;i++) {
    upd(i);
    var row=rows[i];
    try {
      if(bkState.type==='product') {
        var name=getVal(row,'name');
        if(!name){ skip++; continue; }
        var skuVal=getVal(row,'sku');
        var state=window.ERP.state;
        var catName=getVal(row,'category');
        var cat=(state.categories||[]).find(function(c){return c.name.toLowerCase()===catName.toLowerCase();});
        await ERP.api.createProduct({
          name: name,
          sku: skuVal||undefined,
          unitPrice: parseFloat(getVal(row,'unitPrice'))||0,
          unitCost: parseFloat(getVal(row,'unitCost'))||0,
          initialStock: parseFloat(getVal(row,'initialStock'))||0,
          reorderLevel: parseFloat(getVal(row,'reorderLevel'))||0,
          type: getVal(row,'type')||'Product',
          categoryId: cat?cat.id:undefined,
          uom: getVal(row,'uom')||undefined,
          barcode: getVal(row,'barcode')||undefined
        });
      } else {
        var name=getVal(row,'name');
        if(!name){ skip++; continue; }
        await ERP.api.addParty({
          name: name,
          type: bkState.type==='customer'?'Customer':'Vendor',
          phone: getVal(row,'phone')||undefined,
          email: getVal(row,'email')||undefined,
          address: getVal(row,'address')||undefined,
          openingBalance: parseFloat(getVal(row,'openingBalance'))||0
        });
      }
      ok++;
    } catch(e) {
      err++;
      errHtml+='<div class="bk-err-row">Row '+(i+2)+': '+(e.message||'Error')+'</div>';
      document.getElementById('bk-err-list').innerHTML=errHtml;
    }
    // Small yield to keep UI responsive
    if(i%5===0) await new Promise(function(r){setTimeout(r,0);});
  }

  upd(total);
  document.getElementById('bk-prog-lbl').textContent = ok+' imported, '+err+' failed, '+skip+' skipped.';
  document.getElementById('bk-done-btns').style.display='flex';
  await ERP.sync();
}

function bkDownloadTemplate() {
  var fields=BK_FIELDS[bkState.type]||[];
  var header=fields.map(function(f){return f.label;}).join(',');
  var sample='';
  if(bkState.type==='product') sample='\nSample Product,ITEM001,100,80,50,10,Product,General,PCS,1234567890';
  else if(bkState.type==='customer') sample='\nJohn Doe,03001234567,john@example.com,123 Main St,5000';
  else sample='\nABC Supplies,03007654321,abc@vendor.com,456 Trade Rd,0';
  var blob=new Blob([header+sample],{type:'text/csv'});
  var a=document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download='template_'+bkState.type+'.csv';
  a.click();
}

function bkEsc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Dynamic Fields ────────────────────────────────────────────────────────────

var _dynPending = {}; // { fieldKey: { entity, isEnabled } }

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function _hintClass(hint) {
    if (!hint) return '';
    var h = hint.toLowerCase();
    if (h.indexOf('grocery') !== -1 || h.indexOf('pharmacy') === 0) return 'hint-grocery';
    if (h === 'pharmacy') return 'hint-pharmacy';
    if (h === 'retail')   return 'hint-retail';
    if (h === 'automobile') return 'hint-automobile';
    return '';
}

function renderDynamicFieldSettings() {
    var fs          = (window.ERP.state.fieldSettings) || { enabledKeys: { product: [], customer: [] }, definitions: [] };
    var definitions = fs.definitions || [];
    var enabledProduct  = (fs.enabledKeys && fs.enabledKeys.product)  || [];
    var enabledCustomer = (fs.enabledKeys && fs.enabledKeys.customer) || [];

    ['product', 'customer'].forEach(function(entity) {
        var fields    = definitions.filter(function(f) { return f.entity === entity; });
        var enabled   = entity === 'product' ? enabledProduct : enabledCustomer;
        var container = document.getElementById('dynfields-' + entity);
        if (!container) return;

        var groups = {};
        fields.forEach(function(f) {
            var hint = f.industry_hint || 'general';
            if (!groups[hint]) groups[hint] = [];
            groups[hint].push(f);
        });

        var html = '';
        Object.keys(groups).forEach(function(hint) {
            var hc = _hintClass(hint);
            html += '<div class="dynf-group-label ' + hc + '">' + hint.charAt(0).toUpperCase() + hint.slice(1) + '</div>';
            groups[hint].forEach(function(f) {
                var savedOn  = enabled.indexOf(f.key) !== -1;
                var pending  = _dynPending[f.key];
                var isOn     = pending !== undefined ? pending.isEnabled : savedOn;
                var isPendingEnable  = pending !== undefined && pending.isEnabled  && !savedOn;
                var isPendingDisable = pending !== undefined && !pending.isEnabled && savedOn;
                var cardClass = isPendingEnable ? 'dynf-card pending-enable' :
                                isPendingDisable ? 'dynf-card pending-disable' : 'dynf-card';
                var toggleId = 'dynfield-toggle-' + f.key;
                html += '<div class="' + cardClass + '">' +
                    '<div class="dynf-card-left">' +
                        '<span class="dynf-card-name">' + escHtml(f.label) + '</span>' +
                        '<div class="dynf-card-badges">' +
                            '<span class="dynf-badge-type">' + escHtml(f.type) + '</span>' +
                            '<span class="dynf-badge-industry ' + hc + '">' + escHtml(f.industry_hint) + '</span>' +
                            (pending !== undefined ? '<span class="dynf-pending-dot"></span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<label class="dynf-toggle">' +
                        '<input type="checkbox" id="' + toggleId + '" ' +
                            'data-field-key="' + f.key + '" data-entity="' + f.entity + '" ' +
                            (isOn ? 'checked' : '') + ' onchange="queueDynamicFieldChange(this)">' +
                        '<div class="dynf-toggle-track"></div>' +
                        '<div class="dynf-toggle-thumb"></div>' +
                    '</label>' +
                '</div>';
            });
        });
        container.innerHTML = html || '<p class="text-muted">No fields available.</p>';
    });

    _updateSaveBtn();
}

function queueDynamicFieldChange(checkbox) {
    var fieldKey   = checkbox.dataset.fieldKey;
    var entityType = checkbox.dataset.entity;
    var isEnabled  = checkbox.checked;

    var fs         = (window.ERP.state.fieldSettings) || { enabledKeys: { product: [], customer: [] } };
    var enabledArr = entityType === 'product'
        ? ((fs.enabledKeys && fs.enabledKeys.product) || [])
        : ((fs.enabledKeys && fs.enabledKeys.customer) || []);
    var savedOn    = enabledArr.indexOf(fieldKey) !== -1;

    if (isEnabled === savedOn) {
        delete _dynPending[fieldKey]; // reverted to original
    } else {
        _dynPending[fieldKey] = { entity: entityType, isEnabled: isEnabled };
    }

    // Update card appearance immediately without full re-render
    var card = checkbox.closest('.dynf-card');
    if (card) {
        card.className = 'dynf-card';
        if (_dynPending[fieldKey] && isEnabled && !savedOn)  card.classList.add('pending-enable');
        if (_dynPending[fieldKey] && !isEnabled && savedOn)  card.classList.add('pending-disable');
        var dot = card.querySelector('.dynf-pending-dot');
        if (_dynPending[fieldKey] && !dot) {
            var badges = card.querySelector('.dynf-card-badges');
            if (badges) { var d = document.createElement('span'); d.className = 'dynf-pending-dot'; badges.appendChild(d); }
        } else if (!_dynPending[fieldKey] && dot) {
            dot.remove();
        }
    }

    _updateSaveBtn();
}

function _updateSaveBtn() {
    var count = Object.keys(_dynPending).length;
    var btn   = document.getElementById('dynSaveBtn');
    var badge = document.getElementById('dynSaveBadge');
    if (!btn) return;
    badge.textContent = count;
    if (count > 0) {
        btn.classList.remove('d-none');
    } else {
        btn.classList.add('d-none');
    }
}

function saveDynamicFields() {
    var count = Object.keys(_dynPending).length;
    if (count === 0) return;
    var msg = 'You are about to ' + count + ' field change' + (count > 1 ? 's' : '') + '. This will affect all users in your company.';
    document.getElementById('dynSaveConfirmMsg').textContent = msg;
    document.getElementById('dynSaveConfirm').classList.remove('d-none');
}

async function confirmSaveDynamicFields() {
    document.getElementById('dynSaveConfirm').classList.add('d-none');
    var btn = document.getElementById('dynSaveBtn');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

    var keys    = Object.keys(_dynPending);
    var errors  = [];
    var saved   = 0;

    for (var i = 0; i < keys.length; i++) {
        var key  = keys[i];
        var item = _dynPending[key];
        try {
            await ERP.api.updateFieldSetting(key, item.entity, item.isEnabled);
            saved++;
        } catch(e) {
            errors.push(e.message || key);
        }
    }

    _dynPending = {};
    await ERP.sync();
    renderDynamicFieldSettings();

    if (btn) { btn.disabled = false; btn.style.opacity = ''; }

    if (errors.length > 0) {
        document.getElementById('dynFieldDisableErrorMsg').textContent = errors.join('; ');
        document.getElementById('dynFieldDisableError').classList.remove('d-none');
    } else {
        var successMsg = saved + ' field setting' + (saved > 1 ? 's' : '') + ' saved successfully.';
        document.getElementById('dynSaveSuccessMsg').textContent = successMsg;
        document.getElementById('dynSaveSuccess').classList.remove('d-none');
        setTimeout(function() {
            document.getElementById('dynSaveSuccess').classList.add('d-none');
        }, 3000);
    }
}
