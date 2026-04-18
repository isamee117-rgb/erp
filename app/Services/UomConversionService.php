<?php

namespace App\Services;

use App\Models\ProductUomConversion;

class UomConversionService
{
    /**
     * Return the multiplier (base units per 1 of the given UOM) for a product.
     * Returns 1.0 when uomId is null/empty (meaning the base unit itself is used).
     */
    public function resolveMultiplier(string $productId, ?string $uomId): float
    {
        if (!$uomId) {
            return 1.0;
        }

        $conversion = ProductUomConversion::where('product_id', $productId)
            ->where('uom_id', $uomId)
            ->first();

        return $conversion ? (float) $conversion->multiplier : 1.0;
    }

    /**
     * Clear the default purchase unit flag for all conversions of a product,
     * then set it on the specified conversion row.
     */
    public function setDefaultPurchaseUnit(string $productId, string $conversionId): void
    {
        ProductUomConversion::where('product_id', $productId)
            ->update(['is_default_purchase_unit' => false]);

        ProductUomConversion::where('id', $conversionId)
            ->update(['is_default_purchase_unit' => true]);
    }

    /**
     * Clear the default sales unit flag for all conversions of a product,
     * then set it on the specified conversion row.
     */
    public function setDefaultSalesUnit(string $productId, string $conversionId): void
    {
        ProductUomConversion::where('product_id', $productId)
            ->update(['is_default_sales_unit' => false]);

        ProductUomConversion::where('id', $conversionId)
            ->update(['is_default_sales_unit' => true]);
    }
}
