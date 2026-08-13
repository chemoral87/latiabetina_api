# auditorium-index

Permiso `auditorium-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-index`)
- `app\Http\Controllers\AuditoriumController.php` (`applyOrgPermissionScope` con `'auditorium-index'`)
- `app\Http\Controllers\AuditoriumEventController.php` (`applyOrgPermissionScope` con `'auditorium-index'`)

## Routes protected

- `GET /auditorium -> AuditoriumController@index`
- `GET /auditorium/filter -> AuditoriumController@filter`
- `GET /auditorium/{id} -> AuditoriumController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
