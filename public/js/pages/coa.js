var COA_SUB_TYPES = {
    Asset:     ['current_asset', 'fixed_asset', 'other_asset'],
    Liability: ['current_liability', 'long_term_liability', 'other_liability'],
    Equity:    ['owner_equity', 'retained_earnings', 'other_equity'],
    Revenue:   ['operating_revenue', 'other_revenue'],
    Expense:   ['cost_of_goods_sold', 'operating_expense', 'other_expense']
};

var TYPE_BADGES = {
    Asset:     'badge-blue',
    Liability: 'badge-orange',
    Equity:    'badge-purple',
    Revenue:   'badge-green',
    Expense:   'badge-red'
};

var _coaDeleteId   = null;
var _coaSortDir    = 'asc'; // 'asc' | 'desc'

window.ERP.onReady = function() { renderCoa(); };

function renderCoa() {
    var accounts = (window.ERP.state.chartOfAccounts || []).slice();
    var filterType = document.getElementById('coaFilterType').value;
    var search = (document.getElementById('coaSearch').value || '').toLowerCase();

    if (filterType) {
        accounts = accounts.filter(function(a) { return a.type === filterType; });
    }
    if (search) {
        accounts = accounts.filter(function(a) {
            return a.code.toLowerCase().indexOf(search) !== -1 || a.name.toLowerCase().indexOf(search) !== -1;
        });
    }

    // Sort by code
    accounts.sort(function(a, b) {
        return _coaSortDir === 'asc'
            ? a.code.localeCompare(b.code, undefined, { numeric: true })
            : b.code.localeCompare(a.code, undefined, { numeric: true });
    });

    var html = '';
    accounts.forEach(function(a) {
        var typeBadge = TYPE_BADGES[a.type] || 'badge-gray';
        var statusBadge = a.isActive
            ? '<span class="badge-pill badge-green">Active</span>'
            : '<span class="badge-pill badge-gray">Inactive</span>';
        var systemBadge = a.isSystem ? '<span class="badge-pill badge-gray ms-1">System</span>' : '';
        var editBtn = a.isSystem ? '' : '<button class="btn-icon-sm me-1" onclick="openEditAccount(\'' + a.id + '\')" title="Edit"><i class="ti ti-pencil"></i></button>';
        var delBtn  = a.isSystem ? '' : '<button class="btn-icon-sm danger" onclick="confirmCoaDelete(\'' + a.id + '\')" title="Delete"><i class="ti ti-trash"></i></button>';
        var bal = a.balance || 0;
        var balColor = bal > 0 ? '#059669' : bal < 0 ? '#dc2626' : '#94a3b8';
        var balLabel = bal > 0 ? ' <small style="color:#94a3b8;">Dr</small>' : bal < 0 ? ' <small style="color:#94a3b8;">Cr</small>' : '';
        var balCell = '<span style="font-weight:600;color:' + balColor + ';">' + ERP.formatCurrency(Math.abs(bal)) + '</span>' + balLabel;
        html += '<tr>' +
            '<td><code style="font-size:0.82rem;color:#3B4FE4;">' + a.code + '</code></td>' +
            '<td>' + a.name + systemBadge + '</td>' +
            '<td><span class="badge-pill ' + typeBadge + '">' + a.type + '</span></td>' +
            '<td><span class="text-muted" style="font-size:0.82rem;">' + (a.subType || '—').replace(/_/g, ' ') + '</span></td>' +
            '<td class="text-end">' + balCell + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' + editBtn + delBtn + '</td>' +
            '</tr>';
    });
    if (!html) html = '<tr><td colspan="7" class="text-center text-muted py-4">No accounts found.</td></tr>';
    document.getElementById('coaBody').innerHTML = html;
}

function toggleCodeSort() {
    _coaSortDir = _coaSortDir === 'asc' ? 'desc' : 'asc';
    document.getElementById('coaSortIcon').textContent = _coaSortDir === 'asc' ? '↑' : '↓';
    renderCoa();
}

function updateSubTypes() {
    var type = document.getElementById('coaType').value;
    var subs = COA_SUB_TYPES[type] || [];
    var html = subs.map(function(s) {
        return '<option value="' + s + '">' + s.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); }) + '</option>';
    }).join('');
    document.getElementById('coaSubType').innerHTML = html;
}

function openAddAccount() {
    document.getElementById('coaId').value = '';
    document.getElementById('coaCode').value = '';
    document.getElementById('coaName').value = '';
    document.getElementById('coaType').value = 'Asset';
    document.getElementById('coaModalLabel').innerHTML = '<i class="ti ti-list-numbers me-2"></i>Add Account';
    updateSubTypes();
    new bootstrap.Modal(document.getElementById('coaModal')).show();
}

function openEditAccount(id) {
    var accounts = window.ERP.state.chartOfAccounts || [];
    var a = accounts.find(function(x) { return x.id === id; });
    if (!a) return;
    document.getElementById('coaId').value = a.id;
    document.getElementById('coaCode').value = a.code;
    document.getElementById('coaName').value = a.name;
    document.getElementById('coaType').value = a.type;
    document.getElementById('coaModalLabel').innerHTML = '<i class="ti ti-pencil me-2"></i>Edit Account';
    updateSubTypes();
    document.getElementById('coaSubType').value = a.subType || '';
    new bootstrap.Modal(document.getElementById('coaModal')).show();
}

// ── Save flow: modal → confirm overlay → doSave → success overlay ─────────

function confirmSave() {
    var code = document.getElementById('coaCode').value.trim();
    var name = document.getElementById('coaName').value.trim();
    if (!code || !name) { alert('Code and Name are required.'); return; }

    var isEdit = !!document.getElementById('coaId').value;
    document.getElementById('coaSaveConfirmTitle').textContent = isEdit ? 'Update Account?' : 'Save Account?';
    document.getElementById('coaSaveConfirmSub').textContent  = isEdit
        ? 'Save changes to "' + name + '"?'
        : 'Add "' + name + '" to Chart of Accounts?';

    // Hide modal first, then show confirm
    var modal = bootstrap.Modal.getInstance(document.getElementById('coaModal'));
    if (modal) modal.hide();
    document.getElementById('coaSaveConfirm').classList.remove('d-none');
}

function cancelCoaSave() {
    document.getElementById('coaSaveConfirm').classList.add('d-none');
    // Re-open modal so user can continue editing
    new bootstrap.Modal(document.getElementById('coaModal')).show();
}

async function doSaveAccount() {
    document.getElementById('coaSaveConfirm').classList.add('d-none');
    var id   = document.getElementById('coaId').value;
    var data = {
        code:    document.getElementById('coaCode').value.trim(),
        name:    document.getElementById('coaName').value.trim(),
        type:    document.getElementById('coaType').value,
        subType: document.getElementById('coaSubType').value
    };
    try {
        if (id) {
            await ERP.api.updateAccount(id, data);
        } else {
            await ERP.api.createAccount(data);
        }
        var core = await ERP.api.syncCore();
        ERP.state.chartOfAccounts = core.chartOfAccounts || [];
        ERP.state.accountMappings = core.accountMappings || {};
        renderCoa();
        document.getElementById('coaSuccessMsg').textContent = id
            ? 'Account "' + data.name + '" updated successfully.'
            : 'Account "' + data.name + '" added successfully.';
        document.getElementById('coaSuccessOverlay').classList.remove('d-none');
    } catch(e) {
        document.getElementById('coaErrorMsg').textContent = e.message || 'Failed to save account.';
        document.getElementById('coaErrorOverlay').classList.remove('d-none');
    }
}

// ── Delete flow ───────────────────────────────────────────────────────────

function confirmCoaDelete(id) {
    _coaDeleteId = id;
    document.getElementById('coaDeleteConfirm').classList.remove('d-none');
}

function cancelCoaDelete() {
    _coaDeleteId = null;
    document.getElementById('coaDeleteConfirm').classList.add('d-none');
}

async function doCoaDelete() {
    document.getElementById('coaDeleteConfirm').classList.add('d-none');
    if (!_coaDeleteId) return;
    var id = _coaDeleteId;
    _coaDeleteId = null;
    try {
        await ERP.api.deleteAccount(id);
        var core = await ERP.api.syncCore();
        ERP.state.chartOfAccounts = core.chartOfAccounts || [];
        ERP.state.accountMappings = core.accountMappings || {};
        renderCoa();
        document.getElementById('coaSuccessMsg').textContent = 'Account deleted successfully.';
        document.getElementById('coaSuccessOverlay').classList.remove('d-none');
    } catch(e) {
        document.getElementById('coaErrorMsg').textContent = e.message || 'Cannot delete this account.';
        document.getElementById('coaErrorOverlay').classList.remove('d-none');
    }
}