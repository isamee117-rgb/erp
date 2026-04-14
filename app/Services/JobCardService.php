<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\JobCard;
use App\Models\JobCardItem;
use App\Models\Party;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobCardService
{
    public function __construct(
        protected DocumentSequenceService $sequenceService,
        protected InventoryCostingService $costingService,
    ) {}

    public function create(User $user, array $data): JobCard
    {
        $jobCardNo = $this->sequenceService->getNextNumber($user->company_id, 'job_card');

        return JobCard::create([
            'id'                 => 'JC-' . Str::random(9),
            'company_id'         => $user->company_id,
            'job_card_no'        => $jobCardNo,
            'status'             => 'open',
            'created_by'         => $user->id,
            'customer_id'        => $data['customerId'] ?? null,
            'customer_name'      => $data['customerName'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'vehicle_reg_number' => $data['vehicleRegNumber'] ?? null,
            'vin_chassis_number' => $data['vinChassisNumber'] ?? null,
            'engine_number'      => $data['engineNumber'] ?? null,
            'make_model_year'    => $data['makeModelYear'] ?? null,
            'lift_number'        => $data['liftNumber'] ?? null,
            'payment_method'     => 'Cash',
            'parts_subtotal'     => 0,
            'services_subtotal'  => 0,
            'subtotal'           => 0,
            'discount_type'      => 'fixed',
            'discount_value'     => 0,
            'discount'           => 0,
            'grand_total'        => 0,
        ]);
    }

    public function updateHeader(JobCard $card, array $data): JobCard
    {
        $updateData = [];

        if (array_key_exists('customerId', $data))       $updateData['customer_id']        = $data['customerId'];
        if (array_key_exists('customerName', $data))     $updateData['customer_name']       = $data['customerName'];
        if (array_key_exists('phone', $data))            $updateData['phone']               = $data['phone'];
        if (array_key_exists('vehicleRegNumber', $data)) $updateData['vehicle_reg_number']  = $data['vehicleRegNumber'];
        if (array_key_exists('vinChassisNumber', $data)) $updateData['vin_chassis_number']  = $data['vinChassisNumber'];
        if (array_key_exists('engineNumber', $data))     $updateData['engine_number']       = $data['engineNumber'];
        if (array_key_exists('makeModelYear', $data))    $updateData['make_model_year']     = $data['makeModelYear'];
        if (array_key_exists('liftNumber', $data))       $updateData['lift_number']         = $data['liftNumber'];
        if (array_key_exists('currentOdometer', $data))  $updateData['current_odometer']    = $data['currentOdometer'];
        if (array_key_exists('paymentMethod', $data))    $updateData['payment_method']      = $data['paymentMethod'];
        if (array_key_exists('discountType', $data))     $updateData['discount_type']       = $data['discountType'];
        if (array_key_exists('discountValue', $data))    $updateData['discount_value']      = $data['discountValue'];

        if (!empty($updateData)) {
            $card->update($updateData);
        }

        $this->recalculateTotals($card);
        return $card->fresh();
    }

    public function addItem(JobCard $card, array $data): JobCardItem
    {
        if ($card->status !== 'open') {
            throw new \RuntimeException('Cannot modify a closed job card.');
        }

        $itemType = $data['itemType']; // 'part' or 'service'

        $product = Product::where('company_id', $card->company_id)
                          ->where('id', $data['productId'])
                          ->firstOrFail();

        $expectedType = $itemType === 'part' ? 'Product' : 'Service';
        if ($product->type !== $expectedType) {
            throw new \RuntimeException("Product type mismatch: expected {$expectedType}");
        }

        $qty       = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unitPrice'] ?? $product->unit_price ?? 0);
        $discount  = (float) ($data['discount'] ?? 0);
        $total     = ($unitPrice * $qty) - $discount;

        $item = JobCardItem::create([
            'id'               => 'JCI-' . Str::random(9),
            'job_card_id'      => $card->id,
            'item_type'        => $itemType,
            'product_id'       => $product->id,
            'product_name'     => $product->name,
            'quantity'         => $qty,
            'unit_price'       => $unitPrice,
            'discount'         => $discount,
            'total_line_price' => $total,
        ]);

        $this->recalculateTotals($card);
        return $item;
    }

    public function updateItem(JobCard $card, string $itemId, array $data): JobCardItem
    {
        if ($card->status !== 'open') {
            throw new \RuntimeException('Cannot modify a closed job card.');
        }

        $item = JobCardItem::where('job_card_id', $card->id)
                           ->where('id', $itemId)
                           ->firstOrFail();

        $qty       = (float) ($data['quantity'] ?? $item->quantity);
        $unitPrice = (float) ($data['unitPrice'] ?? $item->unit_price);
        $discount  = (float) ($data['discount'] ?? $item->discount);
        $total     = ($unitPrice * $qty) - $discount;

        $item->update([
            'quantity'         => $qty,
            'unit_price'       => $unitPrice,
            'discount'         => $discount,
            'total_line_price' => $total,
        ]);

        $this->recalculateTotals($card);
        return $item->fresh();
    }

    public function removeItem(JobCard $card, string $itemId): void
    {
        if ($card->status !== 'open') {
            throw new \RuntimeException('Cannot modify a closed job card.');
        }

        JobCardItem::where('job_card_id', $card->id)
                   ->where('id', $itemId)
                   ->firstOrFail()
                   ->delete();

        $this->recalculateTotals($card);
    }

    public function finalize(JobCard $card, User $user): JobCard
    {
        return DB::transaction(function () use ($card, $user) {
            if ($card->status === 'closed') {
                throw new \RuntimeException('Job card is already finalized.');
            }

            foreach ($card->items()->where('item_type', 'part')->get() as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                $baseQty = (int) round($item->quantity);
                $this->costingService->calculateCOGS($card->company_id, $item->product_id, $baseQty);

                $product->current_stock -= $baseQty;
                $product->save();

                InventoryLedger::create([
                    'id'               => 'LEG-' . Str::random(9),
                    'company_id'       => $card->company_id,
                    'product_id'       => $item->product_id,
                    'transaction_type' => 'Sale',
                    'quantity_change'  => -$baseQty,
                    'reference_id'     => $card->job_card_no,
                ]);
            }

            if ($card->customer_id && $card->current_odometer !== null) {
                Party::where('id', $card->customer_id)
                     ->update(['last_odometer_reading' => $card->current_odometer]);
            }

            $card->update([
                'status'    => 'closed',
                'closed_at' => now(),
            ]);

            return $card->fresh();
        });
    }

    private function recalculateTotals(JobCard $card): void
    {
        $items = $card->items()->get();

        $partsSubtotal    = $items->where('item_type', 'part')->sum('total_line_price');
        $servicesSubtotal = $items->where('item_type', 'service')->sum('total_line_price');
        $subtotal         = $partsSubtotal + $servicesSubtotal;

        $discountValue = (float) $card->discount_value;
        $discount      = $card->discount_type === 'percent'
                         ? round($subtotal * ($discountValue / 100), 2)
                         : $discountValue;

        $grandTotal = max(0, $subtotal - $discount);

        $card->update([
            'parts_subtotal'    => $partsSubtotal,
            'services_subtotal' => $servicesSubtotal,
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'grand_total'       => $grandTotal,
        ]);
    }
}
