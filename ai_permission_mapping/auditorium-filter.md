# auditorium-filter

Permiso `auditorium-filter` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-filter`)

## Routes protected

- `GET /auditorium/filter -> AuditoriumController@filter`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)