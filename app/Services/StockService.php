<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\UnitStock;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function addAvailable(
        int $unitId,
        int $itemId,
        int $quantity,
        string $reason,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $actorId = null,
        array $metadata = [],
    ): void {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah harus lebih dari nol.']);
        }

        UnitStock::query()->insertOrIgnore([
            'unit_id' => $unitId,
            'item_id' => $itemId,
            'total_qty' => 0,
            'available_qty' => 0,
            'reserved_qty' => 0,
            'borrowed_qty' => 0,
            'damaged_qty' => 0,
            'lost_qty' => 0,
            'acquisition_source' => $metadata['acquisition_source'] ?? 'other',
            'created_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stock = $this->lockStock($unitId, $itemId);
        $stock->increment('available_qty', $quantity);
        $stock->increment('total_qty', $quantity);

        $this->movement($unitId, $itemId, 'available', $quantity, $reason, $referenceType, $referenceId, $actorId, $metadata);
    }

    public function reserve(int $unitId, int $itemId, int $quantity, string $reason, ?string $referenceType = null, ?int $referenceId = null, ?int $actorId = null): void
    {
        $stock = $this->lockStock($unitId, $itemId);

        if ($quantity < 1 || $stock->available_qty < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Stok tersedia tidak mencukupi.']);
        }

        $stock->decrement('available_qty', $quantity);
        $stock->increment('reserved_qty', $quantity);

        $this->movement($unitId, $itemId, 'available', -$quantity, $reason, $referenceType, $referenceId, $actorId);
        $this->movement($unitId, $itemId, 'reserved', $quantity, $reason, $referenceType, $referenceId, $actorId);
    }

    public function releaseReservation(int $unitId, int $itemId, int $quantity, string $reason, ?string $referenceType = null, ?int $referenceId = null, ?int $actorId = null): void
    {
        $stock = $this->lockStock($unitId, $itemId);

        if ($quantity < 1 || $stock->reserved_qty < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Reserved stock tidak mencukupi.']);
        }

        $stock->decrement('reserved_qty', $quantity);
        $stock->increment('available_qty', $quantity);

        $this->movement($unitId, $itemId, 'reserved', -$quantity, $reason, $referenceType, $referenceId, $actorId);
        $this->movement($unitId, $itemId, 'available', $quantity, $reason, $referenceType, $referenceId, $actorId);
    }

    public function handOverReserved(int $unitId, int $itemId, int $quantity, string $reason, ?string $referenceType = null, ?int $referenceId = null, ?int $actorId = null): void
    {
        $stock = $this->lockStock($unitId, $itemId);

        if ($quantity < 1 || $stock->reserved_qty < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Reserved stock tidak mencukupi.']);
        }

        $stock->decrement('reserved_qty', $quantity);
        $stock->increment('borrowed_qty', $quantity);

        $this->movement($unitId, $itemId, 'reserved', -$quantity, $reason, $referenceType, $referenceId, $actorId);
        $this->movement($unitId, $itemId, 'borrowed', $quantity, $reason, $referenceType, $referenceId, $actorId);
    }

    public function returnBorrowed(int $unitId, int $itemId, int $quantity, string $condition, string $reason, ?string $referenceType = null, ?int $referenceId = null, ?int $actorId = null): void
    {
        $stock = $this->lockStock($unitId, $itemId);

        if ($quantity < 1 || $stock->borrowed_qty < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Borrowed stock tidak mencukupi.']);
        }

        if (!in_array($condition, ['good', 'damaged', 'lost'], true)) {
            throw ValidationException::withMessages(['condition' => 'Kondisi pengembalian tidak valid.']);
        }

        $stock->decrement('borrowed_qty', $quantity);
        $bucket = $condition === 'good' ? 'available' : $condition;
        $stock->increment($bucket.'_qty', $quantity);

        $this->movement($unitId, $itemId, 'borrowed', -$quantity, $reason, $referenceType, $referenceId, $actorId);
        $this->movement($unitId, $itemId, $bucket, $quantity, $reason, $referenceType, $referenceId, $actorId);
    }

    public function transferAvailable(
        int $sourceUnitId,
        int $targetUnitId,
        int $itemId,
        int $quantity,
        string $reason = 'distribution',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $actorId = null,
    ): void {
        if ($quantity < 1 || $sourceUnitId === $targetUnitId) {
            throw ValidationException::withMessages(['quantity' => 'Mutasi stok tidak valid.']);
        }

        UnitStock::query()->insertOrIgnore([
            'unit_id' => $targetUnitId,
            'item_id' => $itemId,
            'total_qty' => 0,
            'available_qty' => 0,
            'reserved_qty' => 0,
            'borrowed_qty' => 0,
            'damaged_qty' => 0,
            'lost_qty' => 0,
            'acquisition_source' => 'central_distribution',
            'created_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitIds = [$sourceUnitId, $targetUnitId];
        sort($unitIds);

        $stocks = UnitStock::query()
            ->where('item_id', $itemId)
            ->whereIn('unit_id', $unitIds)
            ->orderBy('unit_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('unit_id');

        $source = $stocks->get($sourceUnitId);
        $target = $stocks->get($targetUnitId);

        if (!$source || !$target || $source->available_qty < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Stok tersedia tidak mencukupi.']);
        }

        $source->decrement('available_qty', $quantity);
        $source->decrement('total_qty', $quantity);
        $target->increment('available_qty', $quantity);
        $target->increment('total_qty', $quantity);

        $this->movement($sourceUnitId, $itemId, 'available', -$quantity, $reason, $referenceType, $referenceId, $actorId, ['target_unit_id' => $targetUnitId]);
        $this->movement($targetUnitId, $itemId, 'available', $quantity, $reason, $referenceType, $referenceId, $actorId, ['source_unit_id' => $sourceUnitId]);
    }

    private function lockStock(int $unitId, int $itemId): UnitStock
    {
        return UnitStock::query()
            ->where('unit_id', $unitId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function movement(int $unitId, int $itemId, string $bucket, int $delta, string $reason, ?string $referenceType, ?int $referenceId, ?int $actorId, array $metadata = []): void
    {
        StockMovement::create([
            'unit_id' => $unitId,
            'item_id' => $itemId,
            'bucket' => $bucket,
            'delta' => $delta,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'actor_id' => $actorId,
            'metadata' => $metadata ?: null,
        ]);
    }
}
