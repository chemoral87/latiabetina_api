# church-event-index

Permiso `church-event-index` en la API.

## Files

- `app\Http\Controllers\ChurchEventController.php` (`applyOrgPermissionScope` con `'church-event-index'`)

## Enforced by

- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
