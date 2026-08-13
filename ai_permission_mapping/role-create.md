# role-create

Permiso `role-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:role-create`)

## Routes protected

- `POST /role → RoleController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
