# sale-index

Permiso `sale-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:sale-index`)
- `app\Http\Controllers\SaleController.php` (`applyOrgPermissionScope` con `'sale-index'`)

## Routes protected

- `GET /sale -> SaleController@index`
- `GET /sale/daily -> SaleController@daily`
- `GET /sale/{sale} -> SaleController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
- ⚠️ Capa latente: `app\Policies\SalePolicy.php` define `viewAny`/`view` con `sale-index`, pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.
