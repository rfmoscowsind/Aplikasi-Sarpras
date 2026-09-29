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
    ];

    protected function casts(): array
    {
        return [
            'expected_return_at' => 'datetime',
            'approved_at' => 'datetime',
            'borrowed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(BorrowingItem::class);
    }
}
