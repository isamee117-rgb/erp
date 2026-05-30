var _rbCurrentType = 'profit_loss';
var _rbDirty = false;
var _rbTomSelects = {};

// Which COA account types are relevant per report
var _rbAccountTypes = {
    profit_loss:   ['Revenue', 'Expense'],
    balance_sheet: ['Asset', 'Liability', 'Equity'],
};

// Section background colours per line key
var _rbSectionColors = {
    profit_loss: {
        sales_revenue:      '#eff6ff',
        cogs:               '#fef3c7',
        operating_expenses: '#fef2f2',
    },
    balance_sheet: {
        current_assets:        '#eff6ff',
        fixed_assets:          '#eff6ff',
        other_assets:          '#eff6ff',
        current_liabilities:   '#fef3c7',
        long_term_liabilities: '#fef3c7',
        owners_equity:         '#f5f3ff',
    },
};

window.ERP.onReady = function() {
    loadReportBuilder('profit_loss');
};

function rbSwitchTab(type) {
    if (type === _rbCurrentType) return;
    _rbCurrentType = type;

    document.getElementById('rb-tab-pl').classList.toggle('active', type === 'profit_loss');
    document.getElementById('rb-tab-bs').classList.toggle('active', type === 'balance_sheet');

    _rbDirty = false;
    document.getElementById('rb-save-btn').disabled = true;
    loadReportBuilder(type);
}

function loadReportBuilder(type) {
    document.getElementById('rb-loading').classList.remove('d-none');
    document.getElementById('rb-content').classList.add('d-none');

    ERP.api.getReportBuilder(type)
        .then(function(data) {
            document.getElementById('rb-loading').classList.add('d-none');
            rbRender(data, type);
            document.getElementById('rb-content').classList.remove('d-none');
        })
        .catch(function(e) {
            document.getElementById('rb-loading').classList.add('d-none');
            alert('Error loading report config: ' + e.message);
        });
}

function rbRender(data, type) {
    // Destroy existing Tom Select instances before re-rendering
    Object.values(_rbTomSelects).forEach(function(ts) { ts.destroy(); });
    _rbTomSelects = {};

    var state = window.ERP.state;
    var relevantTypes = _rbAccountTypes[type] || [];
    var coaAccounts = (state.chartOfAccounts || []).filter(function(a) {
        return relevantTypes.indexOf(a.type) !== -1;
    });

    var sectionColors = _rbSectionColors[type] || {};

    var html = '';
    (data.lines || []).forEach(function(line) {
        var bg = sectionColors[line.lineKey] || '#fff';
        html += '<tr style="background:' + bg + '">';
        html += '<td class="fw-semibold">' + line.label + '</td>';
        html += '<td><select id="rb-select-' + line.lineKey + '" multiple placeholder="Select accounts…"></select></td>';
        html += '</tr>';
    });
    document.getElementById('rb-tbody').innerHTML = html;

    // Initialise Tom Select for each row
    (data.lines || []).forEach(function(line) {
        var el = document.getElementById('rb-select-' + line.lineKey);
        if (!el) return;

        var options = coaAccounts.map(function(a) {
            return { value: a.id, text: a.code + ' - ' + a.name };
        });
        var items = (line.accounts || []).map(function(a) { return a.id; });

        var ts = new TomSelect(el, {
            options:     options,
            items:       items,
            maxItems:    null,
            valueField:  'value',
            labelField:  'text',
            searchField: ['text'],
            plugins:     ['remove_button'],
            onChange: function() {
                _rbDirty = true;
                document.getElementById('rb-save-btn').disabled = false;
            },
        });
        _rbTomSelects[line.lineKey] = ts;
    });
}

function rbSave() {
    var mappings = {};
    Object.keys(_rbTomSelects).forEach(function(lineKey) {
        mappings[lineKey] = _rbTomSelects[lineKey].getValue();
    });

    document.getElementById('rb-save-btn').disabled = true;

    ERP.api.updateReportBuilder(_rbCurrentType, mappings)
        .then(function(data) {
            _rbDirty = false;
            rbRender(data, _rbCurrentType);
            rbShowUnmappedWarning(data.unmappedAccounts || []);

            var toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 m-3 alert alert-success shadow';
            toast.style.zIndex = '9999';
            toast.innerHTML = '<i class="ti ti-check me-1"></i> Mapping saved successfully.';
            document.body.appendChild(toast);
            setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3000);
        })
        .catch(function(e) {
            document.getElementById('rb-save-btn').disabled = false;
            alert('Error saving mapping: ' + e.message);
        });
}

function rbReset() {
    if (!confirm('Clear all mappings for this report? This cannot be undone.')) return;

    var emptyMappings = {};
    Object.keys(_rbTomSelects).forEach(function(lineKey) {
        emptyMappings[lineKey] = [];
    });

    ERP.api.updateReportBuilder(_rbCurrentType, emptyMappings)
        .then(function(data) {
            _rbDirty = false;
            document.getElementById('rb-save-btn').disabled = true;
            rbRender(data, _rbCurrentType);
            rbShowUnmappedWarning([]);
        })
        .catch(function(e) { alert('Error: ' + e.message); });
}

function rbShowUnmappedWarning(unmapped) {
    var el = document.getElementById('rb-unmapped-warning');
    if (!unmapped || !unmapped.length) {
        el.classList.add('d-none');
        return;
    }
    document.getElementById('rb-unmapped-list').textContent =
        unmapped.map(function(a) { return a.code + ' - ' + a.name; }).join(', ');
    el.classList.remove('d-none');
}
