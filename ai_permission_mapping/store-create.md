# store-create

Permiso `store-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-create`)

## Routes protected

- `POST /store -> StoreController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
