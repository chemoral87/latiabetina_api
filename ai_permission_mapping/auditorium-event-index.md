# auditorium-event-index

Permiso `auditorium-event-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-event-index`)

## Routes protected

- `GET /auditorium-event -> AuditoriumEventController@index`
- `GET /auditorium-event-seat -> AuditoriumEventSeatController@index`
- `GET /auditorium-event-seat-log -> AuditoriumEventSeatLogController@index`
- `GET /auditorium-event/{id} -> AuditoriumEventController@show`
- `POST /auditorium-event-seat -> AuditoriumEventSeatController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AuditoriumEventController aplica el scope por organización con `auditorium-index` (no `auditorium-event-index`).
