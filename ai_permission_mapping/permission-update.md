# permission-update

Permiso `permission-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:permission-update`)

## Routes protected

- `PUT /permission/{id} -> PermissionController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
