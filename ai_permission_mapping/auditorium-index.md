# auditorium-index

Permiso `auditorium-index` en la API.

## Files

- `app\Http\Controllers\AuditoriumController.php` (`applyOrgPermissionScope` con `'auditorium-index'`)
- `app\Http\Controllers\AuditoriumEventController.php` (`applyOrgPermissionScope` con `'auditorium-index'`)

## Enforced by

- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
