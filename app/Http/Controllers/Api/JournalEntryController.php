<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\DocumentSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JournalEntryController extends Controller
{
    public function __construct(protected DocumentSequenceService $sequenceService) {}

    public function index(Request $request)
    {
        $user  = $request->get('auth_user');
        $query = JournalEntry::where('company_id', $user->company_id)
            ->with('lines.account');

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->input('to'));
        }
        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->when($type === 'manual', fn($q) => $q->where('reference_type', 'manual'));
            $query->when($type === 'auto',   fn($q) => $q->where('reference_type', '!=', 'manual')->whereNotNull('reference_type'));
        }
        if ($request->filled('status')) {
            $query->where('is_posted', $request->input('status') === 'posted');
        }

        $sortBy  = $request->input('sort_by', 'date');
        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['entry_no', 'date'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'date';
        $query->orderBy($sortBy, $sortDir);

        $entries = $query->paginate(20);
        return JournalEntryResource::collection($entries);
    }

    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        $data = $request->validate([
            'date'              => 'required|date',
            'description'       => 'required|string|max:500',
            'lines'             => 'required|array|min:2',
            'lines.*.accountId' => 'required|string|exists:chart_of_accounts,id',
            'lines.*.debit'     => 'required|numeric|min:0',
            'lines.*.credit'    => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        $totalDebit  = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return response()->json(['error' => 'Journal entry is not balanced. Debits must equal credits.'], 422);
        }

        $entryId = 'JE-' . Str::random(9);
        $entryNo = $this->sequenceService->getNextNumber($user->company_id, 'journal_entry');

        $postImmediately = $request->boolean('postImmediately', false);

        $entry = JournalEntry::create([
            'id'             => $entryId,
            'company_id'     => $user->company_id,
            'entry_no'       => $entryNo,
            'date'           => $data['date'],
            'description'    => $data['description'],
            'reference_type' => 'manual',
            'reference_id'   => null,
            'is_posted'      => $postImmediately,
            'created_by'     => $user->id,
        ]);

        foreach ($data['lines'] as $line) {
            JournalEntryLine::create([
                'id'               => 'JEL-' . Str::random(9),
                'journal_entry_id' => $entryId,
                'account_id'       => $line['accountId'],
                'description'      => $line['description'] ?? null,
                'debit'            => $line['debit'],
                'credit'           => $line['credit'],
            ]);
        }

        $entry->load('lines.account');
        return new JournalEntryResource($entry);
    }

    public function show($id)
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);
        return new JournalEntryResource($entry);
    }

    public function post($id)
    {
        $entry = JournalEntry::findOrFail($id);

        if ($entry->is_posted) {
            return response()->json(['error' => 'Entry is already posted'], 422);
        }

        $entry->update(['is_posted' => true]);
        return new JournalEntryResource($entry->load('lines.account'));
    }

    public function destroy($id)
    {
        $entry = JournalEntry::findOrFail($id);

        if ($entry->is_posted) {
            return response()->json(['error' => 'Posted entries cannot be deleted'], 422);
        }

        $entry->delete();
        return response()->json(['success' => true]);
    }
}