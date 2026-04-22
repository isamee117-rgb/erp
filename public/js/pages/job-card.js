'use strict';

var jcTabs     = [];
var jcActiveId = null;

window.ERP.onReady = function() { jcInit(); };

// ── Init ──────────────────────────────────────────────────────────────────────

function jcInit() {
    jcTabs = (window.ERP.state.jobCards || []).slice();
    if (jcTabs.length > 0) { jcActiveId = jcTabs[0].id; }
    jcRenderTabBar();
    jcRenderPanel();
    jcBindStaticEvents();
}

function jcBindStaticEvents() {
    document.getElementById('jc-new-tab-btn').addEventListener('click', jcOpenNewModal);
    document.getElementById('jcm-save-btn').addEventListener('click', jcSaveNewModal);
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#jcm-customer-sdd')) {
            var wrap = document.getElementById('jcm-customer-sdd');
            if (wrap) wrap.classList.remove('open');
        }
    });
}

// ── SDD (Searchable Dropdown) for Customer ────────────────────────────────────

function jcSddToggle() {
    var wrap = document.getElementById('jcm-customer-sdd');
    var isOpen = wrap.classList.contains('open');
    wrap.classList.toggle('open', !isOpen);
    if (!isOpen) {
        var inp = wrap.querySelector('.sdd-search-inp');
        if (inp) { inp.value = ''; jcSddFilter(''); setTimeout(function() { inp.focus(); }, 50); }
    }
}

function jcSddFilter(query) {
    var wrap = document.getElementById('jcm-customer-sdd');
    var q = query.toLowerCase().trim();
    var opts = wrap.querySelectorAll('.sdd-opt');
    var visible = 0;
    opts.forEach(function(o) {
        var match = !q || o.dataset.search.indexOf(q) !== -1;
        o.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    var nr = wrap.querySelector('.sdd-no-res');
    if (nr) nr.style.display = visible === 0 ? '' : 'none';
}

function jcSddSelectCustomer(partyId, partyName) {
    document.getElementById('jcm-customer-id').value = partyId;
    var disp = document.getElementById('jcm-customer-disp');
    disp.textContent = partyName;
    disp.style.color = '#1A1D2E';
    document.getElementById('jcm-customer-sdd').classList.remove('open');
    var party = (window.ERP.state.parties || []).find(function(p) { return p.id === partyId; });
    if (party) jcModalFillCustomer(party);
}

function jcSddPopulate() {
    var user      = window.ERP.state.currentUser || {};
    var customers = (window.ERP.state.parties || []).filter(function(p) {
        return p.type === 'Customer' && (p.companyId === user.companyId || !p.companyId);
    });
    var html = '';
    customers.forEach(function(c) {
        var label  = escHtml(c.name) + (c.phone ? ' <span class="text-muted" style="font-size:0.8rem">· ' + escHtml(c.phone) + '</span>' : '');
        var search = (c.name + ' ' + (c.phone || '')).toLowerCase();
        html += '<div class="sdd-opt" data-search="' + escHtml(search) + '" onclick="jcSddSelectCustomer(\'' + escHtml(c.id) + '\',\'' + escHtml(c.name) + '\')">' + label + '</div>';
    });
    html += '<div class="sdd-no-res" style="display:none">No customers found</div>';
    document.getElementById('jcm-customer-opts').innerHTML = html;
}

// ── New Job Card Modal ────────────────────────────────────────────────────────

function jcOpenNewModal() {
    ['jcm-name','jcm-phone','jcm-vreg','jcm-make','jcm-vin','jcm-engine','jcm-lift'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    var odom = document.getElementById('jcm-odometer');
    if (odom) odom.value = '';

    // Reset SDD
    document.getElementById('jcm-customer-id').value = '';
    var disp = document.getElementById('jcm-customer-disp');
    disp.textContent = '— Select or search customer —';
    disp.style.color = '#B0B7C9';
    document.getElementById('jcm-customer-sdd').classList.remove('open');
    jcSddPopulate();

    new bootstrap.Modal(document.getElementById('jcNewCardModal')).show();
}

function jcModalFillCustomer(party) {
    document.getElementById('jcm-customer-id').value = party.id || '';
    document.getElementById('jcm-name').value         = party.name || '';
    document.getElementById('jcm-phone').value        = party.phone || '';

    // Fill vehicle info from customer record first
    document.getElementById('jcm-vreg').value    = party.vehicle_reg_number    || '';
    document.getElementById('jcm-make').value    = party.make_model_year       || '';
    document.getElementById('jcm-vin').value     = party.vin_chassis_number    || '';
    document.getElementById('jcm-engine').value  = party.engine_number         || '';
    document.getElementById('jcm-odometer').value = party.last_odometer_reading ? String(party.last_odometer_reading) : '';

    // Fall back to last job card history only if customer record has no vehicle data
    if (!party.vehicle_reg_number && !party.make_model_year && !party.vin_chassis_number && !party.engine_number) {
        var history = window.ERP.state.jobCardHistory || [];
        for (var i = 0; i < history.length; i++) {
            if (history[i].customerId === party.id) {
                document.getElementById('jcm-vreg').value   = history[i].vehicleRegNumber || '';
                document.getElementById('jcm-make').value   = history[i].makeModelYear    || '';
                document.getElementById('jcm-vin').value    = history[i].vinChassisNumber  || '';
                document.getElementById('jcm-engine').value = history[i].engineNumber      || '';
                break;
            }
        }
    }
}

function jcSaveNewModal() {
    var saveBtn    = document.getElementById('jcm-save-btn');
    var customerId = document.getElementById('jcm-customer-id').value.trim();
    var name       = document.getElementById('jcm-name').value.trim();
    var phone      = document.getElementById('jcm-phone').value.trim();
    var vreg       = document.getElementById('jcm-vreg').value.trim();
    var make       = document.getElementById('jcm-make').value.trim();
    var vin        = document.getElementById('jcm-vin').value.trim();
    var engine     = document.getElementById('jcm-engine').value.trim();
    var odometer   = document.getElementById('jcm-odometer').value.trim();
    var lift       = document.getElementById('jcm-lift').value.trim();

    saveBtn.disabled = true;
    saveBtn.textContent = 'Creating...';

    // Step 1: create customer if no id but name given
    var customerPromise;
    if (!customerId && name) {
        var newPartyData = {
            companyId:           (window.ERP.state.currentUser || {}).companyId,
            name:                name,
            phone:               phone || null,
            type:                'Customer',
            make_model_year:     make    || null,
            vehicle_reg_number:  vreg    || null,
            vin_chassis_number:  vin     || null,
            engine_number:       engine  || null,
            last_odometer_reading: odometer ? (parseFloat(odometer) || null) : null,
        };
        customerPromise = ERP.api.addParty(newPartyData).then(function(party) {
            window.ERP.state.parties = (window.ERP.state.parties || []).concat([party]);
            return party.id;
        });
    } else {
        customerPromise = Promise.resolve(customerId || null);
    }

    // Step 2: create job card with all data
    customerPromise.then(function(resolvedCustomerId) {
        var payload = {};
        if (resolvedCustomerId) payload.customerId   = resolvedCustomerId;
        if (name)     payload.customerName     = name;
        if (phone)    payload.phone            = phone;
        if (vreg)     payload.vehicleRegNumber = vreg;
        if (make)     payload.makeModelYear    = make;
        if (vin)      payload.vinChassisNumber = vin;
        if (engine)   payload.engineNumber     = engine;
        if (lift)     payload.liftNumber       = lift;
        if (odometer) payload.currentOdometer  = parseFloat(odometer) || null;

        return ERP.api.createJobCard(payload);
    }).then(function(card) {
        // Update customer record with vehicle info (non-blocking)
        var resolvedCustomerId = document.getElementById('jcm-customer-id').value.trim();
        if (resolvedCustomerId && (vreg || make || vin || engine || odometer)) {
            var partyUpdate = { id: resolvedCustomerId };
            if (vreg)     partyUpdate.vehicle_reg_number    = vreg;
            if (make)     partyUpdate.make_model_year       = make;
            if (vin)      partyUpdate.vin_chassis_number    = vin;
            if (engine)   partyUpdate.engine_number         = engine;
            if (odometer) partyUpdate.last_odometer_reading = parseFloat(odometer) || null;
            ERP.api.updateParty(partyUpdate).catch(function() {});
        }
        // Close modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('jcNewCardModal'));
        if (modal) modal.hide();
        // Open as tab
        jcTabs.push(card);
        jcActiveId = card.id;
        jcRenderTabBar();
        jcRenderPanel();
    }).catch(function(e) {
        alert('Error: ' + e.message);
    }).finally(function() {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="ti ti-circle-check me-1"></i>Create Job Card';
    });
}

// ── Tabs ──────────────────────────────────────────────────────────────────────

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
        if (jcTabs[i].id === updated.id) { jcTabs[i] = updated; return; }
    }
}

// ── Render Tab Bar ────────────────────────────────────────────────────────────

function jcRenderTabBar() {
    var bar = document.getElementById('jc-tab-bar');
    bar.innerHTML = '';

    jcTabs.forEach(function(card) {
        var label = card.jobCardNo || 'New';
        if (card.vehicleRegNumber) label += ' · ' + card.vehicleRegNumber;

        var tab = document.createElement('div');
        tab.className = 'jc-tab' + (card.id === jcActiveId ? ' active' : '');
        tab.innerHTML = '<i class="ti ti-clipboard-list" style="font-size:0.8rem;opacity:0.7"></i>' +
            escHtml(label) +
            '<span class="jc-tab-close" title="Close">&times;</span>';

        tab.addEventListener('click', function(e) {
            if (e.target.classList.contains('jc-tab-close')) return;
            jcSwitchTab(card.id);
        });
        tab.querySelector('.jc-tab-close').addEventListener('click', function(e) {
            e.stopPropagation();
            jcDiscard(card.id);
        });
        bar.appendChild(tab);
    });

    // Show hint if no tabs
    if (jcTabs.length === 0) {
        bar.innerHTML = '<span style="font-size:0.8rem;color:#94a3b8;padding:0 4px">No open job cards</span>';
    }
}

// ── Render Active Panel ───────────────────────────────────────────────────────

function jcRenderPanel() {
    var wrap = document.getElementById('jc-panel-wrap');
    var card = jcGetActive();

    if (!card) {
        wrap.innerHTML = '<div class="jc-empty-state">' +
            '<i class="ti ti-clipboard-list"></i>' +
            '<p>No open job cards.<br>Click <strong>+ New Job Card</strong> to begin.</p>' +
            '</div>';
        return;
    }

    wrap.innerHTML = jcBuildPanelHtml(card);
    jcBindPanelEvents(card);
}

function jcBuildPanelHtml(card) {
    var parts    = (card.items || []).filter(function(i) { return i.itemType === 'part'; });
    var services = (card.items || []).filter(function(i) { return i.itemType === 'service'; });

    return '<div class="jc-panel" id="jc-panel-' + escHtml(card.id) + '">' +

        // LEFT COLUMN — Parts & Services
        '<div class="jc-left">' +

            // Parts
            '<div class="jc-items-card">' +
                '<div class="jc-section-title"><i class="ti ti-tool me-1"></i>Parts</div>' +
                '<div class="jc-search-row">' +
                    '<input type="text" class="form-control pm-input" id="jc-parts-search" placeholder="Search parts by name...">' +
                '</div>' +
                '<div id="jc-parts-suggestions" class="list-group mb-2" style="position:relative;z-index:10"></div>' +
                jcItemsTable('jc-parts-table', parts, card.partsSubtotal) +
            '</div>' +

            // Services
            '<div class="jc-items-card">' +
                '<div class="jc-section-title"><i class="ti ti-briefcase me-1"></i>Services</div>' +
                '<div class="jc-search-row">' +
                    '<input type="text" class="form-control pm-input" id="jc-services-search" placeholder="Search services by name...">' +
                '</div>' +
                '<div id="jc-services-suggestions" class="list-group mb-2" style="position:relative;z-index:10"></div>' +
                jcItemsTable('jc-services-table', services, card.servicesSubtotal) +
            '</div>' +

        '</div>' +

        // RIGHT COLUMN — Customer Info + Totals & Actions
        '<div class="jc-right">' +

            // Customer & Vehicle Info (top of right column)
            '<div class="jc-header-card">' +
                '<div class="jc-section-title"><i class="ti ti-car me-1"></i>Vehicle &amp; Customer</div>' +
                '<div class="row g-2">' +
                    jcField('Customer Name',      'jc-customer-name', 'text',   card.customerName   || '') +
                    jcField('Phone',              'jc-phone',         'text',   card.phone          || '') +
                    jcField('Vehicle Reg No',     'jc-vehicle-reg',   'text',   card.vehicleRegNumber|| '') +
                    jcField('Make / Model / Year','jc-make-model',    'text',   card.makeModelYear  || '') +
                    jcField('VIN / Chassis No',   'jc-vin',           'text',   card.vinChassisNumber|| '') +
                    jcField('Engine No',          'jc-engine',        'text',   card.engineNumber   || '') +
                    jcField('Current Odometer',   'jc-odometer',      'number', card.currentOdometer|| '') +
                    jcField('Lift No',            'jc-lift',          'text',   card.liftNumber     || '') +
                '</div>' +
            '</div>' +

            // Billing Summary (bottom of right column)
            '<div class="jc-totals-card">' +
                '<div class="jc-section-title"><i class="ti ti-receipt me-1"></i>Summary</div>' +
                '<div class="jc-total-row"><span class="text-muted">Subtotal</span><span>' + ERP.formatCurrency(card.subtotal || 0) + '</span></div>' +
                '<div class="jc-total-row align-items-center">' +
                    '<span class="text-muted">Discount</span>' +
                    '<div class="d-flex gap-1">' +
                        '<select class="form-select form-select-sm pm-input" id="jc-discount-type" style="width:70px;height:32px!important;padding:4px 6px!important">' +
                            '<option value="fixed"'   + (card.discountType === 'fixed'   ? ' selected' : '') + '>Rs.</option>' +
                            '<option value="percent"' + (card.discountType === 'percent' ? ' selected' : '') + '>%</option>' +
                        '</select>' +
                        '<input type="number" class="form-control form-control-sm pm-input" id="jc-discount-value" value="' + (card.discountValue || 0) + '" min="0" style="width:75px;height:32px!important">' +
                    '</div>' +
                '</div>' +
                '<div class="jc-total-row grand"><span>Grand Total</span><span id="jc-grand-total">' + ERP.formatCurrency(card.grandTotal || 0) + '</span></div>' +
            '</div>' +

            '<div class="jc-payment-btns">' +
                '<button class="jc-payment-btn' + (card.paymentMethod === 'Cash'   ? ' active' : '') + '" data-method="Cash"><i class="ti ti-cash me-1"></i>Cash</button>' +
                '<button class="jc-payment-btn' + (card.paymentMethod === 'Credit' ? ' active' : '') + '" data-method="Credit"><i class="ti ti-credit-card me-1"></i>Credit</button>' +
            '</div>' +

            '<button class="jc-finalize-btn" id="jc-finalize-btn"><i class="ti ti-check me-1"></i>Finalize Job Card</button>' +
            '<button class="jc-print-btn"    id="jc-print-btn"><i class="ti ti-printer me-1"></i>Print</button>' +
            '<button class="jc-discard-btn"  id="jc-discard-btn"><i class="ti ti-trash me-1"></i>Discard</button>' +
        '</div>' +

    '</div>';
}

function jcField(label, id, type, value) {
    return '<div class="col-md-6">' +
        '<label class="pm-label">' + escHtml(label) + '</label>' +
        '<input type="' + type + '" class="form-control pm-input" id="' + id + '" value="' + escHtml(String(value)) + '">' +
        '</div>';
}

function jcItemsTable(tableId, items, subtotal) {
    var rows = '';
    items.forEach(function(item) {
        rows += '<tr>' +
            '<td>' + escHtml(item.productName || '') + '</td>' +
            '<td style="width:80px"><input type="number" class="form-control form-control-sm pm-input jc-qty-inp" data-item-id="' + escHtml(item.id) + '" value="' + item.quantity + '" min="0.001" style="height:30px!important;padding:2px 6px!important"></td>' +
            '<td class="text-end">' + ERP.formatCurrency(item.unitPrice) + '</td>' +
            '<td class="text-end fw-semibold">' + ERP.formatCurrency(item.totalLinePrice) + '</td>' +
            '<td class="text-center" style="width:36px"><button class="jc-remove-btn" data-item-id="' + escHtml(item.id) + '" title="Remove"><i class="ti ti-x"></i></button></td>' +
            '</tr>';
    });
    var footer = '<tfoot><tr>' +
        '<td colspan="3" class="text-end text-muted" style="font-size:0.8rem;padding-top:6px">Subtotal</td>' +
        '<td class="text-end fw-semibold" style="padding-top:6px">' + ERP.formatCurrency(subtotal || 0) + '</td>' +
        '<td></td>' +
        '</tr></tfoot>';
    return '<table class="jc-items-table" id="' + tableId + '">' +
        '<thead><tr><th>Item</th><th>Qty</th><th class="text-end">Price</th><th class="text-end">Total</th><th></th></tr></thead>' +
        '<tbody>' + (rows || '<tr><td colspan="5" class="text-center text-muted py-3" style="font-size:0.82rem">No items added</td></tr>') + '</tbody>' +
        footer +
        '</table>';
}

// ── Panel Event Binding ───────────────────────────────────────────────────────

function jcBindPanelEvents(card) {
    // Header auto-save on blur/change
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

    // Discount
    var dtype  = document.getElementById('jc-discount-type');
    var dvalue = document.getElementById('jc-discount-value');
    function onDiscountChange() {
        jcSaveHeaderField(card.id, {
            discountType:  dtype.value,
            discountValue: parseFloat(dvalue.value) || 0,
        });
    }
    if (dtype)  dtype.addEventListener('change',  onDiscountChange);
    if (dvalue) dvalue.addEventListener('change', onDiscountChange);

    // Payment method
    document.querySelectorAll('.jc-payment-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            jcSaveHeaderField(card.id, { paymentMethod: btn.dataset.method });
        });
    });

    // Product search
    jcBindProductSearch('jc-parts-search',    'jc-parts-suggestions',    'part',    card.id);
    jcBindProductSearch('jc-services-search', 'jc-services-suggestions', 'service', card.id);

    // Remove item
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
    if (finalizeBtn) finalizeBtn.addEventListener('click', function() { jcFinalize(card.id); });

    // Print
    var printBtn = document.getElementById('jc-print-btn');
    if (printBtn) printBtn.addEventListener('click', function() { jcPrint(card); });

    // Discard
    var discardBtn = document.getElementById('jc-discard-btn');
    if (discardBtn) discardBtn.addEventListener('click', function() { jcDiscard(card.id); });
}

// ── Product Search ────────────────────────────────────────────────────────────

function jcBindProductSearch(inputId, suggId, itemType, cardId) {
    var input = document.getElementById(inputId);
    var sugg  = document.getElementById(suggId);
    if (!input || !sugg) return;

    var typeFilter = itemType === 'part' ? 'Product' : 'Service';

    input.addEventListener('input', function() {
        var q        = input.value.toLowerCase().trim();
        var user     = window.ERP.state.currentUser || {};
        var products = (window.ERP.state.products || []);

        sugg.innerHTML = '';
        if (!q) return;

        var matches = products.filter(function(p) {
            return p.type === typeFilter &&
                   (p.companyId === user.companyId || !p.companyId) &&
                   p.name.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 7);

        matches.forEach(function(p) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            btn.innerHTML = '<span>' + escHtml(p.name) + '</span>' +
                '<span class="text-muted" style="font-size:0.8rem">' + ERP.formatCurrency(p.unitPrice || 0) + '</span>';
            btn.addEventListener('click', function() {
                jcAddItem(cardId, itemType, p);
                sugg.innerHTML = '';
                input.value = '';
            });
            sugg.appendChild(btn);
        });
    });

    // Hide suggestions on outside click
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !sugg.contains(e.target)) {
            sugg.innerHTML = '';
        }
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
        var co = window.ERP.state.currentUser || {};
        ph.innerHTML =
            '<h3>' + escHtml(co.companyName || 'Workshop') + '</h3>' +
            '<p><strong>Job Card No:</strong> ' + escHtml(card.jobCardNo || '') +
            ' &nbsp; <strong>Date:</strong> ' + new Date().toLocaleDateString() + '</p>' +
            '<p><strong>Customer:</strong> ' + escHtml(card.customerName || '') +
            ' &nbsp; <strong>Phone:</strong> ' + escHtml(card.phone || '') + '</p>' +
            '<p><strong>Vehicle Reg:</strong> ' + escHtml(card.vehicleRegNumber || '') +
            ' &nbsp; <strong>Make/Model:</strong> ' + escHtml(card.makeModelYear || '') + '</p>' +
            '<p><strong>VIN:</strong> ' + escHtml(card.vinChassisNumber || '') +
            ' &nbsp; <strong>Engine:</strong> ' + escHtml(card.engineNumber || '') + '</p>' +
            '<p><strong>Odometer:</strong> ' + escHtml(String(card.currentOdometer || '—')) +
            ' km &nbsp; <strong>Lift No:</strong> ' + escHtml(card.liftNumber || '—') + '</p>';
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
