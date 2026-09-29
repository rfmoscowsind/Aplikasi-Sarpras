<?php

namespace Tests\Feature;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicBorrowSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_borrow_page_is_not_indexable_or_cacheable(): void
    {
        $unit = Unit::create([
            'name' => 'TJKT',
            'code' => 'TJKT',
            'type' => 'program',
            'borrow_public_token' => 'test-public-token-abcdefghijklmnopqrstuvwxyz',
            'borrow_pin_hash' => Hash::make('123456'),
            'borrowing_enabled' => true,
        ]);

        $response = $this->get(route('public.borrow.start', $unit->borrow_public_token));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->assertHeader('Cache-Control');
        $response->assertSee('PIN Unit');
    }
}
