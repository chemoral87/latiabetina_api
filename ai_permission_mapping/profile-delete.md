# profile-delete

Permiso `profile-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:profile-delete`)

## Routes protected

- `DELETE /profile/{user_id}/{id} -> ProfileController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
