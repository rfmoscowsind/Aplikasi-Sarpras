<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingGoodItem extends Model
{
    protected $fillable = [
        'incoming_good_id',
        'item_id',
        'quantity',
        'photo_object_key',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
