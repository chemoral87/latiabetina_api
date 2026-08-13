# church-event-index

Permiso `church-event-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-event-index`)
- `app\Http\Controllers\ChurchEventController.php` (`applyOrgPermissionScope` con `'church-event-index'`)

## Routes protected

- `GET /church-event -> ChurchEventController@index`
- `GET /church-event/calendar -> ChurchEventController@calendar`
- `GET /church-event/{churchEvent} -> ChurchEventController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
