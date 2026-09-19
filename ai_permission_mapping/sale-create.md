# sale-create

Permiso `sale-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:sale-create`)

## Routes protected

- `POST /sale -> SaleController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\SalePolicy.php` define `create`/`createForOrg` con `sale-create`, pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: SaleController aplica el scope por organización con `sale-index`, `pos-kds` (no `sale-create`).
