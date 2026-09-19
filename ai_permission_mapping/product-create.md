# product-create

Permiso `product-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-create`)

## Routes protected

- `POST /product -> ProductController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ProductPolicy.php` define `create`/`createForOrg` con `product-create`, pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ProductController aplica el scope por organización con `product-index` (no `product-create`).
