<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ProductPolicy
{
    use HandlesAuthorization;

    private function hasOrgAccess(User $user, Product $model, string $permission): bool
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
        return $this->hasAnyOrgAccess($user, 'product-index');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->hasOrgAccess($user, $product, 'product-index');
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'product-create');
    }

    public function createForOrg(User $user, int $orgId): bool
    {
        $orgIds = $user->getOrgsByPermission('product-create');
        return in_array($orgId, $orgIds);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->hasOrgAccess($user, $product, 'product-update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->hasOrgAccess($user, $product, 'product-delete');
    }
}
