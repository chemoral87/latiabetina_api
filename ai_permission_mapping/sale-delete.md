# sale-delete

Permiso `sale-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:sale-delete`)

## Routes protected

- `DELETE /sale/{sale} -> SaleController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\SalePolicy.php` define `delete` con `sale-delete` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: SaleController aplica el scope por organización con `sale-index`, `pos-kds` (no `sale-delete`).
