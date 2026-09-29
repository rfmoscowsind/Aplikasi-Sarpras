<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;

class UnitAccessService
{
    public function canView(User $user, Unit $unit): bool
    {
        if (in_array($user->system_role, ['admin', 'sarpras'], true)) {
            return true;
        }

        return $user->unitMemberships()->where('unit_id', $unit->id)->exists();
    }

    public function canManageInventory(User $user, Unit $unit): bool
    {
        if (in_array($user->system_role, ['admin', 'sarpras'], true)) {
            return true;
        }

        return $user->unitMemberships()
            ->where('unit_id', $unit->id)
            ->where(function ($query) {
                $query->where('role', 'head')
                    ->orWhere('can_manage_inventory', true);
            })
            ->exists();
    }

    public function canManageBorrowing(User $user, Unit $unit): bool
    {
        if (in_array($user->system_role, ['admin', 'sarpras'], true)) {
            return true;
        }

        return $user->unitMemberships()
            ->where('unit_id', $unit->id)
            ->where(function ($query) {
                $query->where('role', 'head')
                    ->orWhere('can_manage_borrowing', true);
            })
            ->exists();
    }
}
