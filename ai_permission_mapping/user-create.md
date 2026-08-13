# user-create

Permiso `user-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:user-create`)

## Routes protected

- `POST /user -> UserController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
