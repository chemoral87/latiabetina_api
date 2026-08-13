# role-update

Permiso `role-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:role-update`)

## Routes protected

- `POST /role/{id}/permission -> RoleController@addPermission`
- `PUT /role/{id} -> RoleController@update`
- `PUT /role/{id}/children -> RoleController@children`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
