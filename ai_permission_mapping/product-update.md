# product-update

Permiso `product-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-update`)

## Routes protected

- `POST /product/reorder -> ProductController@reorder`
- `PUT /product/{product} -> ProductController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ProductPolicy.php` define `update` con `product-update` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ProductController aplica el scope por organización con `product-index` (no `product-update`).
