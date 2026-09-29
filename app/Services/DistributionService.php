<?php

namespace App\Services;

use App\Models\Distribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributionService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {
    }

    public function cancel(Distribution $distribution, User $actor): Distribution
    {
        return DB::transaction(function () use ($distribution, $actor) {
            $locked = Distribution::query()
                ->with(['items' => fn ($query) => $query->orderBy('item_id')])
                ->lockForUpdate()
                ->findOrFail($distribution->id);

            if ($locked->status === 'cancelled') {
                return $locked;
            }

            if ($locked->status === 'completed') {
                throw ValidationException::withMessages([
                    'distribution' => 'Distribusi yang sudah selesai tidak dapat dibatalkan.',
                ]);
            }

            foreach ($locked->items as $item) {
                $this->stock->releaseReservation(
                    $locked->source_unit_id,
                    $item->item_id,
                    $item->quantity,
                    'distribution_cancelled',
                    Distribution::class,
                    $locked->id,
                    $actor->id,
                );
            }

            $locked->update(['status' => 'cancelled']);

            $this->audit->log($actor, 'distribution.cancelled', $locked);

            return $locked->fresh(['items.item', 'sourceUnit', 'targetUnit']);
        }, 3);
    }

    public function complete(Distribution $distribution, User $actor): Distribution
    {
        return DB::transaction(function () use ($distribution, $actor) {
            $locked = Distribution::query()
                ->with(['items' => fn ($query) => $query->orderBy('item_id')])
                ->lockForUpdate()
                ->findOrFail($distribution->id);

            if ($locked->status === 'completed') {
                return $locked;
            }

            if ($locked->status !== 'awaiting_signed_document' || !$locked->signed_document_object_key) {
                throw ValidationException::withMessages([
                    'distribution' => 'Surat bertanda tangan harus di-upload sebelum distribusi dikonfirmasi.',
                ]);
            }

            foreach ($locked->items as $item) {
                $this->stock->transferReserved(
                    $locked->source_unit_id,
                    $locked->target_unit_id,
                    $item->item_id,
                    $item->quantity,
                    'distribution',
                    Distribution::class,
                    $locked->id,
                    $actor->id,
                );
            }

            $locked->update([
                'status' => 'completed',
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $this->audit->log($actor, 'distribution.completed', $locked, null, [
                'source_unit_id' => $locked->source_unit_id,
                'target_unit_id' => $locked->target_unit_id,
            ]);

            return $locked->fresh(['items.item', 'sourceUnit', 'targetUnit']);
        }, 3);
    }
}
