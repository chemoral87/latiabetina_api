# permission-index

Permiso `permission-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:permission-index`)

## Routes protected

- `GET /permission → PermissionController@index`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
