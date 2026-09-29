<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'public_id',
        'borrowing_id',
        'status',
        'photo_object_key',
        'borrower_notes',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
}
