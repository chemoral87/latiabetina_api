# song-update

Permiso `song-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:song-update`)

## Routes protected

- `PUT /song/{song} -> SongController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: `GET /song` y `GET /song/{song}` no exigen ningún permiso (solo autenticación JWT); `SongController` no aplica scope por organización.
