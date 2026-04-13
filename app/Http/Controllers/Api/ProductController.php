<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductPriceTierResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductUomConversionResource;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\ProductUomConversion;
use App\Models\InventoryLedger;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\SaleReturnItem;
use App\Models\PurchaseReturnItem;
use App\Config\DynamicFields;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\DocumentSequenceService;
use App\Services\UomConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        protected DocumentSequenceService $sequenceService,
        protected UomConversionService $uomService,
    ) {}

    public function store(StoreProductRequest $request)
    {
        $user      = $request->get('auth_user');
        $validated = $request->validated();
        $productId = $this->sequenceService->getNextNumber($user->company_id, 'item_no');
        $sku = $validated['sku'] ?? '';
        if (empty($sku)) {
            $sku = $this->sequenceService->getNextNumber($user->company_id, 'sku');
        }
        $initialStock = $validated['initialStock'] ?? 0;

        $dynamicFields = [];
        foreach (DynamicFields::productFields() as $field) {
            $key = $field['key'];
            if (array_key_exists($key, $validated)) {
                $val = $validated[$key];
                $dynamicFields[$key] = ($val === '' || $val === null) ? null : $val;
            }
        }

        $product = Product::create(array_merge([
            'id'           => 'PRD-' . Str::random(9),
            'company_id'   => $user->company_id,
            'sku'          => $sku,
            'barcode'      => $validated['barcode'] ?? null,
            'item_number'  => $productId,
            'name'         => $validated['name'] ?? '',
            'type'         => $validated['type'] ?? 'Product',
            'uom'          => $validated['uom'] ?? '',
            'base_uom_id'  => $validated['baseUomId'] ?? null,
            'category_id'  => $validated['categoryId'] ?? null,
            'current_stock' => $initialStock,
            'reorder_level' => $validated['reorderLevel'] ?? 0,
            'unit_cost'    => $validated['unitCost'] ?? 0,
            'unit_price'   => $validated['unitPrice'] ?? 0,
        ], $dynamicFields));

        if ($initialStock > 0) {
            InventoryLedger::create([
                'id' => 'LEG-' . Str::random(9),
                'company_id' => $user->company_id,
                'product_id' => $product->id,
                'transaction_type' => 'Adjustment_Internal',
                'quantity_change' => $initialStock,
                'reference_id' => 'OPENING',
            ]);
        }

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product   = Product::findOrFail($id);
        $validated = $request->validated();

        $updateData = [
            'sku'          => $validated['sku']          ?? $product->sku,
            'barcode'      => array_key_exists('barcode', $validated) ? $validated['barcode'] : $product->barcode,
            'name'         => $validated['name']         ?? $product->name,
            'type'         => $validated['type']         ?? $product->type,
            'uom'          => $validated['uom']          ?? $product->uom,
            'base_uom_id'  => array_key_exists('baseUomId', $validated) ? $validated['baseUomId'] : $product->base_uom_id,
            'category_id'  => $validated['categoryId']  ?? $product->category_id,
            'current_stock' => $validated['currentStock'] ?? $product->current_stock,
            'reorder_level' => $validated['reorderLevel'] ?? $product->reorder_level,
            'unit_cost'    => $validated['unitCost']     ?? $product->unit_cost,
            'unit_price'   => $validated['unitPrice']    ?? $product->unit_price,
        ];

        // Dynamic fields
        foreach (DynamicFields::productFields() as $field) {
            $key = $field['key'];
            if (array_key_exists($key, $validated)) {
                $val = $validated[$key];
                $updateData[$key] = ($val === '' || $val === null) ? null : $val;
            }
        }

        $product->update($updateData);

        return new ProductResource($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if (SaleItem::where('product_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this product has been used in one or more sales.'], 422);
        }
        if (PurchaseItem::where('product_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this product has been used in one or more purchases.'], 422);
        }
        if (SaleReturnItem::where('product_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this product has been used in one or more sale returns.'], 422);
        }
        if (PurchaseReturnItem::where('product_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this product has been used in one or more purchase returns.'], 422);
        }

        $product->delete();
        return response()->json(['success' => true]);
    }

    public function findByBarcode(Request $request)
    {
        $user = $request->get('auth_user');
        $barcode = $request->query('code');

        if (empty($barcode)) {
            return response()->json(['error' => 'Barcode is required'], 400);
        }

        $product = Product::where('company_id', $user->company_id)
            ->where('barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return new ProductResource($product);
    }

    public function adjustStock(Request $request)
    {
        $user = $request->get('auth_user');
        $data = $request->all();
        $productId = $data['productId'] ?? $data['product_id'];
        $quantityChange = $data['quantityChange'] ?? $data['quantity_change'] ?? 0;
        $type = $data['type'] ?? 'Adjustment_Internal';

        $product = Product::findOrFail($productId);
        $product->current_stock += $quantityChange;
        $product->save();

        InventoryLedger::create([
            'id' => 'LEG-' . Str::random(9),
            'company_id' => $user->company_id,
            'product_id' => $productId,
            'transaction_type' => $type,
            'quantity_change' => $quantityChange,
            'reference_id' => 'MANUAL_ADJ',
        ]);

        return response()->json(['success' => true]);
    }

    // ── UOM Conversions ───────────────────────────────────────────────────────

    public function listUomConversions(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $conversions = ProductUomConversion::with('uom')
            ->where('product_id', $product->id)
            ->get();

        return ProductUomConversionResource::collection($conversions);
    }

    public function storeUomConversion(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $data    = $request->all();

        $uomId      = $data['uomId'] ?? $data['uom_id'] ?? null;
        $multiplier = $data['multiplier'] ?? null;

        if (!$uomId || !$multiplier || (float) $multiplier <= 0) {
            return response()->json(['error' => 'uomId and a positive multiplier are required'], 422);
        }

        $exists = ProductUomConversion::where('product_id', $product->id)
            ->where('uom_id', $uomId)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'A conversion for this UOM already exists on this product'], 422);
        }

        $conversion = ProductUomConversion::create([
            'id'                      => 'PUC-' . Str::random(9),
            'product_id'              => $product->id,
            'uom_id'                  => $uomId,
            'multiplier'              => (float) $multiplier,
            'is_default_purchase_unit' => false,
            'is_default_sales_unit'   => false,
        ]);

        if (!empty($data['isDefaultPurchaseUnit'])) {
            $this->uomService->setDefaultPurchaseUnit($product->id, $conversion->id);
            $conversion->refresh();
        }

        if (!empty($data['isDefaultSalesUnit'])) {
            $this->uomService->setDefaultSalesUnit($product->id, $conversion->id);
            $conversion->refresh();
        }

        $conversion->load('uom');
        return new ProductUomConversionResource($conversion);
    }

    public function updateUomConversion(Request $request, string $id, string $cid)
    {
        $conversion = ProductUomConversion::where('product_id', $id)
            ->where('id', $cid)
            ->firstOrFail();

        $data       = $request->all();
        $multiplier = $data['multiplier'] ?? null;

        if ($multiplier !== null && (float) $multiplier <= 0) {
            return response()->json(['error' => 'Multiplier must be a positive number'], 422);
        }

        if ($multiplier !== null) {
            $conversion->multiplier = (float) $multiplier;
            $conversion->save();
        }

        if (!empty($data['isDefaultPurchaseUnit'])) {
            $this->uomService->setDefaultPurchaseUnit($id, $cid);
        }

        if (!empty($data['isDefaultSalesUnit'])) {
            $this->uomService->setDefaultSalesUnit($id, $cid);
        }

        $conversion->refresh()->load('uom');
        return new ProductUomConversionResource($conversion);
    }

    public function destroyUomConversion(string $id, string $cid)
    {
        $conversion = ProductUomConversion::where('product_id', $id)
            ->where('id', $cid)
            ->firstOrFail();

        $conversion->delete();
        return response()->json(['success' => true]);
    }

    // ── Price Tiers ───────────────────────────────────────────────────────────

    public function storePriceTier(Request $request, string $id)
    {
        $product  = Product::findOrFail($id);
        $data     = $request->all();
        $category = trim($data['category'] ?? '');
        $price    = $data['price'] ?? null;

        if (empty($category)) {
            return response()->json(['error' => 'category is required'], 422);
        }
        if ($price === null || (float) $price < 0) {
            return response()->json(['error' => 'price must be a non-negative number'], 422);
        }

        $exists = ProductPriceTier::where('product_id', $product->id)
            ->where('category', $category)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'A price tier for this category already exists on this product'], 422);
        }

        $tier = ProductPriceTier::create([
            'id'         => 'PPT-' . Str::random(9),
            'product_id' => $product->id,
            'company_id' => $product->company_id,
            'category'   => $category,
            'price'      => (float) $price,
        ]);

        return new ProductPriceTierResource($tier);
    }

    public function updatePriceTier(Request $request, string $id, string $tid)
    {
        $tier  = ProductPriceTier::where('product_id', $id)->where('id', $tid)->firstOrFail();
        $data  = $request->all();
        $price = $data['price'] ?? null;

        if ($price === null || (float) $price < 0) {
            return response()->json(['error' => 'price must be a non-negative number'], 422);
        }

        $tier->price = (float) $price;
        $tier->save();

        return new ProductPriceTierResource($tier);
    }

    public function destroyPriceTier(string $id, string $tid)
    {
        $tier = ProductPriceTier::where('product_id', $id)->where('id', $tid)->firstOrFail();
        $tier->delete();
        return response()->json(['success' => true]);
    }
}
