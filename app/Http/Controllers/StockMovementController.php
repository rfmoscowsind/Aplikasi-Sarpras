<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::query()
            ->with([
                'unit:id,name,code',
                'item:id,name',
                'actor:id,name',
            ])
            ->latest();

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->integer('unit_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->integer('item_id'));
        }

        if ($request->filled('bucket')) {
            $query->where('bucket', $request->string('bucket')->toString());
        }

        if ($request->filled('reason')) {
            $query->where('reason', 'like', trim((string) $request->input('reason')).'%');
        }

        $movements = $query->paginate(60)->withQueryString();
        $units = Unit::query()->orderBy('name')->get(['id', 'name', 'code']);
        $items = Item::query()->orderBy('name')->get(['id', 'name']);

        return view('stock-movements.index', compact('movements', 'units', 'items'));
    }
}
