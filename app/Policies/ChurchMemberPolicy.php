<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Church\ChurchMember;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ChurchMemberPolicy
{
    use HandlesAuthorization;

    private function hasOrgAccess(User $user, ChurchMember $model, string $permission): bool
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
        return $this->hasAnyOrgAccess($user, 'church-member-index')
            || $this->hasAnyOrgAccess($user, 'church-member-all');
    }

    public function view(User $user, ChurchMember $member): bool
    {
        return $this->hasOrgAccess($user, $member, 'church-member-index')
            || $this->hasOrgAccess($user, $member, 'church-member-all');
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOrgAccess($user, 'church-member-create')
            || $this->hasAnyOrgAccess($user, 'church-member-all');
    }

    public function createForOrg(User $user, int $orgId): bool
    {
        $orgIds = $user->getOrgsByPermission('church-member-create');
        $allOrgIds = $user->getOrgsByPermission('church-member-all');
        return in_array($orgId, $orgIds) || in_array($orgId, $allOrgIds);
    }

    public function update(User $user, ChurchMember $member): bool
    {
        return $this->hasOrgAccess($user, $member, 'church-member-update')
            || $this->hasOrgAccess($user, $member, 'church-member-all');
    }

    public function delete(User $user, ChurchMember $member): bool
    {
        return $this->hasOrgAccess($user, $member, 'church-member-delete')
            || $this->hasOrgAccess($user, $member, 'church-member-all');
    }
}
