<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        if (!preg_match('/^62[0-9]{8,13}$/', $digits)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP tidak valid.',
            ]);
        }

        return $digits;
    }
}
