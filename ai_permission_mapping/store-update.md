# store-update

Permiso `store-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-update`)

## Routes protected

- `PUT /store/{id} -> StoreController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\StorePolicy.php` define `update` con `store-update` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.
