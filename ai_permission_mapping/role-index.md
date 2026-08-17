# role-index

Permiso `role-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:role-index`)

## Routes protected

- `GET /role -> RoleController@index`
- `GET /role/{id} -> RoleController@show`
- `GET /role/{id}/distribution -> RoleController@distribution`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
