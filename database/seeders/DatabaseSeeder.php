<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Unit::query()->firstOrCreate(
            ['code' => 'SARPRAS'],
            [
                'name' => 'Sarpras Pusat',
                'type' => 'central',
                'borrow_public_token' => Str::random(48),
                'borrowing_enabled' => false,
            ],
        );

        $email = trim((string) env('SARPRAS_ADMIN_EMAIL', ''));
        $password = (string) env('SARPRAS_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn('SARPRAS_ADMIN_EMAIL/PASSWORD belum diisi; akun admin tidak dibuat.');
            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SARPRAS_ADMIN_NAME', 'Administrator Sarpras'),
                'password' => Hash::make($password),
                'system_role' => 'admin',
                'is_active' => true,
            ],
        );

        $this->command?->info('Admin Sarpras siap: '.$email);
    }
}
