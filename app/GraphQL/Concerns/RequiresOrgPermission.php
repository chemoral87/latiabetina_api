<?php

declare(strict_types=1);

namespace App\GraphQL\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

/**
 * Authenticated GraphQL fields share the JWT guard and the org-permission model
 * used by the REST `permission_org:` route middleware.
 */
trait RequiresOrgPermission
{
    /**
     * @throws AuthenticationException when no valid JWT is present
     */
    protected function authenticatedUser(): User
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            throw new AuthenticationException();
        }

        return $user;
    }

    /**
     * Mirrors `App\Http\Middleware\CheckOrgPermission`.
     *
     * @return array<int, int> organization ids the user may act on for $permission
     *
     * @throws AuthorizationException when the user holds no org for $permission
     */
    protected function orgIdsFor(User $user, string $permission): array
    {
        $orgIds = $user->getOrgsByPermission($permission);

        if ($orgIds === []) {
            throw new AuthorizationException(
                "No tienes permiso para realizar esta acción. Se requiere el permiso: {$permission}"
            );
        }

        return $orgIds;
    }
}
