# permission-create

Permiso `permission-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:permission-create`)

## Routes protected

- `POST /permission → PermissionController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
