# testimony-update

Permiso `testimony-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:testimony-update`)

## Routes protected

- `PUT /testimony/{id} -> TestimonyController@update`
- `PUT /testimony/{id}/status -> TestimonyController@updateStatus`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: TestimonyController aplica el scope por organización con `testimony-index` (no `testimony-update`).
