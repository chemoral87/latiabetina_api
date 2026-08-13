# auditorium-event-delete

Permiso `auditorium-event-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-event-delete`)

## Routes protected

- `DELETE /auditorium-event/{id} -> AuditoriumEventController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AuditoriumEventController aplica el scope por organización con `auditorium-index` (no `auditorium-event-delete`).
