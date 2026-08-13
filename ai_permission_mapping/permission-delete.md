# permission-delete

Permiso `permission-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:permission-delete`)

## Routes protected

- `DELETE /permission/{id} → PermissionController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
