<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\InventoryCostLayer;
use Illuminate\Support\Str;

class InventoryCostingService
{
    public function getCompanyCostingMethod(string $companyId): string
    {
        $company = Company::find($companyId);
        return $company->costing_method ?? 'moving_average';
    }

    public function addCostLayer(string $companyId, string $productId, int $quantity, float $unitCost, string $referenceId, string $referenceType = 'purchase_receive'): InventoryCostLayer
    {
        return InventoryCostLayer::create([
            'id' => 'CL-' . Str::random(9),
            'company_id' => $companyId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }

    public function updateMovingAverageCost(Product $product, int $newQty, float $newUnitCost): void
    {
        $existingStock = max($product->current_stock, 0);
        $existingCost = $product->unit_cost;

        $totalOldValue = $existingStock * $existingCost;
        $totalNewValue = $newQty * $newUnitCost;
        $totalStock = $existingStock + $newQty;

        if ($totalStock > 0) {
            $product->unit_cost = round(($totalOldValue + $totalNewValue) / $totalStock, 2);
        }
    }

    public function consumeFIFO(string $companyId, string $productId, int $quantityNeeded): float
    {
        $layers = InventoryCostLayer::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalCogs = 0;
        $remaining = $quantityNeeded;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $take = min($remaining, $layer->remaining_quantity);
            $totalCogs += $take * $layer->unit_cost;
            $layer->remaining_quantity -= $take;
            $layer->save();
            $remaining -= $take;
        }

        if ($remaining > 0) {
            $product = Product::find($productId);
            $fallbackCost = $product ? $product->unit_cost : 0;
            $totalCogs += $remaining * $fallbackCost;
        }

        return round($totalCogs, 2);
    }

    public function consumeMovingAverage(string $productId, int $quantity): float
    {
        $product = Product::find($productId);
        $avgCost = $product ? $product->unit_cost : 0;
        return round($quantity * $avgCost, 2);
    }

    public function calculateCOGS(string $companyId, string $productId, int $quantity): float
    {
        $method = $this->getCompanyCostingMethod($companyId);

        if ($method === 'fifo') {
            return $this->consumeFIFO($companyId, $productId, $quantity);
        }

        return $this->consumeMovingAverage($productId, $quantity);
    }

    public function restoreFIFOLayers(string $companyId, string $productId, int $quantity, float $unitCost, string $referenceId): void
    {
        $this->addCostLayer($companyId, $productId, $quantity, $unitCost, $referenceId, 'sale_return');
    }
}
