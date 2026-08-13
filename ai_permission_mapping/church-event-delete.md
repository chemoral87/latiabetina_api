# church-event-delete

Permiso `church-event-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-event-delete`)

## Routes protected

- `DELETE /church-event/{churchEvent} -> ChurchEventController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ChurchEventController aplica el scope por organización con `church-event-index` (no `church-event-delete`).
