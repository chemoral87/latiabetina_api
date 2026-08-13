# store-index

Permiso `store-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-index`)

## Routes protected

- `GET /store -> StoreController@index`
- `GET /store/{id} -> StoreController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
