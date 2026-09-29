<?php

namespace App\Services;

use App\Models\IncomingGood;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IncomingGoodsService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {
    }

    public function create(Unit $centralUnit, User $actor, array $data): IncomingGood
    {
        return DB::transaction(function () use ($centralUnit, $actor, $data) {
            $incoming = IncomingGood::create([
                'central_unit_id' => $centralUnit->id,
                'received_at' => $data['received_at'],
                'supplier' => $data['supplier'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_object_key' => $data['invoice_object_key'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $row) {
                $item = isset($row['item_id'])
                    ? Item::findOrFail($row['item_id'])
                    : Item::create([
                        'name' => $row['name'],
                        'specification' => $row['specification'] ?? null,
                        'borrowable' => (bool) ($row['borrowable'] ?? true),
                        'require_return_photo' => (bool) ($row['require_return_photo'] ?? false),
                        'created_by' => $actor->id,
                    ]);

                $detail = $incoming->items()->create([
                    'item_id' => $item->id,
                    'quantity' => $row['quantity'],
                    'photo_object_key' => $row['photo_object_key'] ?? null,
                ]);

                $this->stock->addAvailable(
                    $centralUnit->id,
                    $item->id,
                    (int) $row['quantity'],
                    'incoming_goods',
                    IncomingGood::class,
                    $incoming->id,
                    $actor->id,
                    ['acquisition_source' => 'other', 'incoming_good_item_id' => $detail->id],
                );
            }

            $this->audit->log($actor, 'incoming_goods.created', $incoming, null, [
                'central_unit_id' => $centralUnit->id,
                'item_count' => count($data['items']),
            ]);

            return $incoming->load('items.item');
        }, 3);
    }
}
