<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Unit;
use App\Models\UnitStock;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_cannot_make_source_stock_negative(): void
    {
        [$actor, $source, $target, $item] = $this->fixture();
        $service = app(StockService::class);

        DB::transaction(fn () => $service->addAvailable(
            $source->id,
            $item->id,
            10,
            'test_seed',
            actorId: $actor->id,
        ));

        DB::transaction(fn () => $service->transferAvailable(
            $source->id,
            $target->id,
            $item->id,
            3,
            actorId: $actor->id,
        ));

        $this->assertStock($source->id, $item->id, total: 7, available: 7);
        $this->assertStock($target->id, $item->id, total: 3, available: 3);

        try {
            DB::transaction(fn () => $service->transferAvailable(
                $source->id,
                $target->id,
                $item->id,
                8,
                actorId: $actor->id,
            ));

            $this->fail('Transfer melebihi available stock seharusnya ditolak.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertStock($source->id, $item->id, total: 7, available: 7);
        $this->assertStock($target->id, $item->id, total: 3, available: 3);
    }

    public function test_borrowing_buckets_keep_quantity_invariant(): void
    {
        [$actor, $unit, , $item] = $this->fixture();
        $service = app(StockService::class);

        DB::transaction(fn () => $service->addAvailable(
            $unit->id,
            $item->id,
            5,
            'test_seed',
            actorId: $actor->id,
        ));

        DB::transaction(fn () => $service->reserve(
            $unit->id,
            $item->id,
            2,
            'test_reserve',
            actorId: $actor->id,
        ));

        DB::transaction(fn () => $service->handOverReserved(
            $unit->id,
            $item->id,
            2,
            'test_handover',
            actorId: $actor->id,
        ));

        DB::transaction(fn () => $service->returnBorrowed(
            $unit->id,
            $item->id,
            1,
            'good',
            'test_return',
            actorId: $actor->id,
        ));

        DB::transaction(fn () => $service->returnBorrowed(
            $unit->id,
            $item->id,
            1,
            'damaged',
            'test_return',
            actorId: $actor->id,
        ));

        $stock = UnitStock::query()
            ->where('unit_id', $unit->id)
            ->where('item_id', $item->id)
            ->firstOrFail();

        $this->assertSame(5, $stock->total_qty);
        $this->assertSame(4, $stock->available_qty);
        $this->assertSame(0, $stock->reserved_qty);
        $this->assertSame(0, $stock->borrowed_qty);
        $this->assertSame(1, $stock->damaged_qty);
        $this->assertSame(0, $stock->lost_qty);
        $this->assertTrue($stock->quantityInvariantIsValid());
    }

    private function fixture(): array
    {
        $actor = User::create([
            'name' => 'Admin',
            'email' => uniqid('admin', true).'@example.test',
            'password' => 'password-testing',
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        $source = Unit::create([
            'name' => 'Sarpras Pusat',
            'code' => 'SRC'.random_int(1000, 9999),
            'type' => 'central',
            'borrowing_enabled' => false,
        ]);

        $target = Unit::create([
            'name' => 'Unit Tujuan',
            'code' => 'DST'.random_int(1000, 9999),
            'type' => 'program',
            'borrowing_enabled' => true,
        ]);

        $item = Item::create([
            'name' => 'Projector Test',
            'borrowable' => true,
            'created_by' => $actor->id,
        ]);

        return [$actor, $source, $target, $item];
    }

    private function assertStock(int $unitId, int $itemId, int $total, int $available): void
    {
        $stock = UnitStock::query()
            ->where('unit_id', $unitId)
            ->where('item_id', $itemId)
            ->firstOrFail();

        $this->assertSame($total, $stock->total_qty);
        $this->assertSame($available, $stock->available_qty);
        $this->assertTrue($stock->quantityInvariantIsValid());
    }
}
