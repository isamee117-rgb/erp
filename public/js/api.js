(function() {
    'use strict';

    var baseMeta = document.querySelector('meta[name="base-url"]');
    var API_BASE = (baseMeta ? baseMeta.getAttribute('content') : '') + '/api';

    function getToken() {
        return localStorage.getItem('leanerp_token');
    }

    function buildHeaders(extra) {
        var h = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (extra) { for (var k in extra) { h[k] = extra[k]; } }
        var token = getToken();
        if (token) { h['Authorization'] = 'Bearer ' + token; }
        return h;
    }

    function request(method, url, body) {
        var opts = { method: method, headers: buildHeaders() };
        if (body !== undefined) { opts.body = JSON.stringify(body); }
        return fetch(API_BASE + url, opts).then(function(res) {
            if (!res.ok) {
                return res.json().catch(function() { return { message: 'Request failed' }; }).then(function(err) {
                    // Format Laravel validation errors into human-readable English
                    if (err.errors && typeof err.errors === 'object') {
                        var msgs = [];
                        Object.keys(err.errors).forEach(function(field) {
                            var fieldErrors = Array.isArray(err.errors[field]) ? err.errors[field] : [err.errors[field]];
                            fieldErrors.forEach(function(msg) { msgs.push(msg); });
                        });
                        if (msgs.length) { throw new Error(msgs.join('; ')); }
                    }
                    throw new Error(err.message || err.error || 'Request failed');
                });
            }
            return res.json();
        });
    }

    var api = {
        login: function(username, password) {
            return request('POST', '/login', { username: username, password: password }).then(function(data) {
                return { token: data.token, user: data.user };
            });
        },
        sync: function() {
            var token = getToken();
            if (!token) return Promise.resolve({});
            return request('GET', '/sync').catch(function() { return {}; });
        },
        syncCore: function() {
            var token = getToken();
            if (!token) return Promise.resolve({});
            return request('GET', '/sync/core').catch(function() { return {}; });
        },
        syncMaster: function() {
            var token = getToken();
            if (!token) return Promise.resolve({});
            return request('GET', '/sync/master').catch(function() { return {}; });
        },
        syncTransactions: function() {
            var token = getToken();
            if (!token) return Promise.resolve({});
            return request('GET', '/sync/transactions').catch(function() { return {}; });
        },
        createCompany: function(name, adminUsername, adminPassword, limit, registrationPayment, saasPlan) {
            return request('POST', '/companies', { name: name, adminUsername: adminUsername, adminPassword: adminPassword, limit: limit, registrationPayment: registrationPayment, saasPlan: saasPlan });
        },
        updateCompanyStatus: function(id, status) {
            return request('PUT', '/companies/' + id + '/status', { status: status });
        },
        updateCompanyLimit: function(id, limit) {
            return request('PUT', '/companies/' + id + '/limit', { limit: limit });
        },
        updateCompanyAdminPassword: function(id, password) {
            return request('PUT', '/companies/' + id + '/admin-password', { password: password });
        },
        updateCompanyDetails: function(id, data) {
            return request('PUT', '/companies/' + id + '/details', data);
        },
        updateCompanyInfo: function(info) {
            return request('PUT', '/company-info', info);
        },
        uploadCompanyLogo: function(file) {
            var formData = new FormData();
            formData.append('logo', file);
            var token = getToken();
            return fetch(API_BASE + '/company-logo', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
                body: formData,
            }).then(function(res) { return res.json(); });
        },
        updateCurrency: function(currency) {
            return request('PUT', '/settings/currency', { currency: currency });
        },
        updateInvoiceFormat: function(format) {
            return request('PUT', '/settings/invoice-format', { format: format });
        },
        updateCostingMethod: function(costingMethod) {
            return request('PUT', '/settings/costing-method', { costingMethod: costingMethod });
        },
        createProduct: function(p) {
            return request('POST', '/products', p);
        },
        updateProduct: function(p) {
            return request('PUT', '/products/' + p.id, p);
        },
        findProductByBarcode: function(code) {
            return request('GET', '/products/barcode?code=' + encodeURIComponent(code)).catch(function() { return null; });
        },
        adjustStock: function(productId, quantityChange, type) {
            return request('POST', '/products/adjust-stock', { productId: productId, quantityChange: quantityChange, type: type });
        },
        addParty: function(partyData) {
            return request('POST', '/parties', partyData);
        },
        updateParty: function(party) {
            return request('PUT', '/parties/' + party.id, party);
        },
        deleteParty: function(id) {
            return request('DELETE', '/parties/' + id);
        },
        deleteProduct: function(id) {
            return request('DELETE', '/products/' + id);
        },
        listUomConversions: function(productId) {
            return request('GET', '/products/' + productId + '/uom-conversions');
        },
        addUomConversion: function(productId, data) {
            return request('POST', '/products/' + productId + '/uom-conversions', data);
        },
        updateUomConversion: function(productId, cid, data) {
            return request('PUT', '/products/' + productId + '/uom-conversions/' + cid, data);
        },
        deleteUomConversion: function(productId, cid) {
            return request('DELETE', '/products/' + productId + '/uom-conversions/' + cid);
        },
        savePriceTier: function(productId, data) {
            return request('POST', '/products/' + productId + '/price-tiers', data);
        },
        updatePriceTier: function(productId, tid, data) {
            return request('PUT', '/products/' + productId + '/price-tiers/' + tid, data);
        },
        deletePriceTier: function(productId, tid) {
            return request('DELETE', '/products/' + productId + '/price-tiers/' + tid);
        },
        createSale: function(saleData) {
            return request('POST', '/sales', saleData);
        },
        createSaleReturn: function(saleId, items, reason) {
            return request('POST', '/sales/return', { saleId: saleId, items: items, reason: reason });
        },
        createPurchaseOrder: function(vendorId, items, orderDate) {
            return request('POST', '/purchases', { vendorId: vendorId, items: items, orderDate: orderDate || null });
        },
        receivePurchaseOrder: function(poId, items, notes) {
            var body = items ? { items: items, notes: notes } : {};
            return request('PUT', '/purchases/' + poId + '/receive', body);
        },
        partialReceivePurchaseOrder: function(poId, items, notes) {
            return request('POST', '/purchases/' + poId + '/partial-receive', { items: items, notes: notes });
        },
        createPurchaseReturn: function(poId, items, reason) {
            return request('POST', '/purchases/return', { poId: poId, items: items, reason: reason });
        },
        addPayment: function(payData) {
            return request('POST', '/payments', payData);
        },
        deletePayment: function(id) {
            return request('DELETE', '/payments/' + id);
        },
        createCategory: function(companyId, name) {
            return request('POST', '/categories', { companyId: companyId, name: name });
        },
        deleteCategory: function(id) {
            return request('DELETE', '/categories/' + id);
        },
        createUOM: function(companyId, name) {
            return request('POST', '/uoms', { companyId: companyId, name: name });
        },
        deleteUOM: function(id) {
            return request('DELETE', '/uoms/' + id);
        },
        createEntityType: function(name) {
            return request('POST', '/entity-types', { name: name });
        },
        deleteEntityType: function(id) {
            return request('DELETE', '/entity-types/' + id);
        },
        createBusinessCategory: function(name) {
            return request('POST', '/business-categories', { name: name });
        },
        deleteBusinessCategory: function(id) {
            return request('DELETE', '/business-categories/' + id);
        },
        addCustomRole: function(roleData) {
            return request('POST', '/roles', roleData);
        },
        updateCustomRole: function(role) {
            return request('PUT', '/roles/' + role.id, role);
        },
        deleteCustomRole: function(id) {
            return request('DELETE', '/roles/' + id);
        },
        addUser: function(userData) {
            return request('POST', '/users', userData);
        },
        setUserStatus: function(userId, isActive) {
            return request('PUT', '/users/' + userId + '/status', { isActive: isActive });
        },
        updateUser: function(userId, data) {
            return request('PUT', '/users/' + userId, data);
        },
        updateUserPassword: function(userId, newPassword) {
            return request('PUT', '/users/' + userId + '/password', { password: newPassword });
        },
        getDocumentSequences: function() {
            return request('GET', '/settings/document-sequences');
        },
        updateDocumentSequence: function(type, prefix, nextNumber) {
            return request('PUT', '/settings/document-sequences', { type: type, prefix: prefix, nextNumber: nextNumber });
        },
        // Accounting — Chart of Accounts
        getCoa: function() {
            return request('GET', '/accounting/coa');
        },
        createAccount: function(data) {
            return request('POST', '/accounting/coa', data);
        },
        updateAccount: function(id, data) {
            return request('PUT', '/accounting/coa/' + id, data);
        },
        deleteAccount: function(id) {
            return request('DELETE', '/accounting/coa/' + id);
        },
        // Accounting — Account Mappings
        getMappings: function() {
            return request('GET', '/accounting/mappings');
        },
        saveMappings: function(mappings) {
            return request('PUT', '/accounting/mappings', { mappings: mappings });
        },
        // Accounting — Journal Entries
        getJournals: function(params) {
            var qs = params ? ('?' + Object.keys(params).map(function(k) { return k + '=' + encodeURIComponent(params[k]); }).join('&')) : '';
            return request('GET', '/accounting/journals' + qs);
        },
        getJournal: function(id) {
            return request('GET', '/accounting/journals/' + id);
        },
        createJournal: function(data) {
            return request('POST', '/accounting/journals', data);
        },
        postJournal: function(id) {
            return request('POST', '/accounting/journals/' + id + '/post');
        },
        deleteJournal: function(id) {
            return request('DELETE', '/accounting/journals/' + id);
        },
        // Accounting — Reports
        getProfitLoss: function(from, to) {
            return request('GET', '/reports/profit-loss?from=' + from + '&to=' + to);
        },
        getBalanceSheet: function(asOf) {
            return request('GET', '/reports/balance-sheet?as_of=' + asOf);
        }
    };

    window.ERP = window.ERP || {};
    window.ERP.api = api;
})();
