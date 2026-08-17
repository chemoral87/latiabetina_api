# auditorium-filter

Permiso `auditorium-filter` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-filter`)
- `app\Http\Controllers\AuditoriumController.php` (`applyOrgPermissionScope` con `'auditorium-filter'`)

## Routes protected

- `GET /auditorium/filter -> AuditoriumController@filter`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)