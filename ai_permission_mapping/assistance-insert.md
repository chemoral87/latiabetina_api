# assistance-insert

Permiso `assistance-insert` en la API. Usado para importacion masiva de asistencias desde Excel.

## Files

- `routes\api.php` (middleware `permission_org:assistance-insert`)
- `app\Http\Controllers\AssistanceController.php` (metodo `bulkStore`)

## Routes protected

- `POST /assistance/bulk -> AssistanceController@bulkStore`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AssistanceController aplica el scope por organizacion con `assistance-index`.
