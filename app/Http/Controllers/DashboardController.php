<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Unit;
use App\Models\UnitStock;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $unitIds = $user->isSarpras()
            ? Unit::query()->pluck('id')
            : $user->unitMemberships()->pluck('unit_id');

        $stockQuery = UnitStock::query()->whereIn('unit_id', $unitIds);
        $borrowQuery = Borrowing::query()->whereIn('unit_id', $unitIds);

        $cards = [
            'item_types' => (clone $stockQuery)->distinct('item_id')->count('item_id'),
            'total_qty' => (int) (clone $stockQuery)->sum('total_qty'),
            'borrowed_qty' => (int) (clone $stockQuery)->sum('borrowed_qty'),
            'damaged_qty' => (int) (clone $stockQuery)->sum('damaged_qty'),
            'pending' => (clone $borrowQuery)->where('status', 'pending_approval')->count(),
            'return_pending' => (clone $borrowQuery)->where('status', 'return_pending')->count(),
            'overdue' => (clone $borrowQuery)
                ->whereIn('status', ['borrowed', 'return_pending', 'partially_returned'])
                ->where('expected_return_at', '<', now())
                ->count(),
        ];

        $recentBorrowings = (clone $borrowQuery)
            ->with(['unit:id,name', 'items.item:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        $units = Unit::query()
            ->whereIn('id', $unitIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return view('dashboard', compact('cards', 'recentBorrowings', 'units'));
    }
}
