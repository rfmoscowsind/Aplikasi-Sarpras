<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {
    }

    public function verify(ReturnRequest $returnRequest, User $actor, array $verifiedConditions, ?string $notes = null): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequest, $actor, $verifiedConditions, $notes) {
            $locked = ReturnRequest::query()
                ->with([
                    'items' => fn ($query) => $query->orderBy('borrowing_item_id'),
                    'items.borrowingItem',
                    'borrowing.items',
                ])
                ->lockForUpdate()
                ->findOrFail($returnRequest->id);

            if ($locked->status === 'approved') {
                return $locked;
            }

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'return' => 'Permintaan pengembalian sudah diproses.',
                ]);
            }

            $borrowing = Borrowing::query()
                ->lockForUpdate()
                ->findOrFail($locked->borrowing_id);

            foreach ($locked->items as $returnItem) {
                $borrowingItem = $returnItem->borrowingItem;
                $condition = $verifiedConditions[$returnItem->id] ?? $returnItem->borrower_condition;

                if (!in_array($condition, ['good', 'damaged', 'lost'], true)) {
                    throw ValidationException::withMessages([
                        'condition' => 'Kondisi verifikasi tidak valid.',
                    ]);
                }

                $outstanding = $borrowingItem->handed_over_qty - $borrowingItem->returned_qty;

                if ($returnItem->quantity < 1 || $returnItem->quantity > $outstanding) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah pengembalian melebihi barang yang masih dipinjam.',
                    ]);
                }

                $this->stock->returnBorrowed(
                    $borrowing->unit_id,
                    $borrowingItem->item_id,
                    $returnItem->quantity,
                    $condition,
                    'borrowing_return',
                    ReturnRequest::class,
                    $locked->id,
                    $actor->id,
                );

                $borrowingItem->increment('returned_qty', $returnItem->quantity);

                if ($condition === 'damaged') {
                    $borrowingItem->increment('damaged_qty', $returnItem->quantity);
                } elseif ($condition === 'lost') {
                    $borrowingItem->increment('lost_qty', $returnItem->quantity);
                }

                $returnItem->verified_condition = $condition;
                $returnItem->save();
            }

            $locked->update([
                'status' => 'approved',
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verification_notes' => $notes,
            ]);

            $borrowing->load('items');
            $allResolved = $borrowing->items->every(
                fn ($item) => $item->returned_qty >= $item->handed_over_qty
            );

            $borrowing->update([
                'status' => $allResolved ? 'completed' : 'partially_returned',
                'completed_at' => $allResolved ? now() : null,
            ]);

            $this->audit->log($actor, 'return.verified', $locked, null, [
                'borrowing_id' => $borrowing->id,
                'borrowing_status' => $borrowing->status,
            ]);

            return $locked->fresh(['items.borrowingItem.item', 'borrowing']);
        }, 3);
    }

    public function reject(ReturnRequest $returnRequest, User $actor, string $notes): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequest, $actor, $notes) {
            $locked = ReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);

            if ($locked->status === 'rejected') {
                return $locked;
            }

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'return' => 'Permintaan pengembalian sudah diproses.',
                ]);
            }

            $borrowing = Borrowing::query()->lockForUpdate()->findOrFail($locked->borrowing_id);

            $hasReturned = $borrowing->items()->where('returned_qty', '>', 0)->exists();

            $locked->update([
                'status' => 'rejected',
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verification_notes' => $notes,
            ]);

            $borrowing->update([
                'status' => $hasReturned ? 'partially_returned' : 'borrowed',
            ]);

            $this->audit->log($actor, 'return.rejected', $locked, null, [
                'notes' => $notes,
            ]);

            return $locked;
        });
    }
}
