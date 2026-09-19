# product-delete

Permiso `product-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-delete`)

## Routes protected

- `DELETE /product/{product} -> ProductController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ProductPolicy.php` define `delete` con `product-delete` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ProductController aplica el scope por organización con `product-index` (no `product-delete`).
