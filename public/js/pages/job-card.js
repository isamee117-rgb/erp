'use strict';

var jcTabs     = [];   // array of job card objects (open cards)
var jcActiveId = null; // currently visible tab id

window.ERP.onReady = function() { jcInit(); };

// ── Init ─────────────────────────────────────────────────────────────────────

function jcInit() {
    jcTabs = (window.ERP.state.jobCards || []).slice();

    if (jcTabs.length > 0) {
        jcActiveId = jcTabs[0].id;
    }

    jcRenderTabBar();
    jcRenderPanel();
    jcBindStaticEvents();
}

function jcBindStaticEvents() {
    document.getElementById('jc-new-tab-btn').addEventListener('click', jcNewTab);
}

// ── Tabs ──────────────────────────────────────────────────────────────────────

function jcNewTab() {
    ERP.api.createJobCard({}).then(function(card) {
        jcTabs.push(card);
        jcActiveId = card.id;
        jcRenderTabBar();
        jcRenderPanel();
    }).catch(function(e) { alert('Error: ' + e.message); });
}

function jcSwitchTab(id) {
    jcActiveId = id;
    jcRenderTabBar();
    jcRenderPanel();
}

function jcCloseTab(id) {
    jcTabs = jcTabs.filter(function(c) { return c.id !== id; });
    if (jcActiveId === id) {
        jcActiveId = jcTabs.length > 0 ? jcTabs[jcTabs.length - 1].id : null;
    }
    jcRenderTabBar();
    jcRenderPanel();
}

function jcGetActive() {
    for (var i = 0; i < jcTabs.length; i++) {
        if (jcTabs[i].id === jcActiveId) return jcTabs[i];
    }
    return null;
}

function jcUpdateTabState(updated) {
    for (var i = 0; i < jcTabs.length; i++) {
        if (jcTabs[i].id === updated.id) {
            jcTabs[i] = updated;
            return;
        }
    }
}

// ── Render Tab Bar ────────────────────────────────────────────────────────────

function jcRenderTabBar() {
    var bar    = document.getElementById('jc-tab-bar');
    var newBtn = document.getElementById('jc-new-tab-btn');
    bar.innerHTML = '';
    bar.appendChild(newBtn);

    jcTabs.forEach(function(card) {
        var label = card.jobCardNo || 'New';
        if (card.vehicleRegNumber) label += ' \u00b7 ' + card.vehicleRegNumber;

        var tab = document.createElement('div');
        tab.className = 'jc-tab' + (card.id === jcActiveId ? ' active' : '');
        tab.dataset.id = card.id;
        tab.innerHTML = escHtml(label) +
            '<span class="jc-tab-close" data-id="' + escHtml(card.id) + '">&times;</span>';

        tab.addEventListener('click', function(e) {
            if (e.target.classList.contains('jc-tab-close')) return;
            jcSwitchTab(card.id);
        });

        tab.querySelector('.jc-tab-close').addEventListener('click', function(e) {
            e.stopPropagation();
            jcCloseTab(card.id);
        });

        bar.insertBefore(tab, newBtn);
    });
}

// ── Render Active Panel ───────────────────────────────────────────────────────

function jcRenderPanel() {
    var wrap = document.getElementById('jc-panel-wrap');
    var card = jcGetActive();

    if (!card) {
        wrap.innerHTML = '<div class="jc-empty-state"><i class="ti ti-clipboard-list"></i>' +
            '<p>No open job cards. Click <strong>+ New Job Card</strong> to begin.</p></div>';
        return;
    }

    wrap.innerHTML = jcBuildPanelHtml(card);
    jcBindPanelEvents(card);
}

function jcBuildPanelHtml(card) {
    var parts    = (card.items || []).filter(function(i) { return i.itemType === 'part'; });
    var services = (card.items || []).filter(function(i) { return i.itemType === 'service'; });

    return '<div class="jc-panel" id="jc-panel-' + escHtml(card.id) + '">' +

        // LEFT
        '<div class="jc-left">' +

            // Header Fields
            '<div class="jc-header-card">' +
                '<div class="jc-section-title">Vehicle &amp; Customer</div>' +
                '<div class="row g-2">' +
                    jcField('Customer Search', 'jc-customer-search', 'text', '', 'col-md-6', 'Search by reg no / phone / name...') +
                    jcField('Customer Name', 'jc-customer-name', 'text', card.customerName || '') +
                    jcField('Phone', 'jc-phone', 'text', card.phone || '') +
                    jcField('Vehicle Reg No', 'jc-vehicle-reg', 'text', card.vehicleRegNumber || '') +
                    jcField('VIN / Chassis No', 'jc-vin', 'text', card.vinChassisNumber || '') +
                    jcField('Engine No', 'jc-engine', 'text', card.engineNumber || '') +
                    jcField('Make / Model / Year', 'jc-make-model', 'text', card.makeModelYear || '') +
                    jcField('Lift No', 'jc-lift', 'text', card.liftNumber || '') +
                    jcField('Current Odometer', 'jc-odometer', 'number', card.currentOdometer || '') +
                '</div>' +
                '<div id="jc-customer-suggestions" class="list-group mt-1"></div>' +
            '</div>' +

            // Parts
            '<div class="jc-items-card">' +
                '<div class="jc-section-title">Parts</div>' +
                '<div class="jc-search-row">' +
                    '<input type="text" class="form-control jc-search-input" id="jc-parts-search" placeholder="Search parts...">' +
                '</div>' +
                '<div id="jc-parts-suggestions" class="list-group mb-2"></div>' +
                jcItemsTable('jc-parts-table', parts) +
            '</div>' +

            // Services
            '<div class="jc-items-card">' +
                '<div class="jc-section-title">Services</div>' +
                '<div class="jc-search-row">' +
                    '<input type="text" class="form-control jc-search-input" id="jc-services-search" placeholder="Search services...">' +
                '</div>' +
                '<div id="jc-services-suggestions" class="list-group mb-2"></div>' +
                jcItemsTable('jc-services-table', services) +
            '</div>' +

        '</div>' +

        // RIGHT
        '<div class="jc-right">' +
            '<div class="jc-totals-card">' +
                '<div class="jc-total-row"><span>Parts</span><span>' + ERP.formatCurrency(card.partsSubtotal || 0) + '</span></div>' +
                '<div class="jc-total-row"><span>Services</span><span>' + ERP.formatCurrency(card.servicesSubtotal || 0) + '</span></div>' +
                '<div class="jc-total-row"><span>Subtotal</span><span>' + ERP.formatCurrency(card.subtotal || 0) + '</span></div>' +
                '<div class="jc-total-row">' +
                    '<span>Discount</span>' +
                    '<div class="d-flex gap-1">' +
                        '<select class="form-select form-select-sm" id="jc-discount-type" style="width:75px">' +
                            '<option value="fixed"' + (card.discountType === 'fixed' ? ' selected' : '') + '>Rs.</option>' +
                            '<option value="percent"' + (card.discountType === 'percent' ? ' selected' : '') + '>%</option>' +
                        '</select>' +
                        '<input type="number" class="form-control form-control-sm" id="jc-discount-value" value="' + (card.discountValue || 0) + '" min="0" style="width:80px">' +
                    '</div>' +
                '</div>' +
                '<div class="jc-total-row grand"><span>Grand Total</span><span id="jc-grand-total">' + ERP.formatCurrency(card.grandTotal || 0) + '</span></div>' +
            '</div>' +
            '<div class="jc-payment-btns">' +
                '<button class="jc-payment-btn' + (card.paymentMethod === 'Cash' ? ' active' : '') + '" data-method="Cash">Cash</button>' +
                '<button class="jc-payment-btn' + (card.paymentMethod === 'Credit' ? ' active' : '') + '" data-method="Credit">Credit</button>' +
            '</div>' +
            '<button class="jc-finalize-btn" id="jc-finalize-btn"><i class="ti ti-check me-1"></i>Finalize Job Card</button>' +
            '<button class="jc-print-btn" id="jc-print-btn"><i class="ti ti-printer me-1"></i>Print</button>' +
            '<button class="jc-discard-btn" id="jc-discard-btn"><i class="ti ti-trash me-1"></i>Discard</button>' +
        '</div>' +

    '</div>';
}

function jcField(label, id, type, value, colClass, placeholder) {
    colClass    = colClass    || 'col-md-6';
    placeholder = placeholder || '';
    return '<div class="' + colClass + '">' +
        '<label class="form-label mb-1" style="font-size:0.8rem">' + escHtml(label) + '</label>' +
        '<input type="' + type + '" class="form-control form-control-sm" id="' + id + '" value="' + escHtml(String(value)) + '" placeholder="' + escHtml(placeholder) + '">' +
        '</div>';
}

function jcItemsTable(tableId, items) {
    var rows = '';
    items.forEach(function(item) {
        rows += '<tr>' +
            '<td>' + escHtml(item.productName) + '</td>' +
            '<td><input type="number" class="form-control form-control-sm jc-qty-inp" data-item-id="' + escHtml(item.id) + '" value="' + item.quantity + '" min="0.001" style="width:65px"></td>' +
            '<td>' + ERP.formatCurrency(item.unitPrice) + '</td>' +
            '<td>' + ERP.formatCurrency(item.totalLinePrice) + '</td>' +
            '<td><button class="jc-remove-btn" data-item-id="' + escHtml(item.id) + '"><i class="ti ti-x"></i></button></td>' +
            '</tr>';
    });
    return '<table class="jc-items-table" id="' + tableId + '">' +
        '<thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>' +
        '<tbody>' + (rows || '<tr><td colspan="5" class="text-muted text-center py-2">No items</td></tr>') + '</tbody>' +
        '</table>';
}

// ── Panel Event Binding ───────────────────────────────────────────────────────

function jcBindPanelEvents(card) {
    // Header auto-save on change
    var headerFields = {
        'jc-customer-name': 'customerName',
        'jc-phone':         'phone',
        'jc-vehicle-reg':   'vehicleRegNumber',
        'jc-vin':           'vinChassisNumber',
        'jc-engine':        'engineNumber',
        'jc-make-model':    'makeModelYear',
        'jc-lift':          'liftNumber',
        'jc-odometer':      'currentOdometer',
    };
    Object.keys(headerFields).forEach(function(elId) {
        var el = document.getElementById(elId);
        if (el) {
            el.addEventListener('change', function() {
                var payload = {};
                payload[headerFields[elId]] = el.value || null;
                jcSaveHeaderField(card.id, payload);
            });
        }
    });

    // Customer search
    var csearch = document.getElementById('jc-customer-search');
    if (csearch) {
        csearch.addEventListener('input', function() {
            jcFilterCustomers(card.id, csearch.value);
        });
    }

    // Discount
    var dtype  = document.getElementById('jc-discount-type');
    var dvalue = document.getElementById('jc-discount-value');
    function onDiscountChange() {
        jcSaveHeaderField(card.id, {
            discountType:  dtype.value,
            discountValue: parseFloat(dvalue.value) || 0,
        });
    }
    if (dtype)  dtype.addEventListener('change', onDiscountChange);
    if (dvalue) dvalue.addEventListener('change', onDiscountChange);

    // Payment method
    document.querySelectorAll('.jc-payment-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            jcSaveHeaderField(card.id, { paymentMethod: btn.dataset.method });
        });
    });

    // Parts search
    jcBindProductSearch('jc-parts-search', 'jc-parts-suggestions', 'part', card.id);

    // Services search
    jcBindProductSearch('jc-services-search', 'jc-services-suggestions', 'service', card.id);

    // Remove item buttons
    document.querySelectorAll('.jc-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            jcRemoveItem(card.id, btn.dataset.itemId);
        });
    });

    // Qty change
    document.querySelectorAll('.jc-qty-inp').forEach(function(inp) {
        inp.addEventListener('change', function() {
            jcUpdateItemQty(card.id, inp.dataset.itemId, parseFloat(inp.value) || 1);
        });
    });

    // Finalize
    var finalizeBtn = document.getElementById('jc-finalize-btn');
    if (finalizeBtn) {
        finalizeBtn.addEventListener('click', function() { jcFinalize(card.id); });
    }

    // Print
    var printBtn = document.getElementById('jc-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function() { jcPrint(card); });
    }

    // Discard
    var discardBtn = document.getElementById('jc-discard-btn');
    if (discardBtn) {
        discardBtn.addEventListener('click', function() { jcDiscard(card.id); });
    }
}

// ── Customer Search ───────────────────────────────────────────────────────────

function jcFilterCustomers(cardId, query) {
    var sugg = document.getElementById('jc-customer-suggestions');
    if (!query || query.length < 2) { sugg.innerHTML = ''; return; }

    var q       = query.toLowerCase();
    var parties = (window.ERP.state.parties || []);
    var user    = window.ERP.state.currentUser || {};
    var matches = parties.filter(function(p) {
        return p.companyId === user.companyId &&
               p.type === 'Customer' &&
               (
                   (p.name  && p.name.toLowerCase().indexOf(q)  !== -1) ||
                   (p.phone && p.phone.toLowerCase().indexOf(q) !== -1)
               );
    }).slice(0, 6);

    sugg.innerHTML = '';
    matches.forEach(function(p) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action';
        btn.textContent = p.name + (p.phone ? ' \u00b7 ' + p.phone : '');
        btn.addEventListener('click', function() {
            jcSelectCustomer(cardId, p);
            sugg.innerHTML = '';
            document.getElementById('jc-customer-search').value = '';
        });
        sugg.appendChild(btn);
    });

    if (matches.length === 0) {
        var newBtn = document.createElement('button');
        newBtn.type = 'button';
        newBtn.className = 'list-group-item list-group-item-action text-primary';
        newBtn.innerHTML = '<i class="ti ti-user-plus me-1"></i>Create new customer "' + escHtml(query) + '"';
        newBtn.addEventListener('click', function() {
            jcCreateCustomer(cardId, query);
            sugg.innerHTML = '';
            document.getElementById('jc-customer-search').value = '';
        });
        sugg.appendChild(newBtn);
    }
}

function jcSelectCustomer(cardId, party) {
    var history  = window.ERP.state.jobCardHistory || [];
    var lastCard = null;
    for (var i = 0; i < history.length; i++) {
        if (history[i].customerId === party.id) { lastCard = history[i]; break; }
    }

    var payload = {
        customerId:   party.id,
        customerName: party.name,
        phone:        party.phone || null,
    };

    if (lastCard) {
        payload.vehicleRegNumber = lastCard.vehicleRegNumber || null;
        payload.vinChassisNumber = lastCard.vinChassisNumber || null;
        payload.engineNumber     = lastCard.engineNumber     || null;
        payload.makeModelYear    = lastCard.makeModelYear    || null;
    }

    jcSaveHeaderField(cardId, payload);
}

function jcCreateCustomer(cardId, name) {
    ERP.api.createParty({
        companyId: (window.ERP.state.currentUser || {}).companyId,
        name:      name,
        type:      'Customer',
    }).then(function(party) {
        window.ERP.state.parties = (window.ERP.state.parties || []).concat([party]);
        jcSaveHeaderField(cardId, {
            customerId:   party.id,
            customerName: party.name,
        });
    }).catch(function(e) { alert('Error creating customer: ' + e.message); });
}

// ── Product Search ────────────────────────────────────────────────────────────

function jcBindProductSearch(inputId, suggId, itemType, cardId) {
    var input = document.getElementById(inputId);
    var sugg  = document.getElementById(suggId);
    if (!input || !sugg) return;

    input.addEventListener('input', function() {
        var q          = input.value.toLowerCase().trim();
        var products   = (window.ERP.state.products || []);
        var user       = window.ERP.state.currentUser || {};
        var typeFilter = itemType === 'part' ? 'Product' : 'Service';

        sugg.innerHTML = '';
        if (!q) return;

        var matches = products.filter(function(p) {
            return p.companyId === user.companyId &&
                   p.type === typeFilter &&
                   p.name.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 6);

        matches.forEach(function(p) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.textContent = p.name + ' \u2014 ' + ERP.formatCurrency(p.unitPrice || 0);
            btn.addEventListener('click', function() {
                jcAddItem(cardId, itemType, p);
                sugg.innerHTML = '';
                input.value = '';
            });
            sugg.appendChild(btn);
        });
    });
}

// ── Item CRUD ─────────────────────────────────────────────────────────────────

function jcAddItem(cardId, itemType, product) {
    ERP.api.addJobCardItem(cardId, {
        itemType:  itemType,
        productId: product.id,
        quantity:  1,
        unitPrice: product.unitPrice || 0,
        discount:  0,
    }).then(function() {
        return jcRefreshCard(cardId);
    }).catch(function(e) { alert('Error: ' + e.message); });
}

function jcUpdateItemQty(cardId, itemId, qty) {
    ERP.api.updateJobCardItem(cardId, itemId, { quantity: qty })
        .then(function() { return jcRefreshCard(cardId); })
        .catch(function(e) { alert('Error: ' + e.message); });
}

function jcRemoveItem(cardId, itemId) {
    ERP.api.removeJobCardItem(cardId, itemId)
        .then(function() { return jcRefreshCard(cardId); })
        .catch(function(e) { alert('Error: ' + e.message); });
}

// ── Header Save ───────────────────────────────────────────────────────────────

function jcSaveHeaderField(cardId, payload) {
    ERP.api.updateJobCard(cardId, payload)
        .then(function(updated) {
            jcUpdateTabState(updated);
            jcRenderTabBar();
            jcRenderPanel();
        })
        .catch(function(e) { alert('Error saving: ' + e.message); });
}

// ── Finalize / Discard / Print ────────────────────────────────────────────────

function jcFinalize(cardId) {
    if (!confirm('Finalize this Job Card? This will deduct inventory and cannot be undone.')) return;

    ERP.api.finalizeJobCard(cardId)
        .then(function() {
            jcCloseTab(cardId);
            alert('Job Card finalized successfully.');
        })
        .catch(function(e) { alert('Error: ' + e.message); });
}

function jcDiscard(cardId) {
    if (!confirm('Discard this Job Card? All entries will be lost.')) return;

    ERP.api.discardJobCard(cardId)
        .then(function() { jcCloseTab(cardId); })
        .catch(function(e) { alert('Error: ' + e.message); });
}

function jcPrint(card) {
    var ph = document.getElementById('jc-print-header');
    if (ph) {
        ph.innerHTML = '<h3>' + escHtml((window.ERP.state.currentUser || {}).companyName || 'Job Card') + '</h3>' +
            '<p><strong>Job Card No:</strong> ' + escHtml(card.jobCardNo || '') + '</p>' +
            '<p><strong>Customer:</strong> ' + escHtml(card.customerName || '') + ' &nbsp; <strong>Phone:</strong> ' + escHtml(card.phone || '') + '</p>' +
            '<p><strong>Vehicle Reg:</strong> ' + escHtml(card.vehicleRegNumber || '') + ' &nbsp; <strong>Make/Model:</strong> ' + escHtml(card.makeModelYear || '') + '</p>' +
            '<p><strong>VIN:</strong> ' + escHtml(card.vinChassisNumber || '') + ' &nbsp; <strong>Engine:</strong> ' + escHtml(card.engineNumber || '') + '</p>' +
            '<p><strong>Odometer:</strong> ' + escHtml(String(card.currentOdometer || '')) + ' &nbsp; <strong>Lift No:</strong> ' + escHtml(card.liftNumber || '') + '</p>';
    }
    window.print();
}

// ── Refresh card from server ──────────────────────────────────────────────────

function jcRefreshCard(cardId) {
    return ERP.api.getJobCard(cardId)
        .then(function(updated) {
            jcUpdateTabState(updated);
            jcRenderTabBar();
            jcRenderPanel();
        });
}

// ── Utilities ─────────────────────────────────────────────────────────────────

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
