# product-index

Permiso `product-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:product-index`)
- `app\Http\Controllers\ProductController.php` (`applyOrgPermissionScope` con `'product-index'`)

## Routes protected

- `GET /product -> ProductController@index`
- `GET /product/pos -> ProductController@pos`
- `GET /product/{product} -> ProductController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
