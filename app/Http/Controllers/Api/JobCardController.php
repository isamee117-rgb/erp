<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobCardRequest;
use App\Http\Requests\UpdateJobCardRequest;
use App\Http\Resources\JobCardItemResource;
use App\Http\Resources\JobCardResource;
use App\Models\JobCard;
use App\Services\JobCardService;
use Illuminate\Http\Request;

class JobCardController extends Controller
{
    public function __construct(protected JobCardService $service) {}

    public function index(Request $request)
    {
        $user  = $request->get('auth_user');
        $cards = JobCard::with('items')
                        ->forCompany($user->company_id)
                        ->where('status', 'open')
                        ->get();

        return JobCardResource::collection($cards);
    }

    public function store(StoreJobCardRequest $request)
    {
        $user = $request->get('auth_user');
        $card = $this->service->create($user, $request->validated());
        return (new JobCardResource($card->load('items')))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->get('auth_user');
        $card = JobCard::with('items')
                       ->forCompany($user->company_id)
                       ->findOrFail($id);

        return new JobCardResource($card);
    }

    public function update(UpdateJobCardRequest $request, string $id)
    {
        $user = $request->get('auth_user');
        $card = JobCard::forCompany($user->company_id)
                       ->where('status', 'open')
                       ->findOrFail($id);

        $card = $this->service->updateHeader($card, $request->validated());
        return new JobCardResource($card->load('items'));
    }

    public function addItem(Request $request, string $id)
    {
        $user = $request->get('auth_user');
        $card = JobCard::forCompany($user->company_id)
                       ->where('status', 'open')
                       ->findOrFail($id);

        $request->validate([
            'itemType'  => 'required|string|in:part,service',
            'productId' => 'required|string|exists:products,id',
            'quantity'  => 'required|numeric|min:0.001',
            'unitPrice' => 'required|numeric|min:0',
            'discount'  => 'sometimes|numeric|min:0',
        ]);

        try {
            $item = $this->service->addItem($card, $request->all());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return (new JobCardItemResource($item))->response()->setStatusCode(201);
    }

    public function updateItem(Request $request, string $id, string $itemId)
    {
        $user = $request->get('auth_user');
        $card = JobCard::forCompany($user->company_id)->findOrFail($id);

        $request->validate([
            'quantity'  => 'sometimes|numeric|min:0.001',
            'unitPrice' => 'sometimes|numeric|min:0',
            'discount'  => 'sometimes|numeric|min:0',
        ]);

        try {
            $item = $this->service->updateItem($card, $itemId, $request->all());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return new JobCardItemResource($item);
    }

    public function removeItem(Request $request, string $id, string $itemId)
    {
        $user = $request->get('auth_user');
        $card = JobCard::forCompany($user->company_id)
                       ->where('status', 'open')
                       ->findOrFail($id);

        try {
            $this->service->removeItem($card, $itemId);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function finalize(Request $request, string $id)
    {
        $user = $request->get('auth_user');
        $card = JobCard::with('items')
                       ->forCompany($user->company_id)
                       ->where('status', 'open')
                       ->findOrFail($id);

        if ($card->items->isEmpty()) {
            return response()->json(['error' => 'Cannot finalize a job card with no items'], 422);
        }

        try {
            $card = $this->service->finalize($card, $user);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return new JobCardResource($card->load('items'));
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->get('auth_user');
        $card = JobCard::forCompany($user->company_id)
                       ->where('status', 'open')
                       ->findOrFail($id);

        $card->delete();
        return response()->json(['success' => true]);
    }

    public function history(Request $request)
    {
        $user  = $request->get('auth_user');
        $cards = JobCard::forCompany($user->company_id)
                        ->where('status', 'closed')
                        ->orderByDesc('closed_at')
                        ->paginate(25);

        return JobCardResource::collection($cards);
    }
}
