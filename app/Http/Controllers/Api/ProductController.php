<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\InventoryLedger;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\SaleReturnItem;
use App\Models\PurchaseReturnItem;
use App\Services\DocumentSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(protected DocumentSequenceService $sequenceService) {}

    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        $data = $request->all();
        $productId = $this->sequenceService->getNextNumber($user->company_id, 'item_no');
        $sku = $data['sku'] ?? '';
        if (empty($sku)) {
            $sku = $this->sequenceService->getNextNumber($user->company_id, 'sku');
        }
        $initialStock = $data['initialStock'] ?? 0;

        $product = Product::create([
            'id' => 'PRD-' . Str::random(9),
            'company_id' => $user->company_id,
            'sku' => $sku,
            'barcode' => $data['barcode'] ?? null,
            'item_number' => $productId,
            'name' => $data['name'] ?? '',
            'type' => $data['type'] ?? 'Product',
            'uom' => $data['uom'] ?? '',
            'category_id' => $data['categoryId'] ?? $data['category_id'] ?? '',
            'current_stock' => $initialStock,
            'reorder_level' => $data['reorderLevel'] ?? $data['reorder_level'] ?? 0,
            'unit_cost' => $data['unitCost'] ?? $data['unit_cost'] ?? 0,
            'unit_price' => $data['unitPrice'] ?? $data['unit_price'] ?? 0,
        ]);

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

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->all();

        $product->update([
            'sku' => $data['sku'] ?? $product->sku,
            'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $product->barcode,
            'name' => $data['name'] ?? $product->name,
            'type' => $data['type'] ?? $product->type,
            'uom' => $data['uom'] ?? $product->uom,
            'category_id' => $data['categoryId'] ?? $data['category_id'] ?? $product->category_id,
            'current_stock' => $data['currentStock'] ?? $data['current_stock'] ?? $product->current_stock,
            'reorder_level' => $data['reorderLevel'] ?? $data['reorder_level'] ?? $product->reorder_level,
            'unit_cost' => $data['unitCost'] ?? $data['unit_cost'] ?? $product->unit_cost,
            'unit_price' => $data['unitPrice'] ?? $data['unit_price'] ?? $product->unit_price,
        ]);

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
}
