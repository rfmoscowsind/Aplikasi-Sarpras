<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'borrow_public_token',
        'borrow_pin_hash',
        'borrow_pin_version',
        'borrowing_enabled',
    ];

    protected function casts(): array
    {
        return [
            'borrowing_enabled' => 'boolean',
        ];
    }

    public function stocks()
    {
        return $this->hasMany(UnitStock::class);
    }
}
