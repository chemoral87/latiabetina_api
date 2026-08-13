# church-event-create

Permiso `church-event-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-event-create`)

## Routes protected

- `POST /church-event -> ChurchEventController@store`
- `POST /church-event/{churchEvent}/copy -> ChurchEventController@copy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ChurchEventController aplica el scope por organización con `church-event-index` (no `church-event-create`).
