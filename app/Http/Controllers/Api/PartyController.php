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
use Illuminate\Http\Request;
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
        $user  = $request->get('auth_user');
        $party = Party::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();
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

    public function destroy(Request $request, $id)
    {
        $user  = $request->get('auth_user');
        $party = Party::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

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

    public function ledger(Request $request, $id)
    {
        $user  = $request->get('auth_user');
        $coId  = $user->company_id;
        $party = Party::where('id', $id)->where('company_id', $coId)->firstOrFail();

        $from = $request->query('from');
        $to   = $request->query('to');
        $fromTs = $from ? strtotime($from . ' 00:00:00') * 1000 : null;
        $toTs   = $to   ? strtotime($to   . ' 23:59:59') * 1000 : null;

        $isCustomer = stripos($party->type, 'customer') !== false;
        $entries = [];

        // Sales
        $salesQ = SaleOrder::where('company_id', $coId)->where('customer_id', $id)->get(['id', 'invoice_no', 'total_amount', 'created_at']);
        foreach ($salesQ as $s) {
            $ts = strtotime($s->created_at) * 1000;
            $entries[] = ['date' => $ts, 'type' => 'Sale', 'ref' => $s->invoice_no ?? $s->id, 'debit' => 0, 'credit' => (float)$s->total_amount];
        }

        // Purchase Orders
        $posQ = PurchaseOrder::where('company_id', $coId)->where('vendor_id', $id)->get(['id', 'po_no', 'total_amount', 'created_at']);
        foreach ($posQ as $po) {
            $ts = strtotime($po->created_at) * 1000;
            $entries[] = ['date' => $ts, 'type' => 'Purchase', 'ref' => $po->po_no ?? $po->id, 'debit' => (float)$po->total_amount, 'credit' => 0];
        }

        // Payments
        $paysQ = Payment::where('company_id', $coId)->where('party_id', $id)->get(['type', 'amount', 'reference_no', 'date', 'created_at']);
        foreach ($paysQ as $p) {
            $ts = is_numeric($p->date) ? (int)$p->date : strtotime($p->date) * 1000;
            $isReceived = in_array($p->type, ['Payment Received', 'Receipt']);
            $entries[] = [
                'date'   => $ts,
                'type'   => $isReceived ? 'Payment Received' : 'Payment Made',
                'ref'    => $p->reference_no ?: '—',
                'debit'  => $isReceived ? (float)$p->amount : 0,
                'credit' => $isReceived ? 0 : (float)$p->amount,
            ];
        }

        // Sale Returns
        $srQ = SaleReturn::where('company_id', $coId)->where('customer_id', $id)->get(['id', 'return_no', 'total_amount', 'created_at']);
        foreach ($srQ as $r) {
            $ts = strtotime($r->created_at) * 1000;
            $entries[] = ['date' => $ts, 'type' => 'Sale Return', 'ref' => $r->return_no ?? $r->id, 'debit' => (float)$r->total_amount, 'credit' => 0];
        }

        // Purchase Returns
        $prQ = PurchaseReturn::where('company_id', $coId)->where('vendor_id', $id)->get(['id', 'return_no', 'total_amount', 'created_at']);
        foreach ($prQ as $r) {
            $ts = strtotime($r->created_at) * 1000;
            $entries[] = ['date' => $ts, 'type' => 'Purchase Return', 'ref' => $r->return_no ?? $r->id, 'debit' => 0, 'credit' => (float)$r->total_amount];
        }

        usort($entries, fn($a, $b) => $a['date'] <=> $b['date']);

        // Apply date filter
        if ($fromTs || $toTs) {
            $entries = array_values(array_filter($entries, function ($e) use ($fromTs, $toTs) {
                return (!$fromTs || $e['date'] >= $fromTs) && (!$toTs || $e['date'] <= $toTs);
            }));
        }

        return response()->json([
            'openingBalance' => (float)($party->opening_balance ?? 0),
            'isCustomer'     => $isCustomer,
            'entries'        => $entries,
        ]);
    }

    public function references(Request $request, $id)
    {
        $user  = $request->get('auth_user');
        $coId  = $user->company_id;
        $party = Party::where('id', $id)->where('company_id', $coId)->firstOrFail();

        $refs = [];

        if (str_contains(strtolower($party->type), 'customer')) {
            $sales = SaleOrder::where('company_id', $coId)
                ->where('customer_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get(['id', 'invoice_no', 'total_amount', 'created_at']);

            foreach ($sales as $s) {
                $refs[] = ['id' => $s->invoice_no ?? $s->id, 'amount' => (float) $s->total_amount, 'date' => $s->created_at?->toDateString(), 'type' => 'sale'];
            }

            $returns = SaleReturn::where('company_id', $coId)
                ->where('customer_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get(['id', 'return_no', 'total_amount', 'created_at']);

            foreach ($returns as $r) {
                $refs[] = ['id' => $r->return_no ?? $r->id, 'amount' => (float) $r->total_amount, 'date' => $r->created_at?->toDateString(), 'type' => 'sale_return'];
            }
        } else {
            $pos = PurchaseOrder::where('company_id', $coId)
                ->where('vendor_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get(['id', 'po_no', 'total_amount', 'created_at']);

            foreach ($pos as $po) {
                $refs[] = ['id' => $po->po_no ?? $po->id, 'amount' => (float) $po->total_amount, 'date' => $po->created_at?->toDateString(), 'type' => 'purchase'];
            }

            $returns = PurchaseReturn::where('company_id', $coId)
                ->where('vendor_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get(['id', 'return_no', 'total_amount', 'created_at']);

            foreach ($returns as $r) {
                $refs[] = ['id' => $r->return_no ?? $r->id, 'amount' => (float) $r->total_amount, 'date' => $r->created_at?->toDateString(), 'type' => 'purchase_return'];
            }
        }

        return response()->json(['references' => $refs]);
    }
}
