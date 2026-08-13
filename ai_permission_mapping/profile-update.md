# profile-update

Permiso `profile-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:profile-update`)

## Routes protected

- `PUT /profile/{user_id}/{id} → ProfileController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
