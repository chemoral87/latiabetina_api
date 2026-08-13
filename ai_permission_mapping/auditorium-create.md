# auditorium-create

Permiso `auditorium-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-create`)

## Routes protected

- `POST /auditorium -> AuditoriumController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AuditoriumController aplica el scope por organización con `auditorium-index` (no `auditorium-create`).
