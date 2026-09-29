<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingGood extends Model
{
    protected $fillable = [
        'central_unit_id',
        'received_at',
        'supplier',
        'invoice_number',
        'invoice_object_key',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'date'];
    }

    public function items()
    {
        return $this->hasMany(IncomingGoodItem::class);
    }

    public function centralUnit()
    {
        return $this->belongsTo(Unit::class, 'central_unit_id');
    }
}
