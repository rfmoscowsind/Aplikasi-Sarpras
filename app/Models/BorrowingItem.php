<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowingItem extends Model
{
    protected $fillable = [
        'borrowing_id',
        'item_id',
        'requested_qty',
        'approved_qty',
        'handed_over_qty',
        'returned_qty',
        'damaged_qty',
        'lost_qty',
    ];

    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
