<?php

namespace App\Services;

use App\Models\UnitStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Move available stock between units atomically.
     *
     * Caller is responsible for creating the domain reference
     * (distribution, correction, etc). This method never permits
     * the source stock to become negative.
     */
    public function transferAvailable(
        int $sourceUnitId,
        int $targetUnitId,
        int $itemId,
        int $quantity,
    ): void {
        if ($quantity < 1 || $sourceUnitId === $targetUnitId) {
            throw ValidationException::withMessages([
                'quantity' => 'Mutasi stok tidak valid.',
            ]);
        }

        DB::transaction(function () use ($sourceUnitId, $targetUnitId, $itemId, $quantity): void {
            $ids = [$sourceUnitId, $targetUnitId];
            sort($ids);

            // Lock in deterministic unit order to reduce deadlock risk.
            $stocks = UnitStock::query()
                ->where('item_id', $itemId)
                ->whereIn('unit_id', $ids)
                ->orderBy('unit_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('unit_id');

            $source = $stocks->get($sourceUnitId);
            if (!$source || $source->available_qty < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tersedia tidak mencukupi.',
                ]);
            }

            $target = $stocks->get($targetUnitId);
            if (!$target) {
                $target = UnitStock::create([
                    'unit_id' => $targetUnitId,
                    'item_id' => $itemId,
                    'total_qty' => 0,
                    'available_qty' => 0,
                ]);

                // Newly created row belongs to this transaction.
                $target->refresh();
            }

            $source->decrement('available_qty', $quantity);
            $source->decrement('total_qty', $quantity);

            $target->increment('available_qty', $quantity);
            $target->increment('total_qty', $quantity);
        }, 3);
    }
}
