# sale-delete

Permiso `sale-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:sale-delete`)

## Routes protected

- `DELETE /sale/{sale} -> SaleController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: SaleController aplica el scope por organización con `sale-index`, `pos-kds` (no `sale-delete`).
