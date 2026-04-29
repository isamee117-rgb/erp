@extends('layouts.app')
@section('page-title', 'Reports - LeanERP')
@section('content')

<div class="inv-page-wrap">

<div class="card inv-header-card">
  <div class="card-body inv-header-body">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="mb-1 inv-title"><i class="ti ti-chart-bar me-2"></i>Reports</h2>
        <p class="mb-0 inv-subtitle">Analyze sales, purchases, inventory, and financial performance.</p>
      </div>
      <div class="col-auto">
        <button class="btn btn-light shadow-sm" class="btn-erp-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

<div class="card inv-section-card" class="erp-overflow-hidden">
  <div class="inv-tab-header">
    <ul class="nav inv-nav-tabs" role="tablist">
      <li class="nav-item"><a class="nav-link inv-nav-link active" data-bs-toggle="tab" href="#reportsTab"><i class="ti ti-file-report me-1"></i>Reports</a></li>
    </ul>
  </div>
  <div class="tab-content">

    {{-- Reports (Printable) --}}
    <div class="tab-pane active" id="reportsTab">

      {{-- Tiles view --}}
      <div id="rpt-tiles-view" class="erp-report-tiles">
        <p class="mb-3" class="erp-label-upper">Select a report to run</p>
        <div class="rpt-tiles-grid">
          <div class="rpt-tile" onclick="rptOpen('product')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-inventory"><i class="ti ti-package"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Product List Report</div>
              <div class="rpt-tile-desc">Products list with name, cost, price, category, stock &amp; stock valuation</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('customer')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-customer"><i class="ti ti-users"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Customer List Report</div>
              <div class="rpt-tile-desc">All customers with contact details, credit limit and balance</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('vendor')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-sales"><i class="ti ti-building-store"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Vendor List Report</div>
              <div class="rpt-tile-desc">All vendors with contact details, credit limit and balance</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('sales')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-sales-ret"><i class="ti ti-receipt"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Detailed Sales Report</div>
              <div class="rpt-tile-desc">Sales invoices with line items grouped by invoice number</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('purchase')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-purchase"><i class="ti ti-truck-delivery"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Detailed Purchase Report</div>
              <div class="rpt-tile-desc">Purchase orders with line items grouped by PO number</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('salesReturn')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-purch-ret"><i class="ti ti-receipt-refund"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Sales Return Report</div>
              <div class="rpt-tile-desc">Sales returns with line items grouped by credit memo number</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('purchaseReturn')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-purch-ret"><i class="ti ti-truck-return"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Purchase Return Report</div>
              <div class="rpt-tile-desc">Purchase returns with line items grouped by debit memo number</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('salesByCustomer')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-party"><i class="ti ti-users-group"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Sales by Customer Report</div>
              <div class="rpt-tile-desc">Total sales grouped by customer with invoice breakdown</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('purchaseByVendor')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-sales"><i class="ti ti-building-store"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Purchase by Vendor Report</div>
              <div class="rpt-tile-desc">Total purchases grouped by vendor with PO breakdown</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('profitLoss')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-inventory"><i class="ti ti-chart-line"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Profit &amp; Loss</div>
              <div class="rpt-tile-desc">Income statement showing revenues, COGS, expenses and net profit</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('balanceSheet')">
            <div class="rpt-tile-icon" class="rpt-tile-icon-purchase"><i class="ti ti-scale"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Balance Sheet</div>
              <div class="rpt-tile-desc">Assets, liabilities and equity position at a specific date</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
          <div class="rpt-tile" onclick="rptOpen('journal')">
            <div class="rpt-tile-icon rpt-tile-icon-journal"><i class="ti ti-notebook"></i></div>
            <div class="rpt-tile-body">
              <div class="rpt-tile-name">Journal Entries Report</div>
              <div class="rpt-tile-desc">All journal entries with debit/credit lines, balance check, and discrepancy detection</div>
            </div>
            <div class="rpt-tile-arrow"><i class="ti ti-chevron-right"></i></div>
          </div>
        </div>
      </div>

      {{-- Product Report Panel --}}
      <div id="rpt-product-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-package me-2" class="rpt-icon-inventory"></i>Product Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportProductExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportProductPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">Product Name / SKU</label>
              <input type="text" class="form-control inv-input" id="rptProdSearch" placeholder="Search..." class="erp-filter-w-190" oninput="runProductReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Category</label>
              <select class="form-select inv-input" id="rptProdCategory" class="erp-filter-w-160" onchange="runProductReport()">
                <option value="">All Categories</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Stock Filter</label>
              <select class="form-select inv-input" id="rptProdStock" class="erp-filter-w-160" onchange="runProductReport()">
                <option value="">All Products</option>
                <option value="in">In Stock Only</option>
                <option value="low">Low Stock Only</option>
                <option value="out">Out of Stock</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">As of Date</label>
              <input type="date" class="form-control inv-input" id="rptProdDate" class="erp-filter-w-150" onchange="runProductReport()">
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptProdClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runProductReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Product Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptPrintParams"></div>
        </div>

        <div id="rpt-product-results">
          <div class="table-responsive">
            <table class="table table-hover table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" style="width:28px">#</th>
                  <th class="inv-th" style="width:120px">Item No.</th>
                  <th class="inv-th" style="width:200px">Product Name</th>
                  <th class="inv-th" style="width:130px">SKU</th>
                  <th class="inv-th" style="width:150px">Category</th>
                  <th class="inv-th text-end" style="width:110px">Cost Price</th>
                  <th class="inv-th text-end" style="width:110px">Sale Price</th>
                  <th class="inv-th text-end" style="width:90px">Stock</th>
                  <th class="inv-th text-end" style="width:120px">Stock Value</th>
                </tr>
              </thead>
              <tbody id="rptProductBody"></tbody>
              <tfoot id="rptProductFoot"></tfoot>
            </table>
          </div>
          <div id="rptProductSummary"></div>
        </div>
      </div>

      {{-- Customer Report Panel --}}
      <div id="rpt-customer-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-users me-2" class="rpt-icon-success"></i>Customer List Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportCustomerExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportCustomerPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">Customer Name / Code</label>
              <input type="text" class="form-control inv-input" id="rptCustSearch" placeholder="Search..." style="width:210px;" oninput="runCustomerReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Balance Filter</label>
              <select class="form-select inv-input" id="rptCustBalance" class="erp-filter-w-190" onchange="runCustomerReport()">
                <option value="">All Customers</option>
                <option value="positive">With Balance (Debit)</option>
                <option value="negative">With Credit</option>
                <option value="zero">Zero Balance</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptCustClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runCustomerReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptCustPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Customer List Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptCustPrintParams"></div>
        </div>

        <div id="rpt-customer-results">
          <div class="table-responsive">
            <table class="table table-hover table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" style="width:28px">#</th>
                  <th class="inv-th" style="width:120px">Code</th>
                  <th class="inv-th" style="width:160px">Name</th>
                  <th class="inv-th" style="width:130px">Phone</th>
                  <th class="inv-th">Email</th>
                  <th class="inv-th" style="width:180px">Address</th>
                  <th class="inv-th">Payment Terms</th>
                  <th class="inv-th text-end">Credit Limit</th>
                  <th class="inv-th text-end">Opening Bal.</th>
                  <th class="inv-th" style="width:200px">Bank Details</th>
                  <th class="inv-th text-end">Balance</th>
                </tr>
              </thead>
              <tbody id="rptCustomerBody"></tbody>
              <tfoot id="rptCustomerFoot"></tfoot>
            </table>
          </div>
          <div id="rptCustomerSummary"></div>
        </div>
      </div>

      {{-- Vendor Report Panel --}}
      <div id="rpt-vendor-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-building-store me-2" class="rpt-icon-sales"></i>Vendor List Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportVendorExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportVendorPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">Vendor Name / Code</label>
              <input type="text" class="form-control inv-input" id="rptVendSearch" placeholder="Search..." style="width:210px;" oninput="runVendorReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Balance Filter</label>
              <select class="form-select inv-input" id="rptVendBalance" class="erp-filter-w-190" onchange="runVendorReport()">
                <option value="">All Vendors</option>
                <option value="positive">With Balance (Payable)</option>
                <option value="negative">With Credit</option>
                <option value="zero">Zero Balance</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptVendClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runVendorReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptVendPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Vendor List Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptVendPrintParams"></div>
        </div>

        <div id="rpt-vendor-results">
          <div class="table-responsive">
            <table class="table table-hover table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" style="width:28px">#</th>
                  <th class="inv-th" style="width:120px">Code</th>
                  <th class="inv-th" style="width:160px">Name</th>
                  <th class="inv-th" style="width:130px">Phone</th>
                  <th class="inv-th">Email</th>
                  <th class="inv-th" style="width:180px">Address</th>
                  <th class="inv-th">Payment Terms</th>
                  <th class="inv-th text-end">Credit Limit</th>
                  <th class="inv-th text-end">Opening Bal.</th>
                  <th class="inv-th" style="width:200px">Bank Details</th>
                  <th class="inv-th text-end">Balance</th>
                </tr>
              </thead>
              <tbody id="rptVendorBody"></tbody>
              <tfoot id="rptVendorFoot"></tfoot>
            </table>
          </div>
          <div id="rptVendorSummary"></div>
        </div>
      </div>

      {{-- Sales Report Panel --}}
      <div id="rpt-sales-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-receipt me-2" class="rpt-icon-sales"></i>Detailed Sales Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportSalesExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportSalesPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptSalesFrom" class="erp-filter-w-150" onchange="runSalesReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptSalesTo" class="erp-filter-w-150" onchange="runSalesReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Customer</label>
              <select class="form-select inv-input" id="rptSalesCustomer" class="erp-filter-w-160" onchange="runSalesReport()">
                <option value="">All Customers</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Payment Method</label>
              <select class="form-select inv-input" id="rptSalesPayment" class="erp-filter-w-150" onchange="runSalesReport()">
                <option value="">All Methods</option>
                <option value="Cash">Cash</option>
                <option value="Credit">Credit</option>
                <option value="Card">Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Invoice No.</label>
              <input type="text" class="form-control inv-input" id="rptSalesSearch" placeholder="Search..." class="erp-filter-w-150" oninput="runSalesReport()">
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptSalesClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runSalesReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptSalesPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Detailed Sales Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptSalesPrintParams"></div>
        </div>

        <div id="rpt-sales-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" class="erp-filter-w-150">Invoice No.</th>
                  <th class="inv-th" class="erp-filter-w-160">Customer</th>
                  <th class="inv-th">Product Name</th>
                  <th class="inv-th text-end" style="width:60px;">Qty</th>
                  <th class="inv-th text-end" style="width:120px;">Unit Price</th>
                  <th class="inv-th text-end" style="width:130px;">Total</th>
                  <th class="inv-th text-end" class="erp-filter-w-160">Date &amp; Time</th>
                </tr>
              </thead>
              <tbody id="rptSalesBody"></tbody>
              <tfoot id="rptSalesFoot"></tfoot>
            </table>
          </div>
          <div id="rptSalesSummary"></div>
        </div>
      </div>

      {{-- Purchase Report Panel --}}
      <div id="rpt-purchase-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-truck-delivery me-2" class="rpt-icon-purchase"></i>Detailed Purchase Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportPurchaseExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportPurchasePDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptPurchFrom" class="erp-filter-w-150" onchange="runPurchaseReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptPurchTo" class="erp-filter-w-150" onchange="runPurchaseReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Vendor</label>
              <select class="form-select inv-input" id="rptPurchVendor" class="erp-filter-w-160" onchange="runPurchaseReport()">
                <option value="">All Vendors</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Status</label>
              <select class="form-select inv-input" id="rptPurchStatus" class="erp-filter-w-160" onchange="runPurchaseReport()">
                <option value="">All Statuses</option>
                <option value="Draft">Draft</option>
                <option value="Partially Received">Partially Received</option>
                <option value="Received">Received</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Returned">Returned</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">PO No.</label>
              <input type="text" class="form-control inv-input" id="rptPurchSearch" placeholder="Search..." class="erp-filter-w-150" oninput="runPurchaseReport()">
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptPurchClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runPurchaseReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptPurchPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Detailed Purchase Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptPurchPrintParams"></div>
        </div>

        <div id="rpt-purchase-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" class="erp-filter-w-150">PO No.</th>
                  <th class="inv-th" class="erp-filter-w-160">Vendor</th>
                  <th class="inv-th">Product Name</th>
                  <th class="inv-th text-end" style="width:60px;">Qty</th>
                  <th class="inv-th text-end" style="width:120px;">Unit Cost</th>
                  <th class="inv-th text-end" style="width:130px;">Total</th>
                  <th class="inv-th text-end" class="erp-filter-w-160">Date &amp; Time</th>
                </tr>
              </thead>
              <tbody id="rptPurchaseBody"></tbody>
              <tfoot id="rptPurchaseFoot"></tfoot>
            </table>
          </div>
          <div id="rptPurchaseSummary"></div>
        </div>
      </div>

      {{-- Sales Return Report Panel --}}
      <div id="rpt-salesReturn-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-receipt-refund me-2" class="rpt-icon-purch-ret-color"></i>Sales Return Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportSalesReturnExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportSalesReturnPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptSReturnFrom" class="erp-filter-w-150" onchange="runSalesReturnReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptSReturnTo" class="erp-filter-w-150" onchange="runSalesReturnReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Customer</label>
              <select class="form-select inv-input" id="rptSReturnCustomer" class="erp-filter-w-160" onchange="runSalesReturnReport()">
                <option value="">All Customers</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Credit Memo No.</label>
              <input type="text" class="form-control inv-input" id="rptSReturnSearch" placeholder="Search..." class="erp-filter-w-150" oninput="runSalesReturnReport()">
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptSReturnClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runSalesReturnReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptSReturnPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Sales Return Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptSReturnPrintParams"></div>
        </div>

        <div id="rpt-salesReturn-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" class="erp-filter-w-160">Credit Memo No.</th>
                  <th class="inv-th" class="erp-filter-w-160">Customer</th>
                  <th class="inv-th">Product Name</th>
                  <th class="inv-th text-end" style="width:60px;">Qty</th>
                  <th class="inv-th text-end" style="width:120px;">Unit Price</th>
                  <th class="inv-th text-end" style="width:130px;">Total</th>
                  <th class="inv-th text-end" class="erp-filter-w-160">Date &amp; Time</th>
                </tr>
              </thead>
              <tbody id="rptSReturnBody"></tbody>
              <tfoot id="rptSReturnFoot"></tfoot>
            </table>
          </div>
          <div id="rptSReturnSummary"></div>
        </div>
      </div>

      {{-- Purchase Return Report Panel --}}
      <div id="rpt-purchaseReturn-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-truck-return me-2" class="rpt-icon-purch-color"></i>Purchase Return Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportPurchaseReturnExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportPurchaseReturnPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptPReturnFrom" class="erp-filter-w-150" onchange="runPurchaseReturnReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptPReturnTo" class="erp-filter-w-150" onchange="runPurchaseReturnReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Vendor</label>
              <select class="form-select inv-input" id="rptPReturnVendor" class="erp-filter-w-160" onchange="runPurchaseReturnReport()">
                <option value="">All Vendors</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Debit Memo No.</label>
              <input type="text" class="form-control inv-input" id="rptPReturnSearch" placeholder="Search..." class="erp-filter-w-150" oninput="runPurchaseReturnReport()">
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptPReturnClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runPurchaseReturnReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptPReturnPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Purchase Return Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptPReturnPrintParams"></div>
        </div>

        <div id="rpt-purchaseReturn-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" class="erp-filter-w-160">Debit Memo No.</th>
                  <th class="inv-th" class="erp-filter-w-160">Vendor</th>
                  <th class="inv-th">Product Name</th>
                  <th class="inv-th text-end" style="width:60px;">Qty</th>
                  <th class="inv-th text-end" style="width:120px;">Unit Cost</th>
                  <th class="inv-th text-end" style="width:130px;">Total</th>
                  <th class="inv-th text-end" class="erp-filter-w-160">Date &amp; Time</th>
                </tr>
              </thead>
              <tbody id="rptPReturnBody"></tbody>
              <tfoot id="rptPReturnFoot"></tfoot>
            </table>
          </div>
          <div id="rptPReturnSummary"></div>
        </div>
      </div>

      {{-- Sales by Customer Report Panel --}}
      <div id="rpt-salesByCustomer-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-users-group me-2" class="rpt-icon-success"></i>Sales by Customer Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportSalesByCustomerExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportSalesByCustomerPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptSBCFrom" class="erp-filter-w-150" onchange="runSalesByCustomerReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptSBCTo" class="erp-filter-w-150" onchange="runSalesByCustomerReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Customer</label>
              <select class="form-select inv-input" id="rptSBCCustomer" class="erp-filter-w-160" onchange="runSalesByCustomerReport()">
                <option value="">All Customers</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Payment Method</label>
              <select class="form-select inv-input" id="rptSBCPayment" class="erp-filter-w-150" onchange="runSalesByCustomerReport()">
                <option value="">All Methods</option>
                <option value="Cash">Cash</option>
                <option value="Credit">Credit</option>
                <option value="Card">Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptSBCClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runSalesByCustomerReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptSBCPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Sales by Customer Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptSBCPrintParams"></div>
        </div>

        <div id="rpt-salesByCustomer-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" style="width:180px;">Customer</th>
                  <th class="inv-th" class="erp-filter-w-150">Invoice No.</th>
                  <th class="inv-th" style="width:120px;">Payment Method</th>
                  <th class="inv-th text-end" style="width:60px;">Items</th>
                  <th class="inv-th text-end" style="width:130px;">Amount</th>
                  <th class="inv-th text-end" class="erp-filter-w-160">Date &amp; Time</th>
                </tr>
              </thead>
              <tbody id="rptSBCBody"></tbody>
              <tfoot id="rptSBCFoot"></tfoot>
            </table>
          </div>
          <div id="rptSBCSummary"></div>
        </div>
      </div>

      {{-- Purchase by Vendor Report Panel --}}
      <div id="rpt-purchaseByVendor-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-building-store me-2" class="rpt-icon-sales"></i>Purchase by Vendor Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportPurchaseByVendorExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportPurchaseByVendorPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptPBVFrom" class="erp-filter-w-150" onchange="runPurchaseByVendorReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptPBVTo" class="erp-filter-w-150" onchange="runPurchaseByVendorReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Vendor</label>
              <select class="form-select inv-input" id="rptPBVVendor" class="erp-filter-w-160" onchange="runPurchaseByVendorReport()">
                <option value="">All Vendors</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Status</label>
              <select class="form-select inv-input" id="rptPBVStatus" class="erp-filter-w-150" onchange="runPurchaseByVendorReport()">
                <option value="">All Statuses</option>
                <option value="Draft">Draft</option>
                <option value="Partially Received">Partially Received</option>
                <option value="Received">Received</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Returned">Returned</option>
              </select>
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptPBVClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runPurchaseByVendorReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top" class="erp-print-header">
          <div class="rpt-print-company" class="rpt-print-company-name" id="rptPBVPrintCompany"></div>
          <div class="rpt-print-title" class="rpt-print-report-title">Purchase by Vendor Report</div>
          <div class="rpt-print-params" class="rpt-print-params" id="rptPBVPrintParams"></div>
        </div>

        <div id="rpt-purchaseByVendor-results">
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table">
              <thead>
                <tr>
                  <th class="inv-th" style="width:180px;">Vendor</th>
                  <th class="inv-th" class="erp-filter-w-150">PO No.</th>
                  <th class="inv-th" style="width:130px;">Status</th>
                  <th class="inv-th text-end" style="width:60px;">Items</th>
                  <th class="inv-th text-end" style="width:130px;">Amount</th>
                  <th class="inv-th text-end" class="erp-filter-w-160">Date &amp; Time</th>
                </tr>
              </thead>
              <tbody id="rptPBVBody"></tbody>
              <tfoot id="rptPBVFoot"></tfoot>
            </table>
          </div>
          <div id="rptPBVSummary"></div>
        </div>
      </div>

      {{-- Profit & Loss Panel --}}
      <div id="rpt-profitLoss-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-chart-line me-2"></i>Profit &amp; Loss</span>
          <div class="d-flex gap-2">
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>
        <div class="rpt-filter-bar d-print-none">
          <div class="d-flex align-items-end flex-wrap gap-2">
            <div>
              <label class="pm-label">From Date</label>
              <input type="date" class="form-control inv-input" id="rptPlFrom" style="min-width:160px;">
            </div>
            <div>
              <label class="pm-label">To Date</label>
              <input type="date" class="form-control inv-input" id="rptPlTo" style="min-width:160px;">
            </div>
            <button class="btn btn-primary rpt-btn" onclick="runProfitLoss()"><i class="ti ti-search me-1"></i>Generate</button>
            <div class="vr mx-1 align-self-center" style="height:28px;"></div>
            <button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('month')">This Month</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('quarter')">This Quarter</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('year')">This Year</button>
          </div>
        </div>
        <div id="rptPlLoading" class="text-center py-5 d-none">
          <div class="spinner-border text-primary"></div>
          <div class="mt-2 text-muted" style="font-size:0.85rem;">Generating report...</div>
        </div>
        <div id="rptPlReport" class="d-none" style="padding:16px;">
          <div class="card inv-section-card">
            <div class="set-card-header d-flex justify-content-between align-items-center">
              <span><i class="ti ti-chart-line me-2 text-green"></i>Profit &amp; Loss Statement</span>
              <span id="rptPlPeriodLabel" class="text-muted" style="font-size:0.8rem;font-weight:400;"></span>
            </div>
            <div class="table-responsive">
              <table class="table table-vcenter inv-table mb-0">
                <thead><tr><th class="inv-th">Account</th><th class="inv-th text-end" style="width:160px;">Amount</th></tr></thead>
                <tbody id="rptPlBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {{-- Balance Sheet Panel --}}
      <div id="rpt-balanceSheet-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-scale me-2"></i>Balance Sheet</span>
          <div class="d-flex gap-2">
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>
        <div class="rpt-filter-bar d-print-none">
          <div class="d-flex align-items-end flex-wrap gap-2">
            <div>
              <label class="pm-label">As of Date</label>
              <input type="date" class="form-control inv-input" id="rptBsAsOf" style="min-width:180px;">
            </div>
            <button class="btn btn-primary rpt-btn" onclick="runBalanceSheet()"><i class="ti ti-search me-1"></i>Generate</button>
            <div class="vr mx-1 align-self-center" style="height:28px;"></div>
            <button class="btn btn-sm btn-outline-secondary" onclick="rptBsSetDate('today')">Today</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rptBsSetDate('monthEnd')">Month End</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rptBsSetDate('yearEnd')">Year End</button>
          </div>
        </div>
        <div id="rptBsLoading" class="text-center py-5 d-none">
          <div class="spinner-border text-primary"></div>
          <div class="mt-2 text-muted" style="font-size:0.85rem;">Generating report...</div>
        </div>
        <div id="rptBsReport" class="d-none rpt-bs-results">
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="card inv-section-card h-100">
                <div class="set-card-header"><i class="ti ti-building-bank me-2 text-blue"></i>Assets</div>
                <div class="table-responsive">
                  <table class="table table-vcenter inv-table mb-0">
                    <tbody id="rptBsAssetsBody"></tbody>
                    <tfoot><tr class="bs-total-row"><td>Total Assets</td><td class="text-end" id="rptBsTotalAssets">—</td></tr></tfoot>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card inv-section-card h-100">
                <div class="set-card-header"><i class="ti ti-receipt me-2 text-orange"></i>Liabilities &amp; Equity</div>
                <div class="table-responsive">
                  <table class="table table-vcenter inv-table mb-0">
                    <tbody id="rptBsLiabEquityBody"></tbody>
                    <tfoot><tr class="bs-total-row"><td>Total Liabilities &amp; Equity</td><td class="text-end" id="rptBsTotalLiabEquity">—</td></tr></tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="card inv-section-card">
            <div class="set-card-body" id="rptBsBalanceCheck"></div>
          </div>
        </div>
      </div>

      {{-- Journal Entries Report Panel --}}
      <div id="rpt-journal-panel" class="d-none">
        <div class="rpt-report-header d-print-none">
          <button class="btn btn-light btn-sm" onclick="rptBack()"><i class="ti ti-arrow-left me-1"></i>Back</button>
          <span class="rpt-report-title"><i class="ti ti-notebook me-2 rpt-icon-journal-color"></i>Journal Entries Report</span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm rpt-export-btn rpt-excel-btn" onclick="exportJournalExcel()" title="Export to Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm rpt-export-btn rpt-pdf-btn" onclick="exportJournalPDF()" title="Export to PDF"><i class="ti ti-file-type-pdf me-1"></i>PDF</button>
            <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ti ti-printer me-1"></i>Print</button>
          </div>
        </div>

        <div class="rpt-filter-bar d-print-none">
          <div class="row g-2 align-items-end">
            <div class="col-auto">
              <label class="pm-label">From</label>
              <input type="date" class="form-control inv-input" id="rptJrnFrom" onchange="runJournalReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">To</label>
              <input type="date" class="form-control inv-input" id="rptJrnTo" onchange="runJournalReport()">
            </div>
            <div class="col-auto">
              <label class="pm-label">Type</label>
              <select class="form-select inv-input" id="rptJrnType" onchange="runJournalReport()">
                <option value="">All Types</option>
                <option value="manual">Manual</option>
                <option value="auto">Auto-Posted</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Status</label>
              <select class="form-select inv-input" id="rptJrnStatus" onchange="runJournalReport()">
                <option value="">All Statuses</option>
                <option value="posted">Posted</option>
                <option value="draft">Draft</option>
              </select>
            </div>
            <div class="col-auto">
              <label class="pm-label">Entry No. / Description</label>
              <input type="text" class="form-control inv-input" id="rptJrnSearch" placeholder="Search..." style="width:210px;" oninput="rptJournalClientFilter()">
            </div>
            <div class="col-auto">
              <button class="btn btn-light inv-input px-3 me-1" onclick="rptJrnClear()" title="Clear filters"><i class="ti ti-x"></i></button>
              <button class="btn btn-primary rpt-btn" onclick="runJournalReport()"><i class="ti ti-player-play me-1"></i>Run Report</button>
            </div>
          </div>
        </div>

        {{-- Print-only header --}}
        <div class="d-none d-print-block rpt-print-top">
          <div class="rpt-print-company" id="rptJrnPrintCompany"></div>
          <div class="rpt-print-title">Journal Entries Report</div>
          <div class="rpt-print-params" id="rptJrnPrintParams"></div>
        </div>

        <div id="rptJrnLoading" class="text-center py-5 d-none">
          <div class="spinner-border text-primary"></div>
          <div class="mt-2 text-muted" style="font-size:0.85rem;">Loading journal entries...</div>
        </div>

        <div id="rpt-journal-results" class="d-none">
          {{-- Discrepancy alert --}}
          <div id="rptJrnDiscrepancy" class="d-none"></div>

          {{-- Grand total balance check --}}
          <div id="rptJrnBalanceCheck" class="d-none"></div>

          {{-- Entries table --}}
          <div class="table-responsive">
            <table class="table table-vcenter inv-table mb-0 rpt-compact-table rpt-journal-table">
              <thead>
                <tr>
                  <th class="inv-th" style="width:110px;">Entry No.</th>
                  <th class="inv-th" style="width:95px;">Date</th>
                  <th class="inv-th" style="width:120px;">Doc. No.</th>
                  <th class="inv-th" style="width:150px;">Customer / Vendor</th>
                  <th class="inv-th" style="width:100px;">Ref. Type</th>
                  <th class="inv-th">Description / Account</th>
                  <th class="inv-th" style="width:90px;">Status</th>
                  <th class="inv-th text-end" style="width:120px;">Debit</th>
                  <th class="inv-th text-end" style="width:120px;">Credit</th>
                </tr>
              </thead>
              <tbody id="rptJrnBody"></tbody>
              <tfoot id="rptJrnFoot"></tfoot>
            </table>
          </div>

          <div id="rptJrnSummary"></div>
        </div>
      </div>

    </div>{{-- end reportsTab --}}

  </div>
</div>

</div>
@endsection
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.css">
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script src="{{ asset('js/pages/reports.js') }}?v={{ filemtime(public_path('js/pages/reports.js')) }}"></script>
@endpush
