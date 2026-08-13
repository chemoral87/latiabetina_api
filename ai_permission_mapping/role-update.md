# role-update

Permiso `role-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:role-update`)

## Routes protected

- `PUT /role/{id} → RoleController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
