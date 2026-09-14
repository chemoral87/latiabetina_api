# assistance-create

Permiso `assistance-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:assistance-create`)

## Routes protected

- `POST /assistance -> AssistanceController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AssistanceController aplica el scope por organizacion con `assistance-index` (no `assistance-create`).
