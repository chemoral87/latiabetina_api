# product-create

Permiso `product-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-create`)

## Routes protected

- `POST /product -> ProductController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ProductController aplica el scope por organización con `product-index` (no `product-create`).
