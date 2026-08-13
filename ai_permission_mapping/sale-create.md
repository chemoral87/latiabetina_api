# sale-create

Permiso `sale-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:sale-create`)

## Routes protected

- `POST /sale -> SaleController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: SaleController aplica el scope por organización con `sale-index`, `pos-kds` (no `sale-create`).
