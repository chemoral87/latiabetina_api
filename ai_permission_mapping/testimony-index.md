# testimony-index

Permiso `testimony-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:testimony-index`)
- `app\Http\Controllers\TestimonyController.php` (`applyOrgPermissionScope` con `'testimony-index'`)

## Routes protected

- `GET /testimony -> TestimonyController@index`
- `GET /testimony/{id} -> TestimonyController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
