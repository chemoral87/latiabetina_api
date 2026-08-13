# store-update

Permiso `store-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-update`)

## Routes protected

- `PUT /store/{id} -> StoreController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
