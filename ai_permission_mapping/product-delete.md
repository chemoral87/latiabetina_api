# product-delete

Permiso `product-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-delete`)

## Routes protected

- `DELETE /product/{product} -> ProductController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ProductController aplica el scope por organización con `product-index` (no `product-delete`).
