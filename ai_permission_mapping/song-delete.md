# song-delete

Permiso `song-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:song-delete`)

## Routes protected

- `DELETE /song/{song} -> SongController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: `GET /song` y `GET /song/{song}` no exigen ningún permiso (solo autenticación JWT); `SongController` no aplica scope por organización.
