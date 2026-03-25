<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesOrgPermissionScope
{
    protected function applyOrgPermissionScope(
        Builder $query,
        User $user,
        string $permission,
        string $orgColumn = 'org_id'
    ): Builder
    {
        $orgIds = $user->getOrgsByPermission($permission);

        if (empty($orgIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($orgColumn, $orgIds);
    }
}
