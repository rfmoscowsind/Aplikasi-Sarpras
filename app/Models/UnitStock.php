<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitStock extends Model
{
    protected $fillable = [
        'unit_id',
        'item_id',
        'total_qty',
        'available_qty',
        'reserved_qty',
        'borrowed_qty',
        'damaged_qty',
        'lost_qty',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function quantityInvariantIsValid(): bool
    {
        return $this->total_qty === (
            $this->available_qty
            + $this->reserved_qty
            + $this->borrowed_qty
            + $this->damaged_qty
            + $this->lost_qty
        );
    }
}
