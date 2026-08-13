# auditorium-event-update

Permiso `auditorium-event-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-event-update`)

## Routes protected

- `PUT /auditorium-event/{id} -> AuditoriumEventController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AuditoriumEventController aplica el scope por organización con `auditorium-index` (no `auditorium-event-update`).
