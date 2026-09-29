<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.test',
            'password' => 'password-testing',
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-testing',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_is_rejected(): void
    {
        $user = User::create([
            'name' => 'Disabled',
            'email' => 'disabled@example.test',
            'password' => 'password-testing',
            'system_role' => 'unit',
            'is_active' => false,
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-testing',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
