var MAPPING_DEFS = [
    { section: 'Sales' },
    { key: 'sales_revenue',       label: 'Sales Revenue',          desc: 'CR — credited when a sale invoice is posted' },
    { key: 'accounts_receivable', label: 'Accounts Receivable',    desc: 'DR — debited when customer buys on credit' },
    { key: 'cash_account',        label: 'Cash / Bank',            desc: 'DR — debited when cash payment received' },
    { section: 'Cost of Goods Sold' },
    { key: 'cost_of_goods_sold',  label: 'Cost of Goods Sold',     desc: 'DR — debited with COGS on every sale' },
    { key: 'inventory_asset',     label: 'Inventory Asset',        desc: 'CR — credited when inventory is sold or returned to vendor' },
    { section: 'Purchases' },
    { key: 'accounts_payable',    label: 'Accounts Payable',       desc: 'CR — credited when goods received from vendor' },
    { section: 'Payments' },
    { key: 'discount_allowed',    label: 'Discount Allowed',       desc: 'DR — debited when discount given to customer' },
    { key: 'discount_received',   label: 'Discount Received',      desc: 'CR — credited when discount received from vendor' },
];

window.ERP.onReady = function() { renderMappings(); };

function renderMappings() {
    var accounts  = window.ERP.state.chartOfAccounts || [];
    var mappings  = window.ERP.state.accountMappings  || {};
    var activeAcc = accounts.filter(function(a) { return a.isActive; });

    var optionsHtml = '<option value="">— Not mapped —</option>';
    activeAcc.forEach(function(a) {
        optionsHtml += '<option value="' + a.id + '">' + a.code + ' — ' + a.name + '</option>';
    });

    var html = '';
    MAPPING_DEFS.forEach(function(def) {
        if (def.section) {
            html += '<tr class="map-section-row"><td colspan="3">' + def.section + '</td></tr>';
            return;
        }
        var mapped = mappings[def.key];
        var selectedId = mapped ? mapped.accountId : '';
        html += '<tr>' +
            '<td><span class="map-key">' + def.key + '</span></td>' +
            '<td><span class="map-desc">' + def.desc + '</span></td>' +
            '<td>' +
                '<select class="form-select pm-input" id="map_' + def.key + '">' +
                optionsHtml +
                '</select>' +
            '</td>' +
            '</tr>';
        // Set selected value after render via script tag trick — done below
        if (selectedId) {
            // We'll set it programmatically after innerHTML is set
        }
    });

    document.getElementById('mapBody').innerHTML = html;

    // Now set selected values
    MAPPING_DEFS.forEach(function(def) {
        if (def.section) return;
        var mapped = mappings[def.key];
        if (mapped && mapped.accountId) {
            var sel = document.getElementById('map_' + def.key);
            if (sel) sel.value = mapped.accountId;
        }
    });
}

async function saveMappings() {
    var mappings = [];
    MAPPING_DEFS.forEach(function(def) {
        if (def.section) return;
        var sel = document.getElementById('map_' + def.key);
        if (sel && sel.value) {
            mappings.push({ mappingKey: def.key, accountId: sel.value });
        }
    });
    try {
        await ERP.api.saveMappings(mappings);
        var core = await ERP.api.syncCore();
        ERP.state.chartOfAccounts = core.chartOfAccounts || [];
        ERP.state.accountMappings = core.accountMappings || {};
        document.getElementById('mapSaveSuccess').classList.remove('d-none');
    } catch(e) {
        alert('Error: ' + e.message);
    }
}