<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ErpSeeder extends Seeder
{
    public function run(): void
    {
        $coId = 'tenant-1';
        $now = Carbon::now();
        $nowMs = (int)(microtime(true) * 1000);
        $day = 24 * 60 * 60 * 1000;

        DB::table('companies')->insert([
            'id' => $coId,
            'name' => 'Demo Business Corp',
            'status' => 'Active',
            'max_user_limit' => 10,
            'registration_payment' => 5000,
            'saas_plan' => 'Annually',
            'info_name' => 'Demo Business Corp',
            'info_tagline' => 'Streamlining Growth with LeanERP',
            'info_address' => 'Suite 204, Tech Plaza, Shara-e-Faisal, Karachi',
            'info_phone' => '021-3445566',
            'info_email' => 'hello@democorp.io',
            'info_website' => 'www.democorp.io',
            'info_tax_id' => 'STR-229988-1',
            'info_logo_url' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')->insert([
            [
                'id' => 'sys-admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'system_role' => 'Super Admin',
                'role_id' => null,
                'company_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'u1',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'system_role' => 'Company Admin',
                'role_id' => null,
                'company_id' => $coId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'u2',
                'username' => 'sales_user',
                'password' => Hash::make('password'),
                'system_role' => 'Standard User',
                'role_id' => null,
                'company_id' => $coId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('categories')->insert([
            ['id' => 'cat-1', 'company_id' => $coId, 'name' => 'Electronics', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'cat-2', 'company_id' => $coId, 'name' => 'Furniture', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'cat-3', 'company_id' => $coId, 'name' => 'Stationery', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('units_of_measure')->insert([
            ['id' => 'uom-1', 'company_id' => $coId, 'name' => 'Pcs', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'uom-2', 'company_id' => $coId, 'name' => 'Kg', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'uom-3', 'company_id' => $coId, 'name' => 'Box', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('entity_types')->insert([
            ['id' => 'e1', 'name' => 'Individual', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'e2', 'name' => 'Business', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('business_categories')->insert([
            ['id' => 'b1', 'name' => 'Wholesale', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'b2', 'name' => 'Retail', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'b3', 'name' => 'Corporate', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('products')->insert([
            ['id' => 'PRD-1', 'company_id' => $coId, 'sku' => 'LAP-001', 'name' => 'MacBook Pro 14"', 'type' => 'Product', 'uom' => 'Pcs', 'category_id' => 'cat-1', 'current_stock' => 8, 'reorder_level' => 2, 'unit_cost' => 180000, 'unit_price' => 215000, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'PRD-2', 'company_id' => $coId, 'sku' => 'MS-023', 'name' => 'Logitech MX Master 3S', 'type' => 'Product', 'uom' => 'Pcs', 'category_id' => 'cat-1', 'current_stock' => 15, 'reorder_level' => 5, 'unit_cost' => 12000, 'unit_price' => 18500, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'PRD-3', 'company_id' => $coId, 'sku' => 'DSK-99', 'name' => 'Ergonomic Standing Desk', 'type' => 'Product', 'uom' => 'Pcs', 'category_id' => 'cat-2', 'current_stock' => 4, 'reorder_level' => 1, 'unit_cost' => 35000, 'unit_price' => 52000, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'PRD-4', 'company_id' => $coId, 'sku' => 'CH-45', 'name' => 'Aeron Executive Chair', 'type' => 'Product', 'uom' => 'Pcs', 'category_id' => 'cat-2', 'current_stock' => 0, 'reorder_level' => 2, 'unit_cost' => 85000, 'unit_price' => 115000, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'PRD-5', 'company_id' => $coId, 'sku' => 'ST-BOOK', 'name' => 'Leather Bound Journal', 'type' => 'Product', 'uom' => 'Box', 'category_id' => 'cat-3', 'current_stock' => 42, 'reorder_level' => 10, 'unit_cost' => 450, 'unit_price' => 1200, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'PRD-6', 'company_id' => $coId, 'sku' => 'SRV-01', 'name' => 'IT Support (Hourly)', 'type' => 'Service', 'uom' => 'Pcs', 'category_id' => 'cat-1', 'current_stock' => 0, 'reorder_level' => 0, 'unit_cost' => 0, 'unit_price' => 2500, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('parties')->insert([
            [
                'id' => 'PT-1', 'company_id' => $coId, 'code' => 'CUST-001', 'type' => 'Customer',
                'name' => 'Alice Henderson', 'phone' => '0300-1122334', 'email' => 'alice@example.com',
                'address' => 'Apartment 4B, Blue Tower, Karachi', 'sub_type' => 'Individual',
                'payment_terms' => 'Net 30', 'credit_limit' => 500000, 'bank_details' => 'HBL-001223344',
                'category' => 'Retail', 'opening_balance' => 0, 'current_balance' => 145000,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'PT-2', 'company_id' => $coId, 'code' => 'CUST-002', 'type' => 'Customer',
                'name' => 'Tech Solutions Ltd', 'phone' => '021-3455667', 'email' => 'billing@techsol.com',
                'address' => 'Plot 23, Industrial Area, Lahore', 'sub_type' => 'Business',
                'payment_terms' => 'Net 15', 'credit_limit' => 2000000, 'bank_details' => 'Meezan-998877',
                'category' => 'Wholesale', 'opening_balance' => 0, 'current_balance' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'PT-3', 'company_id' => $coId, 'code' => 'VND-001', 'type' => 'Vendor',
                'name' => 'Global Tech Wholesale', 'phone' => '042-9988112', 'email' => 'sales@globaltech.com',
                'address' => 'Suite 9, Commercial Plaza, Islamabad', 'sub_type' => 'Business',
                'payment_terms' => 'COD', 'credit_limit' => 0, 'bank_details' => 'Standard Chartered-1122',
                'category' => 'Wholesale', 'opening_balance' => 0, 'current_balance' => 85000,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $fourDaysAgo = Carbon::now()->subDays(4);
        $twoDaysAgo = Carbon::now()->subDays(2);
        $oneDayAgo = Carbon::now()->subDays(1);
        $tenDaysAgo = Carbon::now()->subDays(10);

        DB::table('sale_orders')->insert([
            ['id' => 'INV-1001', 'company_id' => $coId, 'customer_id' => 'PT-1', 'created_at' => $fourDaysAgo, 'payment_method' => 'Card', 'total_amount' => 215000, 'is_returned' => false, 'updated_at' => $fourDaysAgo],
            ['id' => 'INV-1002', 'company_id' => $coId, 'customer_id' => 'PT-2', 'created_at' => $twoDaysAgo, 'payment_method' => 'Cash', 'total_amount' => 37000, 'is_returned' => false, 'updated_at' => $twoDaysAgo],
            ['id' => 'INV-1003', 'company_id' => $coId, 'customer_id' => null, 'created_at' => $oneDayAgo, 'payment_method' => 'Cash', 'total_amount' => 1200, 'is_returned' => false, 'updated_at' => $oneDayAgo],
        ]);

        DB::table('sale_items')->insert([
            ['id' => 'SI-1', 'sale_order_id' => 'INV-1001', 'product_id' => 'PRD-1', 'quantity' => 1, 'unit_price' => 215000, 'discount' => 0, 'total_line_price' => 215000, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'SI-2', 'sale_order_id' => 'INV-1002', 'product_id' => 'PRD-2', 'quantity' => 2, 'unit_price' => 18500, 'discount' => 0, 'total_line_price' => 37000, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'SI-3', 'sale_order_id' => 'INV-1003', 'product_id' => 'PRD-5', 'quantity' => 1, 'unit_price' => 1200, 'discount' => 0, 'total_line_price' => 1200, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('purchase_orders')->insert([
            ['id' => 'PO-5001', 'company_id' => $coId, 'vendor_id' => 'PT-3', 'created_at' => $tenDaysAgo, 'status' => 'Received', 'total_amount' => 180000, 'updated_at' => $tenDaysAgo],
            ['id' => 'PO-5002', 'company_id' => $coId, 'vendor_id' => 'PT-3', 'created_at' => $oneDayAgo, 'status' => 'Draft', 'total_amount' => 120000, 'updated_at' => $oneDayAgo],
        ]);

        DB::table('purchase_items')->insert([
            ['id' => 'PI-1', 'purchase_order_id' => 'PO-5001', 'product_id' => 'PRD-1', 'quantity' => 1, 'unit_cost' => 180000, 'total_line_cost' => 180000, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'PI-2', 'purchase_order_id' => 'PO-5002', 'product_id' => 'PRD-2', 'quantity' => 10, 'unit_cost' => 12000, 'total_line_cost' => 120000, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $threeDaysAgo = Carbon::now()->subDays(3);
        $fiveDaysAgo = Carbon::now()->subDays(5);

        DB::table('payments')->insert([
            [
                'id' => 'PAY-1', 'company_id' => $coId, 'party_id' => 'PT-1',
                'date' => $nowMs - (3 * $day), 'amount' => 70000,
                'payment_method' => 'Bank', 'type' => 'Receipt',
                'reference_no' => 'INV-1001', 'notes' => 'Partial payment',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'PAY-2', 'company_id' => $coId, 'party_id' => 'PT-3',
                'date' => $nowMs - (5 * $day), 'amount' => 95000,
                'payment_method' => 'Bank', 'type' => 'Payment',
                'reference_no' => 'PO-5001', 'notes' => 'Vendor payment',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $thirtyDaysAgo = Carbon::now()->subDays(30);

        DB::table('inventory_ledger')->insert([
            ['id' => 'LEG-1', 'company_id' => $coId, 'product_id' => 'PRD-1', 'created_at' => $tenDaysAgo, 'transaction_type' => 'Purchase_Receive', 'quantity_change' => 10, 'reference_id' => 'PO-5001', 'updated_at' => $tenDaysAgo],
            ['id' => 'LEG-2', 'company_id' => $coId, 'product_id' => 'PRD-1', 'created_at' => $fourDaysAgo, 'transaction_type' => 'Sale', 'quantity_change' => -1, 'reference_id' => 'INV-1001', 'updated_at' => $fourDaysAgo],
            ['id' => 'LEG-3', 'company_id' => $coId, 'product_id' => 'PRD-2', 'created_at' => $thirtyDaysAgo, 'transaction_type' => 'Adjustment_Internal', 'quantity_change' => 17, 'reference_id' => 'OPENING', 'updated_at' => $thirtyDaysAgo],
            ['id' => 'LEG-4', 'company_id' => $coId, 'product_id' => 'PRD-2', 'created_at' => $twoDaysAgo, 'transaction_type' => 'Sale', 'quantity_change' => -2, 'reference_id' => 'INV-1002', 'updated_at' => $twoDaysAgo],
            ['id' => 'LEG-5', 'company_id' => $coId, 'product_id' => 'PRD-5', 'created_at' => $thirtyDaysAgo, 'transaction_type' => 'Adjustment_Internal', 'quantity_change' => 43, 'reference_id' => 'OPENING', 'updated_at' => $thirtyDaysAgo],
            ['id' => 'LEG-6', 'company_id' => $coId, 'product_id' => 'PRD-5', 'created_at' => $oneDayAgo, 'transaction_type' => 'Sale', 'quantity_change' => -1, 'reference_id' => 'INV-1003', 'updated_at' => $oneDayAgo],
        ]);

        DB::table('settings')->insert([
            ['key' => 'currency', 'value' => 'Rs.', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'invoiceFormat', 'value' => 'A4', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
