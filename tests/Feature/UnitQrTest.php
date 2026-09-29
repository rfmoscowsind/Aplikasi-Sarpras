<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_render_unit_qr_locally(): void
    {
        $user = User::create([
            'name' => 'Admin QR',
            'email' => 'qr@example.test',
            'password' => 'password-testing',
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'name' => 'TJKT',
            'code' => 'TJKT',
            'type' => 'program',
            'borrow_public_token' => str_repeat('a', 48),
            'borrowing_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get(route('units.qr', $unit));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }
}
