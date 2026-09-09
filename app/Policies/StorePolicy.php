<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class StorePolicy
{
    use HandlesAuthorization;

    private function hasOrgAccess(User $user, Store $model, string $permission): bool
    {
        $orgIds = $user->getOrgsByPermission($permission);
        return in_array($model->org_id, $orgIds);
    }

    private function hasAnyOrgAccess(User $user, string $permission): bool
    {
        return !empty($user->getOrgsByPermission($permission));
    }

    public function viewAny(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'store-index');
    }

    public function view(User $user, Store $store): bool
    {
        return $this->hasOrgAccess($user, $store, 'store-index');
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'store-create');
    }

    public function createForOrg(User $user, int $orgId): bool
    {
        $orgIds = $user->getOrgsByPermission('store-create');
        return in_array($orgId, $orgIds);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->hasOrgAccess($user, $store, 'store-update');
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->hasOrgAccess($user, $store, 'store-delete');
    }
}
