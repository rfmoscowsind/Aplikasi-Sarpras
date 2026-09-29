<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowingService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {
    }

    public function approve(Borrowing $borrowing, User $actor, array $approvedQuantities): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $actor, $approvedQuantities) {
            $locked = Borrowing::query()
                ->with(['items' => fn ($query) => $query->orderBy('item_id')])
                ->lockForUpdate()
                ->findOrFail($borrowing->id);

            if ($locked->status === 'approved') {
                return $locked;
            }

            if ($locked->status !== 'pending_approval') {
                throw ValidationException::withMessages([
                    'borrowing' => 'Peminjaman ini tidak dapat disetujui dari status saat ini.',
                ]);
            }

            foreach ($locked->items as $item) {
                $qty = (int) ($approvedQuantities[$item->id] ?? $item->requested_qty);

                if ($qty < 1 || $qty > $item->requested_qty) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah yang disetujui tidak valid.',
                    ]);
                }

                $this->stock->reserve(
                    $locked->unit_id,
                    $item->item_id,
                    $qty,
                    'borrowing_approved',
                    Borrowing::class,
                    $locked->id,
                    $actor->id,
                );

                $item->approved_qty = $qty;
                $item->save();
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->log($actor, 'borrowing.approved', $locked);

            return $locked->fresh(['items.item']);
        }, 3);
    }

    public function reject(Borrowing $borrowing, User $actor, string $reason): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $actor, $reason) {
            $locked = Borrowing::query()->lockForUpdate()->findOrFail($borrowing->id);

            if ($locked->status === 'rejected') {
                return $locked;
            }

            if ($locked->status !== 'pending_approval') {
                throw ValidationException::withMessages([
                    'borrowing' => 'Peminjaman ini tidak dapat ditolak dari status saat ini.',
                ]);
            }

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
            ]);

            $this->audit->log($actor, 'borrowing.rejected', $locked, null, [
                'reason' => $reason,
            ]);

            return $locked;
        });
    }

    public function handOver(Borrowing $borrowing, User $actor): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $actor) {
            $locked = Borrowing::query()
                ->with(['items' => fn ($query) => $query->orderBy('item_id')])
                ->lockForUpdate()
                ->findOrFail($borrowing->id);

            if (in_array($locked->status, ['borrowed', 'return_pending', 'partially_returned', 'completed'], true)) {
                return $locked;
            }

            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages([
                    'borrowing' => 'Peminjaman harus disetujui sebelum barang diserahkan.',
                ]);
            }

            foreach ($locked->items as $item) {
                $qty = (int) $item->approved_qty;

                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada item yang belum memiliki jumlah disetujui.',
                    ]);
                }

                $this->stock->handOverReserved(
                    $locked->unit_id,
                    $item->item_id,
                    $qty,
                    'borrowing_handover',
                    Borrowing::class,
                    $locked->id,
                    $actor->id,
                );

                $item->handed_over_qty = $qty;
                $item->save();
            }

            $locked->update([
                'status' => 'borrowed',
                'handed_over_by' => $actor->id,
                'borrowed_at' => now(),
            ]);

            $this->audit->log($actor, 'borrowing.handed_over', $locked);

            return $locked->fresh(['items.item']);
        }, 3);
    }
}
