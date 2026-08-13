# auditorium-update

Permiso `auditorium-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-update`)

## Routes protected

- `PUT /auditorium/{id} -> AuditoriumController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AuditoriumController aplica el scope por organización con `auditorium-index` (no `auditorium-update`).
