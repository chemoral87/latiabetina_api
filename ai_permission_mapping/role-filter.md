# role-filter

Permiso `role-filter` en la API.

## Files

- `routes\api.php` (middleware `permission_org:role-filter`)

## Routes protected

- `GET /role/filter -> RoleController@filter`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)