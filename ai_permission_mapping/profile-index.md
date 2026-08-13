# profile-index

Permiso `profile-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:profile-index`)

## Routes protected

- `GET /profile/{user_id} → ProfileController@index`
- `GET /profile/{user_id}/{id} → ProfileController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
