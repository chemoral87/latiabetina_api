# profile-create

Permiso `profile-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:profile-create`)

## Routes protected

- `POST /profile/{user_id} → ProfileController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
