<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleOrder;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Services\UomConversionService;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        protected InventoryCostingService $costingService,
        protected DocumentSequenceService $sequenceService,
        protected UomConversionService $uomService,
    ) {}

    public function createSale(User $user, array $data): SaleOrder
    {
        $invoiceNo = $this->sequenceService->getNextNumber($user->company_id, 'sale_invoice');
        $saleUUID  = 'SO-' . Str::random(9);

        $mappedItems = [];
        $subtotal    = 0;

        $customerId    = $data['customerId'] ?? $data['customer_id'] ?? null;
        $paymentMethod = $data['paymentMethod'] ?? $data['payment_method'] ?? 'Cash';

        foreach ($data['items'] ?? [] as $item) {
            $productId   = $item['productId'] ?? $item['product_id'];
            $product     = Product::with('priceTiers')->find($productId);
            $uomId       = $item['uomId'] ?? $item['uom_id'] ?? null;
            $multiplier  = $this->uomService->resolveMultiplier($productId, $uomId);
            // Price per the selected UOM = tier or base unit price × multiplier
            $unitPrice   = $this->resolveTierPrice($product, $customerId) * $multiplier;
            $quantity    = $item['quantity'] ?? 0;
            $discount    = $item['discount'] ?? 0;
            // Base-unit qty used for stock/ledger/costing
            $baseQty     = (int) round($quantity * $multiplier);
            $totalLinePrice = ($unitPrice * $quantity) - $discount;
            $cogs        = $this->costingService->calculateCOGS($user->company_id, $productId, $baseQty);

            $mappedItems[] = [
                'id'               => 'SI-' . Str::random(9),
                'product_id'       => $productId,
                'uom_id'           => $uomId,
                'uom_multiplier'   => $multiplier,
                'quantity'         => $quantity,
                'unit_price'       => $unitPrice,
                'discount'         => $discount,
                'total_line_price' => $totalLinePrice,
                'cogs'             => $cogs,
                '_base_qty'        => $baseQty,  // internal, not persisted
            ];

            $subtotal += $totalLinePrice;
        }

        $sale = SaleOrder::create([
            'id'             => $saleUUID,
            'invoice_no'     => $invoiceNo,
            'company_id'     => $user->company_id,
            'customer_id'    => $customerId,
            'payment_method' => $paymentMethod,
            'total_amount'   => $subtotal,
            'is_returned'    => false,
        ]);

        foreach ($mappedItems as $item) {
            $baseQty = $item['_base_qty'];
            unset($item['_base_qty']);

            SaleItem::create(array_merge($item, ['sale_order_id' => $saleUUID]));

            $product = Product::find($item['product_id']);
            if ($product) {
                $product->current_stock -= $baseQty;
                $product->save();

                InventoryLedger::create([
                    'id'               => 'LEG-' . Str::random(9),
                    'company_id'       => $user->company_id,
                    'product_id'       => $product->id,
                    'transaction_type' => 'Sale',
                    'quantity_change'  => -$baseQty,
                    'reference_id'     => $invoiceNo,
                ]);
            }
        }

        $this->recordSalePayment($user->company_id, $customerId, $subtotal, $paymentMethod, $invoiceNo);

        $sale->load('items');
        return $sale;
    }

    public function createReturn(User $user, array $data): SaleReturn
    {
        $saleId   = $data['saleId'] ?? $data['sale_id'] ?? $data['originalSaleId'] ?? '';
        $returnNo = $this->sequenceService->getNextNumber($user->company_id, 'sale_return');
        $returnUUID = 'SR-' . Str::random(9);

        $originalSale = SaleOrder::with('items')
            ->where('company_id', $user->company_id)
            ->where('invoice_no', $saleId)
            ->first()
            ?? SaleOrder::with('items')
                ->where('company_id', $user->company_id)
                ->where('id', $saleId)
                ->first();

        if (!$originalSale) {
            throw new \RuntimeException('Sale not found');
        }

        [$processedItems, $totalAmount] = $this->buildReturnItems($data['items'] ?? [], $originalSale);

        if (empty($processedItems)) {
            throw new \RuntimeException('No valid items to return');
        }

        $originalInvoiceNo = $originalSale->invoice_no ?? $originalSale->id;

        $saleReturn = SaleReturn::create([
            'id'               => $returnUUID,
            'return_no'        => $returnNo,
            'company_id'       => $user->company_id,
            'original_sale_id' => $originalInvoiceNo,
            'customer_id'      => $originalSale->customer_id ?? null,
            'total_amount'     => $totalAmount,
            'reason'           => $data['reason'] ?? '',
        ]);

        foreach ($processedItems as $pi) {
            $baseReturnQty = (int) round($pi['returnQty'] * $pi['multiplier']);

            SaleReturnItem::create([
                'id'               => 'SRI-' . Str::random(9),
                'sale_return_id'   => $returnUUID,
                'product_id'       => $pi['productId'],
                'uom_id'           => $pi['uomId'],
                'uom_multiplier'   => $pi['multiplier'],
                'quantity'         => $pi['returnQty'],
                'unit_price'       => $pi['unitPrice'],
                'discount'         => $pi['discount'],
                'total_line_price' => $pi['lineTotal'],
            ]);

            $pi['saleItem']->returned_quantity += $pi['returnQty'];
            $pi['saleItem']->save();

            $product = Product::find($pi['productId']);
            if ($product) {
                $product->current_stock += $baseReturnQty;
                $product->save();

                $this->costingService->restoreFIFOLayers(
                    $user->company_id, $pi['productId'], $baseReturnQty, $product->unit_cost, $returnNo
                );

                InventoryLedger::create([
                    'id'               => 'LEG-' . Str::random(9),
                    'company_id'       => $user->company_id,
                    'product_id'       => $pi['productId'],
                    'transaction_type' => 'Sale_Return',
                    'quantity_change'  => $baseReturnQty,
                    'reference_id'     => $returnNo,
                ]);
            }
        }

        $this->updateSaleReturnStatus($originalSale);

        if ($originalSale->customer_id) {
            $party = Party::find($originalSale->customer_id);
            if ($party) {
                $party->current_balance -= $totalAmount;
                $party->save();
            }
        }

        $saleReturn->load('items');
        return $saleReturn;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function recordSalePayment(string $companyId, ?string $customerId, float $total, string $paymentMethod, string $invoiceNo): void
    {
        if ($paymentMethod !== 'Cash') {
            if ($customerId) {
                $party = Party::find($customerId);
                if ($party) {
                    $party->current_balance += $total;
                    $party->save();
                }
            }
            return;
        }

        Payment::create([
            'id'             => 'PAY-' . Str::random(9),
            'company_id'     => $companyId,
            'party_id'       => $customerId,
            'date'           => now()->getTimestampMs(),
            'amount'         => $total,
            'payment_method' => 'Cash',
            'type'           => 'Payment Received',
            'reference_no'   => $invoiceNo,
            'notes'          => $customerId ? 'Auto-recorded from POS cash sale' : 'Auto-recorded from POS cash sale (walk-in)',
        ]);
    }

    private function buildReturnItems(array $items, SaleOrder $originalSale): array
    {
        $processedItems = [];
        $totalAmount    = 0;

        foreach ($items as $item) {
            $productId = $item['productId'] ?? $item['product_id'];
            $returnQty = $item['quantity'] ?? 0;
            if ($returnQty <= 0) continue;

            $saleItem = $originalSale->items->firstWhere('product_id', $productId);
            if (!$saleItem) continue;

            $maxReturnable = $saleItem->quantity - $saleItem->returned_quantity;
            $returnQty     = min($returnQty, $maxReturnable);
            if ($returnQty <= 0) continue;

            // Inherit UOM snapshot from original sale item
            $uomId      = $saleItem->uom_id ?? null;
            $multiplier = (float) ($saleItem->uom_multiplier ?? 1);
            $unitPrice  = $item['unitPrice'] ?? $item['unit_price'] ?? (float) $saleItem->unit_price;
            $discount   = $item['discount'] ?? (float) $saleItem->discount;
            $lineTotal  = ($unitPrice * $returnQty) - ($discount * ($returnQty / $saleItem->quantity));

            $processedItems[] = compact(
                'productId', 'returnQty', 'uomId', 'multiplier',
                'unitPrice', 'discount', 'lineTotal', 'saleItem'
            );
            $totalAmount += $lineTotal;
        }

        return [$processedItems, $totalAmount];
    }

    private function updateSaleReturnStatus(SaleOrder $sale): void
    {
        $sale->refresh();
        $totalReturned   = $sale->items->sum('returned_quantity');
        $allFullyReturned = $sale->items->every(fn($si) => $si->returned_quantity >= $si->quantity);

        if ($allFullyReturned) {
            $sale->return_status = 'full';
            $sale->is_returned   = true;
        } elseif ($totalReturned > 0) {
            $sale->return_status = 'partial';
            $sale->is_returned   = false;
        }
        $sale->save();
    }

    private function resolveTierPrice(?Product $product, ?string $customerId): float
    {
        if (!$product) return 0.0;
        if (!$customerId) return (float) $product->unit_price;

        $customer = Party::find($customerId);
        if (!$customer || empty($customer->category)) {
            return (float) $product->unit_price;
        }

        $tier = $product->priceTiers->firstWhere('category', $customer->category);
        return $tier ? (float) $tier->price : (float) $product->unit_price;
    }
}
