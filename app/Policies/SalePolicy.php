<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class SalePolicy
{
    use HandlesAuthorization;

    private function hasOrgAccess(User $user, Sale $model, string $permission): bool
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
        return $this->hasAnyOrgAccess($user, 'sale-index');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $this->hasOrgAccess($user, $sale, 'sale-index');
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'sale-create');
    }

    public function createForOrg(User $user, int $orgId): bool
    {
        $orgIds = $user->getOrgsByPermission('sale-create');
        return in_array($orgId, $orgIds);
    }

    public function update(User $user, Sale $sale): bool
    {
        return $this->hasOrgAccess($user, $sale, 'sale-update');
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $this->hasOrgAccess($user, $sale, 'sale-delete');
    }
}
