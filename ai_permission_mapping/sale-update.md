# sale-update

Permiso `sale-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:sale-update`)

## Routes protected

- `PATCH /sale/{sale}/complete -> SaleController@complete`
- `PATCH /sale/{sale}/item/{saleItem} -> SaleController@updateItem`
- `PUT /sale/{sale} -> SaleController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\SalePolicy.php` define `update` con `sale-update` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: SaleController aplica el scope por organización con `sale-index`, `pos-kds` (no `sale-update`).
