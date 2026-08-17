# auditorium-event-mark

Permiso `auditorium-event-mark` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-event-mark`)

## Routes protected

- `GET /auditorium-event-seat -> AuditoriumEventSeatController@index`
- `POST /auditorium-event-seat -> AuditoriumEventSeatController@store`
- `GET /auditorium-event-seat-log -> AuditoriumEventSeatLogController@index`
- `GET /auditorium-event/{id} -> AuditoriumEventController@show` (acepta también `auditorium-event-index`)

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el middleware `permission_org` acepta permisos separados por coma (`permission_org:a,b`) y permite el acceso si el usuario tiene al menos uno de ellos.