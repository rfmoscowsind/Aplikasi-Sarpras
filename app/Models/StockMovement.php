<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
