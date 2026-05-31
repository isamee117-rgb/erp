<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Http\Request;
use App\Http\Resources\PaymentResource;
use App\Models\Party;
use App\Models\Payment;
use App\Services\JournalPostingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(protected JournalPostingService $journalService) {}

    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $coId = $user->company_id;

        $query = Payment::with('party')
            ->where('company_id', $coId)
            ->orderBy('created_at', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('party', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($type = $request->query('type')) {
            $typeMap = ['Payment Received' => 'Receipt', 'Payment Made' => 'Payment'];
            $query->where('type', $typeMap[$type] ?? $type);
        }

        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return PaymentResource::collection($query->paginate(50));
    }

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

        $journalWarning = null;
        try {
            $this->journalService->postPayment($payment, $user->id);
        } catch (\Throwable $e) {
            Log::error('Journal posting failed for payment', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            $journalWarning = $e->getMessage();
        }

        return (new PaymentResource($payment))->additional(array_filter(['warning' => $journalWarning]));
    }

    public function destroy(Request $request, $id)
    {
        $user    = $request->get('auth_user');
        $payment = Payment::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

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
