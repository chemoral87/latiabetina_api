# user-update

Permiso `user-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:user-update`)

## Routes protected

- `PUT /user/{id} -> UserController@update`
- `PUT /user/{id}/children -> UserController@children`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
