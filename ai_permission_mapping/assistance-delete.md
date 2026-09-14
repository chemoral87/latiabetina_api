# assistance-delete

Permiso `assistance-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:assistance-delete`)

## Routes protected

- `DELETE /assistance/{assistance} -> AssistanceController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AssistanceController aplica el scope por organizacion con `assistance-index` (no `assistance-delete`).
