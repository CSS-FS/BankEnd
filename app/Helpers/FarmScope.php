<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FarmScope
{
    public static function accessibleFarmIds(User $user): array
    {
        if ($user->hasRole('admin')) {
            return DB::table('farms')
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        if ($user->hasRole('owner')) {
            return DB::table('farms')
                ->where('owner_id', $user->id)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        if ($user->hasRole('manager')) {
            return DB::table('farm_managers')
                ->where('manager_id', $user->id)
                ->pluck('farm_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        return [];
    }

    public static function managerFarmId(User $user): ?int
    {
        return DB::table('farm_managers')
            ->where('manager_id', $user->id)
            ->value('farm_id');
    }
}
