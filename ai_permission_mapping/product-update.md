# product-update

Permiso `product-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-update`)

## Routes protected

- `POST /product/reorder -> ProductController@reorder`
- `PUT /product/{product} -> ProductController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ProductController aplica el scope por organización con `product-index` (no `product-update`).
