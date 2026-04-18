<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Http\Resources\PartyResource;
use App\Models\Party;
use App\Models\SaleOrder;
use App\Models\PurchaseOrder;
use App\Models\SaleReturn;
use App\Models\PurchaseReturn;
use App\Models\Payment;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Str;

class PartyController extends Controller
{
    public function __construct(protected DocumentSequenceService $sequenceService) {}

    public function store(StorePartyRequest $request)
    {
        $user           = $request->get('auth_user');
        $data           = $request->validated();
        $openingBalance = $data['openingBalance'] ?? 0;
        $partyType      = $data['type'];
        $seqType        = $partyType === 'Vendor' ? 'vendor_no' : 'customer_no';
        $code           = $request->input('code') ?: $this->sequenceService->getNextNumber($user->company_id, $seqType);

        $party = Party::create([
            'id'              => 'PT-' . Str::random(9),
            'company_id'      => $user->company_id,
            'code'            => $code,
            'type'            => $partyType,
            'name'            => $data['name'],
            'phone'           => $data['phone']        ?? '',
            'email'           => $data['email']        ?? '',
            'address'         => $data['address']      ?? '',
            'sub_type'        => $request->input('subType')       ?? '',
            'payment_terms'   => $request->input('paymentTerms')  ?? '',
            'credit_limit'    => $data['creditLimit']  ?? 0,
            'bank_details'    => $request->input('bankDetails')   ?? '',
            'category'        => $request->input('category')      ?? '',
            'opening_balance'     => $openingBalance,
            'current_balance'     => $openingBalance,
            'make_model_year'     => $data['make_model_year']        ?? null,
            'vehicle_reg_number'  => $data['vehicle_reg_number']    ?? null,
            'vin_chassis_number'  => $data['vin_chassis_number']    ?? null,
            'engine_number'       => $data['engine_number']         ?? null,
            'last_odometer_reading' => isset($data['last_odometer_reading'])
                ? (float) $data['last_odometer_reading']
                : null,
        ]);

        return new PartyResource($party);
    }

    public function update(UpdatePartyRequest $request, $id)
    {
        $party = Party::findOrFail($id);
        $data  = $request->validated();

        $party->update([
            'code'            => $request->input('code',          $party->code),
            'type'            => $data['type']                    ?? $party->type,
            'name'            => $data['name']                    ?? $party->name,
            'phone'           => $data['phone']                   ?? $party->phone,
            'email'           => $data['email']                   ?? $party->email,
            'address'         => $data['address']                 ?? $party->address,
            'sub_type'        => $request->input('subType',       $party->sub_type),
            'payment_terms'   => $request->input('paymentTerms',  $party->payment_terms),
            'credit_limit'    => $data['creditLimit']             ?? $party->credit_limit,
            'bank_details'    => $request->input('bankDetails',   $party->bank_details),
            'category'        => $request->input('category',      $party->category),
            'opening_balance'     => $data['openingBalance']          ?? $party->opening_balance,
            'current_balance'     => $data['currentBalance']          ?? $party->current_balance,
            'make_model_year'     => $request->input('make_model_year',       $party->make_model_year),
            'vehicle_reg_number'  => $request->input('vehicle_reg_number',    $party->vehicle_reg_number),
            'vin_chassis_number'  => $request->input('vin_chassis_number',    $party->vin_chassis_number),
            'engine_number'       => $request->input('engine_number',         $party->engine_number),
            'last_odometer_reading' => $request->has('last_odometer_reading')
                ? ($request->input('last_odometer_reading') !== null
                    ? (float) $request->input('last_odometer_reading')
                    : null)
                : $party->last_odometer_reading,
        ]);

        return new PartyResource($party);
    }

    public function destroy($id)
    {
        $party = Party::findOrFail($id);

        if (SaleOrder::where('customer_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this party has one or more sales invoices in the system.'], 422);
        }
        if (PurchaseOrder::where('vendor_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this party has one or more purchase orders in the system.'], 422);
        }
        if (SaleReturn::where('customer_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this party has one or more sale returns in the system.'], 422);
        }
        if (PurchaseReturn::where('vendor_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this party has one or more purchase returns in the system.'], 422);
        }
        if (Payment::where('party_id', $id)->exists()) {
            return response()->json(['error' => 'Cannot delete: this party has one or more payments in the system.'], 422);
        }

        $party->delete();
        return response()->json(['success' => true]);
    }
}
