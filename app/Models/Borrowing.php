<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $fillable = [
        'public_id',
        'unit_id',
        'juara_student_id',
        'student_name',
        'student_nis',
        'student_class',
        'phone',
        'purpose',
        'expected_return_at',
        'status',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'approved_by',
        'approved_at',
        'handed_over_by',
        'borrowed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_return_at' => 'datetime',
            'rejected_at' => 'datetime',
            'approved_at' => 'datetime',
            'borrowed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function items()
    {
        return $this->hasMany(BorrowingItem::class);
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['borrowed', 'return_pending', 'partially_returned'], true)
            && $this->expected_return_at->isPast();
    }
}
