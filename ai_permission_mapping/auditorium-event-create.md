# auditorium-event-create

Permiso `auditorium-event-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-event-create`)

## Routes protected

- `POST /auditorium-event → AuditoriumEventController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
