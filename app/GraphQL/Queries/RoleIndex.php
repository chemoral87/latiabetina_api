<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Concerns\RequiresOrgPermission;
use App\Http\Controllers\Concerns\AppliesSort;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role list + permission-name catalog in one query.
 *
 * Replaces the `GET /role` + `GET /permission?itemsPerPage=-1` pair that the
 * role page fires to render its table and permission comboboxes.
 */
final class RoleIndex
{
    use AppliesSort;
    use RequiresOrgPermission;

    private const DEFAULT_ITEMS_PER_PAGE = 10;

    /**
     * @param  array{
     *     page?: int|null,
     *     itemsPerPage?: int|null,
     *     filter?: string|null,
     *     sortBy?: array<int, string>|null,
     *     sortDesc?: array<int, bool>|null
     * }  $args
     */
    public function __invoke(mixed $root, array $args): array
    {
        $user = $this->authenticatedUser();
        // Same gate as `routes/api.php`: GET /role.
        $this->orgIdsFor($user, 'role-index');

        $query = Role::query()->with('permissions');

        if (! empty($args['sortBy'])) {
            $query = $this->applySort($query, $args['sortBy'], $args['sortDesc'] ?? []);
        } else {
            $query->orderBy('name');
        }

        if (! empty($args['filter'])) {
            $query->where('name', 'like', '%'.$args['filter'].'%');
        }

        $roles = $query->paginate(
            (int) ($args['itemsPerPage'] ?? self::DEFAULT_ITEMS_PER_PAGE),
            ['*'],
            'page',
            (int) ($args['page'] ?? 1),
        );

        return [
            'roles' => [
                'data' => collect($roles->items())
                    ->map(fn (Role $role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'permissions' => $role->permissions
                            ->map(fn (Permission $permission) => [
                                'id' => $permission->id,
                                'name' => $permission->name,
                            ])
                            ->all(),
                    ])
                    ->all(),
                'total' => $roles->total(),
                'itemsPerPage' => $roles->perPage(),
            ],
            'permissionNames' => Permission::query()->orderBy('name')->pluck('name')->all(),
        ];
    }
}
