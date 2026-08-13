# auditorium-delete

Permiso `auditorium-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:auditorium-delete`)

## Routes protected

- `DELETE /auditorium/{id} -> AuditoriumController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: AuditoriumController aplica el scope por organización con `auditorium-index` (no `auditorium-delete`).
