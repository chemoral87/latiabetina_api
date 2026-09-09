<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ConsoSheet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ConsoSheetPolicy
{
    use HandlesAuthorization;

    private function hasOrgAccess(User $user, ConsoSheet $model, string $permission): bool
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
        return $this->hasAnyOrgAccess($user, 'conso-sheet-index');
    }

    public function view(User $user, ConsoSheet $consoSheet): bool
    {
        return $this->hasOrgAccess($user, $consoSheet, 'conso-sheet-index');
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'conso-sheet-create');
    }

    public function createForOrg(User $user, int $orgId): bool
    {
        $orgIds = $user->getOrgsByPermission('conso-sheet-create');
        return in_array($orgId, $orgIds);
    }

    public function update(User $user, ConsoSheet $consoSheet): bool
    {
        return $this->hasOrgAccess($user, $consoSheet, 'conso-sheet-update');
    }

    public function delete(User $user, ConsoSheet $consoSheet): bool
    {
        return $this->hasOrgAccess($user, $consoSheet, 'conso-sheet-delete');
    }
}
