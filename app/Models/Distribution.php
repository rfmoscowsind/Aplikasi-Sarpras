<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    protected $fillable = [
        'public_id',
        'document_number',
        'source_unit_id',
        'target_unit_id',
        'recipient_name',
        'recipient_title',
        'status',
        'generated_document_object_key',
        'signed_document_object_key',
        'notes',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(DistributionItem::class);
    }

    public function sourceUnit()
    {
        return $this->belongsTo(Unit::class, 'source_unit_id');
    }

    public function targetUnit()
    {
        return $this->belongsTo(Unit::class, 'target_unit_id');
    }
}
