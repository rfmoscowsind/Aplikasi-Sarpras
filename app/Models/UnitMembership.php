<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitMembership extends Model
{
    protected $fillable = [
        'unit_id',
        'user_id',
        'role',
        'can_manage_inventory',
        'can_manage_borrowing',
    ];

    protected function casts(): array
    {
        return [
            'can_manage_inventory' => 'boolean',
            'can_manage_borrowing' => 'boolean',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
