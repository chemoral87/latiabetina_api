# user-index

Permiso `user-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:user-index`)

## Routes protected

- `GET /user -> UserController@index`
- `GET /user/filter -> UserController@filter`
- `GET /user/{id} -> UserController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
