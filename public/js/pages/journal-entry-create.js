var _jeLines = [];
var _jeLineId = 0;

window.ERP.onReady = function() {
    var today = new Date();
    document.getElementById('jeDate').value =
        today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');
    addLine();
    addLine();
};

function buildAccountOptions() {
    var accounts = (window.ERP.state.chartOfAccounts || []).filter(function(a) { return a.isActive; });
    var html = '<option value="">— Select Account —</option>';
    var grouped = {};
    accounts.forEach(function(a) {
        if (!grouped[a.type]) grouped[a.type] = [];
        grouped[a.type].push(a);
    });
    Object.keys(grouped).forEach(function(type) {
        html += '<optgroup label="' + type.toUpperCase() + '">';
        grouped[type].forEach(function(a) {
            html += '<option value="' + a.id + '">' + a.code + ' — ' + a.name + '</option>';
        });
        html += '</optgroup>';
    });
    return html;
}

function addLine() {
    var id = ++_jeLineId;
    _jeLines.push({ id: id });
    renderLines();
}

function removeLine(id) {
    _jeLines = _jeLines.filter(function(l) { return l.id !== id; });
    renderLines();
}

function renderLines() {
    var accOptions = buildAccountOptions();
    var html = '';
    _jeLines.forEach(function(line) {
        html += '<tr id="jeline_' + line.id + '">' +
            '<td>' +
                '<select class="form-select pm-input" id="acc_' + line.id + '" style="height:36px!important;">' +
                accOptions +
                '</select>' +
            '</td>' +
            '<td><input type="text" class="form-control pm-input" id="desc_' + line.id + '" placeholder="Narration..." style="height:36px!important;"></td>' +
            '<td><input type="number" class="form-control pm-input text-end" id="dr_' + line.id + '" placeholder="0.00" min="0" step="0.01" oninput="recalc()" style="height:36px!important;"></td>' +
            '<td><input type="number" class="form-control pm-input text-end" id="cr_' + line.id + '" placeholder="0.00" min="0" step="0.01" oninput="recalc()" style="height:36px!important;"></td>' +
            '<td><button class="btn-icon-sm danger" onclick="removeLine(' + line.id + ')" title="Remove"><i class="ti ti-x"></i></button></td>' +
            '</tr>';
    });
    if (!html) html = '<tr><td colspan="5" class="text-center text-muted py-3" style="font-size:0.82rem;">No lines. Click "Add Line".</td></tr>';
    document.getElementById('jeLinesBody').innerHTML = html;
    recalc();
}

function recalc() {
    var totalDr = 0, totalCr = 0;
    _jeLines.forEach(function(line) {
        var dr = parseFloat(document.getElementById('dr_' + line.id) ? document.getElementById('dr_' + line.id).value : 0) || 0;
        var cr = parseFloat(document.getElementById('cr_' + line.id) ? document.getElementById('cr_' + line.id).value : 0) || 0;
        totalDr += dr;
        totalCr += cr;
    });
    document.getElementById('jeTotalDebit').textContent  = totalDr.toFixed(2);
    document.getElementById('jeTotalCredit').textContent = totalCr.toFixed(2);

    var diff = Math.abs(totalDr - totalCr);
    var balDiv = document.getElementById('jeBalanceCheck');
    if (totalDr === 0 && totalCr === 0) {
        balDiv.innerHTML = '';
    } else if (diff < 0.01) {
        balDiv.innerHTML = '<span class="balance-ok"><i class="ti ti-circle-check me-1"></i>Balanced — Debits equal Credits (' + totalDr.toFixed(2) + ')</span>';
    } else {
        balDiv.innerHTML = '<span class="balance-err"><i class="ti ti-alert-triangle me-1"></i>Out of balance by ' + diff.toFixed(2) + ' — Debit: ' + totalDr.toFixed(2) + ' | Credit: ' + totalCr.toFixed(2) + '</span>';
    }
}

function collectLines() {
    return _jeLines.map(function(line) {
        var accSel = document.getElementById('acc_' + line.id);
        var descEl = document.getElementById('desc_' + line.id);
        var drEl   = document.getElementById('dr_' + line.id);
        var crEl   = document.getElementById('cr_' + line.id);
        return {
            accountId:   accSel ? accSel.value : '',
            description: descEl ? descEl.value.trim() : '',
            debit:       parseFloat(drEl && drEl.value ? drEl.value : 0) || 0,
            credit:      parseFloat(crEl && crEl.value ? crEl.value : 0) || 0
        };
    }).filter(function(l) { return l.accountId; });
}

async function submitJe(postImmediately) {
    var date  = document.getElementById('jeDate').value;
    var desc  = document.getElementById('jeDesc').value.trim();
    var lines = collectLines();

    if (!date) { alert('Please select a date.'); return; }
    if (lines.length < 2) { alert('A journal entry requires at least 2 lines.'); return; }

    var totalDr = lines.reduce(function(s, l) { return s + l.debit; }, 0);
    var totalCr = lines.reduce(function(s, l) { return s + l.credit; }, 0);
    if (Math.abs(totalDr - totalCr) >= 0.01) {
        alert('Journal is not balanced. Debit total must equal Credit total.');
        return;
    }

    var data = { date: date, description: desc, lines: lines, postImmediately: postImmediately };

    try {
        await ERP.api.createJournal(data);
        var base = document.querySelector('meta[name="base-url"]').getAttribute('content');
        window.location.href = base + '/accounting/journals';
    } catch(e) {
        alert('Error: ' + e.message);
    }
}