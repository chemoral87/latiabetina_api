# permission-filter

Permiso `permission-filter` en la API.

## Files

- `routes\api.php` (middleware `permission_org:permission-filter`)

## Routes protected

- `GET /permission/filter -> PermissionController@filter`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)