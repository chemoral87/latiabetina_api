# user-delete

Permiso `user-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:user-delete`)

## Routes protected

- `DELETE /user/{id} → UserController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
