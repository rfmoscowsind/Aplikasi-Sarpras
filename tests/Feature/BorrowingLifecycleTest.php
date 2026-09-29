<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Item;
use App\Models\ReturnRequest;
use App\Models\Unit;
use App\Models\UnitStock;
use App\Models\User;
use App\Services\BorrowingService;
use App\Services\ReturnService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_borrowing_and_return_lifecycle_preserves_stock_invariant(): void
    {
        $actor = User::create([
            'name' => 'Petugas Unit',
            'email' => 'petugas@example.test',
            'password' => 'password-testing',
            'system_role' => 'unit',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'name' => 'TJKT',
            'code' => 'TJKT',
            'type' => 'program',
            'borrowing_enabled' => true,
        ]);

        $item = Item::create([
            'name' => 'Proyektor Epson',
            'borrowable' => true,
            'created_by' => $actor->id,
        ]);

        app(StockService::class)->addAvailable(
            $unit->id,
            $item->id,
            5,
            'test_seed',
            actorId: $actor->id,
        );

        $borrowing = Borrowing::create([
            'public_id' => '11111111-1111-4111-8111-111111111111',
            'unit_id' => $unit->id,
            'juara_student_id' => 123,
            'student_name' => 'Siswa Test',
            'student_nis' => '12345',
            'student_class' => 'XII TJKT 1',
            'phone' => '6281234567890',
            'purpose' => 'Presentasi',
            'expected_return_at' => now()->addDay(),
            'status' => 'pending_approval',
        ]);

        $borrowingItem = $borrowing->items()->create([
            'item_id' => $item->id,
            'requested_qty' => 2,
        ]);

        app(BorrowingService::class)->approve(
            $borrowing,
            $actor,
            [$borrowingItem->id => 2],
        );

        $stock = $this->stock($unit->id, $item->id);
        $this->assertSame(3, $stock->available_qty);
        $this->assertSame(2, $stock->reserved_qty);
        $this->assertTrue($stock->quantityInvariantIsValid());

        app(BorrowingService::class)->handOver($borrowing->fresh(), $actor);

        $stock = $this->stock($unit->id, $item->id);
        $this->assertSame(3, $stock->available_qty);
        $this->assertSame(0, $stock->reserved_qty);
        $this->assertSame(2, $stock->borrowed_qty);
        $this->assertTrue($stock->quantityInvariantIsValid());

        $returnRequest = ReturnRequest::create([
            'public_id' => '22222222-2222-4222-8222-222222222222',
            'borrowing_id' => $borrowing->id,
            'status' => 'pending',
            'photo_object_key' => 'returns/test.jpg',
        ]);

        $returnItem = $returnRequest->items()->create([
            'borrowing_item_id' => $borrowingItem->id,
            'quantity' => 2,
            'borrower_condition' => 'good',
        ]);

        app(ReturnService::class)->verify(
            $returnRequest,
            $actor,
            [$returnItem->id => 'good'],
            'Barang lengkap dan normal.',
        );

        $stock = $this->stock($unit->id, $item->id);
        $this->assertSame(5, $stock->total_qty);
        $this->assertSame(5, $stock->available_qty);
        $this->assertSame(0, $stock->reserved_qty);
        $this->assertSame(0, $stock->borrowed_qty);
        $this->assertSame(0, $stock->damaged_qty);
        $this->assertSame(0, $stock->lost_qty);
        $this->assertTrue($stock->quantityInvariantIsValid());

        $this->assertSame('completed', $borrowing->fresh()->status);
        $this->assertSame('approved', $returnRequest->fresh()->status);
        $this->assertSame(2, $borrowingItem->fresh()->returned_qty);
    }

    private function stock(int $unitId, int $itemId): UnitStock
    {
        return UnitStock::query()
            ->where('unit_id', $unitId)
            ->where('item_id', $itemId)
            ->firstOrFail()
            ->fresh();
    }
}
