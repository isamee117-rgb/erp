<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Party;
use App\Models\Payment;
use App\Services\JournalPostingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(protected JournalPostingService $journalService) {}

    public function store(StorePaymentRequest $request)
    {
        $user    = $request->get('auth_user');
        $data    = $request->validated();
        $partyId = $data['partyId'];

        $payment = Payment::create([
            'id'             => 'PAY-' . Str::random(9),
            'company_id'     => $user->company_id,
            'party_id'       => $partyId,
            'date'           => $data['date'] ?? now()->getTimestampMs(),
            'amount'         => $data['amount'],
            'payment_method' => $data['paymentMethod'],
            'type'           => $data['type'],
            'reference_no'   => $data['referenceNo'] ?? '',
            'notes'          => $data['notes'] ?? '',
            'gl_account_id'  => $data['glAccountId'] ?? null,
        ]);

        $party = Party::find($partyId);
        if ($party) {
            $isDecrease = ($party->type === 'Customer' && $payment->type === 'Receipt') ||
                          ($party->type === 'Vendor'   && $payment->type === 'Payment');
            $party->current_balance += $payment->amount * ($isDecrease ? -1 : 1);
            $party->save();
        }

        try {
            $this->journalService->postPayment($payment, $user->id);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for payment', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
        }

        return new PaymentResource($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        $party = Party::find($payment->party_id);
        if ($party) {
            $isDecrease = ($party->type === 'Customer' && $payment->type === 'Receipt') ||
                          ($party->type === 'Vendor'   && $payment->type === 'Payment');
            $party->current_balance += $payment->amount * ($isDecrease ? 1 : -1);
            $party->save();
        }

        $payment->delete();
        return response()->json(['success' => true]);
    }
}
