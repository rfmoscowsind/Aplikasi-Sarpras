<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Unit;
use App\Models\UnitStock;
use App\Services\ExistingInventoryService;
use App\Services\UnitAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnitInventoryController extends Controller
{
    public function __construct(
        private readonly UnitAccessService $access,
        private readonly ExistingInventoryService $inventory,
    ) {
    }

    public function index(Request $request, Unit $unit)
    {
        abort_unless($this->access->canView($request->user(), $unit), 403);

        $stocks = $unit->stocks()
            ->with(['item', 'creator:id,name'])
            ->orderByDesc('updated_at')
            ->paginate(40);

        $catalog = Item::query()->orderBy('name')->limit(200)->get(['id', 'name', 'specification']);

        return view('inventory.index', compact('unit', 'stocks', 'catalog'));
    }

    public function photo(Request $request, Unit $unit, UnitStock $stock)
    {
        abort_unless($stock->unit_id === $unit->id, 404);
        abort_unless($this->access->canView($request->user(), $unit), 403);
        abort_unless($stock->photo_object_key, 404);

        return Storage::response(
            $stock->photo_object_key,
            basename($stock->photo_object_key),
            ['Cache-Control' => 'private, no-store, max-age=0'],
        );
    }

    public function storeExisting(Request $request, Unit $unit)
    {
        abort_unless($this->access->canManageInventory($request->user(), $unit), 403);

        $data = $request->validate([
            'item_id' => ['nullable', 'exists:items,id'],
            'name' => ['required_without:item_id', 'nullable', 'string', 'max:190'],
            'specification' => ['nullable', 'string', 'max:3000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'borrowable' => ['nullable', 'boolean'],
            'require_return_photo' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'allow_increment' => ['nullable', 'boolean'],
        ]);

        $photoPath = null;

        try {
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('unit-inventory/'.$unit->id);
            }

            $this->inventory->add($unit, $request->user(), [
                ...$data,
                'borrowable' => $request->boolean('borrowable', true),
                'require_return_photo' => $request->boolean('require_return_photo'),
                'allow_increment' => $request->boolean('allow_increment'),
                'photo_object_key' => $photoPath,
            ]);
        } catch (\Throwable $exception) {
            if ($photoPath) {
                Storage::delete($photoPath);
            }
            throw $exception;
        }

        return back()->with('success', 'Inventaris unit berhasil ditambahkan.');
    }
}
