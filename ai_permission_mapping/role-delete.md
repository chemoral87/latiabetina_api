# role-delete

Permiso `role-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:role-delete`)

## Routes protected

- `DELETE /role/{id} → RoleController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
