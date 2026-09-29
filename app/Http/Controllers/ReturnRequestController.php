<?php

namespace App\Http\Controllers;

use App\Models\ReturnRequest;
use App\Services\ReturnService;
use App\Services\UnitAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReturnRequestController extends Controller
{
    public function __construct(
        private readonly UnitAccessService $access,
        private readonly ReturnService $service,
    ) {
    }

    public function approve(Request $request, ReturnRequest $returnRequest)
    {
        $returnRequest->load(['borrowing.unit', 'items']);

        abort_unless(
            $this->access->canManageBorrowing($request->user(), $returnRequest->borrowing->unit),
            403,
        );

        $data = $request->validate([
            'condition' => ['required', 'array'],
            'condition.*' => ['required', Rule::in(['good', 'damaged', 'lost'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->service->verify(
            $returnRequest,
            $request->user(),
            $data['condition'],
            $data['notes'] ?? null,
        );

        return back()->with('success', 'Pengembalian diverifikasi.');
    }

    public function reject(Request $request, ReturnRequest $returnRequest)
    {
        $returnRequest->load('borrowing.unit');

        abort_unless(
            $this->access->canManageBorrowing($request->user(), $returnRequest->borrowing->unit),
            403,
        );

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:3000'],
        ]);

        $this->service->reject($returnRequest, $request->user(), $data['notes']);

        return back()->with('success', 'Permintaan pengembalian ditolak.');
    }

    public function photo(Request $request, ReturnRequest $returnRequest)
    {
        $returnRequest->load('borrowing.unit');

        abort_unless(
            $this->access->canView($request->user(), $returnRequest->borrowing->unit),
            403,
        );

        return Storage::download($returnRequest->photo_object_key);
    }
}
