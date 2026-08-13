# church-event-update

Permiso `church-event-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-event-update`)

## Routes protected

- `PUT /church-event/{churchEvent} -> ChurchEventController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ChurchEventController aplica el scope por organización con `church-event-index` (no `church-event-update`).
