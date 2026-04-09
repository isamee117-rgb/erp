<?php

namespace App\Http\Controllers\Api\Concerns;

trait CamelCaseResponse
{
    protected function toCamel($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
                    $result[$camelKey] = $this->toCamel($value);
                } else {
                    $result[$key] = $this->toCamel($value);
                }
            }
            return $result;
        }

        return $data;
    }

    protected function toSnake($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $snakeKey = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($key)));
                    $result[$snakeKey] = $this->toSnake($value);
                } else {
                    $result[$key] = $this->toSnake($value);
                }
            }
            return $result;
        }

        return $data;
    }

    protected function transformCompany($company)
    {
        $data = $company->toArray();
        $info = [
            'name' => $data['info_name'] ?? $data['name'] ?? '',
            'tagline' => $data['info_tagline'] ?? '',
            'address' => $data['info_address'] ?? '',
            'phone' => $data['info_phone'] ?? '',
            'email' => $data['info_email'] ?? '',
            'website' => $data['info_website'] ?? '',
            'taxId' => $data['info_tax_id'] ?? '',
            'logoUrl' => $data['info_logo_url'] ?? '',
        ];

        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'status' => $data['status'],
            'maxUserLimit' => $data['max_user_limit'],
            'registrationPayment' => (float) $data['registration_payment'],
            'saasPlan' => $data['saas_plan'],
            'info' => $info,
        ];
    }

    protected function transformUser($user)
    {
        $data = is_array($user) ? $user : $user->toArray();
        return [
            'id' => $data['id'],
            'username' => $data['username'],
            'name' => $data['name'] ?? '',
            'systemRole' => $data['system_role'],
            'roleId' => $data['role_id'],
            'companyId' => $data['company_id'],
            'isActive' => $data['is_active'],
        ];
    }

    protected function transformProduct($product)
    {
        $data = is_array($product) ? $product : $product->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'sku' => $data['sku'] ?? '',
            'barcode' => $data['barcode'] ?? '',
            'itemNumber' => $data['item_number'] ?? '',
            'name' => $data['name'],
            'type' => $data['type'],
            'uom' => $data['uom'] ?? '',
            'categoryId' => $data['category_id'],
            'currentStock' => (int) $data['current_stock'],
            'reorderLevel' => (int) $data['reorder_level'],
            'unitCost' => (float) $data['unit_cost'],
            'unitPrice' => (float) $data['unit_price'],
        ];
    }

    protected function transformParty($party)
    {
        $data = is_array($party) ? $party : $party->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'code' => $data['code'] ?? '',
            'type' => $data['type'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
            'subType' => $data['sub_type'] ?? '',
            'paymentTerms' => $data['payment_terms'] ?? '',
            'creditLimit' => (float) ($data['credit_limit'] ?? 0),
            'bankDetails' => $data['bank_details'] ?? '',
            'category' => $data['category'] ?? '',
            'openingBalance' => (float) ($data['opening_balance'] ?? 0),
            'currentBalance' => (float) ($data['current_balance'] ?? 0),
        ];
    }

    protected function transformSale($sale)
    {
        $data = is_array($sale) ? $sale : $sale->toArray();
        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'productId' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unitPrice' => (float) $item['unit_price'],
                'discount' => (float) ($item['discount'] ?? 0),
                'totalLinePrice' => (float) $item['total_line_price'],
                'cogs' => (float) ($item['cogs'] ?? 0),
                'returnedQuantity' => (int) ($item['returned_quantity'] ?? 0),
            ];
        }, $data['items'] ?? []);

        return [
            'id' => $data['invoice_no'] ?? $data['id'],
            'companyId' => $data['company_id'],
            'customerId' => $data['customer_id'],
            'createdAt' => strtotime($data['created_at']) * 1000,
            'paymentMethod' => $data['payment_method'],
            'totalAmount' => (float) $data['total_amount'],
            'items' => $items,
            'isReturned' => $data['is_returned'] ?? false,
            'returnStatus' => $data['return_status'] ?? 'none',
        ];
    }

    protected function transformPurchaseOrder($po)
    {
        $data = is_array($po) ? $po : $po->toArray();
        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'productId' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unitCost' => (float) $item['unit_cost'],
                'totalLineCost' => (float) $item['total_line_cost'],
                'receivedQuantity' => (int) ($item['received_quantity'] ?? 0),
                'returnedQuantity' => (int) ($item['returned_quantity'] ?? 0),
            ];
        }, $data['items'] ?? []);

        $receives = array_map(function ($receive) {
            $receiveItems = array_map(function ($ri) {
                return [
                    'id' => $ri['id'],
                    'purchaseItemId' => $ri['purchase_item_id'],
                    'productId' => $ri['product_id'],
                    'quantity' => (int) $ri['quantity'],
                    'unitCost' => (float) $ri['unit_cost'],
                ];
            }, $receive['items'] ?? []);

            return [
                'id' => $receive['id'],
                'purchaseOrderId' => $receive['purchase_order_id'],
                'createdAt' => strtotime($receive['created_at']) * 1000,
                'notes' => $receive['notes'] ?? '',
                'items' => $receiveItems,
            ];
        }, $data['receives'] ?? []);

        $result = [
            'id' => $data['po_no'] ?? $data['id'],
            'companyId' => $data['company_id'],
            'vendorId' => $data['vendor_id'],
            'createdAt' => strtotime($data['created_at']) * 1000,
            'status' => $data['status'],
            'totalAmount' => (float) $data['total_amount'],
            'receivedAmount' => (float) ($data['received_amount'] ?? 0),
            'returnStatus' => $data['return_status'] ?? 'none',
            'items' => $items,
        ];

        if (!empty($receives)) {
            $result['receives'] = $receives;
        }

        return $result;
    }

    protected function transformPayment($payment)
    {
        $data = is_array($payment) ? $payment : $payment->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'partyId' => $data['party_id'],
            'date' => is_numeric($data['date']) ? $data['date'] : strtotime($data['date']) * 1000,
            'amount' => (float) $data['amount'],
            'paymentMethod' => $data['payment_method'],
            'type' => $data['type'],
            'referenceNo' => $data['reference_no'] ?? '',
            'notes' => $data['notes'] ?? '',
        ];
    }

    protected function transformLedger($entry)
    {
        $data = is_array($entry) ? $entry : $entry->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'productId' => $data['product_id'],
            'createdAt' => strtotime($data['created_at']) * 1000,
            'transactionType' => $data['transaction_type'],
            'quantityChange' => $data['quantity_change'],
            'referenceId' => $data['reference_id'],
        ];
    }

    protected function transformCustomRole($role)
    {
        $data = is_array($role) ? $role : $role->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'permissions' => $data['permissions'] ?? [],
        ];
    }

    protected function transformSaleReturn($sr)
    {
        $data = is_array($sr) ? $sr : $sr->toArray();
        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'productId' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unitPrice' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'totalLinePrice' => $item['total_line_price'],
            ];
        }, $data['items'] ?? []);

        return [
            'id' => $data['return_no'] ?? $data['id'],
            'companyId' => $data['company_id'],
            'originalSaleId' => $data['original_sale_id'],
            'customerId' => $data['customer_id'],
            'createdAt' => strtotime($data['created_at']) * 1000,
            'totalAmount' => (float) $data['total_amount'],
            'items' => $items,
            'reason' => $data['reason'] ?? '',
        ];
    }

    protected function transformPurchaseReturn($pr)
    {
        $data = is_array($pr) ? $pr : $pr->toArray();
        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'productId' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unitCost' => $item['unit_cost'],
                'totalLineCost' => $item['total_line_cost'],
            ];
        }, $data['items'] ?? []);

        return [
            'id' => $data['return_no'] ?? $data['id'],
            'companyId' => $data['company_id'],
            'originalPurchaseId' => $data['original_purchase_id'],
            'vendorId' => $data['vendor_id'],
            'createdAt' => strtotime($data['created_at']) * 1000,
            'totalAmount' => (float) $data['total_amount'],
            'items' => $items,
            'reason' => $data['reason'] ?? '',
        ];
    }

    protected function transformCategory($cat)
    {
        $data = is_array($cat) ? $cat : $cat->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'name' => $data['name'],
        ];
    }

    protected function transformUOM($uom)
    {
        $data = is_array($uom) ? $uom : $uom->toArray();
        return [
            'id' => $data['id'],
            'companyId' => $data['company_id'],
            'name' => $data['name'],
        ];
    }

    protected function transformEntityType($et)
    {
        $data = is_array($et) ? $et : $et->toArray();
        return [
            'id' => $data['id'],
            'name' => $data['name'],
        ];
    }

    protected function transformBusinessCategory($bc)
    {
        $data = is_array($bc) ? $bc : $bc->toArray();
        return [
            'id' => $data['id'],
            'name' => $data['name'],
        ];
    }
}
