<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LifeGroup\LifeGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class LifeGroupPolicy
{
    use HandlesAuthorization;

    private function hasOrgAccess(User $user, LifeGroup $model, string $permission): bool
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
        return $this->hasAnyOrgAccess($user, 'life-group-index');
    }

    public function view(User $user, LifeGroup $lifeGroup): bool
    {
        return $this->hasOrgAccess($user, $lifeGroup, 'life-group-index');
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'life-group-create');
    }

    public function createForOrg(User $user, int $orgId): bool
    {
        $orgIds = $user->getOrgsByPermission('life-group-create');
        return in_array($orgId, $orgIds);
    }

    public function update(User $user, LifeGroup $lifeGroup): bool
    {
        return $this->hasOrgAccess($user, $lifeGroup, 'life-group-update');
    }

    public function delete(User $user, LifeGroup $lifeGroup): bool
    {
        return $this->hasOrgAccess($user, $lifeGroup, 'life-group-delete');
    }
}
