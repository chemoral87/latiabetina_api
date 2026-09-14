# assistance-update

Permiso `assistance-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:assistance-update`)

## Routes protected

- `PUT /assistance/{assistance} -> AssistanceController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AssistanceController aplica el scope por organizacion con `assistance-index` (no `assistance-update`).
