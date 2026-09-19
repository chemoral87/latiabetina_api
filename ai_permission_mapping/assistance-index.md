# assistance-index

Permiso `assistance-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:assistance-index`)
- `app\Http\Controllers\AssistanceController.php` (`applyOrgPermissionScope` con `'assistance-index'`)

## Routes protected

- `GET /assistance -> AssistanceController@index`
- `GET /assistance/{assistance} -> AssistanceController@show`
- `GET /assistance/chart -> AssistanceController@chart` (scope con `assistance-index`; el permiso `assistance-dashboard-view` ya no se usa en la API)

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
