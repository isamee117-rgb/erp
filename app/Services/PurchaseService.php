<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\Party;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\User;
use App\Services\UomConversionService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(
        protected InventoryCostingService $costingService,
        protected DocumentSequenceService $sequenceService,
        protected UomConversionService $uomService,
    ) {}

    public function createOrder(User $user, array $data): PurchaseOrder
    {
        $poNo   = $this->sequenceService->getNextNumber($user->company_id, 'po_number');
        $poUUID = 'PO-' . Str::random(9);

        $mappedItems = [];
        $totalAmount = 0;

        foreach ($data['items'] ?? [] as $item) {
            $productId     = $item['productId'] ?? $item['product_id'];
            $product       = Product::find($productId);
            $uomId         = $item['uomId'] ?? $item['uom_id'] ?? null;
            $multiplier    = $this->uomService->resolveMultiplier($productId, $uomId);
            // unitCost is the cost per the selected UOM (e.g. cost per carton)
            $unitCost      = $item['unitCost'] ?? $item['unit_cost'] ?? $product->unit_cost ?? 0;
            $quantity      = $item['quantity'] ?? 0;
            $totalLineCost = $quantity * $unitCost;

            $mappedItems[] = [
                'id'                => 'PI-' . Str::random(9),
                'product_id'        => $productId,
                'uom_id'            => $uomId,
                'uom_multiplier'    => $multiplier,
                'quantity'          => $quantity,
                'unit_cost'         => $unitCost,
                'total_line_cost'   => $totalLineCost,
                'received_quantity' => 0,
            ];

            $totalAmount += $totalLineCost;
        }

        $po = PurchaseOrder::create([
            'id'              => $poUUID,
            'po_no'           => $poNo,
            'company_id'      => $user->company_id,
            'vendor_id'       => $data['vendorId'] ?? $data['vendor_id'] ?? '',
            'status'          => 'Draft',
            'total_amount'    => $totalAmount,
            'received_amount' => 0,
        ]);

        if (!empty($data['orderDate'])) {
            $po->created_at = Carbon::parse($data['orderDate'])->startOfDay();
            $po->save();
        }

        foreach ($mappedItems as $item) {
            PurchaseItem::create(array_merge($item, ['purchase_order_id' => $poUUID]));
        }

        $po->load('items');
        return $po;
    }

    public function receiveOrder(User $user, string $id, array $receiveItems, string $notes): PurchaseOrder
    {
        $po = PurchaseOrder::with('items')
            ->where('company_id', $user->company_id)
            ->where('po_no', $id)
            ->first()
            ?? PurchaseOrder::with('items')
                ->where('company_id', $user->company_id)
                ->where('id', $id)
                ->first();

        if (!$po) {
            throw new \RuntimeException('Purchase order not found');
        }

        if ($po->status === 'Received') {
            throw new \RuntimeException('PO is already fully received');
        }

        if (in_array($po->status, ['Cancelled', 'Returned'])) {
            throw new \RuntimeException('Cannot receive a cancelled or returned PO');
        }

        if (empty($receiveItems)) {
            $receiveItems = $this->buildDefaultReceiveItems($po);
        }

        if (empty($receiveItems)) {
            throw new \RuntimeException('No items to receive');
        }

        $receiveId = 'RCV-' . Str::random(9);
        PurchaseReceive::create([
            'id'                => $receiveId,
            'company_id'        => $user->company_id,
            'purchase_order_id' => $po->id,
            'notes'             => $notes,
        ]);

        $receivedAmount = 0;

        foreach ($receiveItems as $ri) {
            $productId = $ri['productId'] ?? $ri['product_id'];
            $quantity  = (int) ($ri['quantity'] ?? 0);
            $unitCost  = (float) ($ri['unitCost'] ?? $ri['unit_cost'] ?? 0);

            if ($quantity <= 0) continue;

            $poItem = $this->resolvePoItem($po, $ri);
            if (!$poItem) continue;

            $actualQty = min($quantity, $poItem->quantity - $poItem->received_quantity);
            if ($actualQty <= 0) continue;

            $unitCost = $unitCost > 0 ? $unitCost : $poItem->unit_cost;

            // Convert to base units using the PO item's multiplier snapshot
            $multiplier        = (float) ($poItem->uom_multiplier ?? 1);
            $baseQty           = (int) round($actualQty * $multiplier);
            // Cost per base unit for moving-average and FIFO layer
            $costPerBaseUnit   = $multiplier > 0 ? $unitCost / $multiplier : $unitCost;

            PurchaseReceiveItem::create([
                'id'                  => 'RCI-' . Str::random(9),
                'purchase_receive_id' => $receiveId,
                'purchase_item_id'    => $poItem->id,
                'product_id'          => $productId,
                'quantity'            => $actualQty,
                'unit_cost'           => $unitCost,
            ]);

            $poItem->received_quantity += $actualQty;
            $poItem->save();

            $product = Product::find($productId);
            if ($product) {
                $this->costingService->updateMovingAverageCost($product, $baseQty, $costPerBaseUnit);
                $product->current_stock += $baseQty;
                $product->save();

                $this->costingService->addCostLayer(
                    $user->company_id, $productId, $baseQty, $costPerBaseUnit, $receiveId, 'purchase_receive'
                );

                InventoryLedger::create([
                    'id'               => 'LEG-' . Str::random(9),
                    'company_id'       => $user->company_id,
                    'product_id'       => $product->id,
                    'transaction_type' => 'Purchase_Receive',
                    'quantity_change'  => $baseQty,
                    'reference_id'     => $receiveId,
                ]);
            }

            $receivedAmount += $actualQty * $unitCost;
        }

        $po->refresh()->load('items');
        $allReceived = $po->items->every(fn($i) => $i->received_quantity >= $i->quantity);

        $po->received_amount = ($po->received_amount ?? 0) + $receivedAmount;
        $po->status          = $allReceived ? 'Received' : 'Partially Received';
        $po->save();

        $vendor = Party::find($po->vendor_id);
        if ($vendor) {
            $vendor->current_balance += $receivedAmount;
            $vendor->save();
        }

        $po->load('items', 'receives.items');
        return $po;
    }

    public function createReturn(User $user, array $data): PurchaseReturn
    {
        $poId       = $data['poId'] ?? $data['po_id'] ?? $data['originalPurchaseId'] ?? '';
        $returnNo   = $this->sequenceService->getNextNumber($user->company_id, 'purchase_return');
        $returnUUID = 'PR-' . Str::random(9);

        $originalPO = PurchaseOrder::with('items')
            ->where('company_id', $user->company_id)
            ->where('po_no', $poId)
            ->first()
            ?? PurchaseOrder::with('items')
                ->where('company_id', $user->company_id)
                ->where('id', $poId)
                ->first();

        if (!$originalPO) {
            throw new \RuntimeException('Purchase order not found');
        }

        [$processedItems, $totalAmount] = $this->buildReturnItems($data['items'] ?? [], $originalPO);

        if (empty($processedItems)) {
            throw new \RuntimeException('No valid items to return');
        }

        $purchaseReturn = PurchaseReturn::create([
            'id'                  => $returnUUID,
            'return_no'           => $returnNo,
            'company_id'          => $user->company_id,
            'original_purchase_id' => $originalPO->po_no ?? $originalPO->id,
            'vendor_id'           => $originalPO->vendor_id ?? '',
            'total_amount'        => $totalAmount,
            'reason'              => $data['reason'] ?? '',
        ]);

        foreach ($processedItems as $pi) {
            $baseReturnQty = (int) round($pi['returnQty'] * $pi['multiplier']);

            PurchaseReturnItem::create([
                'id'                 => 'PRI-' . Str::random(9),
                'purchase_return_id' => $returnUUID,
                'product_id'         => $pi['productId'],
                'uom_id'             => $pi['uomId'],
                'uom_multiplier'     => $pi['multiplier'],
                'quantity'           => $pi['returnQty'],
                'unit_cost'          => $pi['unitCost'],
                'total_line_cost'    => $pi['lineCost'],
            ]);

            $pi['poItem']->returned_quantity += $pi['returnQty'];
            $pi['poItem']->save();

            $product = Product::find($pi['productId']);
            if ($product) {
                $product->current_stock -= $baseReturnQty;
                $product->save();

                InventoryLedger::create([
                    'id'               => 'LEG-' . Str::random(9),
                    'company_id'       => $user->company_id,
                    'product_id'       => $pi['productId'],
                    'transaction_type' => 'Purchase_Return',
                    'quantity_change'  => -$baseReturnQty,
                    'reference_id'     => $returnNo,
                ]);
            }
        }

        $this->updatePurchaseReturnStatus($originalPO);

        $vendor = Party::find($originalPO->vendor_id);
        if ($vendor) {
            $vendor->current_balance -= $totalAmount;
            $vendor->save();
        }

        $purchaseReturn->load('items');
        return $purchaseReturn;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildDefaultReceiveItems(PurchaseOrder $po): array
    {
        $items = [];
        foreach ($po->items as $poItem) {
            $pendingQty = $poItem->quantity - $poItem->received_quantity;
            if ($pendingQty > 0) {
                $items[] = [
                    'purchaseItemId' => $poItem->id,
                    'productId'      => $poItem->product_id,
                    'quantity'       => $pendingQty,
                    'unitCost'       => $poItem->unit_cost,
                ];
            }
        }
        return $items;
    }

    private function resolvePoItem(PurchaseOrder $po, array $ri): ?PurchaseItem
    {
        $purchaseItemId = $ri['purchaseItemId'] ?? $ri['purchase_item_id'] ?? null;
        if ($purchaseItemId) {
            return PurchaseItem::find($purchaseItemId);
        }
        $productId = $ri['productId'] ?? $ri['product_id'];
        return PurchaseItem::where('purchase_order_id', $po->id)
            ->where('product_id', $productId)
            ->first();
    }

    private function buildReturnItems(array $items, PurchaseOrder $originalPO): array
    {
        $processedItems = [];
        $totalAmount    = 0;

        foreach ($items as $item) {
            $productId = $item['productId'] ?? $item['product_id'];
            $returnQty = $item['quantity'] ?? 0;
            if ($returnQty <= 0) continue;

            $poItem = $originalPO->items->firstWhere('product_id', $productId);
            if (!$poItem) continue;

            $maxReturnable = $poItem->received_quantity - $poItem->returned_quantity;
            $returnQty     = min($returnQty, $maxReturnable);
            if ($returnQty <= 0) continue;

            // Inherit UOM snapshot from original PO item
            $uomId      = $poItem->uom_id ?? null;
            $multiplier = (float) ($poItem->uom_multiplier ?? 1);
            $unitCost   = $item['unitCost'] ?? $item['unit_cost'] ?? (float) $poItem->unit_cost;
            $lineCost   = $returnQty * $unitCost;

            $processedItems[] = compact(
                'productId', 'returnQty', 'uomId', 'multiplier', 'unitCost', 'lineCost', 'poItem'
            );
            $totalAmount += $lineCost;
        }

        return [$processedItems, $totalAmount];
    }

    private function updatePurchaseReturnStatus(PurchaseOrder $po): void
    {
        $po->refresh();
        $totalReturned    = $po->items->sum('returned_quantity');
        $allFullyReturned = $po->items->every(fn($i) => $i->returned_quantity >= $i->received_quantity);

        if ($allFullyReturned && $totalReturned > 0) {
            $po->return_status = 'full';
            $po->status        = 'Returned';
        } elseif ($totalReturned > 0) {
            $po->return_status = 'partial';
        }
        $po->save();
    }
}
