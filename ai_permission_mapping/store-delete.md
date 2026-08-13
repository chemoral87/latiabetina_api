# store-delete

Permiso `store-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-delete`)

## Routes protected

- `DELETE /store/{id} -> StoreController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
