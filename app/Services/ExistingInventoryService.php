<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Unit;
use App\Models\UnitStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExistingInventoryService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {
    }

    public function add(Unit $unit, User $actor, array $data): UnitStock
    {
        return DB::transaction(function () use ($unit, $actor, $data) {
            $item = !empty($data['item_id'])
                ? Item::findOrFail($data['item_id'])
                : Item::create([
                    'name' => $data['name'],
                    'specification' => $data['specification'] ?? null,
                    'borrowable' => (bool) ($data['borrowable'] ?? true),
                    'require_return_photo' => (bool) ($data['require_return_photo'] ?? false),
                    'created_by' => $actor->id,
                ]);

            $existing = UnitStock::query()
                ->where('unit_id', $unit->id)
                ->where('item_id', $item->id)
                ->exists();

            if ($existing && empty($data['allow_increment'])) {
                throw ValidationException::withMessages([
                    'item_id' => 'Barang sudah tercatat di unit. Gunakan penambahan stok jika memang ingin menambah jumlah.',
                ]);
            }

            $this->stock->addAvailable(
                $unit->id,
                $item->id,
                (int) $data['quantity'],
                'unit_existing',
                UnitStock::class,
                null,
                $actor->id,
                ['acquisition_source' => 'unit_existing'],
            );

            $stock = UnitStock::query()
                ->where('unit_id', $unit->id)
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!empty($data['photo_object_key'])) {
                $stock->photo_object_key = $data['photo_object_key'];
            }
            if (array_key_exists('notes', $data)) {
                $stock->notes = $data['notes'];
            }
            $stock->acquisition_source = 'unit_existing';
            $stock->created_by ??= $actor->id;
            $stock->save();

            $this->audit->log($actor, 'inventory.existing_added', $stock, null, [
                'unit_id' => $unit->id,
                'item_id' => $item->id,
                'quantity_added' => (int) $data['quantity'],
            ]);

            return $stock->load(['item', 'unit', 'creator']);
        }, 3);
    }
}
