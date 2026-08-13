# pos-kds

Permiso `pos-kds` en la API.

## Files

- `routes\api.php` (middleware `permission_org:pos-kds`)
- `app\Http\Controllers\SaleController.php` (`applyOrgPermissionScope` con `'pos-kds'`)

## Routes protected

- `GET /sale/kds -> SaleController@kds`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
