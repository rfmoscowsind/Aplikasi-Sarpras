<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'system_role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function unitMemberships()
    {
        return $this->hasMany(UnitMembership::class);
    }

    public function isSystemAdmin(): bool
    {
        return $this->system_role === 'admin';
    }

    public function isSarpras(): bool
    {
        return in_array($this->system_role, ['admin', 'sarpras'], true);
    }
}
