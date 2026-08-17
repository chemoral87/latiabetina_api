# user-filter

Permiso `user-filter` en la API.

## Files

- `routes\api.php` (middleware `permission_org:user-filter`)

## Routes protected

- `GET /user/filter -> UserController@filter`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)