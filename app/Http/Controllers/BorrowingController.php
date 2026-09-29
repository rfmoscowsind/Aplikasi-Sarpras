<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Unit;
use App\Services\BorrowingService;
use App\Services\UnitAccessService;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function __construct(
        private readonly UnitAccessService $access,
        private readonly BorrowingService $service,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $unitIds = $user->isSarpras()
            ? Unit::query()->pluck('id')
            : $user->unitMemberships()->pluck('unit_id');

        $query = Borrowing::query()
            ->whereIn('unit_id', $unitIds)
            ->with(['unit:id,name,code', 'items.item:id,name'])
            ->latest();

        if ($request->filled('unit_id') && $unitIds->contains((int) $request->integer('unit_id'))) {
            $query->where('unit_id', $request->integer('unit_id'));
        }

        if ($request->filled('status')) {
            if ($request->string('status')->toString() === 'overdue') {
                $query->whereIn('status', ['borrowed', 'return_pending', 'partially_returned'])
                    ->where('expected_return_at', '<', now());
            } else {
                $query->where('status', $request->string('status')->toString());
            }
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('student_name', 'like', $q.'%')
                    ->orWhere('student_nis', 'like', $q.'%')
                    ->orWhere('public_id', 'like', $q.'%');
            });
        }

        $borrowings = $query->paginate(30)->withQueryString();
        $units = Unit::query()->whereIn('id', $unitIds)->orderBy('name')->get(['id', 'name', 'code']);

        return view('borrowings.index', compact('borrowings', 'units'));
    }

    public function show(Request $request, Borrowing $borrowing)
    {
        $borrowing->load([
            'unit',
            'items.item',
            'returnRequests.items.borrowingItem.item',
        ]);

        abort_unless($this->access->canView($request->user(), $borrowing->unit), 403);

        return view('borrowings.show', compact('borrowing'));
    }

    public function approve(Request $request, Borrowing $borrowing)
    {
        $borrowing->load('unit', 'items');
        abort_unless($this->access->canManageBorrowing($request->user(), $borrowing->unit), 403);

        $data = $request->validate([
            'approved' => ['required', 'array'],
            'approved.*' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $this->service->approve($borrowing, $request->user(), $data['approved']);

        return back()->with('success', 'Peminjaman disetujui dan stok sudah direservasi.');
    }

    public function reject(Request $request, Borrowing $borrowing)
    {
        $borrowing->load('unit');
        abort_unless($this->access->canManageBorrowing($request->user(), $borrowing->unit), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->reject($borrowing, $request->user(), $data['reason']);

        return back()->with('success', 'Peminjaman ditolak.');
    }

    public function handOver(Request $request, Borrowing $borrowing)
    {
        $borrowing->load('unit');
        abort_unless($this->access->canManageBorrowing($request->user(), $borrowing->unit), 403);

        $this->service->handOver($borrowing, $request->user());

        return back()->with('success', 'Barang diserahkan. Waktu pinjam tercatat sekarang.');
    }
}
