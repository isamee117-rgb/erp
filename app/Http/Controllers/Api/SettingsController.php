<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessCategoryResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\EntityTypeResource;
use App\Http\Resources\UnitOfMeasureResource;
use App\Models\Setting;
use App\Models\Category;
use App\Models\UnitOfMeasure;
use App\Models\EntityType;
use App\Models\BusinessCategory;
use App\Models\Company;
use App\Models\Product;
use App\Models\Party;
use App\Services\DocumentSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{

    public function updateCurrency(Request $request)
    {
        Setting::updateOrCreate(['key' => 'currency'], ['value' => $request->input('currency')]);
        return response()->json(['success' => true]);
    }

    public function updateInvoiceFormat(Request $request)
    {
        Setting::updateOrCreate(['key' => 'invoice_format'], ['value' => $request->input('format') ?? $request->input('invoiceFormat')]);
        return response()->json(['success' => true]);
    }

    public function updateJobCardMode(Request $request)
    {
        $mode = $request->input('jobCardMode') ? '1' : '0';
        Setting::updateOrCreate(['key' => 'job_card_mode'], ['value' => $mode]);
        return response()->json(['success' => true, 'jobCardMode' => (bool) $mode]);
    }

    public function updateCostingMethod(Request $request)
    {
        $user = $request->get('auth_user');
        $method = $request->input('costingMethod') ?? $request->input('costing_method') ?? 'moving_average';

        if (!in_array($method, ['fifo', 'moving_average'])) {
            return response()->json(['error' => 'Invalid costing method'], 400);
        }

        $company = Company::findOrFail($user->company_id);
        $company->update(['costing_method' => $method]);

        return response()->json(['success' => true, 'costingMethod' => $method]);
    }

    public function createCategory(Request $request)
    {
        $user = $request->get('auth_user');
        $category = Category::create([
            'id' => 'CAT-' . Str::random(9),
            'company_id' => $request->input('companyId') ?? $user->company_id,
            'name' => $request->input('name'),
        ]);
        return new CategoryResource($category);
    }

    public function deleteCategory($id)
    {
        if (Product::where('category_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this category is used by one or more products.'], 422);
        }
        Category::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function createUOM(Request $request)
    {
        $user = $request->get('auth_user');
        $uom = UnitOfMeasure::create([
            'id' => 'UOM-' . Str::random(9),
            'company_id' => $request->input('companyId') ?? $user->company_id,
            'name' => $request->input('name'),
        ]);
        return new UnitOfMeasureResource($uom);
    }

    public function deleteUOM($id)
    {
        $uom = UnitOfMeasure::findOrFail($id);
        if (Product::where('uom', $uom->name)->exists()) {
            return response()->json(['error' => 'Cannot delete: this unit of measure is used by one or more products.'], 422);
        }
        $uom->delete();
        return response()->json(['success' => true]);
    }

    public function createEntityType(Request $request)
    {
        $et = EntityType::create([
            'id' => 'ET-' . Str::random(9),
            'name' => $request->input('name'),
        ]);
        return new EntityTypeResource($et);
    }

    public function deleteEntityType($id)
    {
        $et = EntityType::findOrFail($id);
        if (Party::where('sub_type', $et->name)->exists()) {
            return response()->json(['error' => 'Cannot delete: this entity type is used by one or more parties.'], 422);
        }
        $et->delete();
        return response()->json(['success' => true]);
    }

    public function createBusinessCategory(Request $request)
    {
        $bc = BusinessCategory::create([
            'id' => 'BC-' . Str::random(9),
            'name' => $request->input('name'),
        ]);
        return new BusinessCategoryResource($bc);
    }

    public function deleteBusinessCategory($id)
    {
        $bc = BusinessCategory::findOrFail($id);
        if (Party::where('category', $bc->name)->exists()) {
            return response()->json(['error' => 'Cannot delete: this business category is used by one or more parties.'], 422);
        }
        $bc->delete();
        return response()->json(['success' => true]);
    }

    public function getDocumentSequences(Request $request)
    {
        $user = $request->get('auth_user');
        $service = new DocumentSequenceService();
        $sequences = $service->getSequences($user->company_id);

        return response()->json(array_map(function ($seq) {
            return [
                'id' => $seq['id'],
                'companyId' => $seq['company_id'],
                'type' => $seq['type'],
                'prefix' => $seq['prefix'],
                'nextNumber' => $seq['next_number'],
                'isLocked' => (bool) $seq['is_locked'],
            ];
        }, $sequences));
    }

    public function updateDocumentSequence(Request $request)
    {
        $user = $request->get('auth_user');
        $type = $request->input('type');
        $prefix = $request->input('prefix', '');
        $nextNumber = (int) $request->input('nextNumber', 1);

        $service = new DocumentSequenceService();

        try {
            $seq = $service->updateSequence($user->company_id, $type, $prefix, $nextNumber);
            return response()->json([
                'id' => $seq->id,
                'companyId' => $seq->company_id,
                'type' => $seq->type,
                'prefix' => $seq->prefix,
                'nextNumber' => $seq->next_number,
                'isLocked' => (bool) $seq->is_locked,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
