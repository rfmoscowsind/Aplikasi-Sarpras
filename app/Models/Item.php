<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'specification',
        'borrowable',
        'require_return_photo',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'borrowable' => 'boolean',
            'require_return_photo' => 'boolean',
        ];
    }

    public function stocks()
    {
        return $this->hasMany(UnitStock::class);
    }
}
