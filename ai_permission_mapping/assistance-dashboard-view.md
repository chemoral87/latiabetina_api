# assistance-dashboard-view

Permiso `assistance-dashboard-view` en la API.

## Files

- `routes\api.php` (middleware `permission_org:assistance-dashboard-view`)
- `app\Http\Controllers\AssistanceController.php` (`applyOrgPermissionScope` con `'assistance-dashboard-view'`)

## Routes protected

- `GET /assistance/chart -> AssistanceController@chart`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)

> Nota: Este permiso es independiente de `assistance-index`. Alguien puede ver el resumen del dashboard sin poder ver/editar la captura detallada.
