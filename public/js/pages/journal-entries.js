var _jeMeta = { currentPage: 1, lastPage: 1, total: 0 };
var _jeDeleteId = null;
var _jeCurrentId = null;

window.ERP.onReady = function() {
    setDefaultDates();
    loadJournals();
};

function setDefaultDates() {
    var now = new Date();
    var y = now.getFullYear(), m = now.getMonth();
    var from = new Date(y, m, 1);
    var to   = new Date(y, m + 1, 0);
    document.getElementById('jeFrom').value = formatDate(from);
    document.getElementById('jeTo').value   = formatDate(to);
}

function formatDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

async function loadJournals(page) {
    page = page || 1;
    var params = { page: page };
    var from   = document.getElementById('jeFrom').value;
    var to     = document.getElementById('jeTo').value;
    var type   = document.getElementById('jeType').value;
    var status = document.getElementById('jeStatus').value;
    if (from)   params.from   = from;
    if (to)     params.to     = to;
    if (type)   params.type   = type;
    if (status) params.status = status;

    try {
        var res = await ERP.api.getJournals(params);
        _jeMeta = { currentPage: res.current_page, lastPage: res.last_page, total: res.total };
        renderJournals(res.data || []);
        renderPagination();
    } catch(e) {
        document.getElementById('jeBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">' + e.message + '</td></tr>';
    }
}

function renderJournals(journals) {
    var html = '';
    journals.forEach(function(je) {
        var statusBadge = je.isPosted
            ? '<span class="badge-pill badge-green">Posted</span>'
            : '<span class="badge-pill badge-orange">Draft</span>';
        var typeBadge = '<span class="badge-pill badge-blue">' + (je.referenceType || 'manual') + '</span>';
        var delBtn = (!je.isPosted)
            ? '<button class="btn-icon-sm danger ms-1" onclick="confirmJeDelete(\'' + je.id + '\')" title="Delete"><i class="ti ti-trash"></i></button>'
            : '';
        html += '<tr>' +
            '<td><a href="javascript:void(0)" onclick="viewJe(\'' + je.id + '\')" class="fw-bold text-primary" style="text-decoration:none;">' + (je.entryNo || je.id) + '</a></td>' +
            '<td>' + (je.date || '—') + '</td>' +
            '<td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + (je.description || '—') + '</td>' +
            '<td>' + typeBadge + '</td>' +
            '<td class="text-end fw-bold">' + ERP.formatCurrency(je.totalDebit || 0) + '</td>' +
            '<td class="text-end fw-bold">' + ERP.formatCurrency(je.totalCredit || 0) + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td>' +
                '<button class="btn-icon-sm" onclick="viewJe(\'' + je.id + '\')" title="View"><i class="ti ti-eye"></i></button>' +
                delBtn +
            '</td>' +
            '</tr>';
    });
    if (!html) html = '<tr><td colspan="8" class="text-center text-muted py-4">No journal entries found for selected filters.</td></tr>';
    document.getElementById('jeBody').innerHTML = html;
}

function renderPagination() {
    var html = '';
    html += '<span class="text-muted">Total: ' + _jeMeta.total + '</span>';
    if (_jeMeta.lastPage > 1) {
        html += '<div class="d-flex gap-1 ms-3">';
        for (var i = 1; i <= _jeMeta.lastPage; i++) {
            var active = i === _jeMeta.currentPage ? 'btn-primary' : 'btn-outline-secondary';
            html += '<button class="btn btn-sm ' + active + '" onclick="loadJournals(' + i + ')">' + i + '</button>';
        }
        html += '</div>';
    }
    document.getElementById('jePagination').innerHTML = html;
}

async function viewJe(id) {
    _jeCurrentId = id;
    document.getElementById('jeViewBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Loading...</div>';
    document.getElementById('jePostBtn').style.display = 'none';
    new bootstrap.Modal(document.getElementById('jeViewModal')).show();
    try {
        var je = await ERP.api.getJournal(id);
        renderJeView(je);
        if (!je.isPosted) {
            document.getElementById('jePostBtn').style.display = '';
        }
    } catch(e) {
        document.getElementById('jeViewBody').innerHTML = '<div class="text-danger">' + e.message + '</div>';
    }
}

function renderJeView(je) {
    var lines = je.lines || [];
    var linesHtml = '';
    lines.forEach(function(l) {
        linesHtml += '<tr>' +
            '<td><code style="color:#3B4FE4;">' + (l.accountCode || '') + '</code></td>' +
            '<td>' + (l.accountName || '') + '</td>' +
            '<td style="font-size:0.78rem;color:#64748b;">' + (l.description || '') + '</td>' +
            '<td class="text-end">' + (l.debit > 0 ? ERP.formatCurrency(l.debit) : '') + '</td>' +
            '<td class="text-end">' + (l.credit > 0 ? ERP.formatCurrency(l.credit) : '') + '</td>' +
            '</tr>';
    });
    var statusBadge = je.isPosted
        ? '<span class="badge-pill badge-green">Posted</span>'
        : '<span class="badge-pill badge-orange">Draft</span>';
    document.getElementById('jeViewModalLabel').textContent = je.entryNo || je.id;
    document.getElementById('jeViewBody').innerHTML =
        '<div class="d-flex gap-4 mb-3 flex-wrap" style="font-size:0.82rem;">' +
            '<div><span class="text-muted">Date:</span> <strong>' + (je.date || '—') + '</strong></div>' +
            '<div><span class="text-muted">Type:</span> <strong>' + (je.referenceType || 'manual') + '</strong></div>' +
            '<div><span class="text-muted">Status:</span> ' + statusBadge + '</div>' +
            '<div><span class="text-muted">Ref:</span> <strong>' + (je.referenceId || '—') + '</strong></div>' +
        '</div>' +
        (je.description ? '<p style="font-size:0.85rem;color:#64748b;margin-bottom:12px;">' + je.description + '</p>' : '') +
        '<table class="je-lines-table">' +
            '<thead><tr><th>Code</th><th>Account</th><th>Narration</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>' +
            '<tbody>' + linesHtml + '</tbody>' +
            '<tfoot><tr>' +
                '<td colspan="3" class="text-end">Total</td>' +
                '<td class="text-end">' + ERP.formatCurrency(je.totalDebit || 0) + '</td>' +
                '<td class="text-end">' + ERP.formatCurrency(je.totalCredit || 0) + '</td>' +
            '</tr></tfoot>' +
        '</table>';
}

async function postCurrentJe() {
    if (!_jeCurrentId) return;
    try {
        await ERP.api.postJournal(_jeCurrentId);
        bootstrap.Modal.getInstance(document.getElementById('jeViewModal')).hide();
        loadJournals(_jeMeta.currentPage);
    } catch(e) {
        alert('Error: ' + e.message);
    }
}

function confirmJeDelete(id) {
    _jeDeleteId = id;
    document.getElementById('jeDeleteConfirm').classList.remove('d-none');
}

function cancelJeDelete() {
    _jeDeleteId = null;
    document.getElementById('jeDeleteConfirm').classList.add('d-none');
}

async function doJeDelete() {
    document.getElementById('jeDeleteConfirm').classList.add('d-none');
    if (!_jeDeleteId) return;
    try {
        await ERP.api.deleteJournal(_jeDeleteId);
        _jeDeleteId = null;
        loadJournals(_jeMeta.currentPage);
    } catch(e) {
        _jeDeleteId = null;
        alert('Error: ' + e.message);
    }
}