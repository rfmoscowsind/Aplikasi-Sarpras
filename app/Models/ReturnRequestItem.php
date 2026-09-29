<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequestItem extends Model
{
    protected $fillable = [
        'return_request_id',
        'borrowing_item_id',
        'quantity',
        'borrower_condition',
        'verified_condition',
    ];

    public function borrowingItem()
    {
        return $this->belongsTo(BorrowingItem::class);
    }
}
